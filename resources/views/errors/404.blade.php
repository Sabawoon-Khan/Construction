@extends('errors.layout')

@section('title', __('Page not found') . ' — ' . config('app.name'))

@section('content')
    <div class="error-code">404</div>
    <h1 class="error-title">{{ __('Page not found') }}</h1>
    <p class="error-message">{{ __('The page you are looking for does not exist or has been moved.') }}</p>

    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn btn-primary">
            {{ __('Go home') }}
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-outline">
            {{ __('Go back') }}
        </a>
    </div>
@endsection
