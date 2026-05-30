<div>
    <flux:heading size="xl" class="mb-2">{{ __('settings.title') }}</flux:heading>
    <p class="mb-6 text-sm text-zinc-500">{{ __('settings.subtitle') }}</p>

    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs uppercase text-zinc-500">{{ __('settings.image_limit') }}</p>
            <p class="mt-1 text-lg font-semibold">{{ $maxImageKb }} KB</p>
            <p class="mt-1 text-xs text-zinc-400">{{ __('settings.image_limit_hint') }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs uppercase text-zinc-500">{{ __('settings.retention') }}</p>
            <p class="mt-1 text-lg font-semibold">{{ $retentionMonths }} {{ __('settings.months') }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs uppercase text-zinc-500">{{ __('settings.storage_disk') }}</p>
            <p class="mt-1 text-sm font-medium">storage/app/documents</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-2">{{ __('settings.cleanup_title') }}</flux:heading>
        <p class="mb-4 text-sm text-zinc-500">
            {{ __('settings.cleanup_desc', ['date' => $cutoffDate, 'months' => $retentionMonths]) }}
        </p>

        <div class="mb-4 flex flex-wrap gap-2">
            <flux:button wire:click="scan" variant="primary" icon="magnifying-glass">{{ __('settings.scan') }}</flux:button>
            @if($scanned && count($items) > 0)
                <flux:button wire:click="toggleSelectAll" variant="ghost">{{ __('settings.toggle_all') }}</flux:button>
                <flux:button
                    wire:click="deleteSelected"
                    wire:confirm="{{ __('settings.delete_confirm') }}"
                    variant="danger"
                    icon="trash"
                >
                    {{ __('settings.delete_selected', ['count' => count($selected)]) }}
                </flux:button>
            @endif
        </div>

        @if($scanned)
            <p class="mb-4 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('settings.found', ['count' => count($items), 'size' => $totalHuman]) }}
            </p>
            @if(count($items) === 0)
                <p class="text-sm text-zinc-500">{{ __('settings.nothing_to_delete') }}</p>
            @else
                <div class="max-h-96 overflow-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-3 py-2 text-start w-8"></th>
                                <th class="px-3 py-2 text-start">{{ __('settings.col_type') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('settings.col_context') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('settings.col_file') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('settings.col_date') }}</th>
                                <th class="px-3 py-2 text-end">{{ __('settings.col_size') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($items as $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-3 py-2">
                                    <input type="checkbox" wire:model="selected" value="{{ $item['id'] }}" class="rounded border-zinc-300" />
                                </td>
                                <td class="px-3 py-2">{{ $item['label'] }}</td>
                                <td class="px-3 py-2 text-zinc-600">{{ $item['context'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $item['basename'] }}</td>
                                <td class="px-3 py-2 text-zinc-500">{{ $item['uploaded_at'] }}</td>
                                <td class="px-3 py-2 text-end">{{ $item['size_human'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
