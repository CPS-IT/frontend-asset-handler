# VCS

[Class reference](../../src/Asset/Definition/Vcs.php)

## Config reference

### `type`

The VCS type for this asset that is used to interact with the asset source VCS. This must
be a valid identifier that is specified in the map provided by
[`VcsProviderFactory`](../../src/Vcs/VcsProviderFactory.php).

* Required: **yes**
* Default: **`gitlab`** (resolves to [`GitlabVcsProvider`](../../src/Vcs/GitlabVcsProvider.php))

## Additional VCS configuration

Each VCS provider requires different configuration. Consult the appropriate provider
classes to find out what configuration is actually possible. You can find the supported
configurations at [VCS Providers](../components/vcs-providers.md) as well.
