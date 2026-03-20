<?php

declare (strict_types=1);
/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2025 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mustache\Cache;

use Mustache\Exception\RuntimeException;
use Mustache\Logger;
/**
 * Mustache Cache filesystem implementation.
 *
 * A FilesystemCache instance caches Mustache Template classes from the filesystem by name:
 *
 *     $cache = new FilesystemCache(__DIR__.'/cache');
 *     $cache->cache($className, $compiledSource);
 *
 * The FilesystemCache benefits from any opcode caching that may be setup in your environment. So do that, k?
 */
class Filesystem_Cache extends Abstract_Cache
{
    private $base_dir;
    private $file_mode;
    /**
     * Filesystem cache constructor.
     *
     * @param string $baseDir  Directory for compiled templates
     * @param int    $fileMode Override default permissions for cache files. Defaults to using the system-defined umask
     */
    public function __construct($base_dir, $file_mode = null)
    {
        $this->base_dir = $base_dir;
        $this->file_mode = $file_mode;
    }
    /**
     * Load the class from cache using `require_once`.
     *
     * @param string $key
     *
     * @return bool
     */
    public function load($key)
    {
        $file_name = $this->get_cache_filename($key);
        if (!is_file($file_name)) {
            return false;
        }
        require_once $file_name;
        return true;
    }
    /**
     * Cache and load the compiled class.
     *
     * @param string $key
     * @param string $value
     */
    public function cache($key, $value)
    {
        $file_name = $this->get_cache_filename($key);
        $this->log(Logger::DEBUG, 'Writing to template cache: "{fileName}"', ['fileName' => $file_name]);
        $this->write_file($file_name, $value);
        $this->load($key);
    }
    /**
     * Build the cache filename.
     * Subclasses should override for custom cache directory structures.
     *
     * @param string $name
     *
     * @return string
     */
    protected function get_cache_filename($name)
    {
        return sprintf('%s/%s.php', $this->base_dir, $name);
    }
    /**
     * Create cache directory.
     *
     * @throws RuntimeException If unable to create directory
     *
     * @param string $fileName
     *
     * @return string
     */
    private function build_directory_for_filename($file_name)
    {
        $dir_name = dirname($file_name);
        if (!is_dir($dir_name)) {
            $this->log(Logger::INFO, 'Creating Mustache template cache directory: "{dirName}"', ['dirName' => $dir_name]);
            @mkdir($dir_name, 0777, true);
            // @codeCoverageIgnoreStart
            if (!is_dir($dir_name)) {
                throw new RuntimeException(sprintf('Failed to create cache directory "%s".', $dir_name));
            }
            // @codeCoverageIgnoreEnd
        }
        return $dir_name;
    }
    /**
     * Write cache file.
     *
     * @throws RuntimeException If unable to write file
     *
     * @param string $fileName
     * @param string $value
     */
    private function write_file($file_name, $value)
    {
        $dir_name = $this->build_directory_for_filename($file_name);
        $this->log(Logger::DEBUG, 'Caching compiled template to "{fileName}"', ['fileName' => $file_name]);
        $temp_file = tempnam($dir_name, basename($file_name));
        if (false !== @file_put_contents($temp_file, $value)) {
            if (@rename($temp_file, $file_name)) {
                $mode = isset($this->file_mode) ? $this->file_mode : 0666 & ~umask();
                @chmod($file_name, $mode);
                return;
            }
            // @codeCoverageIgnoreStart
            $this->log(Logger::ERROR, 'Unable to rename Mustache temp cache file: "{tempName}" -> "{fileName}"', ['tempName' => $temp_file, 'fileName' => $file_name]);
            // @codeCoverageIgnoreEnd
        }
        // @codeCoverageIgnoreStart
        @unlink($temp_file);
        throw new RuntimeException(sprintf('Failed to write cache file "%s".', $file_name));
        // @codeCoverageIgnoreEnd
    }
}