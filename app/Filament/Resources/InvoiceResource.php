<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Http\Services\InvoiceService;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $slug = 'invoices';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('filament-resources.resources.InvoiceResource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-resources.resources.InvoiceResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.inventory_and_orders');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('invoice_uuid');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('filament-resources.resources.InvoiceResource.order_number'))
                    ->formatStateUsing(fn ($state) => 'ORD-' . str_pad($state, 6, '0', STR_PAD_LEFT))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('invoice_uuid')
                    ->label(__('filament-resources.resources.InvoiceResource.invoice_id'))
                    ->limit(12)
                    ->tooltip(fn ($state) => $state)
                    ->copyable()
                    ->searchable(),

                TextColumn::make('user.full_name')
                    ->label(__('filament-resources.resources.InvoiceResource.customer'))
                    ->default(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: 'N/A')
                    ->searchable(),

                TextColumn::make('total')
                    ->label(__('filament-resources.resources.InvoiceResource.total'))
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('source')
                    ->label(__('filament-resources.resources.InvoiceResource.source'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'customer_checkout' => __('filament-resources.resources.OrderResource.source_options.customer_checkout'),
                        'agent_manual_sale' => __('filament-resources.resources.OrderResource.source_options.agent_manual_sale'),
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'customer_checkout' => 'info',
                        'agent_manual_sale' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->label(__('filament-resources.resources.InvoiceResource.payment_status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('filament-resources.resources.InvoiceResource.order_status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'delivered' => 'success',
                        'shipped' => 'info',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('invoice_sent_at')
                    ->label(__('filament-resources.resources.InvoiceResource.sent_at'))
                    ->dateTime('d M Y H:i')
                    ->placeholder(__('filament-resources.resources.InvoiceResource.not_sent'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('filament-resources.common.created_at'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('source')
                    ->label(__('filament-resources.resources.InvoiceResource.source'))
                    ->options([
                        'customer_checkout' => __('filament-resources.resources.OrderResource.source_options.customer_checkout'),
                        'agent_manual_sale' => __('filament-resources.resources.OrderResource.source_options.agent_manual_sale'),
                    ]),

                SelectFilter::make('payment_status')
                    ->label(__('filament-resources.resources.InvoiceResource.payment_status'))
                    ->options([
                        'pending' => __('filament-resources.resources.OrderResource.payment_status_options.pending'),
                        'completed' => __('filament-resources.resources.OrderResource.payment_status_options.completed'),
                        'failed' => __('filament-resources.resources.OrderResource.payment_status_options.failed'),
                    ]),

                Filter::make('sent')
                    ->label(__('filament-resources.resources.InvoiceResource.sent_filter'))
                    ->query(fn (Builder $query) => $query->whereNotNull('invoice_sent_at')),

                Filter::make('not_sent')
                    ->label(__('filament-resources.resources.InvoiceResource.not_sent_filter'))
                    ->query(fn (Builder $query) => $query->whereNull('invoice_sent_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label(__('filament-resources.resources.InvoiceResource.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Order $record) {
                        $invoiceService = app(InvoiceService::class);
                        $pdf = $invoiceService->generatePdf($record);
                        $filename = $invoiceService->getFileName($record);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $filename
                        );
                    }),

                Tables\Actions\Action::make('mark_sent')
                    ->label(__('filament-resources.resources.InvoiceResource.mark_sent'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => empty($record->invoice_sent_at))
                    ->action(function (Order $record) {
                        $record->update(['invoice_sent_at' => now()]);

                        Notification::make()
                            ->title(__('filament-resources.resources.InvoiceResource.marked_sent_success'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_order')
                    ->label(__('filament-resources.resources.InvoiceResource.view_order'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Order $record) => OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_sent_bulk')
                    ->label(__('filament-resources.resources.InvoiceResource.mark_sent'))
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $count = $records->whereNull('invoice_sent_at')->count();
                        $records->each(function (Order $record) {
                            if (empty($record->invoice_sent_at)) {
                                $record->update(['invoice_sent_at' => now()]);
                            }
                        });

                        Notification::make()
                            ->title(__('filament-resources.resources.InvoiceResource.bulk_marked_sent', ['count' => $count]))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('download_bulk')
                    ->label(__('filament-resources.resources.InvoiceResource.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        // For single record, download directly
                        if ($records->count() === 1) {
                            $record = $records->first();
                            $invoiceService = app(InvoiceService::class);
                            $pdf = $invoiceService->generatePdf($record);
                            $filename = $invoiceService->getFileName($record);

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                $filename
                            );
                        }

                        Notification::make()
                            ->title(__('filament-resources.resources.InvoiceResource.use_single_download'))
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
