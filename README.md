<div align="center">

![Logo](docs/assets/logo.png)

# Frontend Asset Handler

[![Coverage](https://img.shields.io/coverallsCoverage/github/CPS-IT/frontend-asset-handler?logo=coveralls)](https://coveralls.io/github/CPS-IT/frontend-asset-handler)
[![Tests](https://github.com/CPS-IT/frontend-asset-handler/actions/workflows/tests.yaml/badge.svg)](https://github.com/CPS-IT/frontend-asset-handler/actions/workflows/tests.yaml)
[![CGL](https://github.com/CPS-IT/frontend-asset-handler/actions/workflows/cgl.yaml/badge.svg)](https://github.com/CPS-IT/frontend-asset-handler/actions/workflows/cgl.yaml)
[![Latest Stable Version](http://poser.pugx.org/cpsit/frontend-asset-handler/v)](https://packagist.org/packages/cpsit/frontend-asset-handler)
[![Total Downloads](http://poser.pugx.org/cpsit/frontend-asset-handler/downloads)](https://packagist.org/packages/cpsit/frontend-asset-handler)
[![PHP Version Require](http://poser.pugx.org/cpsit/frontend-asset-handler/require/php)](https://packagist.org/packages/cpsit/frontend-asset-handler)
[![License](http://poser.pugx.org/cpsit/frontend-asset-handler/license)](LICENSE.md)

📦&nbsp;[Packagist](https://packagist.org/packages/cpsit/frontend-asset-handler) |
💾&nbsp;[Repository](https://github.com/CPS-IT/frontend-asset-handler) |
🐛&nbsp;[Issue tracker](https://github.com/CPS-IT/frontend-asset-handler/issues)

</div>

A Composer library that downloads and extracts Frontend assets to a dedicated path in PHP projects.
All Frontend assets are configured through an `assets.json` file and can be easily maintained by
a dedicated CLI application.

## 🚀 Features

* Command-line application to fetch Frontend assets
* Asset configuration via `assets.json` file
* Automated integration into CI systems
* Easy extensible by custom asset providers and processors
* Designed for dependency injection

## 🔥 Getting started

1. [Install](docs/installation.md) the library:

   ```bash
   composer require cpsit/frontend-asset-handler
   ```

2. [Initialize](docs/usage/cli-init-config.md) a new `assets.json` file:

   ```bash
   vendor/bin/frontend-assets init
   ```

3. [Fetch](docs/usage/cli-fetch-assets.md) Frontend assets:

   ```bash
   vendor/bin/frontend-assets fetch
   ```

## 📖 Documentation

* [Installation](docs/installation.md)
* [Usage](docs/usage/index.md)
  - Command-line usage
    + [Configure assets](docs/usage/cli-config-assets.md)
    + [Fetch assets](docs/usage/cli-fetch-assets.md)
    + [Initialize config](docs/usage/cli-init-config.md)
    + [Inspect assets](docs/usage/cli-inspect-assets.md)
  - [API usage](docs/usage/api-usage.md)
* [Configuration](docs/config/index.md)
  - [Full reference](docs/config/full-reference.md)
  - [Source](docs/config/source.md)
  - [Target](docs/config/target.md)
  - [VCS](docs/config/vcs.md)
  - [Environments](docs/config/environments.md)
* [Shipped components](docs/components/index.md)
  - [Providers](docs/components/providers.md)
  - [Processors](docs/components/processors.md)
  - [Handlers](docs/components/handlers.md)
  - [VCS Providers](docs/components/vcs-providers.md)
  - [Placeholder Processors](docs/components/placeholder-processors.md)
  - [Environment Transformers](docs/components/environment-transformers.md)
* [Dependency injection](docs/dependency-injection.md)
* [Migration](docs/migration.md)

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## 💎 Credits

[Direct download icons created by Pixel perfect - Flaticon](https://www.flaticon.com/free-icons/direct-download)

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).
