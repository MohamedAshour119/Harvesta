<?php

namespace App\Http\Services;

use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests\SetItemValues;
use Recombee\RecommApi\Requests\AddDetailView;
use Recombee\RecommApi\Requests\AddBookmark;
use Recombee\RecommApi\Requests\DeleteBookmark;
use Recombee\RecommApi\Requests\AddCartAddition;
use Recombee\RecommApi\Requests\AddPurchase;
use Recombee\RecommApi\Requests\RecommendItemsToUser;
use Recombee\RecommApi\Requests\RecommendItemsToItem;
use Illuminate\Support\Facades\Log;
use Recombee\RecommApi\Exceptions\ApiException;

class RecombeeService
{
    protected ?Client $client = null;

    public function __construct()
    {
        $dbId = config('services.recombee.database_id');
        $token = config('services.recombee.private_token');
        $region = config('services.recombee.region', 'eu-west');

        if ($dbId && $token) {
            $this->client = new Client($dbId, $token, ['region' => $region]);
        }
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function syncItem($product): void
    {
        if (!$this->client) return;

        try {
            $category = $product->industrialCategory ? $product->industrialCategory->name : 'General';
            
            $price = 0;
            if ($product->variants && $product->variants->isNotEmpty()) {
                $price = $product->variants->min('price_after_discount');
            }

            $imageUrl = null;
            if ($product->hasMedia('main_images')) {
                $imageUrl = $product->getFirstMediaUrl('main_images', 'product_card');
            }

            $request = new SetItemValues(
                (string)$product->id,
                [
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $category,
                    'price' => (float)$price,
                    'image_url' => $imageUrl,
                ],
                ['cascadeCreate' => true]
            );

            $this->client->send($request);
        } catch (\Exception $e) {
            Log::error('Recombee Item Sync Error: ' . $e->getMessage());
        }
    }

    public function addDetailView(int|string $userId, int|string $productId): void
    {
        if (!$this->client) return;

        try {
            $this->client->send(new AddDetailView(
                (string)$userId,
                (string)$productId,
                ['cascadeCreate' => true]
            ));
        } catch (\Exception $e) {
            Log::error('Recombee Detail View Error: ' . $e->getMessage());
        }
    }

    public function addBookmark(int|string $userId, int|string $productId): void
    {
        if (!$this->client) return;

        try {
            $this->client->send(new AddBookmark(
                (string)$userId,
                (string)$productId,
                ['cascadeCreate' => true]
            ));
        } catch (\Exception $e) {
            Log::error('Recombee Bookmark Error: ' . $e->getMessage());
        }
    }

    public function removeBookmark(int|string $userId, int|string $productId): void
    {
        if (!$this->client) return;

        try {
            $this->client->send(new DeleteBookmark(
                (string)$userId,
                (string)$productId
            ));
        } catch (ApiException $e) {
            // Ignore if bookmark doesn't exist
        } catch (\Exception $e) {
            Log::error('Recombee Delete Bookmark Error: ' . $e->getMessage());
        }
    }

    public function addCartAddition(int|string $userId, int|string $productId, float $price = 0): void
    {
        if (!$this->client) return;

        try {
            $this->client->send(new AddCartAddition(
                (string)$userId,
                (string)$productId,
                [
                    'cascadeCreate' => true,
                    'price' => $price > 0 ? $price : null,
                ]
            ));
        } catch (\Exception $e) {
            Log::error('Recombee Cart Addition Error: ' . $e->getMessage());
        }
    }

    public function addPurchase(int|string $userId, int|string $productId, float $price = 0, int $quantity = 1): void
    {
        if (!$this->client) return;

        try {
            $this->client->send(new AddPurchase(
                (string)$userId,
                (string)$productId,
                [
                    'cascadeCreate' => true,
                    'price' => $price > 0 ? $price : null,
                    'amount' => $quantity,
                ]
            ));
        } catch (\Exception $e) {
            Log::error('Recombee Purchase Error: ' . $e->getMessage());
        }
    }

    public function recommendItemsToUser(int|string $userId, int $count = 10)
    {
        if (!$this->client) return [];

        try {
            $response = $this->client->send(new RecommendItemsToUser(
                (string)$userId,
                $count,
                [
                    'cascadeCreate' => true,
                    'returnProperties' => true
                ]
            ));
            return $response;
        } catch (\Exception $e) {
            Log::error('Recombee Recommend to User Error: ' . $e->getMessage());
            return [];
        }
    }

    public function recommendItemsToItem(int|string $itemId, int|string|null $userId = null, int $count = 10, array $additionalOptions = [])
    {
        if (!$this->client) return [];

        try {
            $options = array_merge([
                'cascadeCreate' => true, 
                'returnProperties' => true
            ], $additionalOptions);
            
            if ($userId) {
                $options['targetUserId'] = (string)$userId;
            }

            $response = $this->client->send(new RecommendItemsToItem(
                (string)$itemId,
                $userId ? (string)$userId : null,
                $count,
                $options
            ));
            return $response;
        } catch (\Exception $e) {
            Log::error('Recombee Recommend to Item Error: ' . $e->getMessage());
            return [];
        }
    }
}
