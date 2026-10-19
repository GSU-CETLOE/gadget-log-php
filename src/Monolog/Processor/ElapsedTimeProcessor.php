<?php

declare(strict_types=1);

namespace Gadget\Log\Monolog\Processor;

use Gadget\Util\Timer;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

#[AsMonologProcessor]
final class ElapsedTimeProcessor implements ProcessorInterface
{
    public Timer $timer;


    public function __construct()
    {
        $this->timer = (new Timer())->start();
    }


    /** @inheritdoc */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['elapsed'] = $this->timer->getElapsed();
        return $record;
    }
}
