<?php

namespace App\Indices;

use DateTime;
use Sigmie\Base\Contracts\ElasticsearchConnection;
use Sigmie\Document\Document;
use Sigmie\Indices\Index;
use Sigmie\Mappings\NewProperties;
use Sigmie\SigmieIndex;

class Docs extends AbstractIndex
{
    public function name(): string
    {
        return 'docs';
    }

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
            ], _id: $row['show_id']),
        ];
    }
}
