# Sitemap by FriendsOfFlarum
[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/FriendsOfFlarum/sitemap/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![Total Downloads](https://img.shields.io/packagist/dt/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

This extension simply adds a sitemap to your forum.

It uses default entries like Discussions and Users, but is also smart enough to conditionally add further entries
based on the availability of extensions. This currently applies to flarum/tags and fof/pages. Other extensions
can easily inject their own Resource information, check Extending below.

## Modes

There are two modes to use the sitemap.

### Runtime mode

After enabling the extension the sitemap will automatically be available and generated on the fly. It contains
all Users, Discussions, Tags and Pages guests have access to.

_Applicable to small forums, most likely on shared hosting environments, with discussions, users, tags and pages summed
up being less than **10.000 items**._

### Cached mode

For larger forums you can set up a cron job that generates a sitemap index and compressed sitemap files. Remember that after first enabling cache mode, you must either wait for the sitemaps to build.

A rebuild can be triggered at any time by using:

```
php flarum fof:sitemap:build
```

This command creates temporary files in your storage folder and if successful moves them over to the public
directory automatically.

_Best for larger forums, starting at 50.000 items._

## Extending

### Register a new Resource

In order to register your own resource, create a class that implements `FoF\Sitemap\Resources\Resource`. Make sure
to implement all abstract methods, check other implementations for examples. After this, register your 

```php
return [
    new \FoF\Sitemap\Extend\RegisterResource(YourResource::class),
];
```
That's it.

### Remove a Resource

In a very similar way, you can also remove resources from the sitemap:
```php
return [
    (new \FoF\Sitemap\Extend\RemoveResource(\FoF\Sitemap\Resources\Tag::class)),
];
```

### Force cache mode

If you wish to force the use of cache mode, for example in complex hosted environments, this can be done by calling the extender:
```php
return [
    (new \FoF\Sitemap\Extend\ForceCached()),
]
```
## Scheduling

Consider setting up the Flarum scheduler, which removes the requirement to setup a cron job as advised above. Read more information about this [here](https://discuss.flarum.org/d/24118)

## Commissioned

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Installation

Install manually with composer:

```bash
composer require fof/sitemap
```

## Updating

```bash
composer update fof/sitemap
php flarum migrate
php flarum cache:clear
```

## Nginx issues

If you are using nginx and accessing `/sitemap.xml` results in an nginx 404 page, you can add the following rule to your configuration file, underneath your existing `location` rule:

```
location = /sitemap.xml {
    try_files $uri $uri/ /index.php?$query_string;
}
```

This rule makes sure that Flarum will answer the request for `/sitemap.xml` when no file exists with that name.

## Links

- [![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)
- [Flarum Discuss post](https://discuss.flarum.org/d/14941)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/sitemap)
- [Report an issue](https://github.com/FriendsOFflarum/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/fof/sitemap)
