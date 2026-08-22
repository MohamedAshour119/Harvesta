<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\ChatbotIntentDetector;
use Tests\Support\ServiceSmokeTestCase;

class ChatbotIntentDetectorTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return ChatbotIntentDetector::class;
    }

    public function test_detect_returns_greeting_for_hello_messages(): void
    {
        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame('greeting', $service->detect('Hello there'));
    }

    public function test_detect_returns_purchase_intent_for_ordering_questions(): void
    {
        $service = $this->instantiateService($this->serviceClass());

        $this->assertSame('purchase_intent', $service->detect('I want to buy 10 cartons of tea'));
    }
}
