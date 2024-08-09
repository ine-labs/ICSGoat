# Flysystem Adapter for Dropbox

[![Author](http://img.shields.io/badge/author-@hemantmann-blue.svg?style=flat-square)](https://twitter.com/HemantMann13)
[![Codecov](https://img.shields.io/codecov/c/github/Hemant-Mann/flysystem-dropbox.svg?style=flat-square&maxAge=0)](https://codecov.io/gh/Hemant-Mann/flysystem-dropbox)
[![Build Status](https://img.shields.io/travis/Hemant-Mann/flysystem-dropbox/master.svg?style=flat-square)](https://travis-ci.org/Hemant-Mann/flysystem-dropbox)

This is the Dropbox Adapter for the flysystem based on the v2 API of the Dropbox. This adapter supports PHP5

## Usage

Visit https://www.dropbox.com/developers/apps and get your "App secret".
You can also generate OAuth2 access token for testing using the Dropbox App Console without going through the authorization flow.

This Adapter uses the [Community SDK](https://github.com/kunalvarma05/dropbox-php-sdk) for connecting to the Dropbox API v2

```php
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;

use League\Flysystem\Filesystem;
use HemantMann\Flysystem\Dropbox\Adapter;

$app = new DropboxApp($clientId, $clientSecret, $accessToken);
$dropboxClient = new Dropbox($app);
$adapter = new Adapter($dropboxClient);

$filesystem = new Filesystem($adapter);
```

## Tests

```bash
composer install
bin/phpunit
```
