# Processors

## [`FileArchiveProcessor`](../../src/Processor/FileArchiveProcessor.php)

With the help of this Processor, temporarily provided Frontend assets, which are
available as archives, can be extracted. The file formats `zip`, `tar` and `tar.gz`
are supported.

It supports the following additional configuration:

### `base`

Base path within the fetched asset archive that should be extracted to the configured
path. Only files within this path are extracted, all other files are ignored.

> [!TIP]
> You can set this to an empty value to extract all files.

* Required: **no**
* Default: **`''`** _(= extract all files)_

## [`ExistingAssetProcessor`](../../src/Processor/ExistingAssetProcessor.php)

This Processor can be used as a replacement if the chosen download strategy makes it
unnecessary to re-download existing Frontend assets. The Processor is then only used
to check whether the assets are actually available.

It supports no additional configuration.
