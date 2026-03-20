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
 * Mustache Template array Loader implementation.
 *
 * An ArrayLoader instance loads Mustache Template source by name from an initial array:
 *
 *     $loader = new ArrayLoader(
 *         'foo' => '{{ bar }}',
 *         'baz' => 'Hey {{ qux }}!'
 *     );
 *
 *     $tpl = $loader->load('foo'); // '{{ bar }}'
 *
 * The ArrayLoader is used internally as a partials loader by Mustache\Engine instance when an array of partials
 * is set. It can also be used as a quick-and-dirty Template loader.
 */
class Array_Loader implements Loader, Mutable_Loader
{
    private $templates;
    /**
     * ArrayLoader constructor.
     *
     * @param array $templates Associative array of Template source (default: [])
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }
    /**
     * Load a Template.
     *
     * @throws UnknownTemplateException If a template file is not found
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            throw new Unknown_Template_Exception($name);
        }
        return $this->templates[$name];
    }
    /**
     * Set an associative array of Template sources for this loader.
     */
    public function set_templates(array $templates)
    {
        $this->templates = $templates;
    }
    /**
     * Set a Template source by name.
     *
     * @param string $name
     * @param string $template Mustache Template source
     */
    public function set_template($name, $template)
    {
        $this->templates[$name] = $template;
    }
}