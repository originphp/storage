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

namespace Origin\Test\Storage;

use Origin\Storage\Storage;
use InvalidArgumentException;

class StorageTest extends \PHPUnit\Framework\TestCase
{
    public function testCRD()
    {
        $tmpFolder = uniqid();
        $root = sys_get_temp_dir() . '/' . $tmpFolder;
        mkdir($root);

        Storage::config('tmp', [
            'engine' => 'Local',
            'root' => $root,
        ]);

        $file = uniqid();
        # Create
        $this->assertTrue(Storage::write($file, 'bar', ['config' => 'tmp']));
        $this->assertFileExists(sys_get_temp_dir() . '/' . $tmpFolder . '/' . $file); // Check using correct config
        # Read
        $this->assertEquals('bar', Storage::read($file, ['config' => 'tmp']));
        $this->assertTrue(Storage::exists($file, ['config' => 'tmp']));

        $contents = Storage::list(null, ['config' => 'tmp']);
        $this->assertEquals(1, count($contents));
        $this->assertEquals($file, $contents[0]['name']);

        # Delete
        Storage::delete($file, ['config' => 'tmp']);
        $this->assertFalse(Storage::exists($file, ['config' => 'tmp']));
    }

    public function testUnkownConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        Storage::volume('somewhere-out-there');
    }

    public function testClassNotExists()
    {
        Storage::config('foo', ['className' => 'Void\MegaStorage']);
        $this->expectException(InvalidArgumentException::class);
        Storage::volume('foo');
    }
}
