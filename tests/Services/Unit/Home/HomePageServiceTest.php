<?php

namespace Tests\Services\Unit\Home;

use App\Http\Services\HomePageService;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ServiceSmokeTestCase;

class HomePageServiceTest extends ServiceSmokeTestCase
{
    protected function serviceClass(): string
    {
        return HomePageService::class;
    }

    public function test_send_message_falls_back_to_the_mailer_when_brevo_is_not_configured(): void
    {
        config([
            'services.brevo.api_key' => null,
            'app.contact_email' => 'contact@example.com',
            'mail.from.address' => 'no-reply@example.com',
            'mail.from.name' => 'Cairo3A',
        ]);

        Mail::fake();

        $service = $this->instantiateService($this->serviceClass());
        $result = $service->sendMessage([
            'full_name' => 'Mona',
            'email' => 'mona@example.com',
            'phone' => '01000000000',
            'message' => 'Need a quote.',
        ]);

        $this->assertTrue($result);
        Mail::assertSent(ContactMessageMail::class);
    }
}
