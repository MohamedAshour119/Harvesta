<?php

namespace App\Filament\Pages;

use App\Models\InventoryProduct;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Warnings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string $view = 'filament.pages.warnings';

    public function getTitle(): string | Htmlable
    {
        return __('translations.page-label');
    }

    public static function getNavigationLabel(): string
    {
        return __('translations.page-label');
    }

    // Show badge count on navigation icon
    public static function getNavigationBadge(): ?string
    {
        $count = InventoryProduct::whereRaw('quantity <= show_alert_at_quantity')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public $productsWithWarnings;

    public function mount(): void
    {
        $this->productsWithWarnings = InventoryProduct::with(['product', 'variant:id,variant_value'])
            ->whereRaw('quantity <= show_alert_at_quantity')
            ->get();
    }
}
