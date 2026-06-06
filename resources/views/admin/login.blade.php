@extends('filament::layouts.app')

@section('content')
    <div class="flex items-center justify-center h-screen">
        <a href="{{ route('filament.auth.login') }}" class="px-4 py-2 bg-primary-600 text-white rounded">Login to Admin Panel</a>
    </div>
@endsection
