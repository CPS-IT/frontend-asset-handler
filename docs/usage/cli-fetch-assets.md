# Fetch assets

```bash
$ vendor/bin/frontend-assets fetch [-c|--config CONFIG-FILE] [-f|--force] [-s|--failsafe] [<branch>]
```

This command fetches Frontend assets for the given branch using the provided
[configuration](../config/index.md).

## `-c|--config`

Define the path to the assets configuration file.

> :warning: In previous versions, configuration could also be added to the `composer.json`
> file. This is no longer possible. You need to define all settings in a separate file
> and pass it via this command option.

* Required: **yes**
* Default: **`assets.json`**

## `-f|--force`

Enforce downloading and processing of the requested Frontend assets, even if they are
already available locally.

* Required: **no**
* Default: **no**

## `-s|--failsafe`

Fall back to the latest assets if the resolved asset environment is not available
on the remote side. This can be especially useful in feature branches where no
specific Frontend assets are available.

* Required: **no**
* Default: **no**

## `branch`

The branch to be used to resolve the requested asset environment that should be known
by the requested provider.

> :bulb: If no branch is given, the currently checked out branch is used.

* Required: **no**
* Default: **current branch** (see [branch determination logic](../config/environments.md#branch-determination-logic))
