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
use Origin\Storage\Engine\LocalEngine;

define('STORAGE_TMP', sys_get_temp_dir() . '/' . uniqid());
mkdir(STORAGE_TMP);

class LocalEngineTest extends EngineTestCase
{
    protected $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine = new LocalEngine([
                'root' => STORAGE_TMP
            ]);
        }

        return $this->engine;
    }

    public function testInvalidRoot()
    {
        $this->expectException(InvalidArgumentException::class);
        new LocalEngine([
            'root' => '/some-$root/that-does-not-exist',
        ]);
    }
}
