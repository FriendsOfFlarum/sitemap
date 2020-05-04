# Sitemap by ![Flagrow logo](https://avatars0.githubusercontent.com/u/16413865?v=3&s=20) [Flagrow](https://discuss.flarum.org/d/1832-flagrow-extension-developer-group), a project of [Gravure](https://gravure.io/)

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/flagrow/sitemap/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/flagrow/sitemap.svg)](https://packagist.org/packages/flagrow/sitemap) [![Total Downloads](https://img.shields.io/packagist/dt/flagrow/sitemap.svg)](https://packagist.org/packages/flagrow/sitemap) [![Support Us](https://img.shields.io/badge/flagrow.io-support%20us-yellow.svg)](https://flagrow.io/support-us) [![Join our Discord server](https://discordapp.com/api/guilds/240489109041315840/embed.png)](https://flagrow.io/join-discord)

This extension simply adds a sitemap to your forum.

It uses default entries like Discussions and Users, but is also smart enough to conditionally add further entries
based on the availability of extensions. This currently applies to flarum/tags and fof/pages. Other extensions
can easily inject their own Resource information, check Extending below.

There are several modes to use the sitemap.

### Runtime mode

After enabling the extension the sitemap will be automatically be available and generated on the fly. It contains
all Users, Discussions, Tags and Pages guests have access to.

_Applicable to small forums, most likely on shared hosting environments, with discussions, users, tags and pages summed
up being less than **10.000 items**._

### Cache or disk mode

You can set up a cron job that stores the sitemap into cache or onto disk. You need to run:

```
php flarum fof:sitemap:cache
```

To store the sitemap into cache. If you want to save the sitemap directly to your public folder, use the flag:

```
php flarum fof:sitemap:cache --write-xml-file
```

_Best for small forums, most likely on hosting environments allowing cronjobs and with discussions, users, tags and pages summed
up being less than **50.000 items**._

> 50.000 is the technical limit for sitemap files. If you have more entries to store, use the following option!

### Multi file mode

For larger forums you can set up a cron job that generates a sitemap index and compressed sitemap files.

```
php flarum fof:sitemap:multi
```

This command creates temporary files in your storage folder and if successful moves them over to the public
directory automatically.

_Best for larger forums, starting at 50.000 items._

## Extending

In order to register your own resource, create a class that implements `FoF\Sitemap\Resources\Resource`. Make sure
to implement all abstract methods, check other implementations for examples. After this, register your 

```php
return [
    new \FoF\Sitemap\Extend\RegisterResource(YourResource::class)
];
```
That's it.

## Commissioned

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Installation

Use [Bazaar](https://discuss.flarum.org/d/5151) or install manually:

```bash
composer require fof/sitemap
```

## Updating

```bash
composer update fof/sitemap
php flarum migrate
php flarum cache:clear
```

## Support our work

Check out how to support Flagrow extensions at [flagrow.io/support-us](https://flagrow.io/support-us).

## Security

If you discover a security vulnerability within Sitemap, please send an email to the Gravure team at security@gravure.io. All security vulnerabilities will be promptly addressed.

Please include as many details as possible. You can use `php flarum info` to get the PHP, Flarum and extension versions installed.

## Links

- [Flarum Discuss post](https://discuss.flarum.org/d/14941)
- [Source code on GitHub](https://github.com/FriendsOFlarum/sitemap)
- [Report an issue](https://github.com/FriendsOFlarum/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/fof/sitemap)
