<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Services\RecombeeService;

class SendRecombeeInteractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $action;
    protected array $data;

    /**
     * Create a new job instance.
     *
     * @param string $action ('detail_view', 'add_bookmark', 'remove_bookmark', 'cart_add', 'purchase')
     * @param array $data  assoc array of data specific to action
     */
    public function __construct(string $action, array $data)
    {
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(RecombeeService $recombeeService): void
    {
        switch ($this->action) {
            case 'detail_view':
                $recombeeService->addDetailView($this->data['user_id'], $this->data['product_id']);
                break;
            case 'add_bookmark':
                $recombeeService->addBookmark($this->data['user_id'], $this->data['product_id']);
                break;
            case 'remove_bookmark':
                $recombeeService->removeBookmark($this->data['user_id'], $this->data['product_id']);
                break;
            case 'cart_add':
                $recombeeService->addCartAddition(
                    $this->data['user_id'],
                    $this->data['product_id'],
                    $this->data['price'] ?? 0
                );
                break;
            case 'purchase':
                $recombeeService->addPurchase(
                    $this->data['user_id'],
                    $this->data['product_id'],
                    $this->data['price'] ?? 0,
                    $this->data['quantity'] ?? 1
                );
                break;
        }
    }
}
