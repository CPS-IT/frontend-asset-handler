# VCS Providers

The Frontend Assets Handler is able to interact with the asset sources on VCS through
specific providers. Each VCS provider requires configuration. Consult the appropriate
provider classes to find out what configuration is expected.

## [`GithubVcsProvider`](../../src/Vcs/GithubVcsProvider.php)

Interacting with assets on GitHub requires an access token with `repo` scope.

It supports the following additional configuration:

### `access-token`

An access token to identify requests to the GitLab API. This token is supplied as a
`Authorization` header with each request.

* Required: **yes**
* Default: **–**

### `repository`

Name of the project in the form `<owner>/<name>` providing the Frontend assets. It
is used to lookup revisions and current deployments.

* Require: **yes**
* Default: **–**

## [`GitlabVcsProvider`](../../src/Vcs/GitlabVcsProvider.php)

Using this VCS Provider, GitLab can be interacted with as the VCS for the requested
Frontend assets. This is necessary, for example, to query an active deployment of the
requested assets.

It supports the following additional configuration:

### `base-url`

Base URL of the used GitLab instance, will be appended by the API url and the
requested endpoint.

* Required: **yes**
* Default: **`https://gitlab.com`**

### `access-token`

An access token to identify requests to the GitLab API. This token is supplied as a
`PRIVATE-TOKEN` header with each request.

* Required: **yes**
* Default: **–**

### `project-id`

ID of the project providing the Frontend assets. It is used to lookup revisions and
current deployments.

* Require: **yes**
* Default: **–**
