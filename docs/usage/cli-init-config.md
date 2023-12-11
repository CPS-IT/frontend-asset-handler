# Initialize config

```bash
$ vendor/bin/frontend-assets init [-c|--config CONFIG-FILE]
```

With this command, a new asset configuration file can be created. An interactive
tour guides through various configuration options for asset source and target.
In the end, a new config file is created.

> [!NOTE]
> This command can only be used in interactive mode.

## `-c|--config`

Define the path to the assets configuration file.

> [!NOTE]
> In previous versions, configuration could also be added to the `composer.json` file.
> This is no longer possible. You need to define all settings in a separate file
> and pass it via this command option.

* Required: **yes**
* Default: **`assets.json`**
