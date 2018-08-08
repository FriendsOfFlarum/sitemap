# Sitemap by ![Flagrow logo](https://avatars0.githubusercontent.com/u/16413865?v=3&s=20) [Flagrow](https://discuss.flarum.org/d/1832-flagrow-extension-developer-group), a project of [Gravure](https://gravure.io/)

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/flagrow/sitemap/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/flagrow/sitemap.svg)](https://packagist.org/packages/flagrow/sitemap) [![Total Downloads](https://img.shields.io/packagist/dt/flagrow/sitemap.svg)](https://packagist.org/packages/flagrow/sitemap) [![Support Us](https://img.shields.io/badge/flagrow.io-support%20us-yellow.svg)](https://flagrow.io/support-us) [![Join our Discord server](https://discordapp.com/api/guilds/240489109041315840/embed.png)](https://flagrow.io/join-discord)

This extension simply adds a sitemap to your forum.
It can be accessed at `yourflarum.url/sitemap.xml`.

There's no actual file on the server, the sitemap is generated on the fly and is always up to date.

This extension is compatible with the [Pages](https://discuss.flarum.org/d/2605-pages) extension.

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Installation

Use [Bazaar](https://discuss.flarum.org/d/5151-flagrow-bazaar-the-extension-marketplace) or install manually:

```bash
composer require flagrow/sitemap
```

## Updating

```bash
composer update flagrow/sitemap
php flarum migrate
php flarum cache:clear
```

## Support our work

Check out how to support Flagrow extensions at [flagrow.io/support-us](https://flagrow.io/support-us).

## Security

If you discover a security vulnerability within Sitemap, please send an email to the Gravure team at security@gravure.io. All security vulnerabilities will be promptly addressed.

Please include as many details as possible. You can use `php flarum info` to get the PHP, Flarum and extension versions installed.

## Links

- [Flarum Discuss post](https://discuss.flarum.org/d/14941-flagrow-sitemap)
- [Source code on GitHub](https://github.com/flagrow/sitemap)
- [Report an issue](https://github.com/flagrow/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/flagrow/sitemap)

An extension by [Flagrow](https://flagrow.io/), a project of [Gravure](https://gravure.io/).
