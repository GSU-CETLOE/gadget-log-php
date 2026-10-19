<?php

declare(strict_types=1);

namespace Gadget\Log\Tests\Monolog\Formatter;

use Gadget\Log\Monolog\Formatter\ConsoleLogFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class ConsoleLogFormatterTest extends TestCase
{
    /**
     * @param mixed[] $context
     * @param mixed[] $extra
     */
    private static function record(
        string $message = 'hello',
        array $context = [],
        array $extra = ['pid' => '00001234', 'command' => 'app:run', 'elapsed' => '00:00:00:01.500']
    ): LogRecord {
        return new LogRecord(
            new \DateTimeImmutable('2024-01-02 03:04:05.678', new \DateTimeZone('UTC')),
            'app',
            Level::Info,
            $message,
            $context,
            $extra
        );
    }


    public function testDefaultLineHasDatetimePidLevelCommandAndElapsed(): void
    {
        $line = (new ConsoleLogFormatter())->format(self::record());

        self::assertSame(
            "[2024-01-02T03:04:05.678+00:00][00001234][INFO][app:run][00:00:00:01.500]: hello \n",
            $line
        );
    }


    public function testPidCanBeLeftOut(): void
    {
        $line = (new ConsoleLogFormatter(includePid: false))->format(self::record());

        self::assertSame("[2024-01-02T03:04:05.678+00:00][INFO][app:run][00:00:00:01.500]: hello \n", $line);
    }


    public function testCommandCanBeLeftOut(): void
    {
        $line = (new ConsoleLogFormatter(includeCommand: false))->format(self::record());

        self::assertSame("[2024-01-02T03:04:05.678+00:00][00001234][INFO][00:00:00:01.500]: hello \n", $line);
    }


    public function testElapsedCanBeLeftOut(): void
    {
        $line = (new ConsoleLogFormatter(includeElapsed: false))->format(self::record());

        self::assertSame("[2024-01-02T03:04:05.678+00:00][00001234][INFO][app:run]: hello \n", $line);
    }


    public function testAllExtrasCanBeLeftOut(): void
    {
        $formatter = new ConsoleLogFormatter(includePid: false, includeCommand: false, includeElapsed: false);

        self::assertSame("[2024-01-02T03:04:05.678+00:00][INFO]: hello \n", $formatter->format(self::record()));
    }


    public function testMissingExtrasRenderAsEmptyBrackets(): void
    {
        $line = (new ConsoleLogFormatter())->format(self::record(extra: []));

        self::assertSame("[2024-01-02T03:04:05.678+00:00][][INFO][][]: hello \n", $line);
    }


    public function testContextIsAppendedAsJson(): void
    {
        $line = (new ConsoleLogFormatter())->format(self::record(context: ['user' => 'sam', 'count' => 3]));

        self::assertStringEndsWith(': hello {"user":"sam","count":3}' . "\n", $line);
    }


    public function testLineBreaksInTheMessageBecomeSpacesByDefault(): void
    {
        $line = (new ConsoleLogFormatter())->format(self::record("first\nsecond"));

        self::assertStringEndsWith(': first second ' . "\n", $line);
    }


    public function testLineBreaksInTheMessageCanBeKept(): void
    {
        $line = (new ConsoleLogFormatter(allowInlineLineBreaks: true))->format(self::record("first\nsecond"));

        self::assertStringEndsWith(": first\nsecond \n", $line);
    }
}
