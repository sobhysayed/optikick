<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HealthInfoCardResource\Pages;
use App\Models\HealthInfoCard;
use Filament\Forms;
use Filament\Forms\Form;  // Updated import
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;  // Updated import

class HealthInfoCardResource extends Resource
{
    protected static ?string $model = HealthInfoCard::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Health Management';

    public static function form(Form $form): Form  // Updated type hint
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->maxLength(1000),
                Forms\Components\TextInput::make('icon_key')
                    ->required()
                    ->maxLength(50)
                    ->helperText('Enter the icon key for the card'),
            ]);
    }

    public static function table(Table $table): Table  // Updated type hint
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('icon_key'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListHealthInfoCards::route('/'),
            'create' => Pages\CreateHealthInfoCard::route('/create'),
            'edit' => Pages\EditHealthInfoCard::route('/{record}/edit'),
        ];
    }
}
