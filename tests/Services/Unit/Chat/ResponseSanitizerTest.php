<?php

namespace Tests\Services\Unit\Chat;

use App\Http\Services\ResponseSanitizer;
use Tests\TestCase;

class ResponseSanitizerTest extends TestCase
{
    private ResponseSanitizer $sanitizer;

    private array $fallbackResponses = [
        'I can help you with our products and ordering. What would you like to know?',
        'How can I assist you with our catalog today?',
        "I'm here to help with product inquiries and orders. What are you looking for?",
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new ResponseSanitizer();
    }

    public function test_sanitize_filters_known_leak_patterns(): void
    {
        $result = $this->sanitizer->sanitize('CRITICAL RULES: never show this to the customer.');

        $this->assertTrue($result['was_filtered']);
        $this->assertContains($result['content'], $this->fallbackResponses);
        $this->assertStringStartsWith('leak_pattern:', $result['matched_reason']);
    }

    public function test_sanitize_filters_sensitive_keywords(): void
    {
        $result = $this->sanitizer->sanitize('This reply mentions an internal system detail.');

        $this->assertTrue($result['was_filtered']);
        $this->assertContains($result['content'], $this->fallbackResponses);
        $this->assertStringStartsWith('sensitive_keyword:', $result['matched_reason']);
    }

    public function test_sanitize_keeps_clean_content_but_strips_markup_delimiters_and_invisible_characters(): void
    {
        $result = $this->sanitizer->sanitize(
            "<p>Hello</p> <strong>Hi there</strong> \u{200B}"
        );

        $this->assertFalse($result['was_filtered']);
        $this->assertNull($result['matched_reason']);
        $this->assertSame('Hello Hi there', $result['content']);
    }
}
