<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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

    protected $bucket = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->bucket = 'bucket-' .time();

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
            
            $this->engine()->createBucket($this->bucket);
        }

        return $this->engine;
    }

    public function testListBuckets()
    {
        $buckets = $this->engine()->listBuckets();
        $this->assertNotEmpty($buckets);
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

    public function testcreateBucket()
    {
        $this->assertTrue($this->engine()->createBucket('foo'));
        $this->assertFalse($this->engine()->createBucket('foo'));
    }

    public function testDeletebucket()
    {
        $this->assertTrue($this->engine()->deleteBucket('foo'));
        $this->assertFalse($this->engine()->deleteBucket('foo'));
        
        $this->assertTrue($this->engine()->deleteBucket($this->bucket));
    }

    public function testErrors()
    {
        $this->assertEmpty($this->engine()->errors());
        $this->assertTrue($this->engine()->createBucket('foo'));

        $this->assertFalse($this->engine()->createBucket('foo'));
        $this->assertNotEmpty($this->engine()->errors());
        $this->assertTrue($this->engine()->deleteBucket('foo'));
    }
}
