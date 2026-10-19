<?php

declare(strict_types=1);

namespace Gadget\Log\Tests\Monolog\Processor;

use Gadget\Log\Monolog\Processor\PidProcessor;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use PHPUnit\Framework\TestCase;

final class PidProcessorTest extends TestCase
{
    private static function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'hello');
    }


    public function testIsAMonologProcessor(): void
    {
        self::assertInstanceOf(ProcessorInterface::class, new PidProcessor());
        self::assertCount(1, (new \ReflectionClass(PidProcessor::class))->getAttributes(AsMonologProcessor::class));
    }


    public function testAddsTheCurrentProcessIdZeroPaddedToEightDigits(): void
    {
        $pid = getmypid();
        self::assertIsInt($pid);

        $record = (new PidProcessor())(self::record());

        self::assertIsString($record->extra['pid']);
        self::assertSame(8, strlen($record->extra['pid']));
        self::assertMatchesRegularExpression('/^\d{8}$/', $record->extra['pid']);
        self::assertSame($pid, (int) $record->extra['pid']);
    }
}
