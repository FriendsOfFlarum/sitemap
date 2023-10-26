<?php

/*
 * This file is part of fof/sitemap.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 */

namespace FoF\Sitemap\Listeners;

use Flarum\Frontend\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\Extension\ExtensionManager;
use Illuminate\Support\Arr;

class NoIndexListener
{
    public function __construct(protected ExtensionManager $extensionManager)
    {}

    public function __invoke(Document $document, ServerRequestInterface $request)
    {
        $noIndexTags = [38, 33, 35, 36]; // Add tags here so their discussions include the no-index

        if( ! $this->extensionManager->isEnabled('flarum-tags')){
            return;
        }

        $type = Arr::get($document->getForumApiDocument(), 'data.type');

        if($type != 'discussions'){
            return;
        }
        
        $tags = Arr::get($document->getForumApiDocument(), 'data.relationships.tags.data');

        foreach($tags as $tag){
            if(in_array($tag['id'], $noIndexTags)){
                $document->head[] = '<meta name="robots" content="noindex">';
                return;
            }
        }
    }
}
