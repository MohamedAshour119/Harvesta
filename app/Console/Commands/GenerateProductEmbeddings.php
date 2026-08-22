<?php

namespace App\Console\Commands;

use App\Http\Services\JinaEmbeddingService;
use App\Http\Services\ProductSearchDocumentBuilder;
use App\Models\Product;
use App\Models\ProductEmbedding;
use Illuminate\Console\Command;

class GenerateProductEmbeddings extends Command
{
    protected $signature = 'embeddings:generate {--force : Regenerate all embeddings even if unchanged}';
    protected $description = 'Generate/update Jina AI embeddings for all products';

    public function handle(JinaEmbeddingService $jinaService, ProductSearchDocumentBuilder $searchDocumentBuilder): int
    {
        $force = $this->option('force');
        $products = Product::with(['industrialCategory', 'variants.variantType'])->get();

        if ($products->isEmpty()) {
            $this->error('No products found in the database.');
            return Command::FAILURE;
        }

        $this->info("Found {$products->count()} products. Preparing embeddings...");

        $textsToEmbed = [];
        $metadata = []; // [index => ['product_id' => ..., 'hash' => ...]]

        foreach ($products as $product) {
            $textForEmbedding = $searchDocumentBuilder->build($product);

            $hash = hash('sha256', $textForEmbedding);

            // Check if embedding already exists with same hash
            $existing = ProductEmbedding::where('product_id', $product->id)->first();
            if ($existing && $existing->text_hash === $hash && !$force) {
                $this->line("  ⏭️  Skipped (unchanged): {$product->name}");
                continue;
            }

            $textsToEmbed[] = $textForEmbedding;
            $metadata[] = [
                'product_id' => $product->id,
                'hash' => $hash,
                'name' => $product->name,
            ];
        }

        if (empty($textsToEmbed)) {
            $this->info('All embeddings are up to date. Nothing to generate.');
            return Command::SUCCESS;
        }

        $count = count($textsToEmbed);
        $this->info("Sending {$count} texts to Jina AI...");

        // Send in batches of 10 to avoid API rate limits
        $batchSize = 10;
        $generated = 0;
        $totalBatches = ceil(count($textsToEmbed) / $batchSize);

        foreach (array_chunk($textsToEmbed, $batchSize) as $batchIndex => $batch) {
            $this->info("  📦 Batch " . ($batchIndex + 1) . "/{$totalBatches} (" . count($batch) . " texts)...");
            $offset = $batchIndex * $batchSize;

            $embeddings = $jinaService->generatePassageEmbeddings($batch);

            if (empty($embeddings)) {
                $this->error("  ❌ Jina API returned empty response for batch " . ($batchIndex + 1) . ". Retrying in 30s...");
                sleep(30);
                $embeddings = $jinaService->generatePassageEmbeddings($batch);
                if (empty($embeddings)) {
                    $this->error("  ❌ Retry also failed. Skipping batch.");
                    continue;
                }
            }

            // Delay between batches to avoid rate limits
            if ($batchIndex < $totalBatches - 1) {
                sleep(10);
            }

            foreach ($embeddings as $i => $embedding) {
                $meta = $metadata[$offset + $i];

                ProductEmbedding::updateOrCreate(
                    ['product_id' => $meta['product_id']],
                    [
                        'embedding' => json_encode($embedding),
                        'text_hash' => $meta['hash'],
                    ]
                );

                $this->line("  ✅ Generated: {$meta['name']}");
                $generated++;
            }
        }

        $this->info("Done! Generated embeddings for {$generated}/{$count} products.");
        return Command::SUCCESS;
    }
}
