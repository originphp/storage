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

use Origin\Storage\FileObject;
use Origin\Storage\Exception\FileNotFoundException;

class MemoryEngine extends BaseEngine
{
    /**
     * Holds the data
     *
     * @var array
     */
    private $data = [];

    /**
     * Reads from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name) : string
    {
        list($path, $filename) = $this->pathInfo($name);
        if (isset($this->data[$path][$filename])) {
            return $this->data[$path][$filename]['_contents'];
        }
        throw new FileNotFoundException(sprintf('File %s does not exist', $name));
    }

    /**
     * Writes to the storage
     *
     * @param string $name
     * @param string $data
     * @return bool
     */
    public function write(string $name, string $data) : bool
    {
        list($path, $filename) = $this->pathInfo($name);

        $this->data[$path][$filename] = new FileObject([
            'name' => $name,
            'size' => mb_strlen($data, '8bit'),
            'timestamp' => time(),
            '_contents' => $data
        ]);

        return true;
    }

    /**
     * Parses the path info
     *
     * @param string $name
     * @return array
     */
    private function pathInfo(string $name) : array
    {
        $result = pathinfo($name);

        return [
            $result['dirname'] === '.' ?  '/' : $result['dirname'],
            $result['basename']
        ];
    }

    /**
     * Deletes from the storage
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name) : bool
    {
        if ($name and ! $this->exists($name)) {
            throw new FileNotFoundException(sprintf('%s does not exist', $name));
        }

        // Delete file
        list($path, $filename) = $this->pathInfo($name);
        if (isset($this->data[$path][$filename])) {
            unset($this->data[$path][$filename]);

            return true;
        }

        $length = strlen($name) + 1;

        $result = false;
        foreach ($this->data as $path => $files) {
            foreach ($files as $index => $file) {
                if (substr($file->name, 0, $length) === $name . '/') {
                    unset($this->data[$path][$index]);
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Checks if a file exists on the storage
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name) : bool
    {
        list($path, $filename) = $this->pathInfo($name);

        return (isset($this->data[$name]) or isset($this->data[$path][$filename]));
    }

    /**
     * Returns the list of files from the storage
     *
     * @param string $name
     * @return array
     */
    public function list(string $name = null) : array
    {
        if ($name and ! $this->exists($name)) {
            throw new FileNotFoundException(sprintf('%s does not exist', $name));
        }

        $data = $this->data;
        ksort($data);

        $out = [];

        $length = $name ? strlen($name) : false;

        foreach ($data as $files) {
            foreach ($files as $file) {
                if ($name === null or ($length and substr($file->name, 0, $length) === $name)) {
                    unset($file['_contents']);
                    $out[] = $file;
                }
            }
        }
        $data = null;

        return $out;
    }
}
