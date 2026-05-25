<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\MagicTags;

use Sigmie\AgentTools\MagicTags\Sidecar\Index as MagicTagsSidecarIndex;
use Sigmie\Mappings\Properties;

/**
 * Stateful processor for magic-tag generation (used by {@see MagicTagsCollectionHook}).
 *
 * @property Properties $properties
 * @property array<string, MagicTagsSidecarIndex> $sidecars
 */
class MagicTagsProcessor
{
    use ProcessesMagicTags;

    /**
     * @param  array<string, MagicTagsSidecarIndex>  $sidecars  keyed by magic field path
     */
    public function __construct(
        protected Properties $properties,
        protected array $sidecars = [],
    ) {}
}
