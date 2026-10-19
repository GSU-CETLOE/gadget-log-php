<?php

declare(strict_types=1);

namespace Gadget\Log;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;

trait LoggerProxyTrait
{
    use LoggerAwareTrait;
    use LoggerTrait;


    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string|\Stringable $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log(
        mixed $level,
        string|\Stringable $message,
        array $context = []
    ): void {
        $this->logger?->log($level, $message, $context);
    }
}
