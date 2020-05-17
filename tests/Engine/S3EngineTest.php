<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Storage\Engine;

use InvalidArgumentException;
use Origin\Storage\Engine\S3Engine;

class S3EngineTest extends EngineTestCase
{
    protected $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine = new S3Engine([
                'credentials' => [
                    'key' => $this->env('S3_KEY'),
                    'secret' => $this->env('S3_SECRET'),
                ],
                'region' => 'us-east-1',
                'version' => 'latest',
                'endpoint' => $this->env('S3_ENDPOINT'), // for S3 comptabile protocols
                'bucket' => $this->env('S3_BUCKET')
            ]);
        }

        return $this->engine;
    }

    public function testBuckets()
    {
        $s3 = $this->engine();
        $buckets = $s3->listBuckets();
        if (! in_array($this->env('S3_BUCKET'), $buckets)) {
            $this->assertTrue($s3->createBucket($this->env('S3_BUCKET')));
        }
        $this->assertTrue(in_array($this->env('S3_BUCKET'), $buckets));
    }

    /**
     * @link https://docs.aws.amazon.com/aws-sdk-php/v2/guide/service-s3.html#cleaning-up
     *
     * @return void
     */
    public function testBucketCreateDelete()
    {
        $s3 = $this->engine();
        $id = uniqid();
        $this->assertTrue($s3->createBucket($id));
        $this->assertTrue(in_array($id, $s3->listBuckets()));
        $this->assertTrue($s3->deleteBucket($id));
        $this->assertFalse(in_array($id, $s3->listBuckets()));
    }
    public function testNoCredentials()
    {
        $this->expectException(InvalidArgumentException::class);
        new S3Engine([
            'x-credentials' => [
                'key' => 'foo',
                'secret' => 'foo',
            ],
            'region' => 'somehwere',
            'version' => 'latest',
            'endpoint' => null, // for S3 comptabile protocols
            'bucket' => 'data'
        ]);
    }
    public function testInvalidCredentialsNoKey()
    {
        $this->expectException(InvalidArgumentException::class);
        new S3Engine([
            'credentials' => [
                'secret' => 'foo',
            ],
            'region' => 'somehwere',
            'version' => 'latest',
            'endpoint' => null, // for S3 comptabile protocols
            'bucket' => 'data'
        ]);
    }
    
    public function testInvalidCredentialsNoSecret()
    {
        $this->expectException(InvalidArgumentException::class);
        new S3Engine([
            'credentials' => [
                'key' => 'foo',
            ],
            'region' => 'somehwere',
            'version' => 'latest',
            'endpoint' => null, // for S3 comptabile protocols
            'bucket' => 'data'
        ]);
    }
    public function testRegionNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        new S3Engine([
            'credentials' => [
                'key' => 'foo',
                'secret' => 'foo',
            ],
            'region' => null,
            'version' => 'latest',
            'endpoint' => null, // for S3 comptabile protocols
            'bucket' => 'data'
        ]);
    }

    public function testBucketNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        new S3Engine([
            'credentials' => [
                'key' => 'foo',
                'secret' => 'foo',
            ],
            'region' => 'somewhere',
            'version' => 'latest',
            'endpoint' => null, // for S3 comptabile protocols
            'bucket' => null
        ]);
    }
}
