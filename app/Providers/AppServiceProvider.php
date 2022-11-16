<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use Torchlight\Block;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\MarkdownConverter;
use Torchlight\Commonmark\V2\TorchlightExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MarkdownConverter::class, function () {
            $environment = new Environment();
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new TableExtension);
            $environment->addExtension(new HeadingPermalinkExtension);
            $environment->addExtension(new SmartPunctExtension);
            $environment->addExtension(new TableOfContentsExtension);
            $environment->addExtension(new TorchlightExtension);

            $converter = new MarkdownConverter($environment);

            return $converter;
        });
    }
}
