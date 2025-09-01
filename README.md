# Sitemap by FriendsOfFlarum
[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/FriendsOfFlarum/sitemap/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![Total Downloads](https://img.shields.io/packagist/dt/fof/sitemap.svg)](https://packagist.org/packages/fof/sitemap) [![OpenCollective](https://img.shields.io/badge/opencollective-fof-blue.svg)](https://opencollective.com/fof/donate)

This extension simply adds a sitemap to your forum.

It uses default entries like Discussions and Users, but is also smart enough to conditionally add further entries
based on the availability of extensions. This currently applies to flarum/tags and fof/pages. Other extensions
can easily inject their own Resource information, check Extending below.

## Modes

There are two modes to use the sitemap, both now serving content from the main domain for search engine compliance.

### Runtime mode

After enabling the extension the sitemap will automatically be available at `/sitemap.xml` and generated on the fly.
Individual sitemap files are served at `/sitemap-1.xml`, `/sitemap-2.xml`, etc.
It contains all Users, Discussions, Tags and Pages guests have access to.

_Applicable to small forums, most likely on shared hosting environments, with discussions, users, tags and pages summed
up being less than **10,000 items**.
This is not a hard limit, but performance will be degraded as the number of items increase._

### Cached multi-file mode

For larger forums, sitemaps are automatically generated and updated via the Flarum scheduler.
Sitemaps are stored on your configured storage (local disk, S3, CDN) but always served from your main domain
to ensure search engine compliance. Individual sitemaps are accessible at `/sitemap-1.xml`, `/sitemap-2.xml`, etc.

A first sitemap will be automatically generated after the setting is changed. Subsequent updates are handled automatically by the scheduler (see Scheduling section below).

A rebuild can be manually triggered at any time by using:

```
php flarum fof:sitemap:build
```

_Best for larger forums, starting at 10,000 items._

### Risky Performance Improvements

_This setting is meant for large enterprise customers._

The optional "Enable risky performance improvements" option modifies the discussion and user SQL queries to limit the number of columns returned.
By removing those columns, it significantly reduces the size of the database response but might break custom visibility scopes or slug drivers added by extensions.

This setting only brings noticeable improvements if you have millions of discussions or users.
We recommend not enabling it unless the CRON job takes more than an hour to run or that the SQL connection gets saturated by the amount of data.

## Search Engine Compliance

This extension automatically ensures search engine compliance by:

- **Domain consistency**: All sitemaps are served from your main forum domain, even when using external storage (S3, CDN)
- **Unified URLs**: Consistent URL structure (`/sitemap.xml`, `/sitemap-1.xml`) regardless of storage backend
- **Automatic proxying**: When external storage is detected, content is automatically proxied through your main domain

This means you can use S3 or CDN storage for performance while maintaining full Google Search Console compatibility.

## Scheduling

The extension automatically registers with the Flarum scheduler to update cached sitemaps.
This removes the need for manual intervention once configured.
Read more information about setting up the Flarum scheduler [here](https://discuss.flarum.org/d/24118).

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

If you are using nginx and accessing `/sitemap.xml` or individual sitemap files (e.g., `/sitemap-1.xml`) results in an nginx 404 page, you can add the following rules to your configuration file:

```nginx
# FoF Sitemap — Flarum handles everything
location = /sitemap.xml {
    rewrite ^ /index.php?$query_string last;
    add_header Cache-Control "max-age=0";
}

location ^~ /sitemap- {
    rewrite ^ /index.php?$query_string last;
    add_header Cache-Control "max-age=0";
}
```

These rules ensure that Flarum will handle sitemap requests when no physical files exist.

## Extending

### Using the Unified Sitemap Extender (Recommended)

The recommended way to extend the sitemap is using the unified `Sitemap` extender, which allows method chaining and follows Flarum's common extender patterns:

```php
use FoF\Sitemap\Extend;

return [
    (new Extend\Sitemap())
        ->addResource(YourCustomResource::class)
        ->removeResource(\FoF\Sitemap\Resources\Tag::class)
        ->replaceResource(\FoF\Sitemap\Resources\User::class, YourCustomUserResource::class)
        ->addStaticUrl('reviews.index')
        ->addStaticUrl('custom.page')
        ->forceCached(),
];
```

#### Available Methods

- **`addResource(string $resourceClass)`**: Add a custom resource to the sitemap
- **`removeResource(string $resourceClass)`**: Remove an existing resource from the sitemap
- **`replaceResource(string $oldResourceClass, string $newResourceClass)`**: Replace an existing resource with a new one
- **`addStaticUrl(string $routeName)`**: Add a static URL by route name
- **`forceCached()`**: Force cached mode for managed hosting environments

### Register a New Resource

Create a class that extends `FoF\Sitemap\Resources\Resource` and implement all abstract methods:

```php
use FoF\Sitemap\Resources\Resource;
use FoF\Sitemap\Sitemap\Frequency;

class YourCustomResource extends Resource
{
    public function query(): Builder
    {
        return YourModel::query()->where('is_public', true);
    }

    public function url($model): string
    {
        return $this->generateRouteUrl('your.route', ['id' => $model->id]);
    }

    public function priority(): float
    {
        return 0.7;
    }

    public function frequency(): string
    {
        return Frequency::WEEKLY;
    }

    public function lastModifiedAt($model): Carbon
    {
        return $model->updated_at ?? $model->created_at;
    }
}
```

Then register it using the unified extender:

```php
return [
    (new \FoF\Sitemap\Extend\Sitemap())
        ->addResource(YourCustomResource::class),
];
```

#### Dynamic Priority and Frequency (Optional)

Your custom resource can optionally implement dynamic priority and frequency values based on the actual model data:

```php
class YourResource extends Resource
{
    // Required abstract methods...
    
    /**
     * Optional: Dynamic frequency based on model activity
     */
    public function dynamicFrequency($model): ?string
    {
        $lastActivity = $model->updated_at ?? $model->created_at;
        $daysSinceActivity = $lastActivity->diffInDays(now());
        
        if ($daysSinceActivity < 1) return Frequency::HOURLY;
        if ($daysSinceActivity < 7) return Frequency::DAILY;
        if ($daysSinceActivity < 30) return Frequency::WEEKLY;
        return Frequency::MONTHLY;
    }
    
    /**
     * Optional: Dynamic priority based on model importance
     */
    public function dynamicPriority($model): ?float
    {
        // Example: Higher priority for more popular content
        $popularity = $model->view_count ?? 0;
        
        if ($popularity > 1000) return 1.0;
        if ($popularity > 100) return 0.8;
        return 0.5;
    }
}
```

If these methods return `null` or are not implemented, the static `frequency()` and `priority()` methods will be used instead. This ensures full backward compatibility with existing extensions.

### Remove a Resource

Remove existing resources from the sitemap:

```php
return [
    (new \FoF\Sitemap\Extend\Sitemap())
        ->removeResource(\FoF\Sitemap\Resources\Tag::class),
];
```

### Replace a Resource

Replace an existing resource with a custom implementation. This is useful when you want to modify the behavior of a built-in resource:

```php
return [
    (new \FoF\Sitemap\Extend\Sitemap())
        ->replaceResource(\FoF\Sitemap\Resources\User::class, YourCustomUserResource::class),
];
```

**Example Use Cases for `replaceResource`:**

1. **Custom User Resource**: Replace the default user resource to change URL structure or filtering logic
2. **Enhanced Discussion Resource**: Replace the discussion resource to add custom metadata or different priority calculations
3. **Modified Tag Resource**: Replace the tag resource to change how tags are included or prioritized

```php
// Example: Replace the default User resource with a custom one
class CustomUserResource extends \FoF\Sitemap\Resources\User
{
    public function query(): Builder
    {
        // Only include users with profile pictures
        return parent::query()->whereNotNull('avatar_url');
    }
    
    public function url($model): string
    {
        // Use a custom URL structure
        return $this->generateRouteUrl('user.profile', ['username' => $model->username]);
    }
    
    public function priority(): float
    {
        // Higher priority for users
        return 0.8;
    }
}

return [
    (new \FoF\Sitemap\Extend\Sitemap())
        ->replaceResource(\FoF\Sitemap\Resources\User::class, CustomUserResource::class),
];
```

### Register Static URLs

Add static URLs to the sitemap by specifying route names:

```php
return [
    (new \FoF\Sitemap\Extend\Sitemap())
        ->addStaticUrl('reviews.index')
        ->addStaticUrl('custom.page'),
];
```

### Force Cache Mode

Force the use of cache mode for managed hosting environments:

```php
return [
    (new \FoF\Sitemap\Extend\Sitemap())
        ->forceCached(),
];
```

### Legacy Extenders (Deprecated)

The following extenders are still supported for backwards compatibility but are deprecated and will be removed in Flarum 2.0. Please migrate to the unified `Sitemap` extender.

#### Register a Resource (Legacy)
```php
return [
    new \FoF\Sitemap\Extend\RegisterResource(YourResource::class), // Deprecated
];
```

#### Remove a Resource (Legacy)
```php
return [
    new \FoF\Sitemap\Extend\RemoveResource(\FoF\Sitemap\Resources\Tag::class), // Deprecated
];
```

#### Register Static URL (Legacy)
```php
return [
    new \FoF\Sitemap\Extend\RegisterStaticUrl('reviews.index'), // Deprecated
];
```

#### Force Cached Mode (Legacy)
```php
return [
    new \FoF\Sitemap\Extend\ForceCached(), // Deprecated
];
```

## Optional Sitemap Elements

The extension allows you to control whether `<priority>` and `<changefreq>` elements are included in your sitemap:

### Admin Settings

- **Include priority values**: Priority values are ignored by Google but may be used by other search engines like Bing and Yandex
- **Include change frequency values**: Change frequency values are ignored by Google but may be used by other search engines for crawl scheduling

Both settings are enabled by default for backward compatibility.

### Dynamic Values

When enabled, the extension uses intelligent frequency calculation based on actual content activity:

- **Discussions**: Frequency based on last post date (hourly for active discussions, monthly for older ones)
- **Users**: Frequency based on last seen date (weekly for active users, yearly for inactive ones)
- **Static content**: Uses predefined frequency values

This provides more meaningful information to search engines compared to static values.

## Troubleshooting

### Regenerating Sitemaps

If you've updated the extension or changed storage settings, you may need to regenerate your sitemaps:

```bash
php flarum fof:sitemap:build
```

### Debug Logging

When Flarum is in debug mode, the extension provides detailed logging showing:
- Whether sitemaps are being generated on-the-fly or served from storage
- When content is being proxied from external storage
- Route parameter extraction and request handling
- Any issues with sitemap generation or serving

Check your Flarum logs (`storage/logs/`) for detailed information about sitemap operations.

## Commissioned

The initial version of this extension was sponsored by [profesionalreview.com](https://www.profesionalreview.com/).

## Links

- [![OpenCollective](https://img.shields.io/badge/donate-friendsofflarum-44AEE5?style=for-the-badge&logo=open-collective)](https://opencollective.com/fof/donate)
- [Flarum Discuss post](https://discuss.flarum.org/d/14941)
- [Source code on GitHub](https://github.com/FriendsOfFlarum/sitemap)
- [Report an issue](https://github.com/FriendsOFflarum/sitemap/issues)
- [Download via Packagist](https://packagist.org/packages/fof/sitemap)
