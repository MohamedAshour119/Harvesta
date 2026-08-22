<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Str;

class GenerateProductSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-slugs {--force : Force regenerate slugs even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for all products based on their English names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to generate product slugs...');
        
        $force = $this->option('force');
        
        // Get all products
        $query = Product::query();
        
        if (!$force) {
            // Only get products without slugs if not forcing
            $query->whereNull('slug');
        }
        
        $products = $query->get();
        
        if ($products->isEmpty()) {
            $this->info('No products found that need slug generation.');
            return;
        }
        
        $this->info("Found {$products->count()} products to process.");
        
        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($products as $product) {
            try {
                $slug = $this->generateSlug($product);
                
                // Check if slug already exists for another product
                $existingProduct = Product::where('slug', $slug)
                    ->where('id', '!=', $product->id)
                    ->first();
                
                if ($existingProduct) {
                    // If slug exists, append product ID
                    $slug = $slug . '-' . $product->id;
                }
                
                $product->update(['slug' => $slug]);
                $updated++;
                
                $this->newLine();
                $this->line("✓ Product ID {$product->id}: '{$product->name}' → '{$slug}'");
                
            } catch (\Exception $e) {
                $skipped++;
                $this->newLine();
                $this->error("✗ Failed to generate slug for product ID {$product->id}: {$e->getMessage()}");
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Slug generation completed!");
        $this->info("Updated: {$updated} products");
        
        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped} products due to errors");
        }
    }
    
    /**
     * Generate a slug from product name
     */
    private function generateSlug(Product $product): string
    {
        // Get the product name
        $name = $product->name;
        
        // Remove Arabic/non-English characters and extra spaces
        $englishName = $this->extractEnglishText($name);
        
        // If no English text found, use a fallback
        if (empty($englishName)) {
            return 'product-' . $product->id;
        }
        
        // Convert to slug format
        $slug = Str::slug($englishName, '-');
        
        // Count words in the original English text
        $wordCount = str_word_count($englishName);
        
        // If only one word, append product ID
        if ($wordCount <= 1) {
            $slug = $slug . '-' . $product->id;
        }
        
        return $slug;
    }
    
    /**
     * Extract English text from mixed language string
     */
    private function extractEnglishText(string $text): string
    {
        // Remove Arabic characters and keep only English letters, numbers, and spaces
        $englishText = preg_replace('/[^\x00-\x7F\s]/', '', $text);
        
        // Clean up extra spaces
        $englishText = preg_replace('/\s+/', ' ', $englishText);
        
        // Trim and return
        return trim($englishText);
    }
}
