# Minify

[![Build Status](https://img.shields.io/github/actions/workflow/status/DevFactoryCH/minify/tests.yml)](https://github.com/DevFactoryCH/minify/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/devfactory/minify/v/stable.svg)](https://packagist.org/packages/devfactory/minify)
[![Total Downloads](https://poser.pugx.org/devfactory/minify/downloads.svg)](https://packagist.org/packages/devfactory/minify)
[![License](https://poser.pugx.org/devfactory/minify/license.svg)](https://packagist.org/packages/devfactory/minify)

With this package you can minify your existing stylesheet and JavaScript files for Laravel 10.
This process can be a little tough, this package simplifies and automates this process.

For Laravel 5 - 9 please use version 1.x of this package.

For Laravel 4 please use [ceesvanegmond/minify](https://github.com/ceesvanegmond/minify)

## Installation

Begin by installing this package through Composer.


```json
{
    "require": {
        "devfactory/minify": "^2.0"
    }
}
```

After the package installation, the `MinifyServiceProvider` and `Minify` facade are automatically registered.
You can use the `Minify` facade anywhere in your application.

To publish the config file:

```shell
php artisan vendor:publish --provider="Devfactory\Minify\MinifyServiceProvider" --tag="config"
```


## Upgrade to v2
Minify version 2 is PHP 8.1+ and Laravel 10+ only.

### Required upgrade changes
If the [`Devfactory\Minify\Contracts\MinifyInterface`](src/Contracts/MinifyInterface.php) interface is implemented,
make sure update your implementation according to the updated types and exceptions.

If the [`Devfactory\Minify\Providers\BaseProvider`](src/Providers/BaseProvider.php) abstract class is used,
make sure update your classes according to the updated types and exceptions.

The method `Devfactory\Minify\Providers\StyleSheet#urlCorrection` has been renamed to `Devfactory\Minify\Providers\StyleSheet#getFileContentWithCorrectedUrls`.

Rename the `minify.config.php` configuration file to `minify.php`.

## Usage
### Stylesheet

```php
// app/views/hello.blade.php

<html>
    <head>
        ...
        {!! Minify::stylesheet('/css/main.css') !!}
        // or by passing multiple files
        {!! Minify::stylesheet(['/css/main.css', '/css/bootstrap.css']) !!}
        // add custom attributes
        {!! Minify::stylesheet(['/css/main.css', '/css/bootstrap.css'], ['foo' => 'bar']) !!}
        // add full uri of the resource
        {!! Minify::stylesheet(['/css/main.css', '/css/bootstrap.css'])->withFullUrl() !!}
        {!! Minify::stylesheet(['//fonts.googleapis.com/css?family=Roboto']) !!}

        // minify and combine all stylesheet files in given folder
        {!! Minify::stylesheetDir('/css/') !!}
        // add custom attributes to minify and combine all stylesheet files in given folder
        {!! Minify::stylesheetDir('/css/', ['foo' => 'bar', 'defer' => true]) !!}
        // minify and combine all stylesheet files in given folder with full uri
        {!! Minify::stylesheetDir('/css/')->withFullUrl() !!}
    </head>
    ...
</html>
```

### Javascript

```php
// app/views/hello.blade.php

<html>
    <body>
    ...
    </body>
    {!! Minify::javascript('/js/jquery.js') !!}
    // or by passing multiple files
    {!! Minify::javascript(['/js/jquery.js', '/js/jquery-ui.js']) !!}
    // add custom attributes
    {!! Minify::javascript(['/js/jquery.js', '/js/jquery-ui.js'], ['bar' => 'baz']) !!}
    // add full uri of the resource
    {!! Minify::javascript(['/js/jquery.js', '/js/jquery-ui.js'])->withFullUrl() !!}
    {!! Minify::javascript(['//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js']) !!}

    // minify and combine all javascript files in given folder
    {!! Minify::javascriptDir('/js/') !!}
    // add custom attributes to minify and combine all javascript files in given folder
    {!! Minify::javascriptDir('/js/', ['bar' => 'baz', 'async' => true]) !!}
    // minify and combine all javascript files in given folder with full uri
    {!! Minify::javascriptDir('/js/')->withFullUrl() !!}
</html>
```
