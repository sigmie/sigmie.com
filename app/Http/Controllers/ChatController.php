<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sigmie\Sigmie;
use Sigmie\AI\LLMs\OpenAILLM;
use Sigmie\AI\Rerankers\VoyageReranker;
use Sigmie\Search\NewRagPrompt;
use Sigmie\Mappings\NewProperties;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        /** @var Sigmie  */
        $sigmie = app(Sigmie::class);

        $request->validate([
            'question' => 'required|string|max:500'
        ]);

        $question = $request->input('question');

        // If no LLM is configured, use a simple search
        try {
            // // Define properties for semantic search
            // $props = new NewProperties;
            // $props->title('title')->semantic(accuracy: 5);
            // $props->longText('content')->semantic(accuracy: 4);
            // $props->text('description')->semantic(accuracy: 3);

            // // Perform RAG query
            // $answer = $sigmie
            //     ->newRag(new OpenAILLM(config('services.openai.api_key')))
            //     ->reranker(new VoyageReranker(config('services.voyage.api_key')))
            //     ->search(
            //         $sigmie->newSearch('documentation')
            //             ->properties($props)
            //             ->queryString($question)
            //             ->retrieve(['title', 'content', 'description', 'url', 'version'])
            //             ->size(5)
            //     )
            //     ->prompt(function (NewRagPrompt $prompt) use ($question) {
            //         $prompt->question($question);
            //         $prompt->contextFields(['title', 'content', 'description']);
            //         $prompt->guardrails([
            //             'Answer based on the Sigmie documentation provided',
            //             'Be concise and technical',
            //             'Include code examples when relevant',
            //             'Mention the relevant documentation section and version',
            //             'If information is not in context, say so clearly'
            //         ]);
            //     })
            //     ->instructions(
            //         "You are a helpful assistant for the Sigmie PHP Elasticsearch library. " .
            //             "Provide accurate, technical answers based on the documentation. " .
            //             "Include relevant code examples using PHP syntax. " .
            //             "Always mention which version (v1 or v2) the information applies to."
            //     )
            //     ->limits(maxTokens: 500, temperature: 0.1)
            //     ->answer();

            // return response()->json([
            //     'answer' => $answer['answer'],
            //     'usage' => $answer['usage'] ?? null
            // ]);
        } catch (\Exception $e) {
            Log::error('RAG chat error: ' . $e->getMessage());

            return response()->json([
                'answer' => "I'm having trouble searching the documentation right now. Please try again later or browse the documentation directly."
            ], 500);
        }
    }
}
