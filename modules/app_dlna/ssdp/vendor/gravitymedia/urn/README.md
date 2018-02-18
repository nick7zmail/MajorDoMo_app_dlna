#Uniform Resource Names (URN)

A PHP 5.3+ library for generating RFC 2141 compliant uniform resource names (URN)

[![Packagist](https://img.shields.io/packagist/v/gravitymedia/urn.svg)](https://packagist.org/packages/gravitymedia/urn)
[![Downloads](https://img.shields.io/packagist/dt/gravitymedia/urn.svg)](https://packagist.org/packages/gravitymedia/urn)
[![License](https://img.shields.io/packagist/l/gravitymedia/urn.svg)](https://packagist.org/packages/gravitymedia/urn)
[![Build](https://img.shields.io/travis/GravityMedia/Urn.svg)](https://travis-ci.org/GravityMedia/Urn)
[![Code Quality](https://img.shields.io/scrutinizer/g/GravityMedia/Urn.svg)](https://scrutinizer-ci.com/g/GravityMedia/Urn/?branch=master)
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/GravityMedia/Urn.svg)](https://scrutinizer-ci.com/g/GravityMedia/Urn/?branch=master)
[![PHP Dependencies](https://www.versioneye.com/user/projects/54a6c39d27b014005400004b/badge.svg)](https://www.versioneye.com/user/projects/54a6c39d27b014005400004b)

##Requirements##

This library has the following requirements:

 - PHP 5.3+

##Installation##

Install composer in your project:

```bash
$ curl -s https://getcomposer.org/installer | php
```

Create a `composer.json` file in your project root:

```json
{
    "require": {
        "gravitymedia/urn": "dev-master"
    }
}
```

Install via composer:

```bash
$ php composer.phar install
```

##Usage##

```php
require 'vendor/autoload.php';

use GravityMedia\Urn\Urn;

// define URN string
$urnString = 'urn:example-namespace-id:just_an_example';

// check if string is a valid URN
var_dump(Urn::isValid($urnString));

// create URN from string
$urn = Urn::fromString($urnString);

// dump namespace identifier
var_dump($urn->getNamespaceIdentifier());

// dump namespace specific string
var_dump($urn->getNamespaceSpecificString());
```
