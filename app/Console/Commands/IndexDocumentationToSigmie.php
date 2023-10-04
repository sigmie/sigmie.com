<?php

namespace App\Console\Commands;

use App\Services\Documentation;
use Illuminate\Console\Command;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Sigmie\Application\Client;
use Symfony\Component\Console\Helper\ProgressBar;

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

            $progress->advance();

            $markdown = file_get_contents($file);
            $document = $converter->convert($markdown);

            $dom = new \DOMDocument();
            $dom->loadHTML($document);

            $headings = [];
            $body = [];

            foreach ($dom->getElementsByTagName('h1') as $node) {
                $headings[] = $node->nodeValue;
            }

            foreach ($dom->getElementsByTagName('p') as $node) {
                $body[] = $node->nodeValue;
            }

            $sigmie->upsertDocument(
                index: 'sigmie-com-docs',
                body: [
                    'path' => $file,
                    'headings' => $headings,
                    'body' => $body,
                ],
                _id: md5($file)
            );
        });
    }
}
