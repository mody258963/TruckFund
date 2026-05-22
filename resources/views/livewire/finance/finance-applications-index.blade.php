<div>
    <div class="mb-6 flex justify-between">
        <flux:heading size="xl">{{ __('nav.finance') }}</flux:heading>
        <flux:button wire:click="$set('showCreate', true)" variant="primary" icon="plus">{{ __('finance.create') }}</flux:button>
    </div>
    <flux:input wire:model.live.debounce.300ms="search" class="mb-4 max-w-xs" placeholder="{{ __('nav.search') }}" />
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.app_number') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.name') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.status') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('finance.loan_amount') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($applications as $app)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                    <td class="px-4 py-3"><a href="{{ route('finance.show', $app) }}" class="text-teal-600 hover:underline">{{ $app->app_number }}</a></td>
                    <td class="px-4 py-3">{{ $app->customer?->display_name }}</td>
                    <td class="px-4 py-3"><flux:badge color="{{ $app->status->color() }}">{{ $app->status->label() }}</flux:badge></td>
                    <td class="px-4 py-3">{{ number_format($app->total_loan_amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $applications->links() }}</div>
    @if($showCreate)
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
