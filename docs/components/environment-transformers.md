# Environment Transformers

## [`PassthroughTransformer`](../../src/Asset/Environment/Transformer/PassthroughTransformer.php)

This Transformer can be used to pass an incoming branch unmodified through the environment
resolving step and use it as asset environment. The input value passed to transform will
be immediately returned without further modification.

### Configuration

```json
{
    "transformer": "passthrough"
}
```

### Example

```php
$transformer = new \CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\PassthroughTransformer();
$environment = $transformer->transform('foo');

// $environment = 'foo'
```

## [`SlugTransformer`](../../src/Asset/Environment/Transformer/SlugTransformer.php)

Use this Transformer if you need to slugify the incoming branch. This is especially useful
if branches contain characters that need to be URL-encoded.

### Configuration

```json
{
    "transformer": "slug",
    "options": {
        "pattern": "fe-{slug}"
    }
}
```

### Example 1 (default pattern)

```php
$transformer = new \CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer();
$environment = $transformer->transform('feature/some-cool-modifications');

// $environment = 'feature-some-cool-modifications'
```

### Example 2 (custom pattern)

```php
$transformer = new \CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer('fe-{slug}');
$environment = $transformer->transform('feature/some-cool-modifications');

// $environment = 'fe-feature-some-cool-modifications'
```

## [`StaticTransformer`](../../src/Asset/Environment/Transformer/StaticTransformer.php)

Sometimes environments are fixed to a static value, e.g. `latest` or `stable`. In this case
you can use this Transformer and provide the static value which is always returned when
calling the `transform()` method.

### Configuration

```json
{
    "transformer": "static",
    "options": {
        "value": "latest"
    }
}
```

### Example

```php

$transformer = new \CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\StaticTransformer('latest');
$environment = $transformer->transform('foo');

// $environment = 'latest'
```

## [`VersionTransformer`](../../src/Asset/Environment/Transformer/VersionTransformer.php)

This Transformer offers a similar behavior as the [`StaticTransformer`](#statictransformer).
It accepts a version and returns it when calling `transform()`.

### Configuration

```json
{
    "transformer": "version",
    "options": {
        "version": "1.0.0"
    }
}
```

### Example

```php
$transformer = new \CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\VersionTransformer('1.0.0');
$environment = $transformer->transform('foo');

// $environment = '1.0.0'
```
