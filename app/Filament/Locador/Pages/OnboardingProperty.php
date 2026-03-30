<?php

namespace App\Filament\Locador\Pages;

use App\Models\Property;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class OnboardingProperty extends Page
{

    protected string $view = 'filament.locador.pages.onboarding-property';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Cadastre seu Primeiro Imóvel';

    protected static ?string $slug = 'onboarding';

    public ?array $data = [];

    public function mount(): void
    {
        // If user already has properties, skip onboarding
        if (Property::where('user_id', Auth::id())->exists()) {
            $this->redirect(filament()->getUrl());
            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informações Básicas')
                    ->description('Preencha os dados do seu imóvel para começar a anunciar.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título do Anúncio')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Apartamento 2 Quartos no Centro')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descrição Completa')
                            ->rows(4)
                            ->placeholder('Descreva seu imóvel com detalhes...')
                            ->columnSpanFull(),

                        TextInput::make('price')
                            ->label('Valor do Aluguel (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->placeholder('0,00'),

                        TextInput::make('location')
                            ->label('Localização (Bairro/Cidade)')
                            ->required()
                            ->placeholder('Ex: Batel, Curitiba'),

                        TextInput::make('contact_phone')
                            ->label('Telefone de Contato')
                            ->tel()
                            ->placeholder('(00) 00000-0000'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Section::make('Detalhes do Imóvel')
                            ->description('Características que destacam seu imóvel.')
                            ->schema([
                                Repeater::make('details')
                                    ->label('Características')
                                    ->schema([
                                        TextInput::make('detail')
                                            ->label('Detalhe')
                                            ->placeholder('Ex: 2 Vagas de Garagem, Piscina, etc.')
                                            ->required(),
                                    ])
                                    ->addActionLabel('Adicionar mais um detalhe')
                                    ->defaultItems(1),
                            ]),

                        Section::make('Fotos do Imóvel')
                            ->description('Fotos de qualidade atraem mais interessados.')
                            ->schema([
                                FileUpload::make('photos')
                                    ->label('Fotos')
                                    ->multiple()
                                    ->image()
                                    ->directory('properties')
                                    ->hint('Adicione pelo menos 3 fotos de boa qualidade.'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $data['user_id'] = Auth::id();

        Property::create($data);

        Notification::make()
            ->title('Imóvel cadastrado com sucesso!')
            ->body('Seu primeiro imóvel foi adicionado. Bem-vindo ao painel de locadores!')
            ->success()
            ->send();

        $this->redirect(filament()->getUrl());
    }

    public function skip(): void
    {
        session()->put('onboarding_skipped', true);
        $this->redirect(filament()->getUrl());
    }
}
