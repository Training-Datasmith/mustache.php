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
namespace Mustache\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\Unknown_Template_Exception;
use Mustache\Loader;
/**
 * Mustache Template filesystem Loader implementation.
 *
 * A FilesystemLoader instance loads Mustache Template source from the filesystem by name:
 *
 *     $loader = new FilesystemLoader(__DIR__.'/views');
 *     $tpl = $loader->load('foo'); // equivalent to `file_get_contents(__DIR__.'/views/foo.mustache');
 *
 * This is probably the most useful Mustache Loader implementation. It can be used for partials and normal Templates:
 *
 *     $m = new \Mustache\Engine([
 *          'loader'          => new FilesystemLoader(__DIR__.'/views'),
 *          'partials_loader' => new FilesystemLoader(__DIR__.'/views/partials'),
 *     ]);
 */
class Filesystem_Loader implements Loader
{
    private $base_dir;
    private $extension = '.mustache';
    private $templates = [];
    /**
     * Mustache filesystem Loader constructor.
     *
     * Passing an $options array allows overriding certain Loader options during instantiation:
     *
     *     $options = [
     *         // The filename extension used for Mustache templates. Defaults to '.mustache'
     *         'extension' => '.ms',
     *     ];
     *
     * @throws RuntimeException if $baseDir does not exist
     *
     * @param string $baseDir Base directory containing Mustache template files
     * @param array  $options Loader options (default: [])
     */
    public function __construct(string $base_dir, array $options = [])
    {
        $this->base_dir = $base_dir;
        if (strpos($this->base_dir, '://') === false) {
            $this->base_dir = realpath($this->base_dir);
        }
        if ($this->should_check_path() && !is_dir($this->base_dir)) {
            throw new RuntimeException(sprintf('FilesystemLoader baseDir must be a directory: %s', $base_dir));
        }
        if (array_key_exists('extension', $options)) {
            if (empty($options['extension'])) {
                $this->extension = '';
            } else {
                $this->extension = '.' . ltrim($options['extension'], '.');
            }
        }
    }
    /**
     * Load a Template by name.
     *
     *     $loader = new FilesystemLoader(__DIR__.'/views');
     *     $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = $this->load_file($name);
        }
        return $this->templates[$name];
    }
    /**
     * Helper function for loading a Mustache file by name.
     *
     * @throws UnknownTemplateException If a template file is not found
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    protected function load_file($name)
    {
        $file_name = $this->get_file_name($name);
        if ($this->should_check_path() && !file_exists($file_name)) {
            throw new Unknown_Template_Exception($name);
        }
        return file_get_contents($file_name);
    }
    /**
     * Helper function for getting a Mustache template file name.
     *
     *
     * @return string Template file name
     */
    protected function get_file_name(string $name)
    {
        $file_name = $this->base_dir . '/' . $name;
        if (substr($file_name, -strlen($this->extension)) !== $this->extension) {
            $file_name .= $this->extension;
        }
        $real_base = realpath($this->base_dir);
        $real_file = realpath($file_name);
        if ($real_base !== false && $real_file !== false && strncmp($real_file, $real_base . DIRECTORY_SEPARATOR, strlen($real_base) + 1) !== 0) {
            throw new Unknown_Template_Exception($name);
        }
        return $file_name;
    }
    /**
     * Only check if baseDir is a directory and requested templates are files if
     * baseDir is using the filesystem stream wrapper.
     *
     * @return bool Whether to check `is_dir` and `file_exists`
     */
    protected function should_check_path()
    {
        return strpos($this->base_dir, '://') === false || strpos($this->base_dir, 'file://') === 0;
    }
}