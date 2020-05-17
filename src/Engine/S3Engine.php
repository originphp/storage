<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Origin\Storage\Engine;

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\S3\S3Client;
use InvalidArgumentException;
use Origin\Storage\FileObject;
use Aws\S3\Exception\S3Exception;
use Origin\Storage\Exception\FileNotFoundException;
use Aws\S3\Exception\DeleteMultipleObjectsException;

/**
 * S3 Engine
 * To fire up a minio contianer
 * $ docker run -p 9000:9000 minio/minio server /data
 *
 */
class S3Engine extends BaseEngine
{
    /**
     * @var Aws\S3\S3Client $s3
     */
    private $s3;

    /**
     * @var string
     */
    private $bucket;

    protected $defaultConfig = [
        'credentials' => [
            'key' => null,
            'secret' => null,
        ],
        'region' => 'main-rack',
        'version' => 'latest',
        'endpoint' => null, // for S3 comptabile protocols
        'bucket' => 'data'
    ];

    protected function initialize(array $config) : void
    {
        $credentials = $this->config('credentials');

        if ($credentials === null || empty($credentials['key']) || empty($credentials['secret'])) {
            throw new InvalidArgumentException('Invalid Credentials settings');
        }
        if ($this->config('region') === null) {
            throw new InvalidArgumentException('Region not set');
        }
        if ($this->config('bucket') === null) {
            throw new InvalidArgumentException('Bucket not set');
        }

        $this->s3 = new S3Client($config);
        $this->bucket = $config['bucket'];
    }

    /**
     * Returns a list of buckets on the S3 storage
     *
     * @return array
     */
    public function listBuckets() : array
    {
        $out = [];
        $buckets = $this->s3->listBuckets();
        foreach ($buckets['Buckets'] as $bucket) {
            $out[] = $bucket['Name'];
        }

        return $out;
    }

    /**
     * Creates a bucket
     *
     * @param string $name
     * @return boolean
     */
    public function createBucket(string $name) : bool
    {
        $options = ['Bucket' => $name];

        try {
            $this->s3->createBucket($options);
            $this->s3->waitUntil('BucketExists', $options);

            return true;
        } catch (AwsException $exception) {
            throw $exception;
        }

        return false;
    }

    /**
     * Creates a bucket
     *
     * @param string $name
     * @return boolean
     */
    public function deleteBucket(string $name) : bool
    {
        $options = ['Bucket' => $name];

        try {
            $this->s3->deleteBucket($options);
            $this->s3->waitUntil('BucketNotExists', $options);

            return true;
        } catch (AwsException $exception) {
        }

        return false;
    }

    /**
     * Reads from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name) : string
    {
        try {
            $response = $this->s3->getObject([
                'Bucket' => $this->bucket,
                'Key' => trim($name, '/'),
            ]);

            return (string) $response['Body'];
        } catch (S3Exception $exception) {
        }
    
        throw new FileNotFoundException(sprintf('%s does not exist', $name));
    }

    /**
     * Writes to the storage
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     *
     * @param string $name
     * @param string $data
     * @return bool
     */
    public function write(string $name, string $data) : bool
    {
        try {
            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => trim($name, '/'),
                'Body' => $data
            ]);

            return true;
        } catch (S3Exception $exception) {
        }

        return false;
    }

    /**
     * Deletes from the storage
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name) : bool
    {
        // Disable anything with trailing /
        if (substr($name, -1) === '/') {
            return false;
        }

        if (! $this->exists($name)) {
            throw new FileNotFoundException(sprintf('%s does not exist', $name));
        }
        
        if ($this->isDirectory($name)) {
            return $this->deleteDirectory($name);
        }

        return $this->deleteObject($name);
    }

    /**
     * Deletes all items in directory
     *
     * @param string $name
     * @return boolean
     */
    private function deleteDirectory(string $name) : bool
    {
        try {
            $this->s3->deleteMatchingObjects($this->bucket, trim($name, '/') . '/');
        } catch (DeleteMultipleObjectsException $exception) {
            $this->errors[] = $exception->getMessage();

            return false;
        }

        return true;
    }

    /**
     * @param string $name
     * @return boolean
     */
    private function deleteObject(string $name) : bool
    {
        try {
            $result = $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $name,
            ]);

            return empty($result['DeleteMarker']);
        } catch (S3Exception $exception) {
        }

        return false;
    }

    /**
     * Checks if a file or directory exists on the storage
     *
     * @link https://docs.aws.amazon.com/AmazonS3/latest/dev/ListingObjectKeysUsingPHP.html
     * @link http://url.com https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjects
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name) : bool
    {
        $name = trim($name, '/');
        if ($this->s3->doesObjectExist($this->config('bucket'), $name)) {
            return true;
        }
        
        return $this->isDirectory($name);
    }

    /**
     * Returns the list of files from the storage
     *
     * @param string $name
     * @return array
     */
    public function list(string $name = null) : array
    {
        $name = trim((string) $name, '/');

        if (! empty($name) && ! $this->isDirectory($name)) {
            throw new FileNotFoundException('directory does not exist');
        }
       
        $files = [];
        try {
            $results = $this->s3->getPaginator('ListObjects', [
                'Bucket' => $this->bucket,
                'Prefix' => $name
            ]);
            
            foreach ($results as $result) {
                foreach ($this->getListObjects($result) as $object) {
                    $files[] = new FileObject([
                        'name' => $this->rebase($object['Key'], $name .'/'),
                        'timestamp' => strtotime((string) $object['LastModified']),
                        'size' => $object['Size'],
                    ]);
                }
            }
        } catch (S3Exception $exception) {
        }

        return $files;
    }

    /**
     * Undocumented function
     *
     * @param \Aws\Result $result
     * @return array
     */
    private function getListObjects(Result $result) : array
    {
        $contents = $result->get('Contents') ?? [];
        $common = $result->get('CommonPrefixes') ?? [];

        return array_merge($contents, $common);
    }

    /**
     * Checks if an object is a directory
     *
     * @param string $name
     * @return boolean
     */
    private function isDirectory(string $name) : bool
    {
        $command = $this->s3->getCommand('listObjects', [
            'Bucket' => $this->bucket,
            'Prefix' => $name . '/',
            'MaxKeys' => 1,
        ]);

        try {
            $result = $this->s3->execute($command);

            return ! empty($result['Contents'] || ! empty($result['CommonPrefixes']));
        } catch (S3Exception $exception) {
        }

        return false;
    }
}
