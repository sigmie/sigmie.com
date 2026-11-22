<?php

declare(strict_types=1);

namespace App\Markdown;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Phiki\Adapters\CommonMark\Transformers\AnnotationsTransformer;
use Phiki\Grammar\Grammar;
use Phiki\Phiki;

/**
 * Highlights fenced code in the docs with the site's own palette.
 *
 * The theme (resources/highlight/sigmie.json) names CSS variables instead of
 * colours, so app.css holds the palette once for the docs and the landing
 * page, in light and dark. The docs markdown comes from sigmie/sigmie and
 * marks lines with Torchlight's `// [tl! highlight]`, which Phiki reads under
 * the `tl` prefix.
 */
class HighlightedCodeRenderer implements NodeRendererInterface
{
    private Phiki $phiki;

    public function __construct()
    {
        $this->phiki = (new Phiki)->theme('sigmie', resource_path('highlight/sigmie.json'));
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FencedCode) {
            throw new InvalidArgumentException('Block must be instance of '.FencedCode::class);
        }

        $language = $node->getInfoWords()[0] ?? '';

        return $this->phiki
            ->codeToHtml(
                rtrim($node->getLiteral(), "\n"),
                $this->phiki->environment->grammars->has($language) ? $language : Grammar::Txt,
                'sigmie',
            )
            ->transformer(new AnnotationsTransformer('tl'))
            ->toString();
    }
}
