<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Imóveis';
    protected static ?string $modelLabel = 'Imóvel';
    protected static ?string $pluralModelLabel = 'Imóveis';
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Título')->required(),
            TextInput::make('price')->label('Preço')->numeric()->prefix('R$')->required(),
            TextInput::make('location')->label('Localização')->required(),
            TextInput::make('contact_phone')->label('Telefone'),
            Textarea::make('description')->label('Descrição')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Título')->searchable()->sortable(),
            TextColumn::make('user.name')->label('Proprietário')->searchable()->sortable(),
            TextColumn::make('price')->label('Preço')->money('BRL')->sortable(),
            TextColumn::make('location')->label('Localização')->searchable(),
            TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
