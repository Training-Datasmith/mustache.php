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
class_alias(\Mustache\Cache::class, \Mustache_Cache::class);
class_alias(\Mustache\Cache\Abstract_Cache::class, \Mustache_cache_abstract_Cache::class);
class_alias(\Mustache\Cache\Filesystem_Cache::class, \Mustache_cache_filesystem_Cache::class);
class_alias(\Mustache\Cache\Noop_Cache::class, \Mustache_cache_noop_Cache::class);
class_alias(\Mustache\Compiler::class, \Mustache_Compiler::class);
class_alias(\Mustache\Context::class, \Mustache_Context::class);
class_alias(\Mustache\Engine::class, \Mustache_Engine::class);
class_alias(\Mustache\Exception::class, \Mustache_Exception::class);
class_alias(\Mustache\Exception\InvalidArgumentException::class, \Mustache_exception_invalid_Argument_Exception::class);
class_alias(\Mustache\Exception\LogicException::class, \Mustache_exception_logic_Exception::class);
class_alias(\Mustache\Exception\RuntimeException::class, \Mustache_exception_runtime_Exception::class);
class_alias(\Mustache\Exception\Syntax_Exception::class, \Mustache_exception_syntax_Exception::class);
class_alias(\Mustache\Exception\Unknown_Filter_Exception::class, \Mustache_exception_unknown_Filter_Exception::class);
class_alias(\Mustache\Exception\Unknown_Helper_Exception::class, \Mustache_exception_unknown_Helper_Exception::class);
class_alias(\Mustache\Exception\Unknown_Template_Exception::class, \Mustache_exception_unknown_Template_Exception::class);
class_alias(\Mustache\Helper_Collection::class, \Mustache_helper_Collection::class);
class_alias(\Mustache\Lambda_Helper::class, \Mustache_lambda_Helper::class);
class_alias(\Mustache\Loader::class, \Mustache_Loader::class);
class_alias(\Mustache\Loader\Array_Loader::class, \Mustache_loader_array_Loader::class);
class_alias(\Mustache\Loader\Cascading_Loader::class, \Mustache_loader_cascading_Loader::class);
class_alias(\Mustache\Loader\Filesystem_Loader::class, \Mustache_loader_filesystem_Loader::class);
class_alias(\Mustache\Loader\Inline_Loader::class, \Mustache_loader_inline_Loader::class);
class_alias(\Mustache\Loader\Mutable_Loader::class, \Mustache_loader_mutable_Loader::class);
class_alias(\Mustache\Loader\Production_Filesystem_Loader::class, \Mustache_loader_production_Filesystem_Loader::class);
class_alias(\Mustache\Loader\String_Loader::class, \Mustache_loader_string_Loader::class);
class_alias(\Mustache\Logger::class, \Mustache_Logger::class);
class_alias(\Mustache\Logger\Abstract_Logger::class, \Mustache_logger_abstract_Logger::class);
class_alias(\Mustache\Logger\Stream_Logger::class, \Mustache_logger_stream_Logger::class);
class_alias(\Mustache\Parser::class, \Mustache_Parser::class);
class_alias(\Mustache\Source::class, \Mustache_Source::class);
class_alias(\Mustache\Source\Filesystem_Source::class, \Mustache_source_filesystem_Source::class);
class_alias(\Mustache\Template::class, \Mustache_Template::class);
class_alias(\Mustache\Tokenizer::class, \Mustache_Tokenizer::class);
if (!class_exists(\Mustache_Engine::class)) {
    /** @deprecated use Mustache\Engine */
    class Mustache_Engine extends \Mustache\Engine
    {
    }
}
if (!interface_exists(\Mustache_Cache::class)) {
    /** @deprecated use Mustache\Cache */
    interface Mustache_Cache extends \Mustache\Cache
    {
    }
}
if (!class_exists(\Mustache_cache_abstract_Cache::class)) {
    /** @deprecated use Mustache\Cache\AbstractCache */
    abstract class Mustache_cache_abstract_Cache extends \Mustache\Cache\Abstract_Cache
    {
    }
}
if (!class_exists(\Mustache_cache_filesystem_Cache::class)) {
    /** @deprecated use Mustache\Cache\FilesystemCache */
    class Mustache_cache_filesystem_Cache extends \Mustache\Cache\Filesystem_Cache
    {
    }
}
if (!class_exists(\Mustache_cache_noop_Cache::class)) {
    /** @deprecated use Mustache\Cache\NoopCache */
    class Mustache_cache_noop_Cache extends \Mustache\Cache\Noop_Cache
    {
    }
}
if (!class_exists(\Mustache_Compiler::class)) {
    /** @deprecated use Mustache\Compiler */
    class Mustache_Compiler extends \Mustache\Compiler
    {
    }
}
if (!class_exists(\Mustache_Context::class)) {
    /** @deprecated use Mustache\Context */
    class Mustache_Context extends \Mustache\Context
    {
    }
}
if (!class_exists(\Mustache_Engine::class)) {
    /** @deprecated use Mustache\Engine */
    class Mustache_Engine extends \Mustache\Engine
    {
    }
}
if (!interface_exists(\Mustache_Exception::class)) {
    /** @deprecated use Mustache\Exception */
    interface Mustache_Exception extends \Mustache\Exception
    {
    }
}
if (!class_exists(\Mustache_exception_invalid_Argument_Exception::class)) {
    /** @deprecated use Mustache\Exception\InvalidArgumentException */
    class Mustache_exception_invalid_Argument_Exception extends \Mustache\Exception\InvalidArgumentException
    {
    }
}
if (!class_exists(\Mustache_exception_logic_Exception::class)) {
    /** @deprecated use Mustache\Exception\LogicException */
    class Mustache_exception_logic_Exception extends \Mustache\Exception\LogicException
    {
    }
}
if (!class_exists(\Mustache_exception_runtime_Exception::class)) {
    /** @deprecated use Mustache\Exception\RuntimeException */
    class Mustache_exception_runtime_Exception extends \Mustache\Exception\RuntimeException
    {
    }
}
if (!class_exists(\Mustache_exception_syntax_Exception::class)) {
    /** @deprecated use Mustache\Exception\SyntaxException */
    class Mustache_exception_syntax_Exception extends \Mustache\Exception\Syntax_Exception
    {
    }
}
if (!class_exists(\Mustache_exception_unknown_Filter_Exception::class)) {
    /** @deprecated use Mustache\Exception\UnknownFilterException */
    class Mustache_exception_unknown_Filter_Exception extends \Mustache\Exception\Unknown_Filter_Exception
    {
    }
}
if (!class_exists(\Mustache_exception_unknown_Helper_Exception::class)) {
    /** @deprecated use Mustache\Exception\UnknownHelperException */
    class Mustache_exception_unknown_Helper_Exception extends \Mustache\Exception\Unknown_Helper_Exception
    {
    }
}
if (!class_exists(\Mustache_exception_unknown_Template_Exception::class)) {
    /** @deprecated use Mustache\Exception\UnknownTemplateException */
    class Mustache_exception_unknown_Template_Exception extends \Mustache\Exception\Unknown_Template_Exception
    {
    }
}
if (!class_exists(\Mustache_helper_Collection::class)) {
    /** @deprecated use Mustache\HelperCollection */
    class Mustache_helper_Collection extends \Mustache\Helper_Collection
    {
    }
}
if (!class_exists(\Mustache_lambda_Helper::class)) {
    /** @deprecated use Mustache\LambdaHelper */
    class Mustache_lambda_Helper extends \Mustache\Lambda_Helper
    {
    }
}
if (!interface_exists(\Mustache_Loader::class)) {
    /** @deprecated use Mustache\Loader */
    interface Mustache_Loader extends \Mustache\Loader
    {
    }
}
if (!class_exists(\Mustache_loader_array_Loader::class)) {
    /** @deprecated use Mustache\Loader\ArrayLoader */
    class Mustache_loader_array_Loader extends \Mustache\Loader\Array_Loader
    {
    }
}
if (!class_exists(\Mustache_loader_cascading_Loader::class)) {
    /** @deprecated use Mustache\Loader\CascadingLoader */
    class Mustache_loader_cascading_Loader extends \Mustache\Loader\Cascading_Loader
    {
    }
}
if (!class_exists(\Mustache_loader_filesystem_Loader::class)) {
    /** @deprecated use Mustache\Loader\FilesystemLoader */
    class Mustache_loader_filesystem_Loader extends \Mustache\Loader\Filesystem_Loader
    {
    }
}
if (!class_exists(\Mustache_loader_inline_Loader::class)) {
    /** @deprecated use Mustache\Loader\InlineLoader */
    class Mustache_loader_inline_Loader extends \Mustache\Loader\Inline_Loader
    {
    }
}
if (!interface_exists(\Mustache_loader_mutable_Loader::class)) {
    /** @deprecated use Mustache\Loader\MutableLoader */
    interface Mustache_loader_mutable_Loader extends \Mustache\Loader\Mutable_Loader
    {
    }
}
if (!class_exists(\Mustache_loader_production_Filesystem_Loader::class)) {
    /** @deprecated use Mustache\Loader\ProductionFilesystemLoader */
    class Mustache_loader_production_Filesystem_Loader extends \Mustache\Loader\Production_Filesystem_Loader
    {
    }
}
if (!class_exists(\Mustache_loader_string_Loader::class)) {
    /** @deprecated use Mustache\Loader\StringLoader */
    class Mustache_loader_string_Loader extends \Mustache\Loader\String_Loader
    {
    }
}
if (!interface_exists(\Mustache_Logger::class)) {
    /** @deprecated use Mustache\Logger */
    interface Mustache_Logger extends \Mustache\Logger
    {
    }
}
if (!class_exists(\Mustache_logger_abstract_Logger::class)) {
    /** @deprecated use Mustache\Logger\AbstractLogger */
    abstract class Mustache_logger_abstract_Logger extends \Mustache\Logger\Abstract_Logger
    {
    }
}
if (!class_exists(\Mustache_logger_stream_Logger::class)) {
    /** @deprecated use Mustache\Logger\StreamLogger */
    class Mustache_logger_stream_Logger extends \Mustache\Logger\Stream_Logger
    {
    }
}
if (!class_exists(\Mustache_Parser::class)) {
    /** @deprecated use Mustache\Parser */
    class Mustache_Parser extends \Mustache\Parser
    {
    }
}
if (!interface_exists(\Mustache_Source::class)) {
    /** @deprecated use Mustache\Source */
    interface Mustache_Source extends \Mustache\Source
    {
    }
}
if (!class_exists(\Mustache_source_filesystem_Source::class)) {
    /** @deprecated use Mustache\Source\FilesystemSource */
    class Mustache_source_filesystem_Source extends \Mustache\Source\Filesystem_Source
    {
    }
}
if (!class_exists(\Mustache_Template::class)) {
    /** @deprecated use Mustache\Template */
    abstract class Mustache_Template extends \Mustache\Template
    {
    }
}
if (!class_exists(\Mustache_Tokenizer::class)) {
    /** @deprecated use Mustache\Tokenizer */
    class Mustache_Tokenizer extends \Mustache\Tokenizer
    {
    }
}