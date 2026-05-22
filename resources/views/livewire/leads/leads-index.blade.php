<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('nav.leads') }}</flux:heading>
        <flux:button wire:click="$set('showCreate', true)" variant="primary" icon="plus">{{ __('leads.create') }}</flux:button>
    </div>
    <div class="mb-4 flex flex-wrap gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('nav.search') }}" class="max-w-xs" />
        <flux:select wire:model.live="statusFilter" placeholder="{{ __('leads.status') }}">
            <flux:select.option value="">{{ __('common.all') }}</flux:select.option>
            @foreach($statuses as $s)
                <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:checkbox wire:model.live="priorityOnly" label="{{ __('leads.priority_only') }}" />
    </div>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.number') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.customer') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.phone') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.value') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($leads as $lead)
                <tr class="{{ $lead->is_priority ? 'bg-amber-50/50 dark:bg-amber-950/20' : '' }} hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                    <td class="px-4 py-3"><a href="{{ route('leads.show', $lead) }}" class="font-medium text-teal-600 hover:underline">{{ $lead->lead_number }}</a></td>
                    <td class="px-4 py-3">{{ $lead->customer_name }}</td>
                    <td class="px-4 py-3">{{ $lead->phone }}</td>
                    <td class="px-4 py-3">{{ $lead->value?->label() ?? '—' }} @if($lead->is_priority)<flux:badge color="amber" size="sm">{{ __('leads.priority') }}</flux:badge>@endif</td>
                    <td class="px-4 py-3"><flux:badge color="{{ $lead->status->color() }}">{{ $lead->status->label() }}</flux:badge></td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $leads->links() }}</div>
    @if($showCreate)
    <flux:modal wire:model="showCreate" class="md:w-lg">
        <flux:heading>{{ __('leads.create') }}</flux:heading>
        <form wire:submit="create" class="mt-4 space-y-4">
            <flux:input wire:model="customer_name" label="{{ __('leads.customer') }}" required />
            <flux:input wire:model="phone" label="{{ __('leads.phone') }}" />
            <flux:input wire:model="email" type="email" label="{{ __('auth.email') }}" />
            <flux:input wire:model="car_brand" label="{{ __('leads.car_brand') }}" />
            <flux:input wire:model="price" type="number" label="{{ __('leads.price') }}" />
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showCreate', false)" variant="ghost">{{ __('common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif
</div>
