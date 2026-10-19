<?php

declare(strict_types=1);

namespace Gadget\Log\Tests\DependencyInjection;

use Gadget\Log\DependencyInjection\GadgetLogExtension;
use Gadget\Log\LoggerProxy;
use Gadget\Log\LoggerProxyInterface;
use Gadget\Log\Monolog\Formatter\ConsoleLogFormatter;
use Gadget\Log\Monolog\Processor\ConsoleCommandProcessor;
use Gadget\Log\Monolog\Processor\ElapsedTimeProcessor;
use Gadget\Log\Monolog\Processor\PidProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class GadgetLogExtensionTest extends TestCase
{
    private static function loadedContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new GadgetLogExtension())->load([], $container);
        return $container;
    }


    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function providesServiceClasses(): \Generator
    {
        yield LoggerProxy::class => [LoggerProxy::class];
        yield ConsoleLogFormatter::class => [ConsoleLogFormatter::class];
        yield ConsoleCommandProcessor::class => [ConsoleCommandProcessor::class];
        yield ElapsedTimeProcessor::class => [ElapsedTimeProcessor::class];
        yield PidProcessor::class => [PidProcessor::class];
    }


    /**
     * @param class-string $class
     */
    #[DataProvider('providesServiceClasses')]
    public function testRegistersEverySourceClassAsAnAutowiredService(string $class): void
    {
        $container = self::loadedContainer();

        self::assertTrue($container->hasDefinition($class), "$class should be registered");
        $definition = $container->getDefinition($class);
        self::assertTrue($definition->isAutowired(), "$class should be autowired");
        self::assertTrue($definition->isAutoconfigured(), "$class should be autoconfigured");
    }


    public function testAliasesTheProxyInterfaceToItsOnlyImplementation(): void
    {
        $container = self::loadedContainer();

        self::assertTrue($container->hasAlias(LoggerProxyInterface::class));
        self::assertSame(LoggerProxy::class, (string) $container->getAlias(LoggerProxyInterface::class));
    }


    public function testCompiledContainerBuildsEveryService(): void
    {
        $container = self::loadedContainer();
        foreach (self::providesServiceClasses() as [$class]) {
            $container->getDefinition($class)->setPublic(true);
        }

        $container->compile();

        foreach (self::providesServiceClasses() as [$class]) {
            self::assertInstanceOf($class, $container->get($class));
        }
    }
}
