# Test Plan

Walk through this list before tagging a release or merging a change that touches `composer.json`, the analyzers, or the test suite. Run every command from the repository root.

## Setup

- [ ] `composer install` finishes without errors and without asking to change the lock file.
- [ ] `composer validate` reports the manifest is valid.
- [ ] `working/` exists after the first run and is not shown in `git status`.

## Automated checks

- [ ] `composer phpcs` reports no errors and no warnings for `src/`, `bundle/`, and `tests/`.
- [ ] `composer phpstan` reports `No errors` at level 10 for `src/`, `bundle/`, and `tests/`.
- [ ] `composer test` passes with no warnings, deprecations, or notices in the output.
- [ ] `composer check` runs all three in order and exits 0.

## Functional coverage

Confirm each area has passing tests under `tests/` that cover the listed behaviors.

- [ ] `LoggerProxy`: implements `LoggerProxyInterface`, `LoggerInterface`, and `LoggerAwareInterface`; logging with no logger set does nothing; level, message, and context reach the wrapped logger; `Stringable` messages are accepted; each of the eight PSR-3 level helpers logs at its own level; `setLogger()` a second time redirects later messages.
- [ ] `LoggerProxyTrait`: a class using only the trait can accept a logger and log through it.
- [ ] `ConsoleLogFormatter`: default line is `[datetime][pid][level][command][elapsed]: message context`; each of `includePid`, `includeCommand`, and `includeElapsed` removes its bracket; all three off leaves `[datetime][level]`; missing extras print as empty brackets; context is appended as JSON; newlines in the message become spaces by default and are kept with `allowInlineLineBreaks`.
- [ ] `ConsoleCommandProcessor`: implements `ProcessorInterface` and carries `#[AsMonologProcessor]`; `commandName` is null by default; `extra.command` is an empty string when unset and the name when set.
- [ ] `ElapsedTimeProcessor`: implements `ProcessorInterface` and carries `#[AsMonologProcessor]`; the timer is running from construction; `extra.elapsed` matches `DD:HH:MM:SS.mmm`; a later record reports a larger value.
- [ ] `PidProcessor`: implements `ProcessorInterface` and carries `#[AsMonologProcessor]`; `extra.pid` is the current PID zero-padded to eight digits.
- [ ] `GadgetLogBundle`: extends `Bundle` and exposes `GadgetLogExtension` under the alias `gadget_log`.
- [ ] `GadgetLogExtension`: registers each `src/` class as an autowired, autoconfigured service, aliases `LoggerProxyInterface` to `LoggerProxy`, and a compiled container builds every service.

## Consuming the package

- [ ] In a scratch project, `composer require gsu-cetloe/gadget-log-php` installs `gsu-cetloe/gadget-core-php`, `gsu-cetloe/symfony-bundle`, `monolog/monolog` 3, and `psr/log` 3.
- [ ] In a scratch Symfony app with `GadgetLogBundle` and MonologBundle enabled, a handler configured with `formatter: Gadget\Log\Monolog\Formatter\ConsoleLogFormatter` prints lines with the PID and elapsed brackets filled in.
- [ ] In that same app, a service that type-hints `LoggerProxyInterface` autowires.

## Known gaps

- The processors' `#[AsMonologProcessor]` attribute is only acted on by MonologBundle. The suite checks that the attribute is present but does not boot MonologBundle to prove the processors get attached. That is covered by the manual step above.
- Tests run on whatever PHP is installed locally. Composer resolves dependencies as if PHP were 8.1.0, but the behavior checked here is that of the local interpreter.
