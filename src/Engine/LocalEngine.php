<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Origin\Storage\Engine;

use InvalidArgumentException;
use Origin\Storage\FileObject;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Origin\Storage\Exception\StorageException;
use Origin\Storage\Exception\FileNotFoundException;

class LocalEngine extends BaseEngine
{
    protected $defaultConfig = [
        'lock' => false
    ];

    public function initialize(array $config): void
    {
        $root = $this->config('root');
        if (! $root || (! file_exists($root) && ! is_dir($root))) {
            throw new InvalidArgumentException(sprintf('Invalid root `%s`.', $root));
        }
    }

    /**
     * Reads a file from the storage
     *
     * @param string $name
     * @return string
     */
    public function read(string $name): string
    {
        $filename = $this->addPathPrefix($name);

        if (! is_file($filename)) {
            throw new FileNotFoundException(sprintf('File %s does not exist', $name));
        }

        $handle = fopen($filename, 'r') ;
        defer($context, 'fclose', $handle);

        if ($this->config('lock')) {
            if (! flock($handle, LOCK_SH)) {
                throw new StorageException('Error getting shared lock.');
            }
            defer($context, 'flock', $handle, LOCK_UN);
        }
       
        return fread($handle, filesize($filename));
    }

    /**
     * Writes to the disk
     *
     * @param string $name
     * @param string $data
     * @return bool
     */
    public function write(string $name, string $data): bool
    {
        $filename = $this->addPathPrefix($name);

        $folder = pathinfo($filename, PATHINFO_DIRNAME);
        $this->createDirectoryIfNotExists($folder);

        $handle = fopen($filename, 'w') ;
        defer($context, 'fclose', $handle);

        if ($this->config('lock')) {
            if (! flock($handle, LOCK_EX)) {
                throw new StorageException('Error getting exclusive lock.');
            }
            defer($context, 'flock', $handle, LOCK_UN);
        }

        return (bool) fputs($handle, $data);
    }

    /**
     * @param string $directory
     * @return void
     */
    private function createDirectoryIfNotExists(string $directory): void
    {
        if (! is_dir($directory)) {
            $old = umask(0);
            mkdir($directory, 0755, true);
            umask($old);
            clearstatcache(true, $directory);
        }
    }

    /**
     * Deletes a file OR directory
     *
     * @param string $name
     * @return boolean
     */
    public function delete(string $name): bool
    {
        $filename = $this->addPathPrefix($name);

        // Prevent accidentally deleting a folder
        if (substr($name, -1) === '/') {
            return false;
        }

        if (file_exists($filename)) {
            if (is_dir($filename)) {
                return $this->rmdir($filename, true);
            }

            return unlink($filename);
        }
        throw new FileNotFoundException(sprintf('%s does not exist', $name));
    }

    /**
     * Checks if file exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        $filename = $this->addPathPrefix($name);

        return file_exists($filename);
    }

    /**
     * Gets a list of items on the disk
     *
     * @return array
     */
    public function list(string $name = null): array
    {
        $directory = $this->addPathPrefix($name);

        $root = $this->config('root');

        if (file_exists($directory)) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            $files = [];
            foreach ($rii as $file) {
                if ($file->isDir()) {
                    continue;
                }
      
                $files[] = new FileObject([
                    'name' => $this->rebase($file->getPathname(), $root . '/'),
                    'timestamp' => $file->getMTime(),
                    'size' => $file->getSize(),
                ]);
            }

            return $files;
        }
        throw new FileNotFoundException('directory does not exist');
    }

    /**
     * Recursively delete a directory
     *
     * @param string $directory
     * @return bool
     */
    protected function rmdir(string $directory, bool $recursive = true): bool
    {
        if ($recursive) {
            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $filename) {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $filename)) {
                    $this->rmdir($directory . DIRECTORY_SEPARATOR . $filename, true);
                    continue;
                }
                unlink($directory . DIRECTORY_SEPARATOR . $filename);
            }
        }

        return @rmdir($directory);
    }

    /**
     * Adds the prefix
     *
     * @param string $path
     * @return string
     */
    protected function addPathPrefix(string $path = null): string
    {
        $location = $this->config('root');
        if ($path) {
            $location .= DIRECTORY_SEPARATOR . $path;
        }

        return $location;
    }
}
