<?php

namespace App\Indices;

use DateTime;
use Sigmie\Base\Contracts\ElasticsearchConnection;
use Sigmie\Document\Document;
use Sigmie\Indices\Index;
use Sigmie\Mappings\NewProperties;
use Sigmie\SigmieIndex;

class NetflixTitles extends AbstractIndex
{
    public function csvPath(): string
    {
        return storage_path('app/datasets/netflix_titles.csv');
    }

    public function alias(): string
    {
        return 'netflix_titles';
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        // show_id,type,title,director,cast,country,date_added,release_year,rating,duration,listed_in,description
        $properties->category('type')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->name('title')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->name('director')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->name('cast')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->category('country');
        $properties->date('date_added');
        $properties->number('release_year');
        $properties->keyword('rating');
        $properties->category('duration');
        $properties->category('listed_in')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');
        $properties->longText('description')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');

        return $properties;
    }

    public function toDocuments(array $row): array
    {
        $row['date_added'] = trim($row['date_added']);

        $dateAdded = DateTime::createFromFormat('F j, Y', $row['date_added']);

        if ($dateAdded === false) {
            if ($row['date_added'] !== '') {
            }
            // Try parsing with leading/trailing whitespace
            $dateAdded = null;
        }

        return [
            new Document([
                'type' => $row['type'],
                'title' => $row['title'],
                'director' => $row['director'],
                'cast' => $row['cast'],
                'country' => $row['country'],
                'date_added' => $dateAdded?->format('Y-m-d\TH:i:s.uP'),
                'release_year' => $row['release_year'],
            ], _id: $row['show_id']),
        ];
    }
}
