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
namespace Mustache\Logger;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\LogicException;
use Mustache\Exception\RuntimeException;
use Mustache\Logger;
/**
 * A Mustache Stream Logger.
 *
 * The Stream Logger wraps a file resource instance (such as a stream) or a
 * stream URL. All log messages over the threshold level will be appended to
 * this stream.
 *
 * Hint: Try `php://stderr` for your stream URL.
 */
class Stream_Logger extends Abstract_Logger
{
    protected static $levels = [self::DEBUG => 100, self::INFO => 200, self::NOTICE => 250, self::WARNING => 300, self::ERROR => 400, self::CRITICAL => 500, self::ALERT => 550, self::EMERGENCY => 600];
    protected $level;
    protected $stream;
    protected $url;
    /**
     * @throws InvalidArgumentException if the logging level is unknown
     *
     * @param resource|string $stream Resource instance or URL
     * @param int             $level  The minimum logging level at which this handler will be triggered
     */
    public function __construct($stream, $level = Logger::ERROR)
    {
        $this->set_level($level);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
    }
    /**
     * Close stream resources.
     */
    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
    /**
     * Set the minimum logging level.
     *
     * @throws InvalidArgumentException if the logging level is unknown
     *
     * @param int $level The minimum logging level which will be written
     */
    public function set_level($level)
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new InvalidArgumentException(sprintf('Unexpected logging level: %s', $level));
        }
        $this->level = $level;
    }
    /**
     * Get the current minimum logging level.
     *
     * @return int
     */
    public function get_level()
    {
        return $this->level;
    }
    /**
     * Logs with an arbitrary level.
     *
     * @throws InvalidArgumentException if the logging level is unknown
     *
     * @param mixed  $level
     * @param string $message
     */
    public function log($level, $message, array $context = [])
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new InvalidArgumentException(sprintf('Unexpected logging level: %s', $level));
        }
        if (self::$levels[$level] >= self::$levels[$this->level]) {
            $this->write_log($level, $message, $context);
        }
    }
    /**
     * Write a record to the log.
     *
     * @throws LogicException   If neither a stream resource nor url is present
     * @throws RuntimeException If the stream url cannot be opened
     *
     * @param int    $level   The logging level
     * @param string $message The log message
     * @param array  $context The log context
     */
    protected function write_log($level, $message, array $context = [])
    {
        if (!is_resource($this->stream)) {
            if (!isset($this->url)) {
                throw new LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->stream = fopen($this->url, 'a');
            if (!is_resource($this->stream)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(sprintf('The stream or file "%s" could not be opened.', $this->url));
                // @codeCoverageIgnoreEnd
            }
        }
        fwrite($this->stream, self::format_line($level, $message, $context));
    }
    /**
     * Gets the name of the logging level.
     *
     * @throws InvalidArgumentException if the logging level is unknown
     *
     * @param int $level
     *
     * @return string
     */
    protected static function get_level_name($level)
    {
        return strtoupper($level);
    }
    /**
     * Format a log line for output.
     *
     * @param int    $level   The logging level
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return string
     */
    protected static function format_line($level, $message, array $context = [])
    {
        return sprintf("%s: %s\n", self::get_level_name($level), self::interpolate_message($message, $context));
    }
    /**
     * Interpolate context values into the message placeholders.
     *
     * @param string $message
     *
     * @return string
     */
    protected static function interpolate_message($message, array $context = [])
    {
        if (strpos($message, '{') === false) {
            return $message;
        }
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }
        // interpolate replacement values into the the message and return
        return strtr($message, $replace);
    }
}