# Target

[Class reference](../../src/Asset/Definition/Target.php)

## Config reference

### `type`

The target type for this asset that is used to process the fetched asset. This must
be a valid identifier that is specified in the map provided by
[`ProcessorFactory`](../../src/Processor/ProcessorFactory.php).

* Required: **yes**
* Default: **`archive`** (resolves to [`FileArchiveProcessor`](../../src/Processor/FileArchiveProcessor.php))

### `path`

Path where to extract fetched assets. This can be either an absolute path or a path
relative to the path of your project's `composer.json` or the current working
directory (when executing the library as PHAR file).

* Required: **yes**
* Default: **–**

### `revision-file`

Default filename for local asset revisions. This is normally used to check the revision
of locally available assets.

* Required: **no**
* Default: **`REVISION`**

## Additional source configuration

Each target requires different configuration. Consult the appropriate processor classes
to find out what configuration is actually possible. You can find the supported
configurations at [Processors](../components/processors.md) as well.
