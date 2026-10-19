<?php

declare(strict_types=1);

namespace Gadget\Log\Monolog\Processor;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

#[AsMonologProcessor]
final class PidProcessor implements ProcessorInterface
{
    /** @inheritdoc */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['pid'] = sprintf('%08.0d', getmypid());
        return $record;
    }
}
