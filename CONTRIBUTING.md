# Contributing

Thanks for considering contributing to this project! Each contribution is
highly appreciated. In order to maintain a high code quality, please follow
all steps below.

## Requirements

- Composer
- PHP >= 8.2

## Preparation

```bash
# Clone repository
git clone https://github.com/CPS-IT/frontend-asset-handler.git
cd frontend-asset-handler

# Install dependencies
composer install
```

## Run linters

```bash
# All linters
composer lint

# Specific linters
composer lint:composer
composer lint:editorconfig
composer lint:json
composer lint:php

# Fix all CGL issues
composer fix

# Fix specific CGL issues
composer fix:composer
composer fix:editorconfig
composer fix:json
composer fix:php
```

## Run static code analysis

```bash
# All static code analyzers
composer sca

# Specific static code analyzers
composer sca:php
```

## Validate configuration schema

```bash
composer validate-schema
```

💡 You need a local Docker installation for this command.

## Run tests

```bash
# All tests
composer test

# All tests with code coverage
composer test:coverage
```

### Test reports

Code coverage reports are written to `.build/coverage`. You can open the
last HTML report like follows:

```bash
open .build/coverage/html/index.html
```

## Submit a pull request

Once you have finished your work, please **submit a pull request** and describe
what you've done. Ideally, your PR references an issue describing the problem
you're trying to solve.

All described code quality tools are automatically executed on each pull request
for all currently supported PHP versions. Take a look at the appropriate
[workflows][1] to get a detailed overview.

[1]: .github/workflows
