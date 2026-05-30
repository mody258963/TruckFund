<div>
    <flux:heading size="xl" class="mb-6">{{ __('nav.dashboard') }}</flux:heading>
    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">{{ __('dashboard.new_leads') }}</p>
            <p class="mt-2 text-3xl font-bold text-teal-600">{{ $newLeads }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/30">
            <p class="text-sm text-amber-700 dark:text-amber-400">{{ __('dashboard.priority_leads') }}</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $priorityLeads }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">{{ __('dashboard.pending_review') }}</p>
            <p class="mt-2 text-3xl font-bold text-teal-600">{{ $pendingReview }}</p>
        </div>
    </div>
    <flux:heading size="lg" class="mb-4">{{ __('dashboard.recent_leads') }}</flux:heading>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.number') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.customer') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.status') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.value') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($recentLeads as $lead)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                    <td class="px-4 py-3"><a href="{{ route('leads.show', $lead) }}" class="text-teal-600 hover:underline">{{ $lead->lead_number }}</a></td>
                    <td class="px-4 py-3">{{ $lead->customer_name }}</td>
                    <td class="px-4 py-3"><flux:badge color="{{ $lead->status->color() }}">{{ $lead->status->label() }}</flux:badge></td>
                    <td class="px-4 py-3">{{ $lead->value?->label() ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $lead->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
