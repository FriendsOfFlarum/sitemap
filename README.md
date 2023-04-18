# Sitemap by FriendsOfFlarum
[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/FriendsOfFlarum/sitemap/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![Total Downloads](https://img.shields.io/packagist/dt/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

This extension simply adds a sitemap to your forum.

It uses default entries like Discussions and Users, but is also smart enough to conditionally add further entries
based on the availability of extensions. This currently applies to flarum/tags and fof/pages. Other extensions
can easily inject their own Resource information, check Extending below.

## Modes

There are two modes to use the sitemap.

### Runtime mode

After enabling the extension the sitemap will automatically be available and generated on the fly.
It contains all Users, Discussions, Tags and Pages guests have access to.

_Applicable to small forums, most likely on shared hosting environments, with discussions, users, tags and pages summed
up being less than **10.000 items**.
This is not a hard limit, but performance will be degraded as the number of items increase._

### Cached multi-file mode

For larger forums you can set up a cron job that generates a sitemap index and compressed sitemap files.
A first sitemap will be automatically generated after the setting is changed, but subsequent updates will have to be triggered either manually or through the scheduler (see below).

A rebuild can be manually triggered at any time by using:

```
php flarum fof:sitemap:build
```

_Best for larger forums, starting at 10.000 items._

### Risky Performance Improvements

_This setting is meant for large enterprise customers._

The optional "Enable risky performance improvements" option modifies the discussion and user SQL queries to limit the number of columns returned.
By removing those columns, it significantly reduces the size of the database response but might break custom visibility scopes or slug drivers added by extensions.

This setting only brings noticeable improvements if you have millions of discussions or users.
We recommend not enabling it unless the CRON job takes more than an hour to run or that the SQL connection gets saturated by the amount of data.

## Scheduling

Consider setting up the Flarum scheduler, which removes the requirement to setup a cron job as advised above.
Read more information about this [here](https://discuss.flarum.org/d/24118)

The frequency setting for the scheduler can be customized via the extension settings page.

## Installation

This extension requires PHP 8.0 or greater.

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

### Register a static URL

Some pages of your forum might not be covered by the default resources. To add those urls to the sitemap there is a
pseudo resource called `StaticUrls`. You can use the `RegisterStaticUrl` extender to add your own urls. The extender
takes a route name as parameter, which will be resolved to a url using the `Flarum\Http\UrlGenerator` class.
```php
return [
    (new \FoF\Sitemap\Extend\RegisterStaticUrl('reviews.index')),
];
```

### Force cache mode

If you wish to force the use of cache mode, for example in complex hosted environments, this can be done by calling the extender:
```php
return [
    (new \FoF\Sitemap\Extend\ForceCached()),
]
```

## Commissioned

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Links

- [![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)
- [Flarum Discuss post](https://discuss.flarum.org/d/14941)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/sitemap)
- [Report an issue](https://github.com/FriendsOFflarum/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/fof/sitemap)
