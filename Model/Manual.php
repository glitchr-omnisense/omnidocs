<?php

namespace Omnidocs\Model;

/**
 * A manual: its key in the site's addresses, its title, the repository it
 * is read from ("glitchr-studio/flowjs"), its versions by name and the one
 * read by default - the current one; the others are older (or newer)
 * series a site shows with a notice.
 */
final class Manual
{
    /** @var array<string, Version> */
    public readonly array $versions;

    public readonly string $default;

    /**
     * @param iterable<Version> $versions
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        iterable $versions,
        ?string $default = null,
        public readonly ?string $repository = null,
    ) {
        $byName = [];
        foreach ($versions as $version) {
            $byName[$version->name] = $version;
        }
        if ([] === $byName) {
            throw new \InvalidArgumentException(\sprintf('The manual "%s" has no version.', $key));
        }
        $default ??= (string) array_key_first($byName);
        if (!isset($byName[$default])) {
            throw new \InvalidArgumentException(\sprintf('The manual "%s" has no version "%s" to read by default.', $key, $default));
        }
        $this->versions = $byName;
        $this->default = $default;
    }

    /** @return list<string> */
    public function series(): array
    {
        return array_keys($this->versions);
    }

    public function has(string $series): bool
    {
        return isset($this->versions[$series]);
    }

    public function version(?string $series = null): Version
    {
        return $this->versions[$series ?? $this->default] ?? throw new \OutOfBoundsException(\sprintf('The manual "%s" has no version "%s".', $this->key, $series));
    }

    public function isCurrent(string $series): bool
    {
        return $series === $this->default;
    }
}
