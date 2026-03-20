<?php

namespace Mustache\Test\Loader;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\InlineLoader;
use Mustache\Test\TestCase;

class InlineLoaderTest extends TestCase
{
    public function testLoadTemplates()
    {
        $loader = new InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $this->assertSame('{{ foo }}', $loader->load('foo'));
        $this->assertSame('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    public function testMissingTemplatesThrowExceptions()
    {
        $this->expectException(UnknownTemplateException::class);
        $loader = new InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $loader->load('not_a_real_template');
    }

    public function testInvalidOffsetThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new InlineLoader(__FILE__, 'notanumber');
    }

    public function testInvalidFileThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        new InlineLoader('notarealfile', __COMPILER_HALT_OFFSET__);
    }
}

__halt_compiler();

@@ foo
{{ foo }}

@@ bar
{{#bar}}BAR{{/bar}}
