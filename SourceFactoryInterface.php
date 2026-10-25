<?php

namespace Omnidocs;

/** Builds a source from its options (the folder it reads, the repository it comes from...). */
interface SourceFactoryInterface
{
    /** The name sources are configured with: "markdown", "sphinx", "doxygen", "jsdoc". */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): SourceInterface;
}
