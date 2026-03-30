<?php

namespace App\Filament\Locador\Resources;

use App\Filament\Locador\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use BackedEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $modelLabel = 'Imóvel';

    protected static ?string $pluralModelLabel = 'Imóveis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações Básicas')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título do Anúncio')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Apartamento 2 Quartos no Centro'),

                        Textarea::make('description')
                            ->label('Descrição Completa')
                            ->rows(4),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('price')
                                    ->label('Valor do Aluguel (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required(),

                                TextInput::make('location')
                                    ->label('Localização (Bairro/Cidade)')
                                    ->required()
                                    ->placeholder('Ex: Batel, Curitiba'),

                                TextInput::make('contact_phone')
                                    ->label('Telefone de Contato')
                                    ->tel()
                                    ->placeholder('(00) 00000-0000'),
                            ]),
                    ]),

                Section::make('Detalhes do Imóvel')
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
                            ->defaultItems(1)
                            ->grid(2),
                    ]),

                Section::make('Fotos')
                    ->schema([
                        FileUpload::make('photos')
                            ->label('Fotos do Imóvel')
                            ->multiple()
                            ->image()
                            ->directory('properties'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Localização')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Contato')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }
}
