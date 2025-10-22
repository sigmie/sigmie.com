<?php

namespace App\Indices;

use Sigmie\Base\Contracts\ElasticsearchConnection;
use Sigmie\Document\Document;
use Sigmie\Indices\Index;
use Sigmie\Mappings\NewProperties;
use Sigmie\Search\NewRecommendations;
use Sigmie\Sigmie;
use Sigmie\SigmieIndex;

abstract class AbstractIndex extends SigmieIndex
{
    abstract public function alias(): string;

    abstract public function csvPath(): string;

    final public function __construct()
    {
        parent::__construct(app(Sigmie::class));
    }

    public function properties(): NewProperties
    {
        $properties = new NewProperties;

        return $properties;
    }

    public function newRecommend(): NewRecommendations
    {
        return $this->sigmie()
            ->newRecommend($this->alias())
            ->properties($this->properties());
    }

    public function sigmie(): Sigmie
    {
        return app(Sigmie::class);
    }

    public function toDocuments(array $row): array
    {
        return [
            new Document([
                ...$row
            ], _id: $row['id'] ?? null),
        ];
    }
}
