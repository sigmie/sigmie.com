<?php

namespace App\Indices;

use DateTime;
use Sigmie\Base\Contracts\ElasticsearchConnection;
use Sigmie\Document\Document;
use Sigmie\Indices\Index;
use Sigmie\Mappings\NewProperties;
use Sigmie\SigmieIndex;

class AsosProducts extends AbstractIndex
{
    public function name(): string
    {
        return 'asos_products';
    }

    public function csvPath(): string
    {
        return storage_path('app/datasets/asos_products.csv');
    }

    public function alias(): string
    {
        return 'asos_products';
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        // show_id,type,title,director,cast,country,date_added,release_year,rating,duration,listed_in,description
        $properties->name('name');
        $properties->category('size');
        $properties->category('category')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->price('price');
        $properties->category('color')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->keyword('sku');
        $properties->longText('description')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->image('images');

        return $properties;
    }

    public function toDocuments(array $row): array
    {
        preg_match_all('/https:\/\/[^\s\'"]+/', $row['images'], $matches);
        $images = $matches[0] ?? [];

        return [
            new Document([
                'name' => $row['name'],
                'size' => $row['size'],
                'category' => $row['category'],
                'price' => (float) $row['price'],
                'color' => $row['color'],
                'sku' => $row['sku'],
                'description' => $row['description'],
                'images' => $images,
            ], _id: $row['id'] ?? null),
        ];
    }
}
