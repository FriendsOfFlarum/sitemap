# Breaking Changes

## `DeployInterface::storeSet()` — signature change

### What changed

The second parameter of `DeployInterface::storeSet()` has changed from `string` to a PHP **stream resource** (`resource`).

**Before:**
```php
public function storeSet($setIndex, string $set): ?StoredSet;
```

**After:**
```php
public function storeSet(int $setIndex, $stream): ?StoredSet;
```

The first parameter type has also been tightened from untyped to `int`.

### Why

Previously, the generator built each 50,000-URL sitemap set as a string by:

1. Accumulating up to 50,000 `Url` objects in `UrlSet::$urls[]` (~15–20 MB of PHP heap per set).
2. Calling `XMLWriter::outputMemory()` at the end, which returned the full XML blob as a single PHP string (~40 MB for a full set).
3. Passing that string to `storeSet()`.

On a production forum with 700k users and 600k discussions this resulted in peak allocations of 40 MB or more in a single `outputMemory()` call, OOM-killing the PHP process:

```
PHP Fatal error: Allowed memory size of 536870912 bytes exhausted
  (tried to allocate 41797944 bytes) in .../Sitemap/UrlSet.php on line 64
```

The root cause is architectural: materialising the entire XML payload as a PHP string is unnecessary when the destination is a filesystem or cloud storage that can consume a stream directly.

**The fix:** `UrlSet` now writes each URL entry to an XMLWriter whose buffer is flushed every 500 entries into a `php://temp` stream (memory-backed up to 2 MB, then auto-spilling to a kernel-managed temp file). When a set is full, `UrlSet::stream()` returns the rewound stream resource, which `Generator` passes directly to `storeSet()`. The deploy backend passes it on to Flysystem's `put()` method, which accepts a resource and streams it to the destination without ever creating a full string copy in PHP.

**Memory savings per sitemap set (50,000 URLs):**

| Before | After |
|--------|-------|
| ~15–20 MB — `Url[]` object array | 0 — no object array; entries written immediately |
| ~40 MB — `outputMemory()` string | ~few KB — XMLWriter buffer flushed every 500 entries |
| ~40 MB — string passed to `storeSet()` | 0 — stream resource passed, no string copy |
| **~95–100 MB peak per set** | **<5 MB peak per set** |

For a forum with 1.3 M records split across 26 sets this means the difference between reliably completing within a 512 MB container and OOM-crashing on every run.

### How to update third-party deploy backends

If you have implemented `DeployInterface` in your own extension, you need to update `storeSet()` to accept and consume a stream resource instead of a string.

#### Option 1 — Read the stream into a string (simplest, functionally equivalent to before)

Use this only if your backend has no stream-aware API. It will materialise the string in memory the same way as before, so it does not benefit from the memory reduction.

```php
public function storeSet(int $setIndex, $stream): ?StoredSet
{
    $xml = stream_get_contents($stream);
    // ... use $xml as before
}
```

#### Option 2 — Pass the stream directly to a stream-aware storage API (recommended)

Flysystem v3 (used by Flarum 1.x and later), AWS SDK, GCS SDK, and most modern storage libraries accept a resource handle directly, avoiding any string copy.

**Flysystem / Laravel filesystem:**
```php
public function storeSet(int $setIndex, $stream): ?StoredSet
{
    $path = "sitemap-$setIndex.xml";
    $this->storage->put($path, $stream); // Flysystem accepts a resource
    // ...
}
```

**AWS SDK (direct, not via Flysystem):**
```php
public function storeSet(int $setIndex, $stream): ?StoredSet
{
    $this->s3->putObject([
        'Bucket' => $this->bucket,
        'Key'    => "sitemap-$setIndex.xml",
        'Body'   => $stream, // AWS SDK accepts a stream
    ]);
    // ...
}
```

**GCS / Google Cloud Storage:**
```php
public function storeSet(int $setIndex, $stream): ?StoredSet
{
    $this->bucket->upload($stream, [
        'name' => "sitemap-$setIndex.xml",
    ]);
    // ...
}
```

#### Important: do NOT close the stream

The stream is owned by the `Generator` and will be closed with `fclose()` after `storeSet()` returns. Your implementation must not close it.

#### Important: stream position

`UrlSet::stream()` rewinds the stream to position 0 before returning it. The stream will always be at the beginning when your `storeSet()` receives it — you do not need to `rewind()` it yourself.

### What the built-in backends do

| Backend | Strategy |
|---------|----------|
| `Disk` | Passes the stream resource directly to `Flysystem\Cloud::put()`. Zero string copy. |
| `ProxyDisk` | Same as `Disk`. Zero string copy. |
| `Memory` | Calls `stream_get_contents($stream)` and stores the resulting string in its in-memory cache. This is intentional: the `Memory` backend is designed for small/development forums where the full sitemap fits in RAM. It is not recommended for production forums with large datasets. |

### `UrlSet` public API changes

`UrlSet::$urls` (public array) and `UrlSet::toXml(): string` have been removed. They were the primary source of memory pressure and are replaced by the streaming API:

| Removed | Replacement |
|---------|-------------|
| `public array $urls` | No replacement — URLs are written to the stream immediately and not stored |
| `public function toXml(): string` | `public function stream(): resource` — returns rewound php://temp stream |

The `add(Url $url)` and `addUrl(...)` methods retain the same signatures. A new `count(): int` method is available to query how many URLs have been written without exposing the underlying array.

If you were calling `$urlSet->toXml()` or reading `$urlSet->urls` directly in custom code, migrate to the stream API:

```php
// Before
$xml = $urlSet->toXml();
file_put_contents('/path/to/sitemap.xml', $xml);

// After
$stream = $urlSet->stream();
file_put_contents('/path/to/sitemap.xml', stream_get_contents($stream));
fclose($stream);

// Or stream directly to a file handle (zero copy):
$fh = fopen('/path/to/sitemap.xml', 'wb');
stream_copy_to_stream($urlSet->stream(), $fh);
fclose($fh);
```
