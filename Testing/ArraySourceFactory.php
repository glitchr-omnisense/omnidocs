<?php

namespace Omnidocs\Testing;

use Omnidocs\Config;
use Omnidocs\SourceFactory;
use Omnidocs\SourceInterface;

/** The "array" factory: an ArraySource from its 'pages' option, for an application's tests. */
final class ArraySourceFactory extends SourceFactory
{
    public function getName(): string
    {
        return 'array';
    }

    protected function populate(Config $config): void
    {
        $config->defaults(['pages' => [], 'symbols' => [], 'omnidocs.required_options' => ['pages']]);
    }

    protected function build(Config $config): SourceInterface
    {
        /** @var array<string, array{title: string, html: string, source?: string}> $pages */
        $pages = (array) $config->get('pages');
        /** @var list<\Omnidocs\Model\Symbol> $symbols */
        $symbols = array_values((array) $config->get('symbols'));

        return new ArraySource($pages, $symbols);
    }
}
