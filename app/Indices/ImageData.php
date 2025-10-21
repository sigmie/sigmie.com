<?php

namespace App\Indices;

use DateTime;
use Exception;
use Sigmie\Base\Contracts\ElasticsearchConnection;
use Sigmie\Document\Document;
use Sigmie\Indices\Index;
use Sigmie\Mappings\NewProperties;
use Sigmie\SigmieIndex;

class ImageData extends AbstractIndex
{
    public function csvPath(): string
    {
        return storage_path('app/datasets/image_data.csv');
    }

    public function alias(): string
    {
        return 'image_data';
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        $properties->image('image')->semantic(accuracy: 7, dimensions: 512, api: 'infinity-clip');

        return $properties;
    }

    public function toDocuments(array $row): array
    {
        $path = storage_path('app/datasets/image_files/' . $row["high_res"]);

        if (!file_exists($path)) {
            throw new Exception("Image not found at: {$path}");
        }
        $content = file_get_contents($path);

        $base64Image = 'data:image/jpeg;base64,' . base64_encode($content);

        return [
            new Document([
                'image' => $base64Image,
            ], _id: $row['high_res']),
        ];
    }
}
