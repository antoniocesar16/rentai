<?php

namespace App\Filament\Locador\Pages\Auth;

use App\Filament\Locador\Pages\OnboardingProperty;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Filament::auth()->login($user);

        session()->regenerate();

        return new class implements RegistrationResponse {
            public function toResponse($request)
            {
                return redirect()->to(OnboardingProperty::getUrl());
            }
        };
    }

    public function getTitle(): string|Htmlable
    {
        return 'Criar Conta — Apartamento WhatsApp Locadores';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Crie sua conta de locador';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filament()->hasLogin()) {
            return new \Illuminate\Support\HtmlString(
                'Já tem conta? ' . $this->loginAction->toHtml()
            );
        }

        return 'Cadastre-se gratuitamente e comece a anunciar seus imóveis.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getWhatsappFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nome Completo')
            ->required()
            ->maxLength(255)
            ->autofocus()
            ->placeholder('Ex: João Silva');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-mail')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->placeholder('seu@email.com');
    }

    protected function getWhatsappFormComponent(): Component
    {
        return TextInput::make('whatsapp')
            ->label('WhatsApp')
            ->tel()
            ->required()
            ->maxLength(20)
            ->placeholder('(00) 00000-0000');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Senha')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule('min:8')
            ->placeholder('Mínimo 8 caracteres')
            ->dehydrateStateUsing(fn($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute('senha');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Confirmar Senha')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->placeholder('Repita a senha')
            ->dehydrated(false);
    }

    protected function handleRegistration(array $data): Model
    {
        $whatsapp = $data['whatsapp'] ?? null;
        unset($data['whatsapp']);

        $user = $this->getUserModel()::create($data);

        // Create the role if it doesn't exist, then assign it
        if (method_exists($user, 'assignRole')) {
            $roleClass = \Spatie\Permission\Models\Role::class;
            $roleClass::findOrCreate('landlord', 'web');
            $user->assignRole('landlord');
        }

        return $user;
    }
}
