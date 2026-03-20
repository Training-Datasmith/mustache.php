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

/**
 * Abstract Mustache Template class.
 *
 * @abstract
 */
abstract class Template
{
    /**
     * @var Engine
     */
    protected $mustache;
    /**
     * @var bool
     */
    protected $strict_callables = false;
    /**
     * @var bool
     */
    protected $lambdas = true;
    /**
     * Mustache Template constructor.
     */
    public function __construct(Engine $mustache)
    {
        $this->mustache = $mustache;
    }
    /**
     * Mustache Template instances can be treated as a function and rendered by simply calling them.
     *
     *     $m = new \Mustache\Engine;
     *     $tpl = $m->loadTemplate('Hello, {{ name }}!');
     *     echo $tpl(['name' => 'World']); // "Hello, World!"
     *
     * @see \Mustache\Template::render
     *
     * @param mixed $context Array or object rendering context (default: [])
     *
     * @return string Rendered template
     */
    public function __invoke($context = [])
    {
        return $this->render($context);
    }
    /**
     * Render this template given the rendering context.
     *
     * @param mixed $context Array or object rendering context (default: [])
     *
     * @return string Rendered template
     */
    public function render($context = [])
    {
        return $this->render_internal($this->prepare_context_stack($context));
    }
    /**
     * Internal rendering method implemented by Mustache Template concrete subclasses.
     *
     * This is where the magic happens :)
     *
     * NOTE: This method is not part of the Mustache.php public API.
     *
     * @param string $indent (default: '')
     *
     * @return string Rendered template
     */
    abstract public function render_internal(Context $context, $indent = '');
    /**
     * Tests whether a value should be iterated over (e.g. in a section context).
     *
     * In most languages there are two distinct array types: list and hash (or whatever you want to call them). Lists
     * should be iterated, hashes should be treated as objects. Mustache follows this paradigm for Ruby, Javascript,
     * Java, Python, etc.
     *
     * PHP, however, treats lists and hashes as one primitive type: array. So Mustache.php needs a way to distinguish
     * between between a list of things (numeric, normalized array) and a set of variables to be used as section context
     * (associative array). In other words, this will be iterated over:
     *
     *     $items = [
     *         ['name' => 'foo'],
     *         ['name' => 'bar'],
     *         ['name' => 'baz'],
     *     ];
     *
     * ... but this will be used as a section context block:
     *
     *     $items = [
     *         1        => ['name' => 'foo'],
     *         'banana' => ['name' => 'bar'],
     *         42       => ['name' => 'baz'],
     *     ];
     *
     * @param mixed $value
     *
     * @return bool True if the value is 'iterable'
     */
    protected function is_iterable($value)
    {
        switch (gettype($value)) {
            case 'object':
                return $value instanceof \Traversable;
            case 'array':
                $i = 0;
                foreach ($value as $k => $v) {
                    if ($k !== $i++) {
                        return false;
                    }
                }
                return true;
            default:
                return false;
        }
    }
    /**
     * Helper method to prepare the Context stack.
     *
     * Adds the Mustache HelperCollection to the stack's top context frame if helpers are present.
     *
     * @param mixed $context Optional first context frame (default: null)
     *
     * @return Context
     */
    protected function prepare_context_stack($context = null)
    {
        $stack = new Context(null, $this->mustache->get_buggy_property_shadowing());
        $helpers = $this->mustache->get_helpers();
        if (!$helpers->is_empty()) {
            $stack->push($helpers);
        }
        if (!empty($context)) {
            $stack->push($context);
        }
        return $stack;
    }
    /**
     * Resolve a context value.
     *
     * Invoke the value if it is callable, otherwise return the value.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function resolve_value($value, Context $context)
    {
        if (!$this->lambdas) {
            return $value;
        }
        if (($this->strict_callables ? is_object($value) : !is_string($value)) && is_callable($value)) {
            $result = call_user_func($value);
            if (is_string($result)) {
                return $this->mustache->load_lambda($result)->render_internal($context);
            }
            return $result;
        }
        return $value;
    }
}