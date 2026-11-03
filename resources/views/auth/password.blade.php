@extends('layouts.app')

@section('title', 'Change password')
@section('heading', 'Change password')

@section('content')
    <div class="mx-auto max-w-md">
        @if ($forced)
            <div class="mb-4 rounded-lg border border-warn/25 bg-warn-soft px-4 py-3 text-sm text-warn">
                <p class="font-semibold">Choose a new password before continuing.</p>
                <p class="mt-0.5">Your account was set up with a temporary password.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="rounded-xl border border-line bg-surface p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <label class="block text-sm font-medium">Current password
                    <input type="password" name="current_password" required autocomplete="current-password"
                           class="mt-1 w-full rounded-lg border-line bg-white px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                </label>
                <label class="block text-sm font-medium">New password
                    <input type="password" name="password" required minlength="8" autocomplete="new-password"
                           class="mt-1 w-full rounded-lg border-line bg-white px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <span class="mt-1 block text-xs font-normal text-muted">At least 8 characters.</span>
                </label>
                <label class="block text-sm font-medium">Confirm new password
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                           class="mt-1 w-full rounded-lg border-line bg-white px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                </label>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-strong">Save new password</button>
                @unless ($forced)
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-muted hover:text-ink">Cancel</a>
                @endunless
            </div>
        </form>
    </div>
@endsection
