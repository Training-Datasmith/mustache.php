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
 * Unknown helper exception.
 */
class Unknown_Helper_Exception extends InvalidArgumentException implements Exception
{
    protected $helper_name;
    /**
     * @param string    $helperName
     * @param Exception $previous
     */
    public function __construct($helper_name, $previous = null)
    {
        $this->helper_name = $helper_name;
        $message = sprintf('Unknown helper: %s', $helper_name);
        parent::__construct($message, 0, $previous);
    }
    public function get_helper_name()
    {
        return $this->helper_name;
    }
}