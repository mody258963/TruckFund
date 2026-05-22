<div>
    <flux:heading size="xl" class="mb-6">{{ __('nav.audit') }}</flux:heading>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('audit.entity') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('audit.user') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('audit.when') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($logs as $log)
                <tr>
                    <td class="px-4 py-3">{{ $log->entity_type }} #{{ Str::limit($log->entity_id, 8) }}</td>
                    <td class="px-4 py-3">{{ $log->changedByUser?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $log->changed_at?->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
