<?php

namespace Omnidocs\Support;

/** Paths inside a repository or a tool's output, written with "/". */
final class Path
{
    /** "docs/../src/./Foo.php" → "src/Foo.php"; null when it climbs out of its root. */
    public static function normalise(string $path): ?string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }
            if ('..' === $part) {
                if ([] === $parts) {
                    return null;
                }
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    /** $relative read from the folder of $file ("docs/a.md" + "../README.md" → "README.md"); null outside the root. */
    public static function resolve(string $file, string $relative): ?string
    {
        $base = \dirname($file);

        return self::normalise(('.' === $base || '' === $base ? '' : $base.'/').$relative);
    }

    /** Whether a link is absolute (a scheme, "//host", "/path") or only an anchor: left as written. */
    public static function isAbsolute(string $url): bool
    {
        return '' === $url || '#' === $url[0] || '/' === $url[0] || 1 === preg_match('#^[a-z][a-z0-9+.-]*:#i', $url);
    }

    /** ["guides/install.md", "intro"] from "guides/install.md#intro"; the query left out. */
    public static function split(string $url): array
    {
        [$target, $fragment] = explode('#', $url, 2) + [1 => null];
        $target = explode('?', $target, 2)[0];

        return [rawurldecode($target), null !== $fragment && '' !== $fragment ? $fragment : null];
    }

    /** "10-getting-started" → [10, "getting-started"]; [100, $name] without a number. */
    public static function order(string $name, int $default = 100): array
    {
        return 1 === preg_match('/^(\d+)[-_ .](.+)$/', $name, $m) ? [(int) $m[1], $m[2]] : [$default, $name];
    }

    /** "Getting Started!" → "getting-started": lower case ASCII letters, digits and dashes. */
    public static function slug(string $name): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'page';
    }

    /** "getting-started" → "Getting started". */
    public static function humanise(string $slug): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $slug));
    }
}
