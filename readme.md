Providers Collections
====

## Install

```
composer require fenom/providers-collection
```

## Multi directories template provider

Provider loads template from the filesystem.
Provider supports multiple directories where to look for templates.


Create and configure provider

```
$provider = new Fenom\MultiPathProvider([$path1, $path2]);
$provider->addPath($path3);
$provider->prependPath($path4);
```

Use the provider to create the object Fenom

```
$fenom = new Fenom($provider);
```

Use Fenom.