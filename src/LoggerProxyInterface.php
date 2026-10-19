<?php

declare(strict_types=1);

namespace Gadget\Log;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

interface LoggerProxyInterface extends LoggerInterface, LoggerAwareInterface
{
}
