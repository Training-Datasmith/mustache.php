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
 * Mustache Compiler class.
 *
 * This class is responsible for turning a Mustache token parse tree into normal PHP source code.
 */
class Compiler
{
    private $pragmas;
    private $default_pragmas = [];
    private $sections;
    private $blocks;
    private $source;
    private $indent_next_line;
    private $custom_escape;
    private $entity_flags;
    private $charset;
    private $strict_callables;
    // Optional Mustache specs
    private $lambdas = true;
    /**
     * Compile a Mustache token parse tree into PHP source code.
     *
     * @throws InvalidArgumentException if the FILTERS pragma is set but lambdas are not enabled
     *
     * @param string $source          Mustache Template source code
     * @param array  $tree            Parse tree of Mustache tokens
     * @param string $name            Mustache Template class name
     * @param bool   $customEscape    (default: false)
     * @param string $charset         (default: 'UTF-8')
     * @param bool   $strictCallables (default: false)
     * @param int    $entityFlags     (default: ENT_COMPAT)
     *
     * @return string Generated PHP source code
     */
    public function compile($source, array $tree, $name, $custom_escape = false, $charset = 'UTF-8', $strict_callables = false, $entity_flags = ENT_COMPAT)
    {
        $this->pragmas = $this->default_pragmas;
        $this->sections = [];
        $this->blocks = [];
        $this->source = $source;
        $this->indent_next_line = true;
        $this->custom_escape = $custom_escape;
        $this->entity_flags = $entity_flags;
        $this->charset = $charset;
        $this->strict_callables = $strict_callables;
        $code = $this->write_code($tree, $name);
        if (isset($this->pragmas[Engine::PRAGMA_FILTERS]) && !$this->lambdas) {
            throw new InvalidArgumentException('The FILTERS pragma requires lambda support');
        }
        return $code;
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
        if (isset($options['lambdas'])) {
            $this->lambdas = $options['lambdas'] !== false;
        }
    }
    /**
     * Enable pragmas across all templates, regardless of the presence of pragma
     * tags in the individual templates.
     *
     * @internal Users should set global pragmas in \Mustache\Engine, not here :)
     *
     * @param string[] $pragmas
     */
    public function set_pragmas(array $pragmas)
    {
        $this->pragmas = [];
        foreach ($pragmas as $pragma) {
            $this->pragmas[$pragma] = true;
        }
        $this->default_pragmas = $this->pragmas;
    }
    /**
     * Helper function for walking the Mustache token parse tree.
     *
     * @throws SyntaxException upon encountering unknown token types
     *
     * @param array $tree  Parse tree of Mustache tokens
     * @param int   $level (default: 0)
     *
     * @return string Generated PHP source code
     */
    private function walk(array $tree, $level = 0)
    {
        $code = '';
        $level++;
        foreach ($tree as $node) {
            switch ($node[Tokenizer::TYPE]) {
                case Tokenizer::T_PRAGMA:
                    $this->pragmas[$node[Tokenizer::NAME]] = true;
                    break;
                case Tokenizer::T_SECTION:
                    $code .= $this->section($node[Tokenizer::NODES], $node[Tokenizer::NAME], isset($node[Tokenizer::FILTERS]) ? $node[Tokenizer::FILTERS] : [], $node[Tokenizer::INDEX], $node[Tokenizer::END], $node[Tokenizer::OTAG], $node[Tokenizer::CTAG], $level);
                    break;
                case Tokenizer::T_INVERTED:
                    $code .= $this->inverted_section($node[Tokenizer::NODES], $node[Tokenizer::NAME], isset($node[Tokenizer::FILTERS]) ? $node[Tokenizer::FILTERS] : [], $level);
                    break;
                case Tokenizer::T_PARTIAL:
                    $code .= $this->partial($node[Tokenizer::NAME], isset($node[Tokenizer::DYNAMIC]) ? $node[Tokenizer::DYNAMIC] : false, isset($node[Tokenizer::INDENT]) ? $node[Tokenizer::INDENT] : '', $level);
                    break;
                case Tokenizer::T_PARENT:
                    $code .= $this->parent($node[Tokenizer::NAME], isset($node[Tokenizer::DYNAMIC]) ? $node[Tokenizer::DYNAMIC] : false, $node[Tokenizer::NODES], $level);
                    break;
                case Tokenizer::T_BLOCK_ARG:
                    $code .= $this->block_arg($node[Tokenizer::NODES], $node[Tokenizer::NAME], $level);
                    break;
                case Tokenizer::T_BLOCK_VAR:
                    $code .= $this->block_var($node[Tokenizer::NODES], $node[Tokenizer::NAME], $level);
                    break;
                case Tokenizer::T_COMMENT:
                    break;
                case Tokenizer::T_ESCAPED:
                case Tokenizer::T_UNESCAPED:
                case Tokenizer::T_UNESCAPED_2:
                    $code .= $this->variable($node[Tokenizer::NAME], isset($node[Tokenizer::FILTERS]) ? $node[Tokenizer::FILTERS] : [], $node[Tokenizer::TYPE] === Tokenizer::T_ESCAPED, $level);
                    break;
                case Tokenizer::T_TEXT:
                    $code .= $this->text($node[Tokenizer::VALUE], $level);
                    break;
                default:
                    throw new Syntax_Exception(sprintf('Unknown token type: %s', $node[Tokenizer::TYPE]), $node);
            }
        }
        return $code;
    }
    public const KLASS = '<?php

        class %s extends \Mustache\Template
        {
            private $lambdaHelper;%s%s

            public function renderInternal(\Mustache\Context $context, $indent = \'\')
            {
                $this->lambdaHelper = new \Mustache\LambdaHelper($this->mustache, $context);
                $buffer = \'\';
        %s

                return $buffer;
            }
        %s
        %s
        }';
    public const KLASS_NO_LAMBDAS = '<?php

        class %s extends \Mustache\Template
        {%s%s
            public function renderInternal(\Mustache\Context $context, $indent = \'\')
            {
                $buffer = \'\';
        %s

                return $buffer;
            }
        }';
    public const STRICT_CALLABLE = 'protected $strictCallables = true;';
    public const NO_LAMBDAS = 'protected $lambdas = false;';
    /**
     * Generate Mustache Template class PHP source.
     *
     * @param array  $tree Parse tree of Mustache tokens
     * @param string $name Mustache Template class name
     *
     * @return string Generated PHP source code
     */
    private function write_code(array $tree, $name)
    {
        $code = $this->walk($tree);
        $sections = implode("\n", $this->sections);
        $blocks = implode("\n", $this->blocks);
        $klass = empty($this->sections) && empty($this->blocks) ? self::KLASS_NO_LAMBDAS : self::KLASS;
        $callable = $this->strict_callables ? $this->prepare(self::STRICT_CALLABLE) : '';
        $lambda = $this->lambdas ? '' : $this->prepare(self::NO_LAMBDAS);
        return sprintf($this->prepare($klass, 0, false, true), $name, $callable, $lambda, $code, $sections, $blocks);
    }
    public const BLOCK_VAR = '
        $blockFunction = $context->findInBlock(%s);
        if (is_callable($blockFunction)) {
            $buffer .= call_user_func($blockFunction, $context);
        %s}
    ';
    public const BLOCK_VAR_ELSE = '} else {%s';
    /**
     * Generate Mustache Template inheritance block variable PHP source.
     *
     * @param array  $nodes Array of child tokens
     * @param string $id    Section name
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function block_var(array $nodes, $id, $level)
    {
        $id = var_export($id, true);
        $else = $this->walk($nodes, $level);
        if ($else !== '') {
            $else = sprintf($this->prepare(self::BLOCK_VAR_ELSE, $level + 1, false, true), $else);
        }
        return sprintf($this->prepare(self::BLOCK_VAR, $level), $id, $else);
    }
    public const BLOCK_ARG = '%s => [$this, \'block%s\'],';
    /**
     * Generate Mustache Template inheritance block argument PHP source.
     *
     * @param array  $nodes Array of child tokens
     * @param string $id    Section name
     * @param int    $level
     *
     * @return string Generated PHP source code
     */
    private function block_arg(array $nodes, $id, $level)
    {
        $key = $this->block($nodes);
        $id = var_export($id, true);
        return sprintf($this->prepare(self::BLOCK_ARG, $level), $id, $key);
    }
    public const BLOCK_FUNCTION = '
        public function block%s($context)
        {
            $indent = $buffer = \'\';%s

            return $buffer;
        }
    ';
    /**
     * Generate Mustache Template inheritance block function PHP source.
     *
     * @param array $nodes Array of child tokens
     *
     * @return string key of new block function
     */
    private function block(array $nodes)
    {
        $code = $this->walk($nodes, 0);
        $key = ucfirst(md5($code));
        if (!isset($this->blocks[$key])) {
            $this->blocks[$key] = sprintf($this->prepare(self::BLOCK_FUNCTION, 0), $key, $code);
        }
        return $key;
    }
    public const SECTION_CALL = '
        $value = $context->%s(%s%s);%s
        $buffer .= $this->section%s($context, $indent, $value);
    ';
    public const SECTION = '
        private function section%s(\Mustache\Context $context, $indent, $value)
        {
            $buffer = \'\';

            if (%s) {
                $source = %s;
                $value = call_user_func($value, $source, %s);

                if ($value instanceof \Mustache\RenderedString) {
                    return $value->getValue();
                }

                if (is_string($value)) {
                    if (strpos($value, \'{{\') === false) {
                        return $value;
                    }

                    return $this->mustache
                        ->loadLambda($value%s)
                        ->renderInternal($context);
                }
            }

            if (!empty($value)) {
                $values = $this->isIterable($value) ? $value : [$value];
                foreach ($values as $value) {
                    $context->push($value);
                    %s
                    $context->pop();
                }
            }

            return $buffer;
        }
    ';
    public const SECTION_NO_LAMBDAS = '
        private function section%s(\Mustache\Context $context, $indent, $value)
        {
            $buffer = \'\';

            if (!empty($value)) {
                $values = $this->isIterable($value) ? $value : [$value];
                foreach ($values as $value) {
                    $context->push($value);
                    %s
                    $context->pop();
                }
            }

            return $buffer;
        }
    ';
    /**
     * Generate Mustache Template section PHP source.
     *
     * @param array    $nodes   Array of child tokens
     * @param string   $id      Section name
     * @param string[] $filters Array of filters
     * @param int      $start   Section start offset
     * @param int      $end     Section end offset
     * @param string   $otag    Current Mustache opening tag
     * @param string   $ctag    Current Mustache closing tag
     * @param int      $level
     *
     * @return string Generated section PHP source code
     */
    private function section(array $nodes, $id, $filters, $start, $end, $otag, $ctag, $level)
    {
        $source = var_export(substr($this->source, $start, $end - $start), true);
        $callable = $this->get_callable();
        if ($otag !== '{{' || $ctag !== '}}') {
            $delim_tag = var_export(sprintf('{{= %s %s =}}', $otag, $ctag), true);
            $helper = sprintf('$this->lambdaHelper->withDelimiters(%s)', $delim_tag);
            $delims = ', ' . $delim_tag;
        } else {
            $helper = '$this->lambdaHelper';
            $delims = '';
        }
        $key = ucfirst(md5($delims . "\n" . $source));
        if (!isset($this->sections[$key])) {
            if ($this->lambdas) {
                $this->sections[$key] = sprintf($this->prepare(self::SECTION), $key, $callable, $source, $helper, $delims, $this->walk($nodes, 2));
            } else {
                $this->sections[$key] = sprintf($this->prepare(self::SECTION_NO_LAMBDAS), $key, $this->walk($nodes, 2));
            }
        }
        $method = $this->get_find_method($id);
        $id = var_export($id, true);
        $find_arg = $this->get_find_method_args($method);
        $filters = $this->get_filters($filters, $level);
        return sprintf($this->prepare(self::SECTION_CALL, $level), $method, $id, $find_arg, $filters, $key);
    }
    public const INVERTED_SECTION = '
        $value = $context->%s(%s%s);%s
        if (empty($value)) {
            %s
        }
    ';
    /**
     * Generate Mustache Template inverted section PHP source.
     *
     * @param array    $nodes   Array of child tokens
     * @param string   $id      Section name
     * @param string[] $filters Array of filters
     * @param int      $level
     *
     * @return string Generated inverted section PHP source code
     */
    private function inverted_section(array $nodes, $id, $filters, $level)
    {
        $method = $this->get_find_method($id);
        $id = var_export($id, true);
        $find_arg = $this->get_find_method_args($method);
        $filters = $this->get_filters($filters, $level);
        return sprintf($this->prepare(self::INVERTED_SECTION, $level), $method, $id, $find_arg, $filters, $this->walk($nodes, $level));
    }
    public const DYNAMIC_NAME = '$this->resolveValue($context->%s(%s%s), $context)';
    /**
     * Generate Mustache Template dynamic name resolution PHP source.
     *
     * @param string $id      Tag name
     * @param bool   $dynamic True if the name is dynamic
     *
     * @return string Dynamic name resolution PHP source code
     */
    private function resolve_dynamic_name($id, $dynamic)
    {
        if (!$dynamic) {
            return var_export($id, true);
        }
        $method = $this->get_find_method($id);
        $id = $method !== 'last' ? var_export($id, true) : '';
        $find_arg = $this->get_find_method_args($method);
        // TODO: filters?
        return sprintf(self::DYNAMIC_NAME, $method, $id, $find_arg);
    }
    public const PARTIAL_INDENT = ', $indent . %s';
    public const PARTIAL = '
        if ($partial = $this->mustache->loadPartial(%s)) {
            $buffer .= $partial->renderInternal($context%s);
        }
    ';
    /**
     * Generate Mustache Template partial call PHP source.
     *
     * @param string $id      Partial name
     * @param bool   $dynamic Partial name is dynamic
     * @param string $indent  Whitespace indent to apply to partial
     * @param int    $level
     *
     * @return string Generated partial call PHP source code
     */
    private function partial($id, $dynamic, $indent, $level)
    {
        if ($indent !== '') {
            $indent_param = sprintf(self::PARTIAL_INDENT, var_export($indent, true));
        } else {
            $indent_param = '';
        }
        return sprintf($this->prepare(self::PARTIAL, $level), $this->resolve_dynamic_name($id, $dynamic), $indent_param);
    }
    public const PARENT = '
        if ($parent = $this->mustache->loadPartial(%s)) {
            $context->pushBlockContext([%s
            ]);
            $buffer .= $parent->renderInternal($context, $indent);
            $context->popBlockContext();
        }
    ';
    public const PARENT_NO_CONTEXT = '
        if ($parent = $this->mustache->loadPartial(%s)) {
            $buffer .= $parent->renderInternal($context, $indent);
        }
    ';
    /**
     * Generate Mustache Template inheritance parent call PHP source.
     *
     * @param string $id       Parent tag name
     * @param bool   $dynamic  Tag name is dynamic
     * @param array  $children Child nodes
     * @param int    $level
     * @return string Generated PHP source code
     */
    private function parent($id, $dynamic, array $children, $level)
    {
        $real_children = array_filter($children, [self::class, 'onlyBlockArgs']);
        $partial_name = $this->resolve_dynamic_name($id, $dynamic);
        if (empty($real_children)) {
            return sprintf($this->prepare(self::PARENT_NO_CONTEXT, $level), $partial_name);
        }
        return sprintf($this->prepare(self::PARENT, $level), $partial_name, $this->walk($real_children, $level + 1));
    }
    /**
     * Helper method for filtering out non-block-arg tokens.
     *
     * @return bool True if $node is a block arg token
     */
    private static function only_block_args(array $node)
    {
        return $node[Tokenizer::TYPE] === Tokenizer::T_BLOCK_ARG;
    }
    public const VARIABLE = '
        $value = $this->resolveValue($context->%s(%s%s), $context);%s
        $buffer .= %s($value === null ? \'\' : %s);
    ';
    /**
     * Generate Mustache Template variable interpolation PHP source.
     *
     * @param string   $id      Variable name
     * @param string[] $filters Array of filters
     * @param bool     $escape  Escape the variable value for output?
     * @param int      $level
     *
     * @return string Generated variable interpolation PHP source
     */
    private function variable($id, $filters, $escape, $level)
    {
        $method = $this->get_find_method($id);
        $id = $method !== 'last' ? var_export($id, true) : '';
        $find_arg = $this->get_find_method_args($method);
        $filters = $this->get_filters($filters, $level);
        $value = $escape ? $this->get_escape() : '$value';
        return sprintf($this->prepare(self::VARIABLE, $level), $method, $id, $find_arg, $filters, $this->flush_indent(), $value);
    }
    public const FILTER = '
        $filter = $context->%s(%s%s);
        if (!(%s)) {
            throw new \Mustache\Exception\UnknownFilterException(%s);
        }
        $value = call_user_func($filter, %s);%s
    ';
    public const FILTER_FIRST_VALUE = '$this->resolveValue($value, $context)';
    public const FILTER_VALUE = '$value';
    /**
     * Generate Mustache Template variable filtering PHP source.
     *
     * If the initial $value is a lambda it will be resolved before starting the filter chain.
     *
     * @param string[] $filters Array of filters
     * @param int      $level
     * @param bool     $first   (default: false)
     *
     * @return string Generated filter PHP source
     */
    private function get_filters(array $filters, $level, $first = true)
    {
        if (empty($filters)) {
            return '';
        }
        $name = array_shift($filters);
        $method = $this->get_find_method($name);
        $filter = $method !== 'last' ? var_export($name, true) : '';
        $find_arg = $this->get_find_method_args($method);
        $callable = $this->get_callable('$filter');
        $msg = var_export($name, true);
        $value = $first ? self::FILTER_FIRST_VALUE : self::FILTER_VALUE;
        return sprintf($this->prepare(self::FILTER, $level), $method, $filter, $find_arg, $callable, $msg, $value, $this->get_filters($filters, $level, false));
    }
    public const LINE = '$buffer .= "\n";';
    public const TEXT = '$buffer .= %s%s;';
    /**
     * Generate Mustache Template output Buffer call PHP source.
     *
     * @param string $text
     * @param int    $level
     *
     * @return string Generated output Buffer call PHP source
     */
    private function text($text, $level)
    {
        $indent_next_line = substr($text, -1) === "\n";
        $code = sprintf($this->prepare(self::TEXT, $level), $this->flush_indent(), var_export($text, true));
        $this->indent_next_line = $indent_next_line;
        return $code;
    }
    /**
     * Prepare PHP source code snippet for output.
     *
     * @param string $text
     * @param int    $bonus          Additional indent level (default: 0)
     * @param bool   $prependNewline Prepend a newline to the snippet? (default: true)
     * @param bool   $appendNewline  Append a newline to the snippet? (default: false)
     *
     * @return string PHP source code snippet
     */
    private function prepare($text, $bonus = 0, $prepend_newline = true, $append_newline = false)
    {
        $text = ($prepend_newline ? "\n" : '') . trim($text);
        if ($prepend_newline) {
            $bonus++;
        }
        if ($append_newline) {
            $text .= "\n";
        }
        return preg_replace("/\n( {8})?/", "\n" . str_repeat(' ', $bonus * 4), $text);
    }
    public const DEFAULT_ESCAPE = 'htmlspecialchars(%s, %s, %s)';
    public const CUSTOM_ESCAPE = 'call_user_func($this->mustache->getEscape(), %s)';
    /**
     * Get the current escaper.
     *
     * @param string $value (default: '$value')
     *
     * @return string Either a custom callback, or an inline call to `htmlspecialchars`
     */
    private function get_escape($value = '$value')
    {
        if ($this->custom_escape) {
            return sprintf(self::CUSTOM_ESCAPE, $value);
        }
        return sprintf(self::DEFAULT_ESCAPE, $value, var_export($this->entity_flags, true), var_export($this->charset, true));
    }
    /**
     * Select the appropriate Context `find` method for a given $id.
     *
     * The return value will be one of `find`, `findDot`, `findAnchoredDot` or `last`.
     *
     * @see \Mustache\Context::find
     * @see \Mustache\Context::findDot
     * @see \Mustache\Context::last
     *
     * @param string $id Variable name
     *
     * @return string `find` method name
     */
    private function get_find_method($id)
    {
        if ($id === '.') {
            return 'last';
        }
        if (isset($this->pragmas[Engine::PRAGMA_ANCHORED_DOT]) && $this->pragmas[Engine::PRAGMA_ANCHORED_DOT]) {
            if (substr($id, 0, 1) === '.') {
                return 'findAnchoredDot';
            }
        }
        if (strpos($id, '.') === false) {
            return 'find';
        }
        return 'findDot';
    }
    /**
     * Get the args needed for a given find method.
     *
     * In this case, it's "true" iff it's a "find dot" method and strict callables is enabled.
     *
     * @param string $method Find method name
     */
    private function get_find_method_args($method)
    {
        if (($method === 'findDot' || $method === 'findAnchoredDot') && $this->strict_callables) {
            return ', true';
        }
        return '';
    }
    public const IS_CALLABLE = '!is_string(%s) && is_callable(%s)';
    public const STRICT_IS_CALLABLE = 'is_object(%s) && is_callable(%s)';
    /**
     * Helper function to compile strict vs lax "is callable" logic.
     *
     * @param string $variable (default: '$value')
     *
     * @return string "is callable" logic
     */
    private function get_callable($variable = '$value')
    {
        $tpl = $this->strict_callables ? self::STRICT_IS_CALLABLE : self::IS_CALLABLE;
        return sprintf($tpl, $variable, $variable);
    }
    public const LINE_INDENT = '$indent . ';
    /**
     * Get the current $indent prefix to write to the buffer.
     *
     * @return string "$indent . " or ""
     */
    private function flush_indent()
    {
        if (!$this->indent_next_line) {
            return '';
        }
        $this->indent_next_line = false;
        return self::LINE_INDENT;
    }
}