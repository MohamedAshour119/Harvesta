<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class AdminAIChatbot extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('admin-chatbot.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin-chatbot.nav_group');
    }

    public function getTitle(): string
    {
        return __('admin-chatbot.page_title');
    }

    protected static string $view = 'filament.pages.admin-ai-chatbot';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user ? Gate::forUser($user)->allows('superAdminAccess') : false;
    }

    public static function getNavigationBadge(): ?string
    {
        return 'AI';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
