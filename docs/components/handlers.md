# Handlers

## [`AssetHandler`](../../src/Handler/AssetHandler.php)

This is the default Handler for downloading and processing Frontend assets. Depending
on the defined or automatically selected download strategy, the Handler also skips the
asset provisioning process if necessary and uses the
[`ExistingAssetProcessor`](processors.md#existingassetprocessor) to avoid obsolete
asset handling.
