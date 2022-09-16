# Inspect assets

```bash
$ vendor/bin/frontend-assets inspect [-c|--config CONFIG-FILE] [-w|--wait-for-deployments] [<branch>]
```

This command can be used to inspect a frontend asset, based on the given branch.
It shows source and target revision next to asset URLs and active deployments.
The option `--wait-for-deployments` can be additionally used to block a
following `frontend-assets fetch` command as long as the requested assets are
currently deployed. This is especially useful in CI to avoid fetching outdated
assets in case more recent assets are currently built and/or deployed to the
asset source location.

## `-c|--config`

Define the path to the assets configuration file.

> :warning: In previous versions, configuration could also be added to the `composer.json`
> file. This is no longer possible. You need to define all settings in a separate file
> and pass it via this command option.

* Required: **yes**
* Default: **`assets.json`**

## `-w|--wait-for-deployments`

Block further processes until active deployments of the current frontend asset
are finished. Can be used to block following `frontend-assets fetch` commands
to assure asset sources are up-to-date before fetching them.

* Required: **no**
* Default: **no**

## `branch`

The branch to be used to resolve the requested asset environment that should be known
by the requested provider.

> :bulb: If no branch is given, the currently checked out branch is used.

* Required: **no**
* Default: **current branch** (see [branch determination logic](../config/environments.md#branch-determination-logic))
