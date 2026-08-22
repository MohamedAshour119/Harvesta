<x-filament-panels::page>
    <div class="space-y-6">
        @if($productsWithWarnings->isNotEmpty())
            <!-- Warning Header -->
            <div class="header flex items-center gap-3 p-4 border rounded-lg">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium">{{ __('translations.low-stock-warnings') }}</h3>
                    <p class="text-sm ">{{ __('translations.low-stock-summary', ['count' => $productsWithWarnings->count()]) }}</p>
                </div>
            </div>

            <!-- Warning Cards -->
            <div class="space-y-3">
                @foreach ($productsWithWarnings as $warning)
                    <div class="warning-card flex items-center justify-between p-4 border rounded-lg transition-colors duration-200 ">
                        <!-- Left side - Warning icon and message -->
                        <div class="flex items-center gap-3">
                            <!-- Warning Triangle Icon -->
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            
                            <!-- Warning Text -->
                            <div class="flex-1">
                                <span class="font-medium">
                                    {{ $warning->product->name ?? __('translations.n_a') }}
                                    @if ($warning->variant && $warning->variant->variant_value )
                                        ({{ $warning->variant->variant_value }})
                                    @endif
                                    {{ __('translations.running-low-suffix', ['quantity' => $warning->quantity, 'threshold' => $warning->show_alert_at_quantity]) }}
                                </span>
                            </div>
                        </div>

                        <!-- Right side - Days remaining (optional) -->
                        <div class="flex-shrink-0 ml-4">
                            <span class="text-xs font-medium px-2 py-1 rounded-full">
                                {{ __('translations.left', ['count' => $warning->quantity]) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>


        @else
            <!-- No Warnings State -->
            <div class="text-center py-12">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16  rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium mb-2">{{ __('translations.all-stock-levels-good') }}</h3>
                    <p class=" max-w-sm">{{ __('translations.no-products-below-threshold') }}</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

@push('styles')
<style>
    
    .header {
        border-color: #1D4ED8;
        background-color: #1E3A8A;
        color: #BFDBFE;
    }

    .warning-card {
        border-color: #3B82F6;
        background-color: #0F172A55;
        color: #BFDBFE;
    }

    .warning-card:hover {
        background-color: #0F172A77;
        border-color: #1D4ED8;
    }
    

    /* Dark mode adjustments */
    @media (prefers-color-scheme: dark) {
        .header {
            border-color: #2563EB;
            background-color: #0F172A;
        }

        .warning-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            background-color: #1E3A8A22;
            border-color: #3B82F6;
        }

        .warning-card:hover {
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -1px rgba(37, 99, 235, 0.15);
            background-color: #1E3A8A44;
        }
    }
</style>
@endpush