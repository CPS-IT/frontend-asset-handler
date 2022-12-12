# Providers

## [`HttpFileProvider`](../../src/Provider/HttpFileProvider.php)

This Provider enables the delivery of Frontend assets over an HTTP resource. The
downloaded assets are stored in a temporary directory from where they can be processed
further. The Provider also requests the revision of the requested assets and adds them
to the requested [source](../config/source.md).

It supports no additional configuration.

## [`LocalPathProvider`](../../src/Provider/LocalPathProvider.php)

With this Provider, local paths may be used as Frontend asset source. An additional
command can be specified that is executed prior to validating the local path, e.g.
to initialize a new build or archive a list of files and directories.

> :bulb: The local path itself is configured by the [`url`](#url) configuration.

It supports the following additional configuration:

### `command`

Additional command that is locally executed prior to validating the source file.
It can be used to prepare the source file for further processing with a supported
[processor](processors.md). When omitted, the source file is expected to exist
without further modification.

* Required: **no**
* Default: **–**

It may contain placeholders in the form `{<config key>}` that are replaced by source
configuration values. e.g. `{environment}`. In addition, it supports the following
special placeholders:

* `{cwd}` is replaced by the current working directory
* `{url}` is replaced by the resolved [source path (`url`)](#url)

### `url`

In addition to the [default configuration](../config/source.md#url), it may contain
the following special placeholders:

* `{cwd}` is replaced by the current working directory
* `{temp}` is replaced by a temporary filename (must be at the beginning of the path,
  e.g. `{temp}.tar.gz`)

> :warning: The resolved URL must be an existing path on the local filesystem.
