<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sigmie\Sigmie;
use Sigmie\AI\LLMs\OpenAILLM;
use Sigmie\AI\Rerankers\VoyageReranker;
use Sigmie\Document\Hit;
use Sigmie\Search\NewRagPrompt;
use Sigmie\Mappings\NewProperties;

class SearchController extends Controller
{
    protected Sigmie $sigmie;

    public function __construct(Sigmie $sigmie)
    {
        $this->sigmie = $sigmie;
    }

    /**
     * Perform RAG search with AI-generated answer
     */
    public function rag(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500'
        ]);

        $question = $request->input('question');

        try {
            // Define properties for semantic search
            $props = new NewProperties;
            $props->title('title')->semantic(accuracy: 5);
            $props->longText('content')->semantic(accuracy: 4);
            $props->text('description')->semantic(accuracy: 3);

            // First get search results
            $searchResults = $this->sigmie
                ->newSearch('documentation')
                ->properties($props)
                ->queryString($question)
                ->retrieve(['title', 'content', 'description', 'url', 'version', 'section'])
                ->size(5)
                ->get();

            // Format search results for response
            $formattedResults = collect($searchResults->hits())->map(function (Hit $doc) {
                return [
                    '_id' => $doc['_id'] ?? null,
                    'title' => $doc['title'] ?? '',
                    'description' => $doc['description'] ?? '',
                    'url' => $doc['url'] ?? '',
                    'version' => $doc['version'] ?? '',
                    'section' => $doc['section'] ?? ''
                ];
            })->toArray();

            // Try to get AI answer if OpenAI is configured
            $aiAnswer = null;
            $apiKey = config('services.openai.api_key');
            
            if ($apiKey) {
                try {
                    $answer = $this->sigmie
                        ->newRag(new OpenAILLM($apiKey))
                        ->reranker(new VoyageReranker(config('services.voyage.api_key')))
                        ->search(
                            $this->sigmie->newSearch('documentation')
                                ->properties($props)
                                ->queryString($question)
                                ->retrieve(['title', 'content', 'description', 'url', 'version'])
                                ->size(5)
                        )
                        ->prompt(function (NewRagPrompt $prompt) use ($question) {
                            $prompt->question($question);
                            $prompt->contextFields(['title', 'content', 'description']);
                            $prompt->guardrails([
                                'Answer based on the Sigmie documentation provided',
                                'Be concise and technical',
                                'Include code examples when relevant',
                                'Use markdown formatting for better readability',
                                'Mention which version (v1 or v2) the information applies to',
                                'If information is not in context, say so clearly'
                            ]);
                        })
                        ->instructions(
                            "You are a helpful assistant for the Sigmie PHP Elasticsearch library. " .
                            "Provide accurate, technical answers based on the documentation. " .
                            "Format your response using markdown with clear sections. " .
                            "Include relevant PHP code examples with proper syntax highlighting. " .
                            "Always mention which version the information applies to."
                        )
                        ->limits(maxTokens: 800, temperature: 0.1)
                        ->answer();

                    $aiAnswer = $answer['answer'] ?? null;
                } catch (\Exception $e) {
                    Log::error('RAG generation error: ' . $e->getMessage());
                    // Continue without AI answer
                }
            }

            // If no AI answer, create a helpful message
            if (!$aiAnswer && !empty($formattedResults)) {
                $aiAnswer = "Here are the most relevant documentation pages for your query:\n\n";
                foreach (array_slice($formattedResults, 0, 3) as $result) {
                    $aiAnswer .= "- **[{$result['title']}]({$result['url']})** ({$result['version']})\n";
                    if ($result['description']) {
                        $aiAnswer .= "  {$result['description']}\n";
                    }
                }
            } elseif (!$aiAnswer) {
                $aiAnswer = "I couldn't find specific documentation matching your query. Try rephrasing or browse the documentation directly.";
            }

            return response()->json([
                'answer' => $aiAnswer,
                'results' => $formattedResults
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            
            return response()->json([
                'answer' => "An error occurred while searching. Please try again.",
                'results' => []
            ], 500);
        }
    }

    /**
     * Perform standard text search
     */
    public function standard(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:500'
        ]);

        $query = $request->input('query');

        try {

            // Define properties for semantic search
            $props = new NewProperties;
            $props->title('title')->semantic(accuracy: 5);
            $props->longText('content')->semantic(accuracy: 4);
            $props->text('description')->semantic(accuracy: 3);

            $results = $this->sigmie
                ->newSearch('documentation')
                ->properties($props)
                ->semantic()
                ->queryString($query)
                ->retrieve(['title', 'description', 'url', 'version', 'section'])
                ->size(10)
                ->get();

            ray($results->hits());

            $formattedResults = collect($results->hits())->map(function (Hit $doc) {
                return [
                    '_id' => $doc['_id'] ?? null,
                    'title' => $doc['title'] ?? '',
                    'description' => $doc['description'] ?? '',
                    'url' => $doc['url'] ?? '',
                    'version' => $doc['version'] ?? '',
                    'section' => $doc['section'] ?? ''
                ];
            })->toArray();

            return response()->json([
                'results' => $formattedResults
            ]);

        } catch (\Exception $e) {
            Log::error('Standard search error: ' . $e->getMessage());
            
            return response()->json([
                'results' => [],
                'error' => 'Search failed. Please try again.'
            ], 500);
        }
    }
}
