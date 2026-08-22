<?php

namespace Tests\Services\Unit\External;

use App\Http\Services\JinaEmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\Support\ServiceSmokeTestCase;

class JinaEmbeddingServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return JinaEmbeddingService::class;
    }

    public function test_generate_embeddings_returns_empty_array_without_api_key_or_inputs(): void
    {
        config(['services.jina.api_key' => null]);

        $service = new JinaEmbeddingService();

        $this->assertSame([], $service->generateEmbeddings([]));
        $this->assertSame([], $service->generateEmbeddings(['hello']));
    }

    public function test_generate_embeddings_returns_sorted_embedding_vectors(): void
    {
        config([
            'services.jina.api_key' => 'jina-key',
            'services.jina.model' => 'jina-embeddings-v3',
            'services.jina.api_url' => 'https://api.jina.ai/v1/embeddings',
        ]);

        Http::fake([
            'https://api.jina.ai/v1/embeddings' => Http::response([
                'data' => [
                    ['index' => 1, 'embedding' => [0.3, 0.4]],
                    ['index' => 0, 'embedding' => [0.1, 0.2]],
                ],
            ]),
        ]);

        $service = new JinaEmbeddingService();
        $result = $service->generateEmbeddings(['first', 'second']);

        $this->assertSame([[0.1, 0.2], [0.3, 0.4]], $result);
        $this->assertSame([0.1, 0.2], $service->generateSingleEmbedding('first'));
    }
}
