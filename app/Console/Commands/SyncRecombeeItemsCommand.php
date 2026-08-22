<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Http\Services\RecombeeService;

class SyncRecombeeItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recombee:sync-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all products to Recombee item catalog';

    /**
     * Execute the console command.
     */
    public function handle(RecombeeService $recombeeService)
    {
        $this->info('Starting Recombee product sync...');

        $products = Product::with(['variants', 'industrialCategory', 'media'])->get();

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $product) {
            $recombeeService->syncItem($product);
            $bar->advance();
        }

        $bar->finish();
        
        $this->newLine();
        $this->info('Successfully synced ' . count($products) . ' products to Recombee.');
    }
}
