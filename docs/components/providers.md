# Providers

## [`HttpFileProvider`](../../src/Provider/HttpFileProvider.php)

This Provider enables the delivery of Frontend assets over an HTTP resource. The
downloaded assets are stored in a temporary directory from where they can be processed
further. The Provider also requests the revision of the requested assets and adds them
to the requested [source](../config/source.md).

It supports no additional configuration.
