<?php

namespace App\Http\Services;

use App\Http\Repositories\HomePageRepository;
use App\Http\Resources\IndustrialCategoryResource;
use App\Http\Resources\ProductResource;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client as HttpClient;

class HomePageService
{
    public function __construct(protected HomePageRepository $homePageRepository) {}

    public function index()
    {
        $results = $this->homePageRepository->index();
        
        $industrial_categories = $results['industrial_categories']->map(function ($industrial_category) {
            return new IndustrialCategoryResource($industrial_category);
        });

        $top_categories_products = $results['top_categories_products']->map(function ($data) {
            return [
                'category' => new IndustrialCategoryResource($data['category']),
                'products' => $data['products']->map(function ($product) {
                    return new ProductResource($product, 'product_card', true);
                })
            ];
        });

        return [
            'industrial_categories' => $industrial_categories,
            'top_categories_products' => $top_categories_products,
        ];
    }

    public function sendMessage($data)
    {
        $contact_email = config('app.contact_email');

        // Prefer Brevo Transactional Email API
        try {
            $apiKey = config('services.brevo.api_key');
            if (!$apiKey) {
                throw new \RuntimeException('BREVO_API_KEY is not configured');
            }

            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new TransactionalEmailsApi(new HttpClient(), $config);

            $senderEmail = config('mail.from.address');
            $senderName = config('mail.from.name');

            $subject = 'New Contact Message';
            $textContent = "New contact message from {$data['full_name']} ({$data['email']} / {$data['phone']})\n\n{$data['message']}";
            $safeMessage = nl2br(e($data['message']));
            $htmlContent = <<<HTML
                <div style="font-family: system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; line-height:1.6; color:#111827;">
                    <h2 style="margin:0 0 12px;">New Contact Message</h2>
                    <p style="margin:0 0 8px;"><strong>Name:</strong> {$data['full_name']}</p>
                    <p style="margin:0 0 8px;"><strong>Email:</strong> {$data['email']}</p>
                    <p style="margin:0 0 8px;"><strong>Phone:</strong> {$data['phone']}</p>
                    <p style="margin:12px 0 6px;"><strong>Message:</strong></p>
                    <div style="white-space:pre-wrap;">{$safeMessage}</div>
                </div>
            HTML;

            $sendEmail = new SendSmtpEmail([
                'to' => [ [ 'email' => $contact_email ] ],
                'sender' => [ 'email' => $senderEmail, 'name' => $senderName ],
                'replyTo' => [ 'email' => $data['email'], 'name' => $data['full_name'] ],
                'subject' => $subject,
                'textContent' => $textContent,
                'htmlContent' => $htmlContent,
            ]);

            $apiInstance->sendTransacEmail($sendEmail);
            return true;
        } catch (\Throwable $th) {
            Log::error('Brevo mail sending failed: ' . $th->getMessage(), [ 'exception' => $th ]);

            // Fallback to default mailer (e.g., SMTP) if configured
            try {
                Mail::to($contact_email)->send(new ContactMessageMail($data));
                return true;
            } catch (\Throwable $fallbackEx) {
                Log::error('Fallback mail sending failed: ' . $fallbackEx->getMessage(), [ 'exception' => $fallbackEx ]);
                return false;
            }
        }
    }
}
