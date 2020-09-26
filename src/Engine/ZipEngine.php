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

use ZipArchive;
use Origin\Storage\FileObject;
use Origin\Storage\Exception\StorageException;
use Origin\Storage\Exception\FileNotFoundException;

class ZipEngine extends BaseEngine
{
    /**
     * Zip Archive
     *
     * @var \ZipArchive
     */
    private $archive;

    public function initialize(array $config): void
    {
        $this->archive = new ZipArchive();
        $file = $this->config('file');
        if (! $file) {
            throw new StorageException('File config not provided');
        }

        $result = file_exists($file) ?  $this->archive->open($file) : $this->archive->open($file, ZipArchive::CREATE);
        if ($result !== true) {
            throw new StorageException('Error opening ' . $file  . ' error: ' . $result);
        }
    }

    /**
     * Reads from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name): string
    {
        $contents = $this->archive->getFromName($name);
        if ($contents) {
            return $contents;
        }
        throw new FileNotFoundException(sprintf('%s does not exist', $name));
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
        $info  = $this->pathinfo($name);
        if ($info['directory']) {
            $this->archive->addEmptyDir($info['directory']);
        }

        return $this->archive->addFromString($name, $data);
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
            throw new FileNotFoundException(sprintf('%s does not exist', $name));
        }

        // if its a file then delete it
        if ($this->archive->statName($name) !== false) {
            return $this->archive->deleteName($name);
        }

        $length = $name ? strlen($name) : false;

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $file = $this->archive->statIndex($i);
            if (! $file) {
                continue;
            }
            if (substr($file['name'], -1) === '/') {
                continue;
            }

            if (substr($file['name'], 0, $length) === $name) {
                $this->archive->deleteIndex($i);
            }
        }

        return $this->archive->deleteName($name . '/');
    }

    /**
     * Checks if a file/folder exists on the storage
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return ($this->archive->statName($name) !== false || $this->archive->statName($name . '/') !== false);
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
            throw new FileNotFoundException(sprintf('%s does not exist', $name));
        }

        $length = $name ? strlen($name) : false;
        $out = [];

        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $file = $this->archive->statIndex($i);
            if (! $file) {
                continue;
            }
            // skip folders
            if (substr($file['name'], -1) === '/') {
                continue;
            }

            if ($name === null || ($length && substr($file['name'], 0, $length) === $name)) {
                $info = $this->pathinfo($file['name']);
                $info['timestamp'] = $file['mtime'];
                $info['size'] = $file['size'];
                $out[] = new FileObject($info);
            }
        }

        return $out;
    }

    /**
     * Closes the archive and saves changes
     *
     * @return boolean
     */
    public function close(): bool
    {
        return $this->archive->close();
    }
}
