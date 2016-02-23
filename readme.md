Providers Collections
====

[![Latest Stable Version](https://poser.pugx.org/fenom/providers-collection/v/stable)](https://packagist.org/packages/fenom/providers-collection) [![Total Downloads](https://poser.pugx.org/fenom/providers-collection/downloads)](https://packagist.org/packages/fenom/providers-collection) [![Latest Unstable Version](https://poser.pugx.org/fenom/providers-collection/v/unstable)](https://packagist.org/packages/fenom/providers-collection) [![License](https://poser.pugx.org/fenom/providers-collection/license)](https://packagist.org/packages/fenom/providers-collection)

## Install

```
composer require fenom/providers-collection
```

## Multi directories template provider

Provider loads template from the filesystem.
Provider supports multiple directories where to look for templates.


Create and configure provider

```php
$provider = new Fenom\MultiPathProvider([$path1, $path2]);
$provider->addPath($path3);
$provider->prependPath($path4);
```

Use the provider to create the object Fenom

```php
$fenom = new Fenom($provider);
```

Use Fenom.