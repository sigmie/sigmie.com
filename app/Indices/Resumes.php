<?php

namespace App\Indices;

use DateTime;
use Sigmie\Base\Contracts\ElasticsearchConnection;
use Sigmie\Document\Document;
use Sigmie\Indices\Index;
use Sigmie\Mappings\NewProperties;
use Sigmie\SigmieIndex;

class Resumes extends AbstractIndex
{
    public function csvPath(): string
    {
        return storage_path('app/datasets/resumes.csv');
    }

    public function alias(): string
    {
        return 'resumes';
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        $properties->category('category');
        $properties->html('resume_html');
        $properties->text('resume_str')->semantic(accuracy: 6, dimensions: 384, api: 'infinity-embeddings');

        return $properties;
    }

    public function toDocuments(array $row): array
    {
        return [
            new Document([
                'resume_str' => $row['Resume_str'],
                'resume_html' => $row['Resume_html'],
                'category' => $row['Category'],
            ], _id: $row['ID']),
        ];
    }
}
