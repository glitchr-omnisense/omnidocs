<?php

namespace Omnidocs\Linking;

use Omnidocs\LinkerInterface;

/**
 * The links of a manual read from a GitHub repository at a commit: a page
 * goes where the site's closure says, another file of the repository to
 * github.com at that commit (never a moving branch), a picture to
 * raw.githubusercontent.com at that commit - or wherever the closures given
 * say.
 *
 *   new RepositoryLinker(
 *       fn (string $path, ?string $fragment) => '/docs/'.$path.($fragment ? '#'.$fragment : ''),
 *       'glitchr-studio/flowjs', 'c417356...',
 *   );
 */
final class RepositoryLinker implements LinkerInterface
{
    /** @var \Closure(string, ?string): string */
    private readonly \Closure $page;

    /** @var (\Closure(string): ?string)|null */
    private readonly ?\Closure $image;

    /**
     * @param callable(string $path, ?string $fragment): string $page
     * @param (callable(string $path): ?string)|null             $image a picture's address; raw.githubusercontent.com by default
     */
    public function __construct(
        callable $page,
        private readonly ?string $repository = null,
        private readonly ?string $ref = null,
        ?callable $image = null,
        private readonly string $forge = 'https://github.com',
        private readonly string $raw = 'https://raw.githubusercontent.com',
    ) {
        $this->page = $page(...);
        $this->image = null !== $image ? $image(...) : null;
    }

    public function page(string $path, ?string $fragment = null): string
    {
        return ($this->page)($path, $fragment);
    }

    public function file(string $path, ?string $fragment = null): ?string
    {
        if (null === $this->repository || null === $this->ref) {
            return null;
        }

        return rtrim($this->forge, '/').'/'.$this->repository.'/blob/'.$this->ref.'/'.self::encode($path).(null !== $fragment ? '#'.$fragment : '');
    }

    public function image(string $path): ?string
    {
        if (null !== $this->image) {
            return ($this->image)($path);
        }
        if (null === $this->repository || null === $this->ref) {
            return null;
        }

        return rtrim($this->raw, '/').'/'.$this->repository.'/'.$this->ref.'/'.self::encode($path);
    }

    /** Where a file is edited on the forge, at a branch: "edit this page". */
    public function edit(string $path, string $branch): ?string
    {
        return null !== $this->repository ? rtrim($this->forge, '/').'/'.$this->repository.'/edit/'.$branch.'/'.self::encode($path) : null;
    }

    private static function encode(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
