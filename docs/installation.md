# Installation and a manual in plain PHP

```sh
composer require omnisense/omnidocs:1.x-dev omnidocs/markdown:1.x-dev
```

The core needs PHP 8.2 and nothing else; each provider requires what its format needs
(league/commonmark for Markdown, nikic/php-parser for PHP sources, ext-dom for Doxygen).

```php
require __DIR__.'/vendor/autoload.php';

use Omnidocs\Linking\RepositoryLinker;
use Omnidocs\Markdown\MarkdownSourceFactory;
use Omnidocs\Php\PhpSourceFactory;
use Omnidocs\Model\Version;
use Omnidocs\Registry;

// No container: the registry is built by hand, its sources by name.
$docs = new Registry([new MarkdownSourceFactory(), new PhpSourceFactory()], [
    'manual' => ['factory' => 'markdown', 'options' => ['root' => __DIR__, 'docs' => 'docs', 'home' => 'README.md']],
    'api' => ['factory' => 'php', 'options' => ['root' => __DIR__]],
]);

$version = new Version('1.x', $docs->get('manual'), '1.x', 'abc1234');
$links = new RepositoryLinker(fn (string $path, ?string $anchor) => '/docs/'.$path.($anchor ? '#'.$anchor : ''), 'acme/widget', 'abc1234');

foreach ($version->pages() as $node) {
    $page = $version->page($node->path, $links);
    echo $page->title, ': ', count($page->headings()), " headings\n";
}
```

A page's links are the linker's: `page()` for another page of the manual, `file()` for another file of
the repository (`RepositoryLinker`: github.com at the commit read, never a moving branch), `image()`
for a picture. A site gives its own addresses there.

In Symfony, register the factories and the registry as services (autowiring takes them as they are)
and Twig's namespace `Omnidocs` for `vendor/omnisense/omnidocs/Bridge/Twig/templates`.
