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
 * Unknown filter exception.
 */
class Unknown_Filter_Exception extends \UnexpectedValueException implements Exception
{
    protected $filter_name;
    /**
     * @param string    $filterName
     * @param Exception $previous
     */
    public function __construct($filter_name, $previous = null)
    {
        $this->filter_name = $filter_name;
        $message = sprintf('Unknown filter: %s', $filter_name);
        parent::__construct($message, 0, $previous);
    }
    public function get_filter_name()
    {
        return $this->filter_name;
    }
}