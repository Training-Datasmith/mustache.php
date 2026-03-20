<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Mustache\Engine;

// --- Example 1: Render a simple template with variable interpolation ---
$mustache = new Engine();

$template = 'Hello, {{name}}! You have {{count}} new messages.';
echo $mustache->render($template, ['name' => 'Alice', 'count' => 5]);
echo "\n\n";
// Hello, Alice! You have 5 new messages.

// --- Example 2: Sections (conditionals and loops) ---
$list_template = '{{#items}}
- {{title}} by {{author}}
{{/items}}
{{^items}}
No items found.
{{/items}}';

echo $mustache->render($list_template, [
    'items' => [
        ['title' => 'Clean Code', 'author' => 'Robert C. Martin'],
        ['title' => 'The Pragmatic Programmer', 'author' => 'Hunt & Thomas'],
    ],
]);
echo "\n";

// --- Example 3: Partials (reusable sub-templates) ---
$engine_with_partials = new Engine([
    'partials' => [
        'header' => '<header><h1>{{site_name}}</h1></header>',
    ],
]);

echo $engine_with_partials->render(
    '{{> header}}<p>Welcome to {{site_name}}!</p>',
    ['site_name' => 'My App']
);
echo "\n\n";

// --- Example 4: Filesystem template loader ---
// $engine = new Engine([
//     'loader' => new \Mustache\Loader\Filesystem_Loader('/path/to/templates'),
//     'cache'  => new \Mustache\Cache\Filesystem_Cache('/path/to/cache'),
// ]);
// echo $engine->render('home', ['title' => 'Welcome']);

// --- Example 5: Pre-escaped HTML (triple mustache) ---
$html_data = ['content' => '<b>Bold</b> text'];
echo $mustache->render('Escaped: {{content}}', $html_data);
echo "\n";
echo $mustache->render('Unescaped: {{{content}}}', $html_data);
echo "\n";
