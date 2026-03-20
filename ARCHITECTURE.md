# Architecture: mustache.php

## Purpose

A complete Mustache template engine implementation for PHP. Mustache is a logic-less templating language: templates contain only variable interpolations, sections, partials, and comments — no embedded PHP code.

## Directory Structure

```
src/
  Engine.php              — Main entry point; configures and orchestrates all components
  Tokenizer.php           — Lexer: converts template string to a flat token stream
  Parser.php              — Parser: converts token stream to an AST
  Compiler.php            — Code generator: converts AST to PHP class source code
  Context.php             — Variable resolution: walks a data stack to find values
  Template.php            — Base class for compiled template objects
  Rendered_String.php     — Value object wrapping pre-rendered (unescaped) output
  Helper_Collection.php   — Registry of callable template helpers
  Lambda_Helper.php       — Wraps lambdas for section rendering
  Cache/
    Abstract_Cache.php    — Base cache implementation
    Filesystem_Cache.php  — Writes compiled templates to .php files on disk
    Noop_Cache.php        — No-op cache (recompiles every render)
  Loader/
    Array_Loader.php      — Loads templates from a PHP array
    Filesystem_Loader.php — Loads templates from .mustache files on disk
    String_Loader.php     — Treats the template name as the template itself
    Cascading_Loader.php  — Tries multiple loaders in order
    Inline_Loader.php     — Loads templates from inline DATA sections
    ...
  Logger/
    Abstract_Logger.php   — PSR-3-compatible base logger
    Stream_Logger.php     — Writes log messages to a stream (e.g., STDERR)
  Exception/
    Syntax_Exception.php
    Unknown_Template_Exception.php
    ...
```

## Key Design Decisions

- **Compile-to-PHP**: Templates are compiled to native PHP classes and cached on disk; subsequent renders skip tokenizing and parsing
- **Logic-less enforcement**: The compiler generates only variable lookups, loops, and partial includes — no `eval` of user expressions
- **PSR-3 logging**: `Engine` accepts a `LoggerInterface` for debug/info logging of template loading and rendering
- **Pragmas**: Optional behaviors (FILTERS, ANCHORED_DOT) can be enabled per-template via `{{% PRAGMA }}` tags
- **Strict callables**: With `strict_callables: true` (default), non-callable values are not invoked as lambdas

## Extension Points

- Implement `Loader` to add a new template source (database, Redis, etc.)
- Implement `Cache` to add a new caching backend
- Pass custom helpers via `Engine::__construct(['helpers' => [...]])`

## Render Pipeline

```
$engine->render($templateName, $data)
  → Loader::load($templateName)        — fetch template source
  → Tokenizer::scan($source)           — lex to tokens
  → Parser::parse($tokens)             — build AST
  → Compiler::compile($tree)           — generate PHP class source
  → Cache::cache($className, $source)  — write to disk (optional)
  → new CompiledTemplate($engine)
  → Template::render(Context::create($data))
```
