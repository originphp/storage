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

use phpseclib\Net\SFTP;

use phpseclib\Crypt\RSA;

use InvalidArgumentException;

use Origin\Storage\Engine\SftpEngine;

use Origin\Storage\Exception\StorageException;
use Origin\Storage\Exception\FileNotFoundException;

class MockSftpEngine extends SftpEngine
{
    public function initialize(array $config) : void
    {
        // dont do anthing
    }

    public function start()
    {
        parent::initialize($this->config());
    }

    public function callMethod(string $method, array $args = [])
    {
        if (empty($args)) {
            return $this->$method();
        }

        return call_user_func_array([$this, $method], $args);
    }
}

class SftpEngineTest extends EngineTestCase
{
    protected function setUp() : void
    {
        if (! $this->env('SFTP_USERNAME')) {
            $this->markTestSkipped('SFTP $this->env vars not set');
        }
        if (! class_exists(SFTP::class)) {
            $this->markTestSkipped('phpseclib not installed.');
        }
    }

    protected $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine = new SftpEngine([
                'host' => $this->env('SFTP_HOST'),
                'username' => $this->env('SFTP_USERNAME'),
                'password' => $this->env('SFTP_PASSWORD'),
            ]);
        }

        return $this->engine;
    }
    public function testConfig()
    {
        $config = $this->engine()->config();

        $this->assertNotEmpty($config['host']);
        $this->assertNotEmpty($config['username']);
        $this->assertNotEmpty($config['password']);
        $this->assertEquals(22, $config['port']);
        $this->assertNotEmpty($config['root']);
        $this->assertEquals(10, $config['timeout']);
    }

    public function testInvalidRoot()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new SftpEngine([
            'host' => $this->env('SFTP_HOST'),
            'username' => $this->env('SFTP_USERNAME'),
            'password' => $this->env('SFTP_PASSWORD'),
            'root' => '/some-directory/that-does-not-exist',
        ]);
    }

    public function testNotFoundPrivateKey()
    {
        $this->expectException(FileNotFoundException::class);
        $engine = new SftpEngine([
            'host' => $this->env('SFTP_HOST'),
            'username' => 'username',
            'password' => 'password',
            'privateKey' => '/somewhere/privateKey',
        ]);
    }

    public function testPrivateKey()
    {
        $rsa = new RSA();
        $pair = $rsa->createKey();

        $engine = new MockSftpEngine([
            'host' => $this->env('SFTP_HOST'),
            'username' => $this->env('SFTP_USERNAME'),
            'password' => $this->env('SFTP_PASSWORD'),
            'privateKey' => $pair['privatekey'],
        ]);
        $rsa = $engine->callMethod('loadPrivatekey');
        $this->assertEquals('phpseclib-generated-key', $rsa->comment);
        $this->assertEquals($this->env('SFTP_PASSWORD'), $rsa->password);
    }

    public function testPrivateKeyFile()
    {
        $rsa = new RSA();
        $pair = $rsa->createKey();
        $tmp = sys_get_temp_dir() . '/' . uniqid();
        file_put_contents($tmp, $pair['privatekey']);
        $engine = new MockSftpEngine([
            'host' => $this->env('SFTP_HOST'),
            'username' => $this->env('SFTP_USERNAME'),
            'password' => $this->env('SFTP_PASSWORD'),
            'privateKey' => $tmp,
        ]);
        $rsa = $engine->callMethod('loadPrivatekey');
        $this->assertEquals('phpseclib-generated-key', $rsa->comment);
        $this->assertEquals($this->env('SFTP_PASSWORD'), $rsa->password);
    }

    public function testNoHostSetException()
    {
        $this->expectException(InvalidArgumentException::class);
        $engine = new SftpEngine([]);
    }

    public function testInvalidUsernamePassword()
    {
        $this->expectException(StorageException::class);
        $engine = new SftpEngine([
            'host' => $this->env('SFTP_HOST'),
            'username' => 'admin',
            'password' => 1234,
        ]);
    }

    public function testInvalidUsernamePasswordPrivateKey()
    {
        $this->expectException(StorageException::class);

        $rsa = new RSA();
        $pair = $rsa->createKey();
        $engine = new SftpEngine([
            'host' => $this->env('SFTP_HOST'),
            'username' => $this->env('SFTP_USERNAME'),
            'password' => $this->env('SFTP_PASSWORD'),
            'privateKey' => $pair['privatekey'],
        ]);
    }
}
