@extends('layouts.app')

@section('title', 'Approvals')
@section('heading', 'Approvals')

@section('content')
    @php
        $statusStyle = fn ($s) => match ($s) {
            'approved' => 'bg-ok-soft text-ok',
            'pending' => 'bg-warn-soft text-warn',
            'returned' => 'bg-info-soft text-info',
            'rejected' => 'bg-crit-soft text-crit',
            default => 'bg-paper text-muted',
        };
        $tabs = $isReviewer
            ? [
                'awaiting' => ['label' => 'Waiting for me', 'count' => $awaitingCount],
                'mine' => ['label' => 'Raised by me', 'count' => $mineCount],
                'all' => ['label' => 'All requests', 'count' => null],
            ]
            : [
                'mine' => ['label' => 'My requests', 'count' => $mineCount],
            ];
    @endphp

    <div>
        <h1 class="font-display text-2xl font-semibold">{{ $isReviewer ? 'Approvals' : 'My requests' }}</h1>
        <p class="mt-0.5 text-sm text-muted">{{ $isReviewer ? 'Every request moves maker → checker → MD, and each step is written to the audit trail.' : 'Requests you raised and their progress through approval.' }}</p>
    </div>

    <div class="mt-4 flex flex-wrap gap-1.5">
        @foreach ($tabs as $key => $meta)
            <a href="{{ route('approvals.index', ['tab' => $key]) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $tab === $key ? 'bg-brand text-white' : 'bg-surface text-muted ring-1 ring-line' }}">
                {{ $meta['label'] }}@if ($meta['count'] !== null) · {{ $meta['count'] }}@endif
            </a>
        @endforeach
    </div>

    <div class="mt-3 overflow-hidden rounded-xl border border-line bg-surface">
        @forelse ($items as $request)
            <a href="{{ route('approvals.show', $request) }}" class="flex items-start gap-3 border-b border-line px-4 py-3.5 last:border-0 hover:bg-paper">
                <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg {{ $request->maker_role === 'staff' ? 'bg-brand-soft text-brand' : 'bg-accent-soft text-accent' }} text-[10px] font-bold">
                    {{ $request->maker_role === 'staff' ? 'ST' : 'HR' }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="truncate text-sm font-medium">{{ $request->subject }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusStyle($request->status) }}">{{ ucfirst($request->status) }}</span>
                    </span>
                    <span class="mt-0.5 block text-[11px] text-muted">{{ $request->ref }} · {{ $request->kind }} · {{ $request->created_at->format('j M Y, H:i') }}</span>
                </span>
                <span class="hidden shrink-0 text-right text-[11px] text-muted sm:block">
                    @if ($request->status === 'pending')
                        with {{ ($request->chain[$request->stage] ?? 'md') === 'md' ? 'MD' : 'HR' }}
                        <span class="block text-faint">stage {{ $request->stage + 1 }} of {{ count($request->chain) }}</span>
                    @else
                        {{ $request->actions->last()?->acted_at?->format('j M') }}
                        <span class="block text-faint">{{ $request->actions->last()?->actor_role }}</span>
                    @endif
                </span>
            </a>
        @empty
            <div class="px-4 py-14 text-center">
                <p class="text-sm font-medium">{{ $tab === 'awaiting' ? 'Nothing is waiting for you.' : 'No requests here yet.' }}</p>
                <p class="mt-1 text-xs text-muted">{{ $tab === 'awaiting' ? 'Requests will appear once the person before you acts.' : 'Requests you raise will appear here with their progress.' }}</p>
            </div>
        @endforelse
    </div>
@endsection
