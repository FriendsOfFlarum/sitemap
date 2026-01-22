# Memory Stress Tests

## Overview

The `MemoryStressTest.php` file contains stress tests designed to verify that sitemap generation can handle large datasets without running out of memory. These tests replicate production scenarios where forums may have 100,000+ discussions.

## Running the Tests

The stress tests are opt-in and controlled by environment variables. By default, they are skipped to avoid slowing down CI/CD pipelines.

### Baseline Test (Always Runs)

```bash
vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/console/MemoryStressTest.php --filter baseline_memory_measurement
```

This establishes a memory usage baseline with minimal data.

### Small Dataset Test (100k discussions, 2 UrlSets)

```bash
SITEMAP_STRESS_TEST_SMALL=1 vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/console/MemoryStressTest.php --filter small_dataset
```

Expected runtime: ~25-35 seconds
Expected memory usage: < 256MB

### Medium Dataset Test (250k discussions, 5 UrlSets)

```bash
SITEMAP_STRESS_TEST_MEDIUM=1 vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/console/MemoryStressTest.php --filter medium_dataset
```

Expected runtime: ~60-90 seconds
Expected memory usage: < 256MB

### Large Dataset Test (1 million discussions, 20 UrlSets)

```bash
SITEMAP_STRESS_TEST_LARGE=1 vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/console/MemoryStressTest.php --filter large_dataset
```

Expected runtime: ~5-10 minutes
Expected memory usage: < 256MB

### Run All Stress Tests

```bash
SITEMAP_STRESS_TEST_SMALL=1 \
SITEMAP_STRESS_TEST_MEDIUM=1 \
SITEMAP_STRESS_TEST_LARGE=1 \
vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/console/MemoryStressTest.php
```

## Understanding the Tests

### What They Test

1. **Memory Efficiency**: Verifies that memory usage stays within acceptable limits (256MB) even with large datasets
2. **Correctness**: Ensures sitemap generation produces valid XML and correct URL counts
3. **Performance**: Measures how the system handles datasets that require multiple UrlSet files

### How They Work

1. **Data Generation**: Uses batch inserts to efficiently create large numbers of discussions and posts
2. **Memory Tracking**: Measures peak memory usage before and after sitemap generation
3. **Validation**: Verifies generated sitemaps are valid and contain the expected data

### Memory Limit

Tests enforce a 256MB memory limit. If memory usage exceeds this threshold, the test fails with a detailed message showing actual memory used.

## Interpreting Results

### Successful Test

```
OK (1 test, 15 assertions)
```

Indicates:
- Sitemap generation completed without errors
- Memory usage stayed within limits
- Generated sitemaps are valid XML
- Expected number of sitemap files were created

### Failed Test (Memory Exceeded)

```
Memory usage (512.45MB) exceeded limit for 500000 discussions
```

This indicates a memory leak or inefficient memory management that needs to be addressed.

### Failed Test (Invalid Output)

```
XML does not validate against sitemap schema
```

Indicates the generated sitemap is malformed.

## Use Cases

### Before Fixing Memory Issues

Run the stress tests to reproduce the out-of-memory error:

```bash
# This might fail with current code on large datasets
SITEMAP_STRESS_TEST_LARGE=1 vendor/bin/phpunit ... --filter large_dataset
```

### After Fixing Memory Issues

Re-run the same tests to verify the fixes work:

```bash
# Should pass after memory optimizations
SITEMAP_STRESS_TEST_LARGE=1 vendor/bin/phpunit ... --filter large_dataset
```

### Performance Regression Testing

Run periodically (e.g., nightly builds) to catch performance regressions:

```bash
# Add to CI/CD for nightly builds
SITEMAP_STRESS_TEST_SMALL=1 SITEMAP_STRESS_TEST_MEDIUM=1 vendor/bin/phpunit ...
```

## Technical Details

### Batch Insert Strategy

Tests use batch inserts with a batch size of 4,000 records to stay within MySQL's prepared statement placeholder limit (65,535). Each batch inserts discussions and posts efficiently.

### Foreign Key Handling

The circular foreign key dependency between `discussions.first_post_id` and `posts.discussion_id` is handled by:
1. Temporarily disabling foreign key checks
2. Inserting both tables
3. Updating `first_post_id` with a bulk UPDATE statement
4. Re-enabling foreign key checks

This approach follows MySQL best practices for bulk data insertion.

### Data Characteristics

- Each discussion has exactly 1 post
- Users and tags are excluded via settings
- Dates cycle through a 365-day period
- Minimal data per record to focus on volume

## Troubleshooting

### Test Timeout

If tests timeout, increase PHPUnit's timeout or reduce dataset size for your environment.

### MySQL Memory

Large batch inserts may require adequate MySQL buffer pool size. Check your MySQL configuration if you see database-related errors.

### Disk Space

Large datasets require disk space for:
- Test database storage
- Generated sitemap files in `public/sitemaps/`

Ensure adequate free space before running large tests.
