<?php

namespace FoF\Sitemap\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Flarum\Extend\ExtenderInterface;

/**
 * Extender for customizing robots.txt generation.
 * 
 * This extender allows extensions to add, remove, or replace robots.txt entries,
 * enabling flexible customization of the robots.txt file.
 * 
 * @example
 * // In your extension's extend.php:
 * (new \FoF\Sitemap\Extend\Robots())
 *     ->addEntry(MyCustomRobotsEntry::class)
 *     ->removeEntry(\FoF\Sitemap\Robots\Entries\ApiEntry::class)
 *     ->replace(\FoF\Sitemap\Robots\Entries\AdminEntry::class, MyCustomAdminEntry::class)
 */
class Robots implements ExtenderInterface
{
    /** @var array List of entry classes to add */
    private array $entriesToAdd = [];
    
    /** @var array List of entry classes to remove */
    private array $entriesToRemove = [];
    
    /** @var array List of entry classes to replace [old => new] */
    private array $entriesToReplace = [];

    /**
     * Add a robots.txt entry.
     * 
     * The entry class must extend RobotsEntry and implement the getRules() method.
     * 
     * @param string $entryClass Fully qualified class name of the entry
     * @return self For method chaining
     * @throws \InvalidArgumentException If the entry class is invalid
     */
    public function addEntry(string $entryClass): self
    {
        $this->validateEntry($entryClass);
        $this->entriesToAdd[] = $entryClass;
        return $this;
    }

    /**
     * Remove a robots.txt entry.
     * 
     * This can be used to remove default entries or entries added by other extensions.
     * 
     * @param string $entryClass Fully qualified class name of the entry to remove
     * @return self For method chaining
     */
    public function removeEntry(string $entryClass): self
    {
        $this->entriesToRemove[] = $entryClass;
        return $this;
    }

    /**
     * Replace a robots.txt entry with another entry.
     * 
     * This allows you to replace default entries or entries from other extensions
     * with your own custom implementations.
     * 
     * @param string $oldEntryClass Fully qualified class name of the entry to replace
     * @param string $newEntryClass Fully qualified class name of the replacement entry
     * @return self For method chaining
     * @throws \InvalidArgumentException If either entry class is invalid
     */
    public function replace(string $oldEntryClass, string $newEntryClass): self
    {
        $this->validateEntry($newEntryClass);
        $this->entriesToReplace[$oldEntryClass] = $newEntryClass;
        return $this;
    }

    /**
     * Apply the extender configuration to the container.
     * 
     * @param Container $container The service container
     * @param Extension|null $extension The extension instance
     */
    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend('fof-sitemap.robots.entries', function (array $entries) {
            // Replace entries first
            foreach ($this->entriesToReplace as $oldEntry => $newEntry) {
                $key = array_search($oldEntry, $entries);
                if ($key !== false) {
                    $entries[$key] = $newEntry;
                }
            }

            // Remove entries
            foreach ($this->entriesToRemove as $entryToRemove) {
                $entries = array_filter($entries, fn($entry) => $entry !== $entryToRemove);
            }

            // Add new entries
            foreach ($this->entriesToAdd as $entryToAdd) {
                if (!in_array($entryToAdd, $entries)) {
                    $entries[] = $entryToAdd;
                }
            }

            return array_values($entries);
        });
    }

    /**
     * Validate that an entry class is valid.
     * 
     * @param string $entryClass The entry class to validate
     * @throws \InvalidArgumentException If the class is invalid
     */
    private function validateEntry(string $entryClass): void
    {
        if (!class_exists($entryClass)) {
            throw new \InvalidArgumentException("Robots entry class {$entryClass} does not exist");
        }

        if (!is_subclass_of($entryClass, \FoF\Sitemap\Robots\RobotsEntry::class)) {
            throw new \InvalidArgumentException("Robots entry class {$entryClass} must extend RobotsEntry");
        }
    }
}
