<?php

declare(strict_types=1);

namespace Gadget\Log\Monolog\Processor;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

#[AsMonologProcessor]
final class ConsoleCommandProcessor implements ProcessorInterface
{
    public string|null $commandName = null;


    /** @inheritdoc */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['command'] = $this->commandName ?? '';
        return $record;
    }
}
