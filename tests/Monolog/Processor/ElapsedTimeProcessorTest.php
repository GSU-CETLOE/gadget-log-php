<?php

declare(strict_types=1);

namespace Gadget\Log\Tests\Monolog\Processor;

use Gadget\Log\Monolog\Processor\ElapsedTimeProcessor;
use Gadget\Util\Timer;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use PHPUnit\Framework\TestCase;

final class ElapsedTimeProcessorTest extends TestCase
{
    private const ELAPSED_PATTERN = '/^\d{2}:\d{2}:\d{2}:\d{2}\.\d{3}$/';


    private static function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'hello');
    }


    public function testIsAMonologProcessor(): void
    {
        self::assertInstanceOf(ProcessorInterface::class, new ElapsedTimeProcessor());
        self::assertCount(
            1,
            (new \ReflectionClass(ElapsedTimeProcessor::class))->getAttributes(AsMonologProcessor::class)
        );
    }


    public function testTimerIsRunningFromConstruction(): void
    {
        $processor = new ElapsedTimeProcessor();
        usleep(2000);

        self::assertInstanceOf(Timer::class, $processor->timer);
        self::assertNotSame('00:00:00:00.000', $processor->timer->getElapsed(), 'A stopped timer reports all zeros');
    }


    public function testAddsElapsedTimeToExtra(): void
    {
        $record = (new ElapsedTimeProcessor())(self::record());

        self::assertIsString($record->extra['elapsed']);
        self::assertMatchesRegularExpression(self::ELAPSED_PATTERN, $record->extra['elapsed']);
    }


    public function testElapsedTimeGrowsBetweenRecords(): void
    {
        $processor = new ElapsedTimeProcessor();

        $first = $processor(self::record())->extra['elapsed'];
        usleep(2000);
        $second = $processor(self::record())->extra['elapsed'];

        self::assertIsString($first);
        self::assertIsString($second);
        self::assertGreaterThan($first, $second);
    }
}
