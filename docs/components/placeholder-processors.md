# Placeholder Processors

## [`EnvironmentVariableProcessor`](../../src/Value/Placeholder/EnvironmentVariableProcessor.php)

This Processor can be used to process different configuration settings in order to use
environment variables. This allows certain configurations to be outsourced.

Values with the scheme `%env(VARIABLE_NAME)%` are considered here and resolved as follows:

* Input value: `%env(FOO)%`
* Environment variable: `FOO=baz`
* Resolved value: `baz`
