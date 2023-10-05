<?php

namespace App\Console\Commands;

use App\Services\Documentation;
use Illuminate\Console\Command;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Sigmie\Application\Client;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;
use Masterminds\HTML5;

class IndexDocumentationToSigmie extends Command
{
    protected $signature = 'docs:index';

    protected $description = 'Index Documentation';

    public function handle()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension);

        $converter = new MarkdownConverter($environment);

        $sigmie = new Client(
            applicationId: config('services.sigmie.application_id'),
            apiKey: config('services.sigmie.admin_api_key')
        );

        $documentation = new Documentation($converter);

        $progress = new ProgressBar($this->output);

        $documentation->eachPage(function ($file) use ($sigmie, $converter, $documentation, $progress) {

            $page = str_replace('.md', '', basename($file));

            $progress->advance();

            $fileContent = file_get_contents($file);
            $document = $converter->convert($fileContent);

            $html5 = new HTML5(['encode_entities' => true]);
            $dom = $html5->loadHTML(mb_encode_numericentity($document, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

            $headings = [];
            $body = [];
            $version = 'v0';

            $link = collect(config("docs.{$version}.navigation"))
                ->flatten(2)
                ->filter(fn ($link)  => isset($link['href']))
                ->filter(fn ($link)  => $link['href'] === "/docs/{$version}/{$page}")
                ->first();

            if (is_null($link)) {
                return;
            }

            foreach ($dom->getElementsByTagName('h1') as $node) {
                $headings[] = $node->nodeValue;
            }

            foreach ($dom->getElementsByTagName('p') as $node) {
                $value = $node->nodeValue;
                $value = str_replace('@info', '', $value);
                $value = str_replace('@endinfo', '', $value);

                $body[] = $value;
            }

            if (count($body) === 0) {
                return;
            }

            $sigmie->upsertDocument(
                index: 'sigmie-com-docs',
                body: [
                    'headings' => $headings,
                    'body' => $body,
                    ...$link
                ],
                _id: md5($file)
            );
        });
    }
}
