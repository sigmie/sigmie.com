<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sigmie\Sigmie;
use Sigmie\AI\APIs\OpenAIConversationsApi;
use Sigmie\AI\APIs\OpenAIResponseApi;
use Sigmie\AI\APIs\VoyageRerankApi;
use Sigmie\Document\Hit;
use Sigmie\Search\NewRagPrompt;
use Sigmie\Rag\NewRerank;
use Sigmie\Mappings\NewProperties;

class SearchController extends Controller
{
    protected Sigmie $sigmie;

    public function __construct(Sigmie $sigmie)
    {
        $this->sigmie = $sigmie;
    }

    /**
     * Perform RAG search with streaming response
     */
    public function ragStream(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'conversation_id' => 'nullable|string'
        ]);

        $question = $request->input('question');
        $conversationId = $request->input('conversation_id') ?? session('rag_conversation_id');

        return response()->stream(function () use ($question, $conversationId) {
            // Disable any output buffering for immediate streaming
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            
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

                // Format search results
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

                // Send search results immediately
                echo "data: " . json_encode([
                    'type' => 'results',
                    'results' => $formattedResults
                ]) . "\n\n";
                flush();

                // Check if OpenAI is configured
                $apiKey = config('services.openai.api_key');
                
                if (!$apiKey) {
                    // Send fallback message if no API key
                    $fallbackAnswer = "Here are the most relevant documentation pages for your query:\n\n";
                    foreach (array_slice($formattedResults, 0, 3) as $result) {
                        $fallbackAnswer .= "- **[{$result['title']}]({$result['url']})** ({$result['version']})\n";
                        if ($result['description']) {
                            $fallbackAnswer .= "  {$result['description']}\n";
                        }
                    }
                    
                    echo "data: " . json_encode([
                        'type' => 'content',
                        'content' => $fallbackAnswer
                    ]) . "\n\n";
                    flush();
                    
                    echo "data: " . json_encode([
                        'type' => 'done',
                        'finish_reason' => 'stop'
                    ]) . "\n\n";
                    flush();
                    return;
                }

                // Setup LLM with conversations API, reusing conversation if available
                $llm = new OpenAIConversationsApi($apiKey, $conversationId);
                $ragBuilder = $this->sigmie->newRag($llm);
                
                // Setup reranker if available
                $voyageKey = config('services.voyage.api_key');
                // if ($voyageKey) {
                    // $voyageReranker = new VoyageRerankApi($voyageKey);
                    // $ragBuilder->reranker($voyageReranker)
                    //     ->rerank(function (NewRerank $rerank) use ($question) {
                    //         $rerank->fields(['title', 'content']);
                    //         $rerank->topK(3);
                    //         $rerank->query($question);
                    //     });
                // }

                // Stream AI answer with new API - all events
                $stream = $ragBuilder
                    ->search(
                        $this->sigmie->newSearch('documentation')
                            ->properties($props)
                            ->queryString($question)
                            ->retrieve(['title', 'content', 'description', 'url', 'version', 'section'])
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
                            'If information is not in context, say so clearly',
                            'Reference source documents as [1], [2], etc when citing information'
                        ]);
                    })
                    ->instructions(
                        "You are a helpful assistant for the Sigmie PHP Elasticsearch library. " .
                        "Provide accurate, technical answers based on the documentation. " .
                        "Format your response using markdown with clear sections. " .
                        "Include relevant PHP code examples with proper syntax highlighting. " .
                        "Always mention which version the information applies to. " .
                        "When citing information, reference sources as [1], [2], etc."
                    )
                    ->answer(true); // true for streaming

                foreach ($stream as $event) {
                    // Stream all event types directly to frontend
                    if (isset($event['type'])) {
                        switch($event['type']) {
                            case 'search.started':
                            case 'search.completed':
                            case 'rerank.started':
                            case 'rerank.completed':
                            case 'prompt.generated':
                            case 'llm.request.started':
                            case 'llm.first_token':
                                // Pass through status events
                                echo "data: " . json_encode([
                                    'type' => $event['type'],
                                    'message' => $event['message'] ?? '',
                                    'metadata' => $event['metadata'] ?? null
                                ]) . "\n\n";
                                flush();
                                break;
                                
                            case 'conversation.created':
                            case 'conversation.reused':
                                // Store conversation ID in session for reuse
                                if (isset($event['conversation_id'])) {
                                    session(['rag_conversation_id' => $event['conversation_id']]);
                                }
                                // Pass through conversation events
                                echo "data: " . json_encode([
                                    'type' => $event['type'],
                                    'conversation_id' => $event['conversation_id'] ?? null,
                                    'metadata' => $event['metadata'] ?? null
                                ]) . "\n\n";
                                flush();
                                break;
                                
                            case 'stream.start':
                                // Send context with source documents
                                $sources = [];
                                $documents = [];
                                if (isset($event['context']['documents'])) {
                                    foreach ($event['context']['documents'] as $index => $doc) {
                                        $sources[] = [
                                            'index' => $index + 1,
                                            'title' => $doc['title'] ?? '',
                                            'url' => $doc['url'] ?? '',
                                            'version' => $doc['version'] ?? '',
                                            'section' => $doc['section'] ?? ''
                                        ];
                                        $documents[] = [
                                            'title' => $doc['title'] ?? '',
                                            'description' => $doc['description'] ?? '',
                                            'url' => $doc['url'] ?? '',
                                            'version' => $doc['version'] ?? '',
                                            'section' => $doc['section'] ?? '',
                                            'score' => $doc['_score'] ?? null
                                        ];
                                    }
                                }
                                echo "data: " . json_encode([
                                    'type' => 'stream.start',
                                    'sources' => $sources,
                                    'documents' => $documents,
                                    'context' => $event['context'] ?? null,
                                    'conversation_id' => $event['context']['conversation_id'] ?? null
                                ]) . "\n\n";
                                flush();
                                break;
                                
                            case 'content.delta':
                                // Stream content chunks
                                echo "data: " . json_encode([
                                    'type' => 'content.delta',
                                    'content' => $event['delta'] ?? ''
                                ]) . "\n\n";
                                flush();
                                break;
                                
                            case 'stream.complete':
                                // Stream completion
                                echo "data: " . json_encode([
                                    'type' => 'stream.complete',
                                    'finish_reason' => 'stop'
                                ]) . "\n\n";
                                flush();
                                break;
                                
                            default:
                                // Pass through any other events
                                echo "data: " . json_encode($event) . "\n\n";
                                flush();
                                break;
                        }
                    }
                }

            } catch (\Exception $e) {
                Log::error('RAG streaming error: ' . $e->getMessage());
                
                echo "data: " . json_encode([
                    'type' => 'error',
                    'error' => 'An error occurred while generating the response.'
                ]) . "\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable Nginx buffering
            'Content-Encoding' => 'none', // Disable compression for streaming
        ]);
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
                    // Use OpenAIConversationsApi for better context management
                    $llm = new OpenAIConversationsApi($apiKey);
                    $ragBuilder = $this->sigmie->newRag($llm);
                    
                    // Add reranker if available
                    $voyageKey = config('services.voyage.api_key');
                    if ($voyageKey) {
                        $voyageReranker = new VoyageRerankApi($voyageKey);
                        $ragBuilder->reranker($voyageReranker)
                            ->rerank(function (NewRerank $rerank) use ($question) {
                                $rerank->fields(['title', 'content']);
                                $rerank->topK(3);
                                $rerank->query($question);
                            });
                    }
                    
                    $responses = $ragBuilder
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
                                'If information is not in context, say so clearly',
                                'Reference source documents as [1], [2], etc when citing information'
                            ]);
                        })
                        ->instructions(
                            "You are a helpful assistant for the Sigmie PHP Elasticsearch library. " .
                            "Provide accurate, technical answers based on the documentation. " .
                            "Format your response using markdown with clear sections. " .
                            "Include relevant PHP code examples with proper syntax highlighting. " .
                            "Always mention which version the information applies to. " .
                            "When citing information, reference sources as [1], [2], etc."
                        )
                        ->answer(false); // false for non-streaming
                    
                    // Get the RagResponse object
                    $ragResponse = iterator_to_array($responses)[0] ?? null;
                    
                    if ($ragResponse) {
                        $aiAnswer = $ragResponse->finalAnswer();
                        
                        // Add source references at the end
                        $sources = "\n\n### Sources:\n";
                        $context = $ragResponse->context();
                        if (isset($context['documents'])) {
                            foreach ($context['documents'] as $index => $doc) {
                                $num = $index + 1;
                                $sources .= "[{$num}] [{$doc['title']}]({$doc['url']}) (v{$doc['version']})\n";
                            }
                            $aiAnswer .= $sources;
                        }
                    } else {
                        $aiAnswer = null;
                    }
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
     * Clear conversation context
     */
    public function clearConversation(Request $request)
    {
        session()->forget('rag_conversation_id');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Conversation cleared'
        ]);
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
