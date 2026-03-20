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
namespace Mustache\Exception;

use Mustache\Exception;
/**
 * Unknown template exception.
 */
class Unknown_Template_Exception extends InvalidArgumentException implements Exception
{
    protected $template_name;
    /**
     * @param string    $templateName
     * @param Exception $previous
     */
    public function __construct($template_name, $previous = null)
    {
        $this->template_name = $template_name;
        $message = sprintf('Unknown template: %s', $template_name);
        parent::__construct($message, 0, $previous);
    }
    public function get_template_name()
    {
        return $this->template_name;
    }
}