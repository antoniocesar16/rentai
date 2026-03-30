<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class EditProfile extends Page
{
    protected string $view = 'filament.pages.edit-profile';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => 'Edit Profile',
        ];
    }
}
