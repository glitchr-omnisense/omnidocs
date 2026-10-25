<?php

namespace Omnidocs;

/**
 * Where a site publishes what a page links to. A source turns every link it
 * reads into one of three: another page of the manual (page()), another file
 * of the repository (file(): the source tree on its forge, at the commit
 * read), a picture (image()). An address that is absolute, or only an
 * anchor, is left as it was written.
 */
interface LinkerInterface
{
    /** A page of the manual, by its path ("" the home), with its anchor. */
    public function page(string $path, ?string $fragment = null): string;

    /** A file of the repository that is not a page ("src/js/stops.js"); null leaves the link as written. */
    public function file(string $path, ?string $fragment = null): ?string;

    /** A picture, by its path in the repository or in the tool's output; null leaves it as written. */
    public function image(string $path): ?string;
}
