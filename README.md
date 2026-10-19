# gadget-log-php

A small logging package for PHP built on [PSR-3](https://www.php-fig.org/psr/psr-3/) and [Monolog](https://github.com/Seldaek/monolog). It gives you three things:

- A logger proxy you can inject into services that may or may not have a logger wired up. Calls go through when a logger is set and quietly do nothing when one isn't.
- Three Monolog processors that stamp each record with the process ID, the console command that is running, and how long the process has been running.
- A console line formatter that prints those stamps in a fixed, grep-friendly layout.

A Symfony bundle is included so all of it autowires.

CETLOE at Georgia State University maintains this package. It started as `mjfk23/gadget-log`, written by Melody Kimball.

## Requirements

- PHP 8.1
- Monolog 3

## Setup

Install [Composer](https://getcomposer.org/) if you don't have it, then add the package to your project:

```sh
composer require gsu-cetloe/gadget-log-php
```

To work on the library itself, clone the repository and install the development dependencies:

```sh
git clone https://github.com/GSU-CETLOE/gadget-log-php.git
cd gadget-log-php
composer install
```

Your machine does not have to run PHP 8.1. Composer is told to resolve dependencies as if it were PHP 8.1.0 no matter what version runs it, so the lock file always reflects what the library targets. Before a release, run the suite on an actual 8.1 build.

## Development commands

| Command | What it does |
| --- | --- |
| `composer test` | Runs the PHPUnit suite in `tests/`. |
| `composer phpstan` | Runs PHPStan at level 10 over `src/`, `bundle/`, and `tests/`. |
| `composer phpcs` | Checks `src/`, `bundle/`, and `tests/` against PSR-12. |
| `composer check` | Runs all three in order. Run this before you commit. |

Anything generated, such as the PHPStan and PHPUnit caches, lands in `working/`. Git ignores that directory.

## What's in the box

Everything lives under the `Gadget\Log` namespace.

| Class | Purpose |
| --- | --- |
| `LoggerProxy` | A PSR-3 logger that forwards to whatever logger you give it, or to nothing. |
| `LoggerProxyInterface` | `Psr\Log\LoggerInterface` plus `Psr\Log\LoggerAwareInterface`. Type-hint this. |
| `LoggerProxyTrait` | The same behavior as a trait, for classes that want to be their own proxy. |
| `Monolog\Processor\PidProcessor` | Adds `extra.pid`, the current process ID padded to eight digits. |
| `Monolog\Processor\ConsoleCommandProcessor` | Adds `extra.command`, the name you set on it, or an empty string. |
| `Monolog\Processor\ElapsedTimeProcessor` | Adds `extra.elapsed`, time since the processor was created, as `DD:HH:MM:SS.mmm`. |
| `Monolog\Formatter\ConsoleLogFormatter` | A `LineFormatter` that lays out the stamps above in a fixed order. |

### The logger proxy

Use the proxy where a logger is optional. You never have to null-check it.

```php
use Gadget\Log\LoggerProxy;
use Psr\Log\LoggerInterface;

final class Importer
{
    private LoggerProxy $log;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->log = new LoggerProxy();
        if ($logger !== null) {
            $this->log->setLogger($logger);
        }
    }

    public function run(): void
    {
        $this->log->info('Starting import');
        // ...
    }
}
```

`setLogger()` can be called again at any time. Messages after the swap go to the new logger.

If you'd rather not hold a separate object, pull the trait into your own class:

```php
use Gadget\Log\LoggerProxyTrait;

final class Importer
{
    use LoggerProxyTrait;

    public function run(): void
    {
        $this->info('Starting import');
    }
}
```

The class then satisfies both `LoggerInterface` and `LoggerAwareInterface`, so Symfony's `LoggerAwareInterface` autoconfiguration will inject the app logger for you.

### Processors and the formatter

Each processor adds one key to the record's `extra` array. The formatter reads those keys. If a processor isn't attached, its bracket prints empty.

Plain Monolog:

```php
use Gadget\Log\Monolog\Formatter\ConsoleLogFormatter;
use Gadget\Log\Monolog\Processor\ConsoleCommandProcessor;
use Gadget\Log\Monolog\Processor\ElapsedTimeProcessor;
use Gadget\Log\Monolog\Processor\PidProcessor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$command = new ConsoleCommandProcessor();
$command->commandName = 'app:import';

$handler = (new StreamHandler('php://stderr'))->setFormatter(new ConsoleLogFormatter());

$logger = (new Logger('app', [$handler]))
    ->pushProcessor(new PidProcessor())
    ->pushProcessor($command)
    ->pushProcessor(new ElapsedTimeProcessor());

$logger->info('Loaded {count} rows', ['count' => 1200]);
```

That prints:

```
[2024-01-02T03:04:05.678+00:00][00001234][INFO][app:import][00:00:00:01.500]: Loaded {count} rows {"count":1200}
```

Left to right: timestamp with milliseconds and offset, PID, level, command, elapsed time, message, then the context as JSON. Empty context prints nothing after the message.

The formatter's constructor takes four booleans, all defaulting to `true` except the last:

| Argument | Default | Effect |
| --- | --- | --- |
| `includePid` | `true` | Prints the `[pid]` bracket. |
| `includeCommand` | `true` | Prints the `[command]` bracket. |
| `includeElapsed` | `true` | Prints the `[elapsed]` bracket. |
| `allowInlineLineBreaks` | `false` | When `false`, newlines in the message become spaces. |

```php
// Timestamp, level, and message only.
new ConsoleLogFormatter(includePid: false, includeCommand: false, includeElapsed: false);
```

`ElapsedTimeProcessor` starts its timer the moment it is constructed, so build it once at startup and reuse it. The `timer` property is public if you need to read or reset it.

`ConsoleCommandProcessor` doesn't know what command is running. You tell it. In a Symfony console app, a listener on `ConsoleEvents::COMMAND` is the usual place:

```php
use Gadget\Log\Monolog\Processor\ConsoleCommandProcessor;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ConsoleEvents::COMMAND)]
final class RecordCommandName
{
    public function __construct(private ConsoleCommandProcessor $processor)
    {
    }

    public function __invoke(ConsoleCommandEvent $event): void
    {
        $this->processor->commandName = $event->getCommand()?->getName();
    }
}
```

## Symfony bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Gadget\Log\GadgetLogBundle::class => ['all' => true],
];
```

The bundle registers every class under `src/` as an autowired, autoconfigured service, and aliases `LoggerProxyInterface` to `LoggerProxy`. Each processor carries Monolog's `#[AsMonologProcessor]` attribute, so with MonologBundle installed they attach to your handlers without any extra configuration.

To use the console formatter in MonologBundle, point a handler at the service:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        console:
            type: stream
            path: php://stderr
            formatter: Gadget\Log\Monolog\Formatter\ConsoleLogFormatter
```

To turn off one of the brackets, define your own formatter service with the arguments you want and reference that instead.

## Testing

`composer test` runs the suite. The proxy tests log through a real Monolog `Logger` and `TestHandler`, the formatter tests compare whole output lines, and the bundle tests compile a real `ContainerBuilder`. [TESTPLAN.md](TESTPLAN.md) has the manual walkthrough.

## License

MIT. See [LICENSE](LICENSE).
