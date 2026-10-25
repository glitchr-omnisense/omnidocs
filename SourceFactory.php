<?php

namespace Omnidocs;

/**
 * What a provider's factory has in common: its defaults set under the
 * options given, the options it requires checked, then the source built.
 */
abstract class SourceFactory implements SourceFactoryInterface
{
    final public function create(array $options = []): SourceInterface
    {
        $config = new Config($options);
        $this->populate($config);
        $config->validateNotEmpty($config->list('omnidocs.required_options'), $this->getName());

        return $this->build($config);
    }

    /** The factory's defaults (Config::defaults) and 'omnidocs.required_options'. */
    abstract protected function populate(Config $config): void;

    abstract protected function build(Config $config): SourceInterface;
}
