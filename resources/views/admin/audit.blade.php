@extends('layouts.app')

@section('title', 'Audit trail')
@section('heading', 'Administration')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Administration</p>
            <h2 class="mt-1 font-display text-xl font-semibold">Audit trail</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">Read-only log of every action. Nobody edits or deletes entries, including system admins.</p>
        </div>
        <form method="GET" action="{{ route('admin.audit') }}">
            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-muted">Filter by role
                <select name="role" onchange="this.form.submit()" class="mt-1 block min-w-[190px] rounded-lg border border-line bg-white px-3 py-2 text-sm font-normal normal-case tracking-normal text-ink">
                    <option value="">All roles</option>
                    @foreach ($roles as $code => $label)
                        <option value="{{ $code }}" @selected($role === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface">
        @if ($rows->isEmpty())
            <p class="px-4 py-10 text-center text-sm text-muted">No entries for this role.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-line bg-paper text-[11px] uppercase tracking-[0.1em] text-muted">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Time</th>
                        <th class="px-4 py-3 font-semibold">User</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                        <th class="px-4 py-3 font-semibold">Reference</th>
                        <th class="px-4 py-3 font-semibold">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $row->created_at?->format('j M Y, H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="block font-medium">{{ $names[$row->actor] ?? ($row->actor ?: 'System') }}</span>
                                <span class="block text-xs text-muted">{{ $row->actor_role ? \App\Support\PermissionCatalog::roleLabel($row->actor_role) : 'System' }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $row->action }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-muted">{{ $row->ref ?: '-' }}</td>
                            <td class="px-4 py-3 text-muted">{{ $row->detail }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
@endsection
