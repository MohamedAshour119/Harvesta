<?php

namespace Tests\Services\Unit\Support;

use App\Http\Services\MessageSanitizer;
use Tests\TestCase;

class MessageSanitizerTest extends TestCase
{
    private MessageSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new MessageSanitizer();
    }

    public function test_sanitize_strips_markup_control_characters_and_normalizes_spacing(): void
    {
        $message = "<b>Hello</b>\u{200B}\x07\n\n\nLine     two";

        $this->assertSame(
            "Hello\n\nLine    two",
            $this->sanitizer->sanitize($message)
        );
    }

    public function test_is_valid_accepts_meaningful_text_and_rejects_empty_symbol_only_and_spam_messages(): void
    {
        $this->assertTrue($this->sanitizer->isValid('Need 20 units of this cleaner.'));
        $this->assertFalse($this->sanitizer->isValid(" \n\t "));
        $this->assertFalse($this->sanitizer->isValid('!!! ???'));
        $this->assertFalse($this->sanitizer->isValid(str_repeat('a', 21)));
    }

    public function test_wrap_for_llm_adds_customer_query_delimiters(): void
    {
        $this->assertSame(
            "<<CUSTOMER_QUERY>>\nNeed product details\n<<END_CUSTOMER_QUERY>>",
            $this->sanitizer->wrapForLLM('Need product details')
        );
    }
}
