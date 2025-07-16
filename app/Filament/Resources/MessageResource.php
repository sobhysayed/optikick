<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('conversation_id')
                    ->relationship('conversation', 'id')
                    ->required(),
                Forms\Components\Select::make('sender_id')
                    ->relationship('sender', 'name')
                    ->required(),
                Forms\Components\Select::make('recipient_id')
                    ->relationship('recipient', 'name')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'voice' => 'Voice',
                        'photo' => 'Photo',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('content')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('file_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('reaction')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('read_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipient.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('content')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('read_at')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'voice' => 'Voice',
                        'photo' => 'Photo',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
        ];
    }
}
