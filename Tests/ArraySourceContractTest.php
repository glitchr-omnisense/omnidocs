<?php

namespace Omnidocs\Tests;

use Omnidocs\Model\Symbol;
use Omnidocs\SourceInterface;
use Omnidocs\Testing\ArraySource;
use Omnidocs\Testing\SourceContractTests;
use PHPUnit\Framework\TestCase;

/** The conformance suite on the core's own source: the suite holds for a source that is right. */
final class ArraySourceContractTest extends TestCase
{
    use SourceContractTests;

    protected function source(): SourceInterface
    {
        return new ArraySource([
            '' => ['title' => 'Flow', 'html' => '<h2 id="use">Use</h2><p><a href="page:sticky#keys">keys</a></p>'],
            'sticky' => ['title' => 'Sticky', 'html' => '<h2 id="keys">Keys</h2>', 'source' => 'docs/sticky.md'],
        ], [new Symbol('function', 'StickyStops.attach', 'sticky', 'keys')]);
    }

    protected function documentsSymbols(): bool
    {
        return true;
    }
}
