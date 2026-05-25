<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\MagicTags;

use Sigmie\Contracts\Package;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

/**
 * Registers the {@see MagicTagsFieldType} mapping macro and {@see MagicTagsCollectionHook} on Sigmie.
 */
class MagicTagsPackage implements Package
{
    public function register(Sigmie $sigmie): void
    {
        // Register the magicTags macro for NewProperties
        NewProperties::macro('magicTags', function (string $name, string $fromField): MagicTagsFieldType {
            $field = new MagicTagsFieldType($name, $fromField);
            $this->fields->add($field);

            return $field;
        });

        $sigmie->addCollectionHook(new MagicTagsCollectionHook());
    }
}
