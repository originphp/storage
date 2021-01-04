<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
use Origin\Storage\Exception\NotFoundException;

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
    public function read(string $name): string
    {
        $info = $this->pathinfo($name);

        if (isset($this->data[$info['directory']][$info['name']])) {
            return $this->data[$info['directory']][$info['name']]['_contents'];
        }
        throw new NotFoundException(sprintf('File %s does not exist', $name));
    }

    /**
     * Writes to the storage
     *
     * @param string $name
     * @param string $data
     * @return bool
     */
    public function write(string $name, string $data): bool
    {
        $info = $this->pathinfo($name);

        $info['timestamp'] = time();
        $info['size'] = mb_strlen($data, '8bit');
        $info['_contents'] = $data;
    
        $this->data[$info['directory']][$info['name']] = new FileObject($info);

        return true;
    }

    /**
     * Deletes from the storage
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        if ($name && ! $this->exists($name)) {
            throw new NotFoundException(sprintf('%s does not exist', $name));
        }

        // Delete file
        $info = $this->pathinfo($name);
        if (isset($this->data[$info['directory']][$info['name']])) {
            unset($this->data[$info['directory']][$info['name']]);

            return true;
        }

        $length = strlen($name) + 1;

        $result = false;
        foreach ($this->data as $path => $files) {
            foreach ($files as $index => $file) {
                if (substr($file->path, 0, $length) === $name . '/') {
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
    public function exists(string $name): bool
    {
        $info = $this->pathinfo($name);

        return (isset($this->data[$name]) || isset($this->data[$info['directory']][$info['name']]));
    }

    /**
     * Returns the list of files from the storage
     *
     * @param string $name
     * @return array
     */
    public function list(string $name = null): array
    {
        if ($name && ! $this->exists($name)) {
            throw new NotFoundException(sprintf('%s does not exist', $name));
        }

        $data = $this->data;
        ksort($data);

        $out = [];

        $length = $name ? strlen($name) : false;

        foreach ($data as $files) {
            foreach ($files as $file) {
                if ($name === null || ($length && substr($file->path, 0, $length) === $name)) {
                    unset($file['_contents']);
                    $out[] = $file;
                }
            }
        }
        $data = null;

        return $out;
    }
}
