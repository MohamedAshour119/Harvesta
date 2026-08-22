<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use Illuminate\Console\Command;

class CleanupExpiredConversations extends Command
{
    protected $signature = 'chatbot:cleanup-expired';
    protected $description = 'Delete expired chatbot conversations and their messages';

    public function handle(): int
    {
        $count = ChatConversation::where('expires_at', '<', now())->count();

        if ($count === 0) {
            $this->info('No expired conversations to clean up.');
            return Command::SUCCESS;
        }

        // Delete in chunks to avoid memory issues
        ChatConversation::where('expires_at', '<', now())
            ->chunkById(100, function ($conversations) {
                foreach ($conversations as $conversation) {
                    $conversation->messages()->delete();
                    $conversation->delete();
                }
            });

        $this->info("Cleaned up {$count} expired conversations.");
        return Command::SUCCESS;
    }
}
