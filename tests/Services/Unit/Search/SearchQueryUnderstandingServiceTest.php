<?php

namespace Tests\Services\Unit\Search;

use App\Http\Services\GroqService;
use App\Http\Services\SearchQueryUnderstandingService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class SearchQueryUnderstandingServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_should_analyze_requires_at_least_two_meaningful_words(): void
    {
        $service = new SearchQueryUnderstandingService(Mockery::mock(GroqService::class));

        $this->assertFalse($service->shouldAnalyze('tea'));
        $this->assertFalse($service->shouldAnalyze(' a '));
        $this->assertTrue($service->shouldAnalyze('corn oil'));
    }

    public function test_analyze_returns_empty_analysis_without_calling_groq_for_short_queries(): void
    {
        $groq = Mockery::mock(GroqService::class);
        $groq->shouldNotReceive('chat');

        $service = new SearchQueryUnderstandingService($groq);

        $this->assertSame([
            'search_phrases' => [],
            'keywords' => [],
            'negative_keywords' => [],
            'language' => 'mixed',
            'is_intent_query' => false,
        ], $service->analyze('oil'));
    }

    public function test_analyze_decodes_sanitizes_and_caches_groq_responses(): void
    {
        $groq = Mockery::mock(GroqService::class);
        $groq->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => 'Preface {"language":"xx","search_phrases":["Corn Oil","corn oil","x","deep frying oil","refined corn oil","cooking oil","kitchen oil","pantry oil"],"keywords":["corn","oil",123,"corn"],"negative_keywords":["soap","","detergent","soap"]} tail',
            ]);

        $service = new SearchQueryUnderstandingService($groq);

        $first = $service->analyze('Need corn oil for deep frying');
        $second = $service->analyze('Need corn oil for deep frying');

        $expected = [
            'search_phrases' => [
                'corn oil',
                'deep frying oil',
                'refined corn oil',
                'cooking oil',
                'kitchen oil',
                'pantry oil',
            ],
            'keywords' => ['corn', 'oil'],
            'negative_keywords' => ['soap', 'detergent'],
            'language' => 'mixed',
            'is_intent_query' => true,
        ];

        $this->assertSame($expected, $first);
        $this->assertSame($expected, $second);
    }
}
