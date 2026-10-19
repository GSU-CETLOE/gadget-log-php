<?php

declare(strict_types=1);

namespace Gadget\Log\Tests\Monolog\Processor;

use Gadget\Log\Monolog\Processor\ConsoleCommandProcessor;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use PHPUnit\Framework\TestCase;

final class ConsoleCommandProcessorTest extends TestCase
{
    private static function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'hello');
    }


    public function testIsAMonologProcessor(): void
    {
        self::assertInstanceOf(ProcessorInterface::class, new ConsoleCommandProcessor());
        self::assertCount(
            1,
            (new \ReflectionClass(ConsoleCommandProcessor::class))->getAttributes(AsMonologProcessor::class)
        );
    }


    public function testCommandNameIsUnsetByDefault(): void
    {
        self::assertNull((new ConsoleCommandProcessor())->commandName);
    }


    public function testAddsAnEmptyCommandWhenNoneIsSet(): void
    {
        $record = (new ConsoleCommandProcessor())(self::record());

        self::assertSame('', $record->extra['command']);
    }


    public function testAddsTheCommandNameToExtra(): void
    {
        $processor = new ConsoleCommandProcessor();
        $processor->commandName = 'app:import';

        $record = $processor(self::record());

        self::assertSame('app:import', $record->extra['command']);
    }
}
