<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Http\Services\HomePageService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use HttpResponses;
    protected $homePageService;

    public function __construct(HomePageService $homePageService)
    {
        $this->homePageService = $homePageService;
    }
    public function sendMessage(SendMessageRequest $request)
    {
        $validated = $request->validated();
        try {
            $sent = $this->homePageService->sendMessage($validated);
            if ($sent) {
                return $this->response_success('Message sent successfully');
            }
            return $this->response_error('Failed to send message', [], 500);
        } catch (\Throwable $th) {
            return $this->response_error('Failed to send message', [], 500);
        }
    }
}
