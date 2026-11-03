@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-muted">
            Showing
            @if ($paginator->firstItem())
                <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span>
            @else
                <span class="font-semibold text-ink">{{ $paginator->count() }}</span>
            @endif
            of
            <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
            {{ \Illuminate\Support\Str::plural('record', $paginator->total()) }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex size-8 cursor-not-allowed items-center justify-center rounded-lg border border-line bg-paper text-faint">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex size-8 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:border-brand hover:text-brand">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex size-8 items-center justify-center text-xs text-faint">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex size-8 items-center justify-center rounded-lg bg-brand text-xs font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="hidden size-8 items-center justify-center rounded-lg border border-line bg-surface text-xs font-medium text-muted transition hover:border-brand hover:text-brand sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <span class="px-1 text-[11px] text-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex size-8 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:border-brand hover:text-brand">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex size-8 cursor-not-allowed items-center justify-center rounded-lg border border-line bg-paper text-faint">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif