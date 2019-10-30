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

namespace Origin\Test\Storage;

use Origin\Storage\FileObject;

class FileObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testAccess()
    {
        $data = ['name' => 'foo.txt','path' => 'folder/subfolder','size' => 32000,'timestamp' => strtotime('2019-10-31 14:40')];
        $object = new FileObject($data);

        $this->assertEquals('foo.txt', $object['name']);
        $this->assertEquals('folder/subfolder', $object['path']);

        $this->assertEquals('foo.txt', $object->name);
        $this->assertEquals('folder/subfolder', $object->path);

        $this->assertNull($object['abc']);
        $this->assertNull($object->abc);
    }
}
