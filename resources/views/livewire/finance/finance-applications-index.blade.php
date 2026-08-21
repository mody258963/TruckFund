<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">{{ __('nav.finance') }}</flux:heading>
        @if($canCreate)
            <flux:button wire:click="$set('showCreate', true)" variant="primary" icon="plus">{{ __('finance.create') }}</flux:button>
        @endif
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" class="max-w-xs" placeholder="{{ __('nav.search') }}" />
        <div class="min-w-48">
            <label class="mb-1 block text-xs font-medium text-zinc-500">{{ __('finance.funder_status') }}</label>
            <select
                wire:model.live="funderStatusFilter"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
            >
                <option value="">{{ __('finance.funder_filter_all') }}</option>
                <option value="none">{{ __('finance.funder_filter_none') }}</option>
                @foreach($funderStatuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm text-zinc-700 dark:text-zinc-300">
            <input type="checkbox" wire:model.live="reentryDueOnly" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500" />
            {{ __('finance.reentry_due_only') }}
        </label>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.app_number') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.name') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.status') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.funder_status') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.loan_amount') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($applications as $app)
                @php
                    $rowClass = match (true) {
                        $app->funder_status === \App\Enums\FunderReviewStatus::NeedsAction => 'bg-amber-50 dark:bg-amber-950/20',
                        $app->funder_status === \App\Enums\FunderReviewStatus::Rejected => 'bg-red-50 dark:bg-red-950/20',
                        $app->isReentryDue() => 'bg-orange-50 dark:bg-orange-950/20',
                        default => '',
                    };
                @endphp
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 {{ $rowClass }}">
                    <td class="px-4 py-3">
                        <a href="{{ route('finance.show', $app) }}" class="text-teal-600 hover:underline" wire:navigate>{{ $app->app_number }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $app->customer?->display_name }}</td>
                    <td class="px-4 py-3"><flux:badge color="{{ $app->status->color() }}">{{ $app->status->label() }}</flux:badge></td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @if($app->funder_status)
                                <flux:badge color="{{ $app->funder_status->color() }}" size="sm">{{ $app->funder_status->label() }}</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">{{ __('finance.funder_pending') }}</span>
                            @endif
                            @if($app->isReentryDue())
                                <flux:badge color="red" size="sm">{{ __('finance.reentry_overdue') }}</flux:badge>
                            @elseif($app->isReentryUpcoming())
                                <flux:badge color="amber" size="sm">{{ __('finance.reentry_soon') }}</flux:badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ number_format($app->total_loan_amount, 2) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $app->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $applications->links() }}</div>
    @if($showCreate && $canCreate)
    <flux:modal wire:model="showCreate">
        <flux:heading>{{ __('finance.create') }}</flux:heading>
        <form wire:submit="create" class="mt-4 space-y-4">
            <flux:select wire:model="customer_id" label="{{ __('customers.name') }}" required>
                <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                @foreach($customers as $c)
                    <flux:select.option value="{{ $c->customer_id }}">{{ $c->display_name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
        </form>
    </flux:modal>
    @endif
</div>
