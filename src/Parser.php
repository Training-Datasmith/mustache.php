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

use Mustache\Exception\Syntax_Exception;
/**
 * Mustache Parser class.
 *
 * This class is responsible for turning a set of Mustache tokens into a parse tree.
 */
class Parser
{
    private $line_num;
    private $line_tokens;
    private $pragmas;
    private $default_pragmas = [];
    // Optional Mustache specs
    private $dynamic_names = true;
    private $inheritance = true;
    private $pragma_filters;
    /**
     * Process an array of Mustache tokens and convert them into a parse tree.
     *
     * @param array $tokens Set of Mustache tokens
     *
     * @return array Mustache token parse tree
     */
    public function parse(array $tokens = [])
    {
        $this->line_num = -1;
        $this->line_tokens = 0;
        $this->pragmas = $this->default_pragmas;
        $this->pragma_filters = isset($this->pragmas[Engine::PRAGMA_FILTERS]);
        return $this->build_tree($tokens);
    }
    /**
     * Disable optional Mustache specs.
     *
     * @internal Users should set options in Mustache\Engine, not here :)
     *
     * @param bool[] $options
     */
    public function set_options(array $options)
    {
        if (isset($options['dynamic_names'])) {
            $this->dynamic_names = $options['dynamic_names'] !== false;
        }
        if (isset($options['inheritance'])) {
            $this->inheritance = $options['inheritance'] !== false;
        }
    }
    /**
     * Enable pragmas across all templates, regardless of the presence of pragma
     * tags in the individual templates.
     *
     * @internal Users should set global pragmas in Mustache\Engine, not here :)
     *
     * @param string[] $pragmas
     */
    public function set_pragmas(array $pragmas)
    {
        $this->pragmas = [];
        foreach ($pragmas as $pragma) {
            $this->enable_pragma($pragma);
        }
        $this->default_pragmas = $this->pragmas;
    }
    /**
     * Helper method for recursively building a parse tree.
     *
     * @throws SyntaxException when nesting errors or mismatched section tags are encountered
     *
     * @param array &$tokens Set of Mustache tokens
     * @param array $parent  Parent token (default: null)
     *
     * @return array Mustache Token parse tree
     */
    private function build_tree(array &$tokens, $parent = null)
    {
        $nodes = [];
        while (!empty($tokens)) {
            $token = array_shift($tokens);
            if ($token[Tokenizer::LINE] === $this->line_num) {
                $this->line_tokens++;
            } else {
                $this->line_num = $token[Tokenizer::LINE];
                $this->line_tokens = 0;
            }
            if ($token[Tokenizer::TYPE] !== Tokenizer::T_COMMENT) {
                if (isset($token[Tokenizer::NAME])) {
                    list($name, $is_dynamic) = $this->get_dynamic_name($token);
                    if ($is_dynamic) {
                        $token[Tokenizer::NAME] = $name;
                        $token[Tokenizer::DYNAMIC] = true;
                    }
                }
                if ($this->pragma_filters && isset($token[Tokenizer::NAME])) {
                    list($name, $filters) = $this->get_name_and_filters($token[Tokenizer::NAME]);
                    if (!empty($filters)) {
                        $token[Tokenizer::NAME] = $name;
                        $token[Tokenizer::FILTERS] = $filters;
                    }
                }
            }
            switch ($token[Tokenizer::TYPE]) {
                case Tokenizer::T_DELIM_CHANGE:
                    $this->check_if_token_is_allowed_in_parent($parent, $token);
                    $this->clear_standalone_lines($nodes, $tokens);
                    break;
                case Tokenizer::T_SECTION:
                case Tokenizer::T_INVERTED:
                    $this->check_if_token_is_allowed_in_parent($parent, $token);
                    $this->clear_standalone_lines($nodes, $tokens);
                    $nodes[] = $this->build_tree($tokens, $token);
                    break;
                case Tokenizer::T_END_SECTION:
                    if (!isset($parent)) {
                        $msg = sprintf('Unexpected closing tag: /%s on line %d', $token[Tokenizer::NAME], $token[Tokenizer::LINE]);
                        throw new Syntax_Exception($msg, $token);
                    }
                    $same_name = $token[Tokenizer::NAME] !== $parent[Tokenizer::NAME];
                    $token_dynamic = isset($token[Tokenizer::DYNAMIC]) && $token[Tokenizer::DYNAMIC];
                    $parent_dynamic = isset($parent[Tokenizer::DYNAMIC]) && $parent[Tokenizer::DYNAMIC];
                    if ($same_name || $token_dynamic !== $parent_dynamic) {
                        $msg = sprintf('Nesting error: %s%s (on line %d) vs. %s%s (on line %d)', $parent_dynamic ? '*' : '', $parent[Tokenizer::NAME], $parent[Tokenizer::LINE], $token_dynamic ? '*' : '', $token[Tokenizer::NAME], $token[Tokenizer::LINE]);
                        throw new Syntax_Exception($msg, $token);
                    }
                    $this->clear_standalone_lines($nodes, $tokens);
                    $parent[Tokenizer::END] = $token[Tokenizer::INDEX];
                    $parent[Tokenizer::NODES] = $nodes;
                    return $parent;
                case Tokenizer::T_PARTIAL:
                    $this->check_if_token_is_allowed_in_parent($parent, $token);
                    //store the whitespace prefix for laters!
                    if ($indent = $this->clear_standalone_lines($nodes, $tokens)) {
                        $token[Tokenizer::INDENT] = $indent[Tokenizer::VALUE];
                    }
                    $nodes[] = $token;
                    break;
                case Tokenizer::T_PARENT:
                    $this->check_if_token_is_allowed_in_parent($parent, $token);
                    $nodes[] = $this->build_tree($tokens, $token);
                    break;
                case Tokenizer::T_BLOCK_VAR:
                    if ($this->inheritance) {
                        if (isset($parent) && $parent[Tokenizer::TYPE] === Tokenizer::T_PARENT) {
                            $token[Tokenizer::TYPE] = Tokenizer::T_BLOCK_ARG;
                        }
                        $this->clear_standalone_lines($nodes, $tokens);
                        $nodes[] = $this->build_tree($tokens, $token);
                    } else {
                        // pretend this was just a normal "escaped" token...
                        $token[Tokenizer::TYPE] = Tokenizer::T_ESCAPED;
                        // TODO: figure out how to figure out if there was a space after this dollar:
                        $token[Tokenizer::NAME] = '$' . $token[Tokenizer::NAME];
                        $nodes[] = $token;
                    }
                    break;
                case Tokenizer::T_PRAGMA:
                    $this->enable_pragma($token[Tokenizer::NAME]);
                // no break
                case Tokenizer::T_COMMENT:
                    $this->clear_standalone_lines($nodes, $tokens);
                    $nodes[] = $token;
                    break;
                default:
                    $nodes[] = $token;
                    break;
            }
        }
        if (isset($parent)) {
            $msg = sprintf('Missing closing tag: %s opened on line %d', $parent[Tokenizer::NAME], $parent[Tokenizer::LINE]);
            throw new Syntax_Exception($msg, $parent);
        }
        return $nodes;
    }
    /**
     * Clear standalone line tokens.
     *
     * Returns a whitespace token for indenting partials, if applicable.
     *
     * @param array $nodes  Parsed nodes
     * @param array $tokens Tokens to be parsed
     *
     * @return array|null Resulting indent token, if any
     */
    private function clear_standalone_lines(array &$nodes, array &$tokens)
    {
        if ($this->line_tokens > 1) {
            // this is the third or later node on this line, so it can't be standalone
            return;
        }
        $prev = null;
        if ($this->line_tokens === 1) {
            // this is the second node on this line, so it can't be standalone
            // unless the previous node is whitespace.
            if ($prev = end($nodes)) {
                if (!$this->token_is_whitespace($prev)) {
                    return;
                }
            }
        }
        if ($next = reset($tokens)) {
            // If we're on a new line, bail.
            if ($next[Tokenizer::LINE] !== $this->line_num) {
                return;
            }
            // If the next token isn't whitespace, bail.
            if (!$this->token_is_whitespace($next)) {
                return;
            }
            if (count($tokens) !== 1) {
                // Unless it's the last token in the template, the next token
                // must end in newline for this to be standalone.
                if (substr($next[Tokenizer::VALUE], -1) !== "\n") {
                    return;
                }
            }
            // Discard the whitespace suffix
            array_shift($tokens);
        }
        if ($prev) {
            // Return the whitespace prefix, if any
            return array_pop($nodes);
        }
    }
    /**
     * Check whether token is a whitespace token.
     *
     * True if token type is T_TEXT and value is all whitespace characters.
     *
     * @return bool True if token is a whitespace token
     */
    private function token_is_whitespace(array $token)
    {
        if ($token[Tokenizer::TYPE] === Tokenizer::T_TEXT) {
            return preg_match('/^\s*$/', $token[Tokenizer::VALUE]);
        }
        return false;
    }
    /**
     * Check whether a token is allowed inside a parent tag.
     *
     * @throws SyntaxException if an invalid token is found inside a parent tag
     *
     * @param array|null $parent
     */
    private function check_if_token_is_allowed_in_parent(?array $parent, array $token)
    {
        if ($parent !== null && $parent[Tokenizer::TYPE] === Tokenizer::T_PARENT) {
            throw new Syntax_Exception('Illegal content in < parent tag', $token);
        }
    }
    /**
     * Parse dynamic names.
     *
     * @throws SyntaxException when a tag does not allow *
     * @throws SyntaxException on multiple *s, or dots or filters with *
     */
    private function get_dynamic_name(array $token)
    {
        $name = $token[Tokenizer::NAME];
        $is_dynamic = false;
        if ($this->dynamic_names && preg_match('/^\s*\*\s*/', $name)) {
            $this->ensure_tag_allows_dynamic_names($token);
            $name = preg_replace('/^\s*\*\s*/', '', $name);
            $is_dynamic = true;
        }
        return [$name, $is_dynamic];
    }
    /**
     * Check whether the given token supports dynamic tag names.
     *
     * @throws SyntaxException when a tag does not allow *
     */
    private function ensure_tag_allows_dynamic_names(array $token)
    {
        switch ($token[Tokenizer::TYPE]) {
            case Tokenizer::T_PARTIAL:
            case Tokenizer::T_PARENT:
            case Tokenizer::T_END_SECTION:
                return;
        }
        $msg = sprintf('Invalid dynamic name: %s in %s tag', $token[Tokenizer::NAME], Tokenizer::get_tag_name($token[Tokenizer::TYPE]));
        throw new Syntax_Exception($msg, $token);
    }
    /**
     * Split a tag name into name and filters.
     *
     * @param string $name
     *
     * @return array [Tag name, Array of filters]
     */
    private function get_name_and_filters($name)
    {
        $filters = array_map('trim', explode('|', $name));
        $name = array_shift($filters);
        return [$name, $filters];
    }
    /**
     * Enable a pragma.
     *
     * @param string $name
     */
    private function enable_pragma($name)
    {
        $this->pragmas[$name] = true;
        switch ($name) {
            case Engine::PRAGMA_FILTERS:
                $this->pragma_filters = true;
                break;
        }
    }
}