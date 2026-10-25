<?php

namespace Omnidocs;

use Omnidocs\Exception\InvalidConfigException;

/**
 * A source's options while its factory builds it: the options given, the
 * factory's defaults under them, the keys that must be filled. Options come
 * as configuration and environment variables give them: strings too.
 *
 * @extends \ArrayObject<string, mixed>
 */
final class Config extends \ArrayObject
{
    /** @param array<string, mixed> $defaults */
    public function defaults(array $defaults): self
    {
        foreach ($defaults as $key => $value) {
            if (!$this->offsetExists($key)) {
                $this[$key] = $value;
            }
        }

        return $this;
    }

    /** @param list<string> $keys */
    public function validateNotEmpty(array $keys, string $source): void
    {
        $missing = array_values(array_filter($keys, fn (string $key): bool => !isset($this[$key]) || '' === $this[$key] || [] === $this[$key]));
        if ([] !== $missing) {
            throw new InvalidConfigException(\sprintf('The "%s" source needs: %s.', $source, implode(', ', $missing)));
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->offsetExists($key) ? $this[$key] : $default;
    }

    /** A string option; null when it is empty. */
    public function string(string $key): ?string
    {
        $value = $this->get($key);

        return \is_scalar($value) && '' !== (string) $value ? (string) $value : null;
    }

    /** A boolean option, as an environment variable gives it too: "1", "true", "yes", "on". */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if (null === $value || '' === $value) {
            return $default;
        }

        return \is_bool($value) ? $value : \in_array(strtolower(trim(\is_scalar($value) ? (string) $value : '')), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * A list; one given as an environment variable is a comma-separated string.
     *
     * @return list<string>
     */
    public function list(string $key): array
    {
        $list = [];
        $value = $this->get($key, []);
        foreach (\is_array($value) ? $value : [$value] as $item) {
            foreach (explode(',', \is_scalar($item) ? (string) $item : '') as $one) {
                if ('' !== trim($one)) {
                    $list[] = trim($one);
                }
            }
        }

        return $list;
    }

    /**
     * A map of strings ("note" => "Remarque").
     *
     * @return array<string, string>
     */
    public function map(string $key): array
    {
        $map = [];
        $value = $this->get($key, []);
        foreach (\is_array($value) ? $value : [] as $name => $item) {
            if (\is_scalar($item)) {
                $map[(string) $name] = (string) $item;
            }
        }

        return $map;
    }

    /** A folder that must exist, without its trailing slash. */
    public function directory(string $key, string $source): string
    {
        $dir = $this->string($key);
        if (null === $dir || !is_dir($dir)) {
            throw new InvalidConfigException(\sprintf('The "%s" source reads a folder that is not there: %s = %s.', $source, $key, $dir ?? '(none)'));
        }

        return rtrim($dir, '/');
    }
}
