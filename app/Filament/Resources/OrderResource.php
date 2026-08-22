<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Http\Services\ManualOrderApprovalService;
use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class OrderResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'icon-orders';
    
    public static function getModelLabel(): string 
    {
        return __('filament-resources.resources.OrderResource.label');
    }

    public static function getPluralModelLabel(): string 
    {
        return __('filament-resources.resources.OrderResource.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-resources.groups_names.inventory_and_orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('filament-resources.resources.OrderResource.sections.general'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->label(__('filament-resources.resources.OrderResource.user'))
                                    ->nullable(),
                                TextInput::make('agent_code')
                                    ->label(__('filament-resources.resources.OrderResource.agent_code'))
                                    ->maxLength(255),
                                Select::make('coupon_id')
                                    ->relationship('coupon', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->label(__('filament-resources.resources.OrderResource.coupon'))
                                    ->nullable(),
                                Select::make('source')
                                    ->label(__('filament-resources.resources.OrderResource.source'))
                                    ->options([
                                        'customer_checkout' => __('filament-resources.resources.OrderResource.source_options.customer_checkout'),
                                        'agent_manual_sale' => __('filament-resources.resources.OrderResource.source_options.agent_manual_sale'),
                                    ])
                                    ->native(false)
                                    ->disabled(),
                            ]),
                    ]),

                Section::make(__('filament-resources.resources.OrderResource.sections.customer_info'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label(__('filament-resources.resources.OrderResource.first_name'))
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label(__('filament-resources.resources.OrderResource.last_name'))
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label(__('filament-resources.resources.OrderResource.email'))
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone_number')
                                    ->label(__('filament-resources.resources.OrderResource.phone_number'))
                                    ->maxLength(255),
                                TextInput::make('company_name')
                                    ->label(__('filament-resources.resources.OrderResource.company_name'))
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make(__('filament-resources.resources.OrderResource.sections.address'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('address')
                                    ->label(__('filament-resources.resources.OrderResource.address'))
                                    ->columnSpan(2),
                                TextInput::make('city')
                                    ->label(__('filament-resources.resources.OrderResource.city')),
                                TextInput::make('province')
                                    ->label(__('filament-resources.resources.OrderResource.province')),
                                TextInput::make('postal_code')
                                    ->label(__('filament-resources.resources.OrderResource.postal_code')),
                            ]),
                    ]),

                Section::make(__('filament-resources.resources.OrderResource.manual_sale_review'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('manual_sale_status')
                                    ->label(__('filament-resources.resources.OrderResource.manual_sale_status'))
                                    ->options([
                                        'submitted' => __('filament-resources.resources.OrderResource.manual_sale_status_options.submitted'),
                                        'approved' => __('filament-resources.resources.OrderResource.manual_sale_status_options.approved'),
                                        'rejected' => __('filament-resources.resources.OrderResource.manual_sale_status_options.rejected'),
                                    ])
                                    ->native(false)
                                    ->disabled(),
                                DateTimePicker::make('sold_at')
                                    ->label(__('filament-resources.resources.OrderResource.sold_at'))
                                    ->disabled(),
                                DateTimePicker::make('approved_at')
                                    ->label(__('filament-resources.resources.OrderResource.reviewed_at'))
                                    ->disabled(),
                            ]),
                        Textarea::make('review_notes')
                            ->label(__('filament-resources.resources.OrderResource.review_notes'))
                            ->rows(3),
                        SpatieMediaLibraryFileUpload::make('manual_sale_proof_photo')
                            ->label(__('filament-resources.resources.OrderResource.visit_proof_photo'))
                            ->collection('manual_sale_proof_photo')
                            ->image()
                            ->disabled(),
                        Placeholder::make('order_lines')
                            ->label(__('filament-resources.resources.OrderResource.order_lines'))
                            ->content(function (?Order $record): HtmlString {
                                if (!$record) {
                                    return new HtmlString('<div style="color:#94a3b8;">-</div>');
                                }

                                $items = $record->items()
                                    ->with(['product', 'variant'])
                                    ->get();

                                if ($items->isEmpty()) {
                                    return new HtmlString('
                                        <div style="
                                            border:1px dashed rgba(148,163,184,0.35);
                                            border-radius:20px;
                                            padding:18px;
                                            background:rgba(15,23,42,0.18);
                                            color:#94a3b8;
                                        ">
                                            ' . e(__('filament-resources.resources.OrderResource.no_order_lines')) . '
                                        </div>
                                    ');
                                }

                                $cards = $items->map(function ($item) {
                                    $productName = $item->product?->name ?? __('filament-resources.resources.OrderResource.unknown_product');
                                    $variantValue = $item->variant?->variant_value ?: __('filament-resources.resources.OrderResource.default_variant');
                                    $quantity = (int) $item->quantity;
                                    $lineTotal = number_format((float) ($item->total ?? 0), 2);
                                    $unitPrice = number_format((float) ($item->unit_price ?? 0), 2);

                                    return '
                                        <div style="
                                            border:1px solid rgba(251,191,36,0.22);
                                            background:linear-gradient(180deg, rgba(15,23,42,0.22), rgba(15,23,42,0.12));
                                            border-radius:22px;
                                            padding:18px;
                                            box-shadow:0 14px 35px rgba(15,23,42,0.12);
                                        ">
                                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                                                <div>
                                                    <div style="font-size:16px;font-weight:700;color:#f8fafc;">' . e($productName) . '</div>
                                                    <div style="margin-top:8px;display:inline-flex;align-items:center;border:1px solid rgba(251,191,36,0.24);border-radius:999px;padding:6px 12px;background:rgba(255,255,255,0.04);font-size:12px;color:#fde68a;">
                                                        ' . e($variantValue) . '
                                                    </div>
                                                </div>
                                                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                                    <div style="min-width:96px;border-radius:18px;background:rgba(255,255,255,0.04);padding:10px 12px;">
                                                        <div style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;">' . e(__('filament-resources.resources.OrderResource.quantity')) . '</div>
                                                        <div style="margin-top:6px;font-size:18px;font-weight:700;color:#f8fafc;">' . $quantity . '</div>
                                                    </div>
                                                    <div style="min-width:132px;border-radius:18px;background:rgba(255,255,255,0.04);padding:10px 12px;">
                                                        <div style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;">' . e(__('filament-resources.resources.OrderResource.unit_price')) . '</div>
                                                        <div style="margin-top:6px;font-size:18px;font-weight:700;color:#f8fafc;">' . $unitPrice . ' EGP</div>
                                                    </div>
                                                    <div style="min-width:132px;border-radius:18px;background:linear-gradient(180deg, rgba(251,191,36,0.18), rgba(251,191,36,0.08));padding:10px 12px;">
                                                        <div style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#cbd5e1;">' . e(__('filament-resources.resources.OrderResource.line_total')) . '</div>
                                                        <div style="margin-top:6px;font-size:18px;font-weight:700;color:#fef3c7;">' . $lineTotal . ' EGP</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ';
                                })->implode('<div style="height:12px"></div>');

                                return new HtmlString('
                                    <div style="
                                        border:1px solid rgba(148,163,184,0.16);
                                        border-radius:28px;
                                        padding:18px;
                                        background:linear-gradient(180deg, rgba(15,23,42,0.12), rgba(15,23,42,0.05));
                                    ">
                                        ' . $cards . '
                                    </div>
                                ');
                            }),
                    ])
                    ->visible(fn (?Order $record): bool => (bool) $record?->isManualSale()),

                Section::make(__('filament-resources.resources.OrderResource.sections.payment_status'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('filament-resources.common.status'))
                                    ->native(false)
                                    ->options([
                                        'pending' => __('filament-resources.resources.OrderResource.status_options.pending'),
                                        'approved' => __('filament-resources.resources.OrderResource.status_options.approved'),
                                        'shipped' => __('filament-resources.resources.OrderResource.status_options.shipped'),
                                        'delivered' => __('filament-resources.resources.OrderResource.status_options.delivered'),
                                        'cancelled' => __('filament-resources.resources.OrderResource.status_options.cancelled'),
                                    ]),
                                Select::make('payment_status')
                                    ->label(__('filament-resources.resources.OrderResource.payment_status'))
                                    ->native(false)
                                    ->options([
                                        'pending' => __('filament-resources.resources.OrderResource.payment_status_options.pending'),
                                        'completed' => __('filament-resources.resources.OrderResource.payment_status_options.completed'),
                                        'failed' => __('filament-resources.resources.OrderResource.payment_status_options.failed'),
                                    ]),
                            ]),
                    ]),

                Section::make(__('filament-resources.resources.OrderResource.sections.amounts'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label(__('filament-resources.resources.OrderResource.subtotal'))
                                    ->numeric()
                                    ->suffix('EGP')
                                    ->step('0.01'),
                                TextInput::make('discount_amount')
                                    ->label(__('filament-resources.resources.OrderResource.discount_amount'))
                                    ->numeric()
                                    ->suffix('EGP')
                                    ->step('0.01')
                                    ->nullable(),
                                TextInput::make('total')
                                    ->label(__('filament-resources.resources.OrderResource.total'))
                                    ->numeric()
                                    ->suffix('EGP')
                                    ->step('0.01'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                SpatieMediaLibraryImageColumn::make('manual_sale_proof_photo')
                    ->label(__('filament-resources.resources.OrderResource.proof'))
                    ->collection('manual_sale_proof_photo')
                    ->height(72)
                    ->visible(fn (?Order $record): bool => (bool) $record?->isManualSale()),
                TextColumn::make('first_name')
                    ->label(__('filament-resources.resources.OrderResource.first_name')),
                TextColumn::make('last_name')
                    ->label(__('filament-resources.resources.OrderResource.last_name')),
                TextColumn::make('email')
                    ->label(__('filament-resources.resources.OrderResource.email')),
                TextColumn::make('phone_number')
                    ->label(__('filament-resources.resources.OrderResource.phone_number')),
                TextColumn::make('company_name')
                    ->label(__('filament-resources.resources.OrderResource.company_name')),
                TextColumn::make('address')
                    ->label(__('filament-resources.resources.OrderResource.address')),
                TextColumn::make('city')
                    ->label(__('filament-resources.resources.OrderResource.city')),
                TextColumn::make('province')
                    ->label(__('filament-resources.resources.OrderResource.province')),
                TextColumn::make('postal_code')
                    ->label(__('filament-resources.resources.OrderResource.postal_code')),
                TextColumn::make('subtotal')
                    ->label(__('filament-resources.resources.OrderResource.subtotal'))
                    ->money('EGP'),
                TextColumn::make('discount_amount')
                    ->label(__('filament-resources.resources.OrderResource.discount_amount'))
                    ->money('EGP'),
                TextColumn::make('total')
                    ->label(__('filament-resources.resources.OrderResource.total'))
                    ->money('EGP'),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('filament-resources.common.status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('filament-resources.resources.OrderResource.status_options.pending'),
                        'approved' => __('filament-resources.resources.OrderResource.status_options.approved'),
                        'shipped' => __('filament-resources.resources.OrderResource.status_options.shipped'),
                        'delivered' => __('filament-resources.resources.OrderResource.status_options.delivered'),
                        'cancelled' => __('filament-resources.resources.OrderResource.status_options.cancelled'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->label(__('filament-resources.resources.OrderResource.source'))
                    ->color(fn (string $state): string => $state === 'agent_manual_sale' ? 'warning' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'agent_manual_sale'
                        ? __('filament-resources.resources.OrderResource.source_options.agent_manual_sale')
                        : __('filament-resources.resources.OrderResource.source_options.customer_checkout')),
                TextColumn::make('manual_sale_status')
                    ->badge()
                    ->label(__('filament-resources.resources.OrderResource.manual_sale_status'))
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'submitted' => __('filament-resources.resources.OrderResource.manual_sale_status_options.submitted'),
                        'approved' => __('filament-resources.resources.OrderResource.manual_sale_status_options.approved'),
                        'rejected' => __('filament-resources.resources.OrderResource.manual_sale_status_options.rejected'),
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('sold_at')
                    ->label(__('filament-resources.resources.OrderResource.sold_at'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('agent_code')
                    ->label(__('filament-resources.resources.OrderResource.agent_code')),
                TextColumn::make('payment_status')
                    ->badge()
                    ->label(__('filament-resources.resources.OrderResource.payment_status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('filament-resources.resources.OrderResource.payment_status_options.pending'),
                        'completed' => __('filament-resources.resources.OrderResource.payment_status_options.completed'),
                        'failed' => __('filament-resources.resources.OrderResource.payment_status_options.failed'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coupon.name')
                    ->label(__('filament-resources.resources.OrderResource.coupon')),

                
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label(__('filament-resources.resources.OrderResource.source'))
                    ->options([
                        'customer_checkout' => __('filament-resources.resources.OrderResource.source_options.customer_checkout'),
                        'agent_manual_sale' => __('filament-resources.resources.OrderResource.source_options.agent_manual_sale'),
                    ])
                    ->native(false),
                SelectFilter::make('manual_sale_status')
                    ->label(__('filament-resources.resources.OrderResource.manual_sale_status'))
                    ->options([
                        'submitted' => __('filament-resources.resources.OrderResource.manual_sale_status_options.submitted'),
                        'approved' => __('filament-resources.resources.OrderResource.manual_sale_status_options.approved'),
                        'rejected' => __('filament-resources.resources.OrderResource.manual_sale_status_options.rejected'),
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve_manual_sale')
                    ->label(__('filament-resources.resources.OrderResource.approve'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (?Order $record): bool => (bool) $record?->isManualSale() && $record?->manual_sale_status === 'submitted')
                    ->form([
                        Textarea::make('review_notes')
                            ->label(__('filament-resources.resources.OrderResource.approval_note'))
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(ManualOrderApprovalService::class)->approve($record, auth()->user(), $data['review_notes'] ?? null);

                            Notification::make()
                                ->title(__('filament-resources.resources.OrderResource.manual_sale_approved'))
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        } catch (\RuntimeException $exception) {
                            Notification::make()
                                ->title(__('filament-resources.resources.OrderResource.insufficient_stock_title', ['default' => 'Insufficient Stock']))
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject_manual_sale')
                    ->label(__('filament-resources.resources.OrderResource.reject'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (?Order $record): bool => (bool) $record?->isManualSale() && $record?->manual_sale_status === 'submitted')
                    ->form([
                        Textarea::make('review_notes')
                            ->label(__('filament-resources.resources.OrderResource.rejection_reason'))
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(ManualOrderApprovalService::class)->reject($record, auth()->user(), $data['review_notes'] ?? null);

                            Notification::make()
                                ->title(__('filament-resources.resources.OrderResource.manual_sale_rejected'))
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
