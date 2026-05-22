<?php

namespace App\Filament\Resources\Users\Schemas;

// Tambahkan import Select di bagian atas
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),

               // DateTimePicker::make('email_verified_at'),

                // --- FIELD ROLE BARU DI SINI ---
                Select::make('role_id') // Sesuaikan nama input dengan nama fungsi relasi di model User Anda (misal: 'roles' atau 'role')
                    ->label('Role / Hak Akses')
                    ->relationship('role', 'nama_role') // Parameter 1: nama relasi di model, Parameter 2: kolom nama role yang mau ditampilkan
                    ->preload() // Memuat data di awal agar pencarian lebih cepat dan responsif
                    ->required(), // Wajib diisi saat membuat user baru

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->label('Password'),

                TextInput::make('password_confirmation')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->same('password')
                    ->dehydrated(false)
                    ->label('Konfirmasi Password'),
            ]);
    }
}
