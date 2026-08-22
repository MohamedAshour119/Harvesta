<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests\AddPurchase;
use Recombee\RecommApi\Requests\RecommendItemsToItem;
use App\Models\Product;

class TestRecombeeFrequentlyBought extends Command
{
    protected $signature = 'recombee:test-fbt';
    protected $description = 'Simulate purchases of 3 specific products to test Frequently Bought Together recommendations';

    public function handle()
    {
        $this->info('Starting Recombee simulation...');

        $client = new Client(
            config('services.recombee.database_id'),
            config('services.recombee.private_token'),
            ['region' => config('services.recombee.region', 'eu-west')]
        );

        // Fetch 3 existing products, or just use hardcoded IDs from the database. 
        // We will grab any 3 active products for this test.
        $products = Product::where('status', 'approved')->inRandomOrder()->take(3)->get();
        
        if ($products->count() < 3) {
            $this->error('Not enough products in DB to simulate.');
            return;
        }

        $p1 = $products[0];
        $p2 = $products[1];
        $p3 = $products[2];

        $this->info("Selected Products:");
        $this->info("1. {$p1->name} (ID: {$p1->id})");
        $this->info("2. {$p2->name} (ID: {$p2->id})");
        $this->info("3. {$p3->name} (ID: {$p3->id})");

        $this->info('Simulating 50 users buying all 3 products together...');

        $requests = [];
        for ($i = 1; $i <= 50; $i++) {
            $userId = "test_user_bundle_tester_" . $i;
            
            // User buys p1, p2, p3
            $requests[] = new AddPurchase($userId, (string)$p1->id, ['cascadeCreate' => true]);
            $requests[] = new AddPurchase($userId, (string)$p2->id, ['cascadeCreate' => true]);
            $requests[] = new AddPurchase($userId, (string)$p3->id, ['cascadeCreate' => true]);
        }

        try {
            // Note: Batch might be better, but doing it sequentially or via batch is fine for 45 requests
            $client->send(new \Recombee\RecommApi\Requests\Batch($requests));
            $this->info('Successfully sent 45 purchase interactions.');
            
            // Wait a moment for Recombee to process the interactions
            $this->info('Waiting 3 seconds for Recombee model to update...');
            sleep(3);

            $this->info("Fetching recommendations for Product 1 (ID: {$p1->id})...");
            
            $recommendations = $client->send(new RecommendItemsToItem((string)$p1->id, 'test_user_bundle_tester_1', 5));
            
            $this->info("Recommendations returned:");
            
            $foundP2 = false;
            $foundP3 = false;
            
            foreach ($recommendations['recomms'] as $recomm) {
                $recId = str_replace('product_', '', $recomm['id']);
                $this->line("- Product ID: " . $recId);
                if ($recId == $p2->id) $foundP2 = true;
                if ($recId == $p3->id) $foundP3 = true;
            }

            if ($foundP2 && $foundP3) {
                $this->info('SUCCESS! The recommendation engine correctly associated the grouped products.');
            } else {
                $this->warn('Partially failed or requires more time for the model to train. It recommended some, but not both guaranteed products.');
            }

        } catch (\Exception $e) {
            $this->error("Failed to connect to Recombee: " . $e->getMessage());
        }
    }
}
