<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\IndustrialCategory;
use App\Jobs\SendRecombeeInteractionJob;

class SimulateRecombeeInteractions extends Command
{
    protected $signature = 'recombee:simulate {user_id} {--category= : Category ID to focus behavior on} {--actions=15 : How many random actions to perform}';
    protected $description = 'Simulate user activity for the Recombee Recommendation Engine';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User #$userId not found!");
            return Command::FAILURE;
        }

        $categoryId = $this->option('category');
        $actionsCount = (int)$this->option('actions');
        
        $query = Product::with(['industrialCategory', 'variants']);
        if ($categoryId) {
            $query->where('industry_category_id', $categoryId);
            $this->info("Focusing simulation on category #$categoryId");
        }
        
        $products = $query->get();
        if ($products->isEmpty()) {
            $this->error("No products found to interact with!");
            return Command::FAILURE;
        }

        $this->info("Starting simulation of {$actionsCount} actions for user {$user->first_name}...");
        
        for ($i = 1; $i <= $actionsCount; $i++) {
            $product = $products->random();
            $action = $this->getRandomAction();
            $price = 0;
            if ($product->variants && $product->variants->isNotEmpty()) {
                $price = $product->variants->min('price_after_discount');
            }
            
            // Queue the interaction directly into our existing Job flow!
            SendRecombeeInteractionJob::dispatch($action, [
                'user_id' => $userId,
                'product_id' => $product->id,
                'price' => (float)$price,
                'quantity' => rand(1, 3) 
            ]);

            $this->logAction($action, $product->name, $i);
        }

        $this->info('Done! Interactions dispatched to Recombee via Queue. Make sure your worker is running!');
        return Command::SUCCESS;
    }

    private function getRandomAction()
    {
        // Weighted random choice: 50% view, 20% cart, 15% bookmark, 15% purchase
        $rand = rand(1, 100);
        if ($rand <= 50) return 'detail_view';
        if ($rand <= 70) return 'cart_add';
        if ($rand <= 85) return 'add_bookmark';
        return 'purchase';
    }

    private function logAction($action, $productName, $iteration)
    {
        $actionEmojis = [
            'detail_view'  => '👀 Viewed',
            'cart_add'     => '🛒 Added to Cart',
            'add_bookmark' => '⭐ Bookmarked',
            'purchase'     => '💳 Purchased',
        ];
        $label = $actionEmojis[$action] ?? '❓ Unknown Action';
        $this->line("[$iteration] " . $label . ": " . $productName);
    }
}
