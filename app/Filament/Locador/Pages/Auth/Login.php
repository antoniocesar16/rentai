<?php

namespace App\Filament\Locador\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Entrar — Apartamento WhatsApp Locadores';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Bem-vindo de volta!';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        if (filament()->hasRegistration()) {
            return new \Illuminate\Support\HtmlString(
                'Não tem conta? ' . $this->registerAction->toHtml()
            );
        }

        return 'Acesse sua conta de locador para gerenciar seus imóveis.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-mail')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->placeholder('seu@email.com');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Senha')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->placeholder('••••••••');
    }
}
