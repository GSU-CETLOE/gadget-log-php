<?php

declare(strict_types=1);

namespace Gadget\Log\Tests;

use Gadget\Log\LoggerProxy;
use Gadget\Log\LoggerProxyInterface;
use Gadget\Log\LoggerProxyTrait;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LoggerProxyTest extends TestCase
{
    private TestHandler $handler;
    private LoggerProxy $proxy;


    protected function setUp(): void
    {
        $this->handler = new TestHandler();
        $this->proxy = new LoggerProxy();
        $this->proxy->setLogger(new Logger('test', [$this->handler]));
    }


    public function testIsALoggerAndLoggerAware(): void
    {
        $proxy = new LoggerProxy();

        self::assertInstanceOf(LoggerProxyInterface::class, $proxy);
        self::assertInstanceOf(LoggerInterface::class, $proxy);
        self::assertInstanceOf(LoggerAwareInterface::class, $proxy);
    }


    public function testLoggingWithoutALoggerIsANoOp(): void
    {
        $proxy = new LoggerProxy();

        $proxy->log(LogLevel::INFO, 'nobody is listening');
        $proxy->error('still nobody');

        $this->expectNotToPerformAssertions();
    }


    public function testForwardsLevelMessageAndContextToTheLogger(): void
    {
        $this->proxy->log(LogLevel::WARNING, 'disk at {percent}%', ['percent' => 91]);

        $records = $this->handler->getRecords();
        self::assertCount(1, $records);
        self::assertSame(Level::Warning, $records[0]->level);
        self::assertSame('disk at {percent}%', $records[0]->message);
        self::assertSame(['percent' => 91], $records[0]->context);
    }


    public function testForwardsStringableMessages(): void
    {
        $message = new class implements \Stringable {
            public function __toString(): string
            {
                return 'from a Stringable';
            }
        };

        $this->proxy->info($message);

        self::assertTrue($this->handler->hasInfo('from a Stringable'));
    }


    /**
     * @return \Generator<string, array{\Closure(LoggerProxy): void, Level}>
     */
    public static function providesLevelHelpers(): \Generator
    {
        yield 'emergency' => [fn(LoggerProxy $proxy) => $proxy->emergency('hello'), Level::Emergency];
        yield 'alert' => [fn(LoggerProxy $proxy) => $proxy->alert('hello'), Level::Alert];
        yield 'critical' => [fn(LoggerProxy $proxy) => $proxy->critical('hello'), Level::Critical];
        yield 'error' => [fn(LoggerProxy $proxy) => $proxy->error('hello'), Level::Error];
        yield 'warning' => [fn(LoggerProxy $proxy) => $proxy->warning('hello'), Level::Warning];
        yield 'notice' => [fn(LoggerProxy $proxy) => $proxy->notice('hello'), Level::Notice];
        yield 'info' => [fn(LoggerProxy $proxy) => $proxy->info('hello'), Level::Info];
        yield 'debug' => [fn(LoggerProxy $proxy) => $proxy->debug('hello'), Level::Debug];
    }


    /**
     * @param \Closure(LoggerProxy): void $logHello
     */
    #[DataProvider('providesLevelHelpers')]
    public function testEachLevelHelperLogsAtItsLevel(\Closure $logHello, Level $expected): void
    {
        $logHello($this->proxy);

        self::assertTrue(
            $this->handler->hasRecord('hello', $expected),
            "The helper should log at {$expected->getName()}"
        );
    }


    public function testReplacingTheLoggerRedirectsLaterMessages(): void
    {
        $second = new TestHandler();
        $this->proxy->setLogger(new Logger('second', [$second]));

        $this->proxy->info('after swap');

        self::assertFalse($this->handler->hasInfoRecords());
        self::assertTrue($second->hasInfo('after swap'));
    }


    public function testTraitCanBeUsedOnItsOwn(): void
    {
        $handler = new TestHandler();
        $service = new class {
            use LoggerProxyTrait;
        };
        $service->setLogger(new Logger('own', [$handler]));

        $service->debug('from the trait');

        self::assertTrue($handler->hasDebug('from the trait'));
    }
}
