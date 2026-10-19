<?php

declare(strict_types=1);

namespace Gadget\Log\Monolog\Formatter;

use Monolog\Formatter\LineFormatter;

final class ConsoleLogFormatter extends LineFormatter
{
    /**
     * @param bool $includePid
     * @param bool $includeCommand
     * @param bool $includeElapsed
     */
    public function __construct(
        bool $includePid = true,
        bool $includeCommand = true,
        bool $includeElapsed = true,
        bool $allowInlineLineBreaks = false
    ) {
        $pid = $includePid ? "[%extra.pid%]" : "";
        $command = $includeCommand ? "[%extra.command%]" : "";
        $elapsed = $includeElapsed ? "[%extra.elapsed%]" : "";

        parent::__construct(
            format: "[%datetime%]{$pid}[%level_name%]{$command}{$elapsed}: %message% %context%\n",
            dateFormat: "Y-m-d\\TH:i:s.vP",
            allowInlineLineBreaks: $allowInlineLineBreaks,
            ignoreEmptyContextAndExtra: true,
            includeStacktraces: false
        );
    }
}
