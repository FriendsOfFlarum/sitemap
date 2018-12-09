<?php

namespace Flagrow\Sitemap;

use Carbon\Carbon;
use Flagrow\Sitemap\Sitemap\Frequency;
use Flagrow\Sitemap\Sitemap\UrlSet;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Application;
use Flarum\Tags\Tag;
use Flarum\User\Guest;
use Flarum\User\User;
use Sijad\Pages\Page;

class SitemapGenerator
{
    protected $app;
    protected $extensions;

    public function __construct(Application $app, ExtensionManager $extensions)
    {
        $this->app = $app;
        $this->extensions = $extensions;
    }

    public function getUrlSet()
    {
        $urlSet = new UrlSet();

        $url = $this->app->url();

        $urlSet->addUrl($url . '/', Carbon::now(), Frequency::DAILY, 0.9);

        /**
         * @var $users User[]
         */
        $users = User::whereVisibleTo(new Guest())->get();

        foreach ($users as $user) {
            $urlSet->addUrl($url . '/u/' . $user->username, Carbon::now(), Frequency::DAILY, 0.5);
        }

        /**
         * @var $discussions Discussion[]
         */
        $discussions = Discussion::whereVisibleTo(new Guest())->get();

        foreach ($discussions as $discussion) {
            $urlSet->addUrl($url . '/d/' . $discussion->id . '-' . $discussion->slug, $discussion->last_time, Frequency::DAILY, '0.7');
        }

        if ($this->extensions->isEnabled('flarum-tags') && class_exists(Tag::class)) {
            $tags = Tag::whereVisibleTo(new Guest())->get();

            foreach ($tags as $tag) {
                $urlSet->addUrl($url . '/t/' . $tag->slug, Carbon::now(), Frequency::DAILY, 0.9);
            }
        }

        if ($this->extensions->isEnabled('sijad-pages') && class_exists(Page::class)) {
            $pages = Page::all();

            foreach ($pages as $page) {
                $urlSet->addUrl($url . '/p/' . $page->id . '-' . $page->slug, $page->edit_time, Frequency::DAILY, 0.5);
            }
        }

        return $urlSet;
    }
}
