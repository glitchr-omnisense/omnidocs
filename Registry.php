<?php

namespace Omnidocs;

use Omnidocs\Exception\InvalidConfigException;

/**
 * The application's sources by name, each built once from its factory and
 * options:
 *
 *   $docs = new Registry([new MarkdownSourceFactory(), new JsdocSourceFactory()], [
 *       'manual' => ['factory' => 'markdown', 'options' => ['root' => __DIR__.'/..', 'docs' => 'docs']],
 *       'api' => ['factory' => 'jsdoc', 'options' => ['file' => __DIR__.'/var/jsdoc.json']],
 *   ]);
 *
 *   $docs->get('manual')->tree();
 */
final class Registry
{
    /** @var array<string, SourceFactoryInterface> */
    private array $factories = [];

    /** @var array<string, SourceInterface> */
    private array $sources = [];

    /**
     * @param iterable<SourceFactoryInterface>                                        $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config = [])
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): SourceInterface
    {
        $source = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" source; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->sources[$name] ??= $this->factory($source['factory'], $name)->create($source['options'] ?? []);
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /**
     * A source built afresh by a factory, from options given here.
     *
     * @param array<string, mixed> $options
     */
    public function create(string $factory, array $options = []): SourceInterface
    {
        return $this->factory($factory)->create($options);
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $factory, ?string $source = null): SourceFactoryInterface
    {
        return $this->factories[$factory] ?? throw new InvalidConfigException(\sprintf('No "%s" factory%s; installed: %s.', $factory, null !== $source ? ' for the "'.$source.'" source' : '', implode(', ', array_keys($this->factories)) ?: 'none'));
    }
}
