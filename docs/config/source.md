# Source

[Class reference](../../src/Asset/Definition/Source.php)

## Config reference

### `type`

The source type for this asset. This must be a valid identifier that is specified in
the map provided by [`ProviderFactory`](../../src/Provider/ProviderFactory.php).

* Required: **yes**
* Default: **`http`** (resolves to [`HttpFileProvider`](../../src/Provider/HttpFileProvider.php))

### `url`

Base URL to be used when fetching assets using the configured provider. It should
contain placeholders in the form `{<config key>}` that are replaced by source
configuration values. e.g. `{environment}`.

* Required: **yes**
* Default: **–**

### `version`

A locked version that is requested for specific branches. See
[Environment resolving](environments.md#environment-resolving) for more information
about when the version is used.

* Required: **no**
* Default: **–**

### `revision-url`

URL to a remote file that represents the revision of the fetched assets. Since not
every Frontend asset provides its revision, this configuration can also be left empty.

* Required: **no**
* Default: **–**

### `environment`

Asset environment to be used when fetching assets. **Note that this configuration is
always overridden by the resolved environment.**

## Additional source configuration

Each source requires different configuration. Consult the appropriate provider classes
to find out what configuration is actually possible. You can find the supported
configurations at [Providers](../components/providers.md) as well.
