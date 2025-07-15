# Migration

## 4.x → 5.0.0

* Minimum PHP version is now 8.2.
  - Upgrade your code base to PHP 8.2.

## 0.4.x → 1.0.0

* Package is now publicly available.
  - Package was renamed to `cpsit/frontend-asset-handler`.
  - Root PSR-4 namespace is now `CPSIT\FrontendAssetHandler`.
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
* Configuration schema was hardened.
  - Add a `type` configuration to the `vcs` definition.
  - Explicitly configure `url` and `revision-url` for the `source` definition.
  - Migrate the `source.revision-file` configuration to `source.revision-url`:
    ```diff
     "source": {
         "type": "http",
         "url": "https://example.com/assets/{environment}.tar.gz",
    -    "revision-url": "https://example.com/assets/{environment}/{revision-file}",
    -    "revision-file": "REVISION"
    +    "revision-url": "https://example.com/assets/{environment}/REVISION"
     }
    ```
  - See [Configuration](config/index.md) for all available configuration options
    and check out the updated [schema file](../resources/configuration.schema.json).
* Asset handlers are now configurable.
  - Implement the [`HandlerInterface`](../src/Handler/HandlerInterface.php) for custom handlers.
  - Reference the handler type within the asset definition:
    ```diff
     {
    +    "handler": "my-custom-handler",
         "source": { /* ... */ },
         "target": { /* ... */ }
     }
    ```
* Custom service configuration can now be configured.
  - Add a `services.yaml` or `services.php` file to your code base.
  - Reference the files in the asset configuration file:
    ```diff
     {
         "frontend-assets": [
             /* ... */
    -    ]
    +    ],
    +    "services": [
    +        "/path/to/my/services.php",
    +        "/path/to/my/services.yaml"
    +    ]
     }
    ```
  - See [Dependency injection](dependency-injection.md) for a detailed overview.
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
