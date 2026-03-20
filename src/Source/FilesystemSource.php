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
namespace Mustache\Source;

use Mustache\Exception\RuntimeException;
use Mustache\Source;
/**
 * Mustache template Filesystem Source.
 *
 * This template Source uses stat() to generate the Source key, so that using
 * pre-compiled templates doesn't require hitting the disk to read the source.
 * It is more suitable for production use, and is used by default in the
 * ProductionFilesystemLoader.
 */
class Filesystem_Source implements Source
{
    private $file_name;
    private $stat_props;
    private $stat;
    /**
     * Filesystem Source constructor.
     *
     * @param string $fileName
     */
    public function __construct($file_name, array $stat_props)
    {
        $this->file_name = $file_name;
        $this->stat_props = $stat_props;
    }
    /**
     * Get the Source key (used to generate the compiled class name).
     *
     * @throws RuntimeException when a source file cannot be read
     *
     * @return string
     */
    public function get_key()
    {
        $chunks = ['fileName' => $this->file_name];
        if (!empty($this->stat_props)) {
            if (!isset($this->stat)) {
                $this->stat = @stat($this->file_name);
            }
            if ($this->stat === false) {
                throw new RuntimeException(sprintf('Failed to read source file "%s".', $this->file_name));
            }
            foreach ($this->stat_props as $prop) {
                $chunks[$prop] = $this->stat[$prop];
            }
        }
        return json_encode($chunks);
    }
    /**
     * Get the template Source.
     *
     * @return string
     */
    public function get_source()
    {
        return file_get_contents($this->file_name);
    }
}