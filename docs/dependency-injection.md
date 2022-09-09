# Dependency injection

All services in this package are stored into a service container that enables dependency injection.
This makes it relatively easy to add more services or configure existing services.

## Configuration

The library provides service configuration files. With those files, the resulting service container
is configured in a way that it's usable across the whole library and potential external libraries.
The following files are configured:

* [`services.php`](../config/services.php)
* [`services.yaml`](../config/services.yaml)
* [`services_test.php`](../config/services_test.php) (for testing purposes only)
* [`services_test.yaml`](../config/services_test.yaml) (for testing purposes only)

### Extending the service configuration

You can also extend the service container by additional service configuration files. This can be
achieved by adding a `services` array to your [assets configuration file](config/index.md):

```json
{
    "frontend-assets": [
        // ...
    ],
    "services": [
        "/path/to/custom/services.yaml",
        "/path/to/custom/services.php"
    ]
}
```

:bulb: **Note:** Only `yaml` and `php` files are supported. Take a look at the
[official Symfony documentation][1] to get an overview about service configuration.

## Service container

All services are stored in a service container that is built and cached during runtime. In order
to manually build the container, you can run the following:

```php
use CPSIT\FrontendAssetHandler\DependencyInjection;

// Declare config file
$configFile = '/path/to/assets.json';

// Get services from container
$containerFactory = new DependencyInjection\ContainerFactory($configFile);
$container = $containerFactory->get();
```

:bulb: **Note:** You must specify the path to a valid [assets configuration file](config/index.md).
Otherwise, a failsafe container will be created which can only be used for some low-level commands
(see the following section).

### Failsafe container

If the config file parameter is omitted, a failsafe container is built. This container is mainly
used in the command application and testing environment. It should not be used anywhere else.

[1]: https://symfony.com/doc/current/configuration.html#configuration-formats
