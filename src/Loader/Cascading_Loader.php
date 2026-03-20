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

use Mustache\Exception\Unknown_Template_Exception;
use Mustache\Loader;
/**
 * A Mustache Template cascading loader implementation, which delegates to other
 * Loader instances.
 */
class Cascading_Loader implements Loader
{
    private $loaders;
    /**
     * Construct a CascadingLoader with an array of loaders.
     *
     *     $loader = new CascadingLoader([
     *         new InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__),
     *         new FilesystemLoader(__DIR__.'/templates')
     *     ]);
     *
     * @param Loader[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        $this->loaders = [];
        foreach ($loaders as $loader) {
            $this->add_loader($loader);
        }
    }
    /**
     * Add a Loader instance.
     */
    public function add_loader(Loader $loader)
    {
        $this->loaders[] = $loader;
    }
    /**
     * Load a Template by name.
     *
     * @throws UnknownTemplateException If a template file is not found
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name);
            } catch (Unknown_Template_Exception $e) {
                // do nothing, check the next loader.
            }
        }
        throw new Unknown_Template_Exception($name);
    }
}