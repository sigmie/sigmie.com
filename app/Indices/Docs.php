<?php

declare(strict_types=1);

namespace App\Indices;

use Sigmie\Document\Document;
use Sigmie\Mappings\NewProperties;

class Docs extends AbstractIndex
{
    public function name(): string
    {
        return 'docs';
    }

    public function csvPath(): string
    {
        return ''; // Not used for docs
    }

    public function alias(): string
    {
        return 'docs';
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        $properties->name('title')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->keyword('version');
        $properties->keyword('page');
        $properties->text('url');
        $properties->longText('content')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->text('headings')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');

        return $properties;
    }

    public function toDocuments(array $data): array
    {
        return [
            new Document([
                'title' => $data['title'] ?? '',
                'version' => $data['version'] ?? '',
                'page' => $data['page'] ?? '',
                'url' => $data['url'] ?? '',
                'content' => $data['content'] ?? '',
                'headings' => $data['headings'] ?? [],
            ], _id: $data['_id'] ?? null),
        ];
    }
}
