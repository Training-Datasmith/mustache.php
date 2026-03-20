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
namespace Mustache;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\Syntax_Exception;
/**
 * Mustache Tokenizer class.
 *
 * This class is responsible for turning raw template source into a set of Mustache tokens.
 */
class Tokenizer
{
    // Finite state machine states
    public const IN_TEXT = 0;
    public const IN_TAG_TYPE = 1;
    public const IN_TAG = 2;
    // Token types
    public const T_SECTION = '#';
    public const T_INVERTED = '^';
    public const T_END_SECTION = '/';
    public const T_COMMENT = '!';
    public const T_PARTIAL = '>';
    public const T_PARENT = '<';
    public const T_DELIM_CHANGE = '=';
    public const T_ESCAPED = '_v';
    public const T_UNESCAPED = '{';
    public const T_UNESCAPED_2 = '&';
    public const T_TEXT = '_t';
    public const T_PRAGMA = '%';
    public const T_BLOCK_VAR = '$';
    public const T_BLOCK_ARG = '$arg';
    // Valid token types
    private static $tag_types = [self::T_SECTION => true, self::T_INVERTED => true, self::T_END_SECTION => true, self::T_COMMENT => true, self::T_PARTIAL => true, self::T_PARENT => true, self::T_DELIM_CHANGE => true, self::T_ESCAPED => true, self::T_UNESCAPED => true, self::T_UNESCAPED_2 => true, self::T_PRAGMA => true, self::T_BLOCK_VAR => true];
    private static $tag_names = [self::T_SECTION => 'section', self::T_INVERTED => 'inverted section', self::T_END_SECTION => 'section end', self::T_COMMENT => 'comment', self::T_PARTIAL => 'partial', self::T_PARENT => 'parent', self::T_DELIM_CHANGE => 'set delimiter', self::T_ESCAPED => 'variable', self::T_UNESCAPED => 'unescaped variable', self::T_UNESCAPED_2 => 'unescaped variable', self::T_PRAGMA => 'pragma', self::T_BLOCK_VAR => 'block variable', self::T_BLOCK_ARG => 'block variable'];
    // Token properties
    public const TYPE = 'type';
    public const NAME = 'name';
    public const DYNAMIC = 'dynamic';
    public const OTAG = 'otag';
    public const CTAG = 'ctag';
    public const LINE = 'line';
    public const INDEX = 'index';
    public const END = 'end';
    public const INDENT = 'indent';
    public const NODES = 'nodes';
    public const VALUE = 'value';
    public const FILTERS = 'filters';
    private $state;
    private $tag_type;
    private $buffer;
    private $tokens;
    private $seen_tag;
    private $line;
    private $otag;
    private $otag_char;
    private $otag_len;
    private $ctag;
    private $ctag_char;
    private $ctag_len;
    /**
     * Scan and tokenize template source.
     *
     * @throws SyntaxException          when mismatched section tags are encountered
     * @throws InvalidArgumentException when $delimiters string is invalid
     *
     * @param string $text       Mustache template source to tokenize
     * @param string $delimiters Optionally, pass initial opening and closing delimiters (default: empty string)
     *
     * @return array Set of Mustache tokens
     */
    public function scan($text, $delimiters = '')
    {
        // Setting mbstring.func_overload makes things *really* slow.
        // Let's do everyone a favor and scan this string as ASCII instead.
        //
        // The INI directive was removed in PHP 8.0 so we don't need to check there (and can drop it
        // when we remove support for older versions of PHP).
        //
        // @codeCoverageIgnoreStart
        $encoding = null;
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            if (function_exists('mb_internal_encoding') && ini_get('mbstring.func_overload') & 2) {
                $encoding = mb_internal_encoding();
                mb_internal_encoding('ASCII');
            }
        }
        // @codeCoverageIgnoreEnd
        $this->reset();
        if (is_string($delimiters) && ($delimiters = trim($delimiters)) !== '') {
            $this->set_delimiters($delimiters);
        }
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            switch ($this->state) {
                case self::IN_TEXT:
                    $char = $text[$i];
                    // Test whether it's time to change tags.
                    if ($char === $this->otag_char && substr($text, $i, $this->otag_len) === $this->otag) {
                        $i--;
                        $this->flush_buffer();
                        $this->state = self::IN_TAG_TYPE;
                    } else {
                        $this->buffer .= $char;
                        if ($char === "\n") {
                            $this->flush_buffer();
                            $this->line++;
                        }
                    }
                    break;
                case self::IN_TAG_TYPE:
                    $i += $this->otag_len - 1;
                    $char = $text[$i + 1];
                    if (isset(self::$tag_types[$char])) {
                        $tag = $char;
                        $this->tag_type = $tag;
                    } else {
                        $tag = null;
                        $this->tag_type = self::T_ESCAPED;
                    }
                    if ($this->tag_type === self::T_DELIM_CHANGE) {
                        $i = $this->change_delimiters($text, $i);
                        $this->state = self::IN_TEXT;
                    } elseif ($this->tag_type === self::T_PRAGMA) {
                        $i = $this->add_pragma($text, $i);
                        $this->state = self::IN_TEXT;
                    } else {
                        if ($tag !== null) {
                            $i++;
                        }
                        $this->state = self::IN_TAG;
                    }
                    $this->seen_tag = $i;
                    break;
                default:
                    $char = $text[$i];
                    // Test whether it's time to change tags.
                    if ($char === $this->ctag_char && substr($text, $i, $this->ctag_len) === $this->ctag) {
                        $token = [self::TYPE => $this->tag_type, self::NAME => trim($this->buffer), self::OTAG => $this->otag, self::CTAG => $this->ctag, self::LINE => $this->line, self::INDEX => $this->tag_type === self::T_END_SECTION ? $this->seen_tag - $this->otag_len : $i + $this->ctag_len];
                        if ($this->tag_type === self::T_UNESCAPED) {
                            // Clean up `{{{ tripleStache }}}` style tokens.
                            if ($this->ctag === '}}') {
                                if ($i + 2 < $len && $text[$i + 2] === '}') {
                                    $i++;
                                } else {
                                    $msg = sprintf('Mismatched tag delimiters: %s on line %d', $token[self::NAME], $token[self::LINE]);
                                    throw new Syntax_Exception($msg, $token);
                                }
                            } else {
                                $last_name = $token[self::NAME];
                                if (substr($last_name, -1) === '}') {
                                    $token[self::NAME] = trim(substr($last_name, 0, -1));
                                } else {
                                    $msg = sprintf('Mismatched tag delimiters: %s on line %d', $token[self::NAME], $token[self::LINE]);
                                    throw new Syntax_Exception($msg, $token);
                                }
                            }
                        }
                        $this->buffer = '';
                        $i += $this->ctag_len - 1;
                        $this->state = self::IN_TEXT;
                        $this->tokens[] = $token;
                    } else {
                        $this->buffer .= $char;
                    }
                    break;
            }
        }
        if ($this->state !== self::IN_TEXT) {
            $this->throw_unclosed_tag_exception();
        }
        $this->flush_buffer();
        // Restore the user's encoding...
        // @codeCoverageIgnoreStart
        if ($encoding) {
            mb_internal_encoding($encoding);
        }
        // @codeCoverageIgnoreEnd
        return $this->tokens;
    }
    /**
     * Helper function to reset tokenizer internal state.
     */
    private function reset()
    {
        $this->state = self::IN_TEXT;
        $this->tag_type = null;
        $this->buffer = '';
        $this->tokens = [];
        $this->seen_tag = false;
        $this->line = 0;
        $this->otag = '{{';
        $this->otag_char = '{';
        $this->otag_len = 2;
        $this->ctag = '}}';
        $this->ctag_char = '}';
        $this->ctag_len = 2;
    }
    /**
     * Flush the current buffer to a token.
     */
    private function flush_buffer()
    {
        if (strlen($this->buffer) > 0) {
            $this->tokens[] = [self::TYPE => self::T_TEXT, self::LINE => $this->line, self::VALUE => $this->buffer];
            $this->buffer = '';
        }
    }
    /**
     * Change the current Mustache delimiters. Set new `otag` and `ctag` values.
     *
     * @throws SyntaxException when delimiter string is invalid
     *
     * @param string $text  Mustache template source
     * @param int    $index Current tokenizer index
     *
     * @return int New index value
     */
    private function change_delimiters($text, $index)
    {
        $start_index = strpos($text, '=', $index) + 1;
        $close = '=' . $this->ctag;
        $close_index = strpos($text, $close, $index);
        if ($close_index === false) {
            $this->throw_unclosed_tag_exception();
        }
        $token = [self::TYPE => self::T_DELIM_CHANGE, self::LINE => $this->line];
        try {
            $this->set_delimiters(trim(substr($text, $start_index, $close_index - $start_index)));
        } catch (InvalidArgumentException $e) {
            throw new Syntax_Exception($e->get_message(), $token);
        }
        $this->tokens[] = $token;
        return $close_index + strlen($close) - 1;
    }
    /**
     * Set the current Mustache `otag` and `ctag` delimiters.
     *
     * @throws InvalidArgumentException when delimiter string is invalid
     *
     * @param string $delimiters
     */
    private function set_delimiters($delimiters)
    {
        if (!preg_match('/^\s*(\S+)\s+(\S+)\s*$/', $delimiters, $matches)) {
            throw new InvalidArgumentException(sprintf('Invalid delimiters: %s', $delimiters));
        }
        list($_, $otag, $ctag) = $matches;
        $this->otag = $otag;
        $this->otag_char = $otag[0];
        $this->otag_len = strlen($otag);
        $this->ctag = $ctag;
        $this->ctag_char = $ctag[0];
        $this->ctag_len = strlen($ctag);
    }
    /**
     * Add pragma token.
     *
     * Pragmas are hoisted to the front of the template, so all pragma tokens
     * will appear at the front of the token list.
     *
     * @param string $text
     * @param int    $index
     *
     * @return int New index value
     */
    private function add_pragma($text, $index)
    {
        $end = strpos($text, $this->ctag, $index);
        if ($end === false) {
            $this->throw_unclosed_tag_exception();
        }
        $pragma = trim(substr($text, $index + 2, $end - $index - 2));
        // Pragmas are hoisted to the front of the template.
        array_unshift($this->tokens, [self::TYPE => self::T_PRAGMA, self::NAME => $pragma, self::LINE => 0]);
        return $end + $this->ctag_len - 1;
    }
    private function throw_unclosed_tag_exception()
    {
        $name = trim($this->buffer);
        if ($name !== '') {
            $msg = sprintf('Unclosed tag: %s on line %d', $name, $this->line);
        } else {
            $msg = sprintf('Unclosed tag on line %d', $this->line);
        }
        throw new Syntax_Exception($msg, [self::TYPE => $this->tag_type, self::NAME => $name, self::OTAG => $this->otag, self::CTAG => $this->ctag, self::LINE => $this->line, self::INDEX => $this->seen_tag - $this->otag_len]);
    }
    /**
     * Get the human readable name for a tag type.
     *
     * @param string $tagType One of the tokenizer T_* constants
     *
     * @return string
     */
    public static function get_tag_name($tag_type)
    {
        return isset(self::$tag_names[$tag_type]) ? self::$tag_names[$tag_type] : 'unknown';
    }
}