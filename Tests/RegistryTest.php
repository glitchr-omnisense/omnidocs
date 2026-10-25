<?php

namespace Omnidocs\Tests;

use Omnidocs\Config;
use Omnidocs\Exception\InvalidConfigException;
use Omnidocs\Registry;
use Omnidocs\Testing\ArraySource;
use Omnidocs\Testing\ArraySourceFactory;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testASourceIsBuiltOnceByName(): void
    {
        $registry = new Registry([new ArraySourceFactory()], ['manual' => ['factory' => 'array', 'options' => ['pages' => ['' => ['title' => 'Home', 'html' => '']]]]]);

        self::assertInstanceOf(ArraySource::class, $registry->get('manual'));
        self::assertSame($registry->get('manual'), $registry->get('manual'));
        self::assertTrue($registry->has('manual'));
        self::assertSame(['manual'], $registry->names());
        self::assertSame(['array'], $registry->factories());
    }

    public function testAnUnknownSourceOrFactoryIsSaid(): void
    {
        $registry = new Registry([new ArraySourceFactory()], ['api' => ['factory' => 'jsdoc']]);
        try {
            $registry->get('manual');
            self::fail();
        } catch (InvalidConfigException $e) {
            self::assertSame('No "manual" source; configured: api.', $e->getMessage());
        }
        $this->expectExceptionMessage('No "jsdoc" factory for the "api" source; installed: array.');
        $registry->get('api');
    }

    public function testARequiredOptionMissingIsSaid(): void
    {
        $this->expectExceptionMessage('The "array" source needs: pages.');
        (new Registry([new ArraySourceFactory()]))->create('array', ['pages' => []]);
    }

    public function testOptionsAsEnvironmentVariablesGiveThem(): void
    {
        $config = new Config(['on' => 'yes', 'list' => 'a, b,,c', 'labels' => ['note' => 'Remarque', 'x' => []]]);

        self::assertTrue($config->bool('on'));
        self::assertSame(['a', 'b', 'c'], $config->list('list'));
        self::assertSame(['note' => 'Remarque'], $config->map('labels'));
        self::assertNull($config->string('none'));
        $this->expectException(InvalidConfigException::class);
        $config->directory('none', 'markdown');
    }
}
