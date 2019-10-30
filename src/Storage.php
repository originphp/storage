<?php

/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Storage;

use InvalidArgumentException;
use Origin\Storage\Engine\BaseEngine;
use Origin\Configurable\StaticConfigurable as Configurable;

class Storage
{
    use Configurable;

    protected static $defaultConfig = [];

    /**
     * Holds the Storage Engines
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * The default storage to use
     * @internal whilst use is being deprecated
     * @var string
     */
    protected static $default = 'default';

    /**
     * Alias for Storage::engine. Gets the configured engine
     *
     * @param string $name
     * @return \Origin\Storage\Engine\BaseEngine
     */
    public static function volume(string $name) : BaseEngine
    {
        return static::engine($name);
    }

    /**
     * Gets the configured Storage Engine
     *
     * @param string $name
     * @return \Origin\Storage\Engine\BaseEngine
     */
    public static function engine(string $name) : BaseEngine
    {
        if (isset(static::$loaded[$name])) {
            return static::$loaded[$name];
        }

        return static::$loaded[$name] = static::buildEngine($name);
    }

    /**
     * Builds an engine using the configuration
     *
     * @param string $name
     * @throws \InvalidArgumentException
     * @return \Origin\Storage\Engine\BaseEngine
     */
    protected static function buildEngine(string $name) : BaseEngine
    {
        $config = static::config($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = "Origin\Storage\Engine\\{$config['engine']}Engine";
            }
            if (empty($config['className']) or ! class_exists($config['className'])) {
                throw new InvalidArgumentException("Storage Engine for {$name} could not be found");
            }

            return new $config['className']($config);
        }
        throw new InvalidArgumentException(sprintf('The storage configuration `%s` does not exist.', $name));
    }

    /**
     * Reads an item from the Storage
     *
     * @param string $name
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return string
     */
    public static function read(string $name, array $options = []) : string
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->read($name);
    }
    /**
     * Writes an item from Storage
     *
     * @param string $name
     * @param mixed $value
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return bool
     */
    public static function write(string $name, $value, array $options = []) : bool
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->write($name, $value);
    }

    /**
     * Checks if an item is in the Storage
     *
     * @param string $name
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return bool
     */
    public static function exists(string $name, array $options = []) : bool
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->exists($name);
    }

    /**
     * Deletes a file OR directory
     *
     * @param string $name
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return boolean
     */
    public static function delete(string $name, array $options = []) : bool
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->delete($name);
    }

    /**
     * Returns a list of items in the storage
     *
     * @param string $path images or public/images
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return array
     */
    public static function list(string $path = null, array $options = []) : array
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->list($path);
    }
}
