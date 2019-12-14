<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
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

use Origin\Storage\Engine\MemoryEngine;
use Origin\Storage\Exception\FileNotFoundException;

class MemoryEngineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Its a memory test
     *
     * @return void
     */
    public function testReadWrite()
    {
        $engine = new MemoryEngine();
    
        $this->assertTrue($engine->write('foo.txt', 'foo'));
        $this->assertTrue($engine->write('folder/bar.txt', 'bar'));
        $this->assertTrue($engine->write('folder/subfolder/foobar.txt', 'foobar'));

        $this->assertEquals('foo', $engine->read('foo.txt'));
        $this->assertEquals('bar', $engine->read('folder/bar.txt'));
        $this->assertEquals('foobar', $engine->read('folder/subfolder/foobar.txt'));

        $this->assertTrue($engine->exists('foo.txt'));
       
        // exists files
        $this->assertTrue($engine->exists('folder/bar.txt'));
        $this->assertFalse($engine->exists('folder/dota.txt'));
        $this->assertTrue($engine->exists('folder/subfolder/foobar.txt'));

        // exists directories
        $this->assertTrue($engine->exists('folder'));
        $this->assertTrue($engine->exists('folder/subfolder'));
        $this->assertFalse($engine->exists('dota'));
        $this->assertFalse($engine->exists('folder/dota'));

        $result = $engine->list();
        $this->assertEquals(3, count($result));
        $result = $engine->list('folder');
        $this->assertEquals(2, count($result));
        $result = $engine->list('folder/subfolder');
        $this->assertEquals(1, count($result));
    }

    public function testDelete()
    {
        $engine = new MemoryEngine();
        $engine->write('foo.txt', 'foo');
        $engine->write('folder/bar.txt', 'bar');
        $engine->write('folder/baz.txt', 'baz');
        $engine->write('folder/daz.txt', 'daz');
        $engine->write('folder/subfolder/foobar.txt', 'foobar');

        // sanity check
        $this->assertTrue($engine->exists('folder/subfolder/foobar.txt'));

        // test non-recursive delete
        $this->assertTrue($engine->delete('foo.txt'));
        $this->assertFalse($engine->exists('foo.txt'));

        $this->assertTrue($engine->delete('folder/baz.txt'));
        $this->assertFalse($engine->exists('folder/baz.txt'));
        $this->assertTrue($engine->exists('folder/daz.txt')); // santity check

        // test recursive delete
        $this->assertTrue($engine->delete('folder'));
        $this->assertFalse($engine->exists('folder/subfolder/foobar.txt'));
    }

    public function testReadException()
    {
        $this->expectException(FileNotFoundException::class);
        (new MemoryEngine())->read('foo');
    }

    public function testDeleteException()
    {
        $this->expectException(FileNotFoundException::class);
        (new MemoryEngine())->delete('foo');
    }

    public function testListException()
    {
        $this->expectException(FileNotFoundException::class);
        (new MemoryEngine())->list('foo');
    }
}
