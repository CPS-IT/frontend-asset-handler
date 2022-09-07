# Configure assets

```bash
$ vendor/bin/frontend-assets config [-c|--config CONFIG-FILE] [--unset] [--validate] [--json] <path> [<newValue>]
```

This command reads, writes or validates [configuration](../config/index.md) for the assets
from the given configuration file.

## `--config`

Define the path to the assets configuration file.

> :warning: In previous versions, configuration could also be added to the `composer.json`
> file. This is no longer possible. You need to define all settings in a separate file
> and pass it via this command option.

* Required: **yes**
* Shorthand: **`-c`**
* Default: **`assets.json`**

## `--unset`

Use this command option to unset configuration at the given path.

> :warning: This option cannot be used in combination with the `--validate` option and the
> `newValue` argument.

* Required: **no**
* Default: **no**

## `--validate`

This option can be used to validate asset configuration provided by the given assets
configuration file.

> :warning: It cannot be used in combination with the `--unset` option and the `newValue`
> argument.

* Required: **no**
* Default: **no**

## `--json`

Treat the value passed with `newValue` argument as JSON.

* Required: **no**
* Shorthand: **–**
* Default: **no**

## `path`

The path to the configuration value to be read or written inside the configuration
file. Path segments should be combined with a slash (`/`), e.g. `0/source/version`.

* Required: **yes**
* Default: **–**

## `newValue`

Define the new value to be written at the given path in the configuration file. If
you leave this argument out, the current value at the given path will be returned.

> :warning: This argument cannot be used in combination with the `--unset` and
> `--validate` options.

* Required: **no**
* Default: **–**
