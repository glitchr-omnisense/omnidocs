<?php

namespace Omnidocs\Tests;

use Omnidocs\Registry;
use PHPUnit\Framework\TestCase;

/**
 * omniscient/omnidocs with no framework: every class of the package loaded
 * in a PHP process of its own, and no class of a framework, an HTTP client
 * or an application among what that loaded; then the harness's bare script
 * (docker/harness/bin/bare) builds a manual, reads its pages in order and
 * writes its search index with Composer's autoloader alone.
 */
final class BareTest extends TestCase
{
    private const FOREIGN = '~^(?:Symfony\\\\(?!Contracts\\\\|Polyfill\\\\)|(?:Illuminate|Laravel|Doctrine|Twig|GuzzleHttp|Omnibridge|Base|App)\\\\)~';

    public function testEveryClassLoadsWithoutAFramework(): void
    {
        $report = self::launch(__DIR__.'/Fixtures/load-all.php', [\dirname(__DIR__)]);

        self::assertIsArray($report['loaded']);
        self::assertIsArray($report['symbols']);
        self::assertContains(Registry::class, $report['loaded']);
        self::assertSame([], array_values(preg_grep(self::FOREIGN, array_filter($report['symbols'], 'is_string')) ?: []), 'no class of a framework, an HTTP client or an application');
    }

    public function testAManualInPlainPhpLoadsNothingButOmnidocs(): void
    {
        $report = self::launch(__DIR__.'/../docker/harness/bin/bare', []);

        self::assertSame(['', 'install', 'api'], $report['pages']);
        self::assertSame('install', $report['next']);
        self::assertSame(['page', 'heading', 'page', 'heading', 'page', 'heading', 'symbol'], $report['kinds']);
        self::assertIsArray($report['loaded']);
        self::assertSame([], array_values((array) preg_grep('~^(?!Omnidocs\\\\)~', $report['loaded'])), 'nothing but omnidocs');
    }

    /**
     * @param list<string> $arguments
     *
     * @return array<string, mixed>
     */
    private static function launch(string $script, array $arguments): array
    {
        $autoload = null;
        foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $vendor => $loader) {
            if ($loader->findFile(Registry::class)) {
                $autoload = $vendor.'/autoload.php';
                break;
            }
        }
        self::assertNotNull($autoload, 'an autoloader that knows omnidocs');
        $process = proc_open([\PHP_BINARY, $script, $autoload, ...$arguments], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['OMNIDOCS_AUTOLOAD' => $autoload] + getenv());
        self::assertIsResource($process);
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        self::assertSame(0, proc_close($process), $err.$out);
        $report = json_decode($out, true);
        self::assertIsArray($report, $err.$out);

        /** @var array<string, mixed> $report */
        return $report;
    }
}
