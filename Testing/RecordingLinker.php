<?php

namespace Omnidocs\Testing;

use Omnidocs\LinkerInterface;

/**
 * A linker for tests: pages at "/<path>", files at "repo:<path>", pictures
 * at "img:<path>", and every request kept, so a test can check what a
 * source linked to.
 */
final class RecordingLinker implements LinkerInterface
{
    /** @var list<array{string, ?string}> */
    public array $pages = [];

    /** @var list<string> */
    public array $files = [];

    /** @var list<string> */
    public array $images = [];

    public function page(string $path, ?string $fragment = null): string
    {
        $this->pages[] = [$path, $fragment];

        return '/'.$path.(null !== $fragment ? '#'.$fragment : '');
    }

    public function file(string $path, ?string $fragment = null): ?string
    {
        $this->files[] = $path;

        return 'repo:'.$path.(null !== $fragment ? '#'.$fragment : '');
    }

    public function image(string $path): ?string
    {
        $this->images[] = $path;

        return 'img:'.$path;
    }
}
