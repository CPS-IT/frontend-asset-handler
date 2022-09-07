# Migration

## 0.4.x → 1.0.0

* Package was converted to Composer library.
  - Package does no longer act as Composer plugin.
  - Console commands are no longer available within Composer.
  - Separate console application is now provided as `vendor/bin/frontend-assets`.
  - Execute commands like follows: `vendor/bin/frontend-assets <command>`
* Console commands were renamed.
  - `fetch-assets` must now be executed as `vendor/bin/frontend-assets fetch`.
  - `wait-for-assets` must now be executed as `vendor/bin/frontend-assets inspect --wait-for-deployments`
    (see following migration).
* Console command `wait-for-assets` was rewritten.
  - Use `vendor/bin/frontend-assets inspect --wait-for-deployments` instead.
* Minimum PHP version is now 8.1.
  - Upgrade your code base to PHP 8.1.

## 0.3.x → 0.4.0

* Support for configuration via `composer.json` has been removed.
  - Migrate your configuration at `extra/frontend-assets` to an external file, e.g. `assets.json`.
  - Reference the external file with the new command option `--config` (shorthand: `-c`).
  - Remove reference to external config file in `composer.json`.
* Environment map configuration changed.
  - For static transformers, no migration is needed.
  - For other transformers, migrate your configuration as follows
    (see [Environment Transformers](components/environment-transformers.md)):
    ```diff
     "environments": {
         "map": {
    -        "feature/*": "fe-{slug}"
    +        "feature/*": {
    +            "transformer": "slug",
    +            "options": {
    +                "pattern": "fe-{slug}"
    +            }
    +        }
         },
         "merge": true
     }
    ```
* Several classes were adapted for the use of dependency injection.
  - Upgrade third-party code if necessary.
* Minimum PHP version was raised to PHP 7.4.
  - Upgrade your codebase to support at least PHP 7.4.
* Several classes were marked as `final`.
  - If you still need to extend or override them, consider refactoring your code.
  - Try using [dependency injection](dependency-injection.md) to use customized services.
