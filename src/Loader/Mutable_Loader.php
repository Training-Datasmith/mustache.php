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

interface Mutable_Loader
{
    /**
     * Set an associative array of Template sources for this loader.
     */
    public function set_templates(array $templates);
    /**
     * Set a Template source by name.
     *
     * @param string $name
     * @param string $template Mustache Template source
     */
    public function set_template($name, $template);
}