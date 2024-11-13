# API usage

If asset handling is to be performed via PHP, the following steps are generally required:

1. Declare the config file
2. Get the current container instance
3. Resolve the requested asset environment
4. Define [asset source](../config/source.md) and [asset target](../config/target.md)
5. Fetch a Handler using the [`HandlerFactory`](../../src/Handler/HandlerFactory.php)
6. Execute the Handler, optionally with a freely selectable download strategy

## Example

```php
use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Handler;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Strategy;

// Declare config file
$configFile = '/path/to/assets.json';

// Get the current service container
$containerFactory = new DependencyInjection\ContainerFactory($configFile);
$container = $containerFactory->get();

// Resolve environment
$map = Asset\Environment\Map\MapFactory::createDefault();
$environmentResolver = new Asset\Environment\EnvironmentResolver($map);
$environment = $environmentResolver->resolve('main');

// Describe asset
$source = new Asset\Definition\Source([
    'type' => 'http',
    'url' => 'https://www.example.com/assets/{environment}.tar.gz'
    'environment' => $environment,
]);
$target = new Asset\Definition\Target([
    'type' => 'archive',
    'path' => 'app/web/assets',
]);

// Instantiate components
$handlerFactory = $container->get(Handler\HandlerFactory::class);
$handler = $handlerFactory->get('default');

// Optional: Define download strategy
// $strategy = Strategy\Strategy::FetchNew;

// Run asset handler
/** @var Asset\ProcessedAsset $asset */
$asset = $handler->handle(
    $source,
    $target,
    /* $strategy */
);

echo 'Assets downloaded and extracted to ' . $asset->getProcessedTargetPath();
```

> [!TIP]
> If you prefer configuration with a config file instead, you can load
> and parse this file as follows:
>
> ```php
> use CPSIT\FrontendAssetHandler\Asset;
> use CPSIT\FrontendAssetHandler\Config;
> use CPSIT\FrontendAssetHandler\Handler;
>
> // Load config
> $configFacade = $container->get(Config\ConfigFacade::class);
> $config = $configFacade->load('/path/to/assets.json');
>
> // Parse config
> $instructions = new Config\Parser\ParserInstructions($config);
> $parser = $container->get(Config\Parser\Parser::class);
> $parsedConfig = $parser->parse($instructions);
>
> // Instantiate components
> $assetDefinitionFactory = $container->get(Asset\Definition\AssetDefinitionFactory::class);
> $handlerFactory = $container->get(Handler\HandlerFactory::class);
>
> // Optional: Define download strategy
> // $strategy = Strategy\Strategy::FetchNew;
>
> // Run asset handler
> foreach ($parsedConfig['frontend-assets'] as $assetDefinition) {
>     $handler = $handlerFactory->get($assetDefinition['handler'] ?? 'default');
>
>     /** @var Asset\ProcessedAsset $asset */
>     $asset = $handler->handle(
>         $assetDefinitionFactory->buildSource($assetDefinition, 'main'),
>         $assetDefinitionFactory->buildTarget($assetDefinition),
>         /* $strategy */
>     );
>
>     echo 'Assets downloaded and extracted to ' . $asset->getProcessedTargetPath();
> }
> ```
