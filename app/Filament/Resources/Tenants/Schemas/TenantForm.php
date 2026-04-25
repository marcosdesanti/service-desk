<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Company Name')
                    ->required()
                    ->live(onBlur: true) // Aciona a atualização quando o usuário sai do campo
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        $set('slug', Str::slug($state ?? ''));
                    }),
                    
                TextInput::make('slug')
                    ->label('URL Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                
                TextInput::make('tax_id')
                    ->label('CNPJ')
                    ->placeholder('00.000.000/0000-00')
                    // Usamos 'a' para aceitar letras/números nas posições
                    // Se quiser garantir que aceite TUDO (letras e números), 
                    // a máscara abaixo usa o padrão de caracteres alfanuméricos:
                    // Salva sempre em maiúsculo
                    ->mask('**.***.***/****-**') 
                    ->dehydrateStateUsing(fn (string $state) => mb_strtoupper($state)),


                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_master')
                    ->required(),
            ]);
    }
}
