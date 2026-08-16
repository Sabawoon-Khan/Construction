@extends('errors.layout')

@section('title', __('Access denied') . ' — ' . config('app.name'))

@section('content')
    <div class="error-code">403</div>
    <h1 class="error-title">{{ __('Access denied') }}</h1>
    <p class="error-message">{{ __('You do not have permission to view this page.') }}</p>

    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn btn-primary">
            {{ __('Go home') }}
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-outline">
            {{ __('Go back') }}
        </a>
    </div>
@endsection
