<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('crm.freelancers.title') }}</flux:heading>
        @can('create', App\Models\Freelancer::class)
        <flux:button wire:click="$set('showCreate', true)" variant="primary" icon="plus">{{ __('crm.freelancers.create') }}</flux:button>
        @endcan
    </div>
    <p class="mb-4 text-sm text-zinc-500">{{ __('crm.freelancers.locked_hint') }}</p>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('nav.search') }}" class="mb-4 max-w-xs" />
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.customer') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.phone') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('crm.freelancers.national_id') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($freelancers as $ref)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $ref->full_name }}</td>
                    <td class="px-4 py-3">{{ $ref->phone }}</td>
                    <td class="px-4 py-3">{{ $ref->national_id ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $ref->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $freelancers->links() }}</div>
    @if($showCreate)
    <flux:modal wire:model="showCreate" class="md:w-lg">
        <flux:heading>{{ __('crm.freelancers.create') }}</flux:heading>
        <form wire:submit="create" class="mt-4 space-y-4">
            <flux:input wire:model="full_name" label="{{ __('leads.customer') }}" required />
            <flux:input wire:model="phone" label="{{ __('leads.phone') }}" required />
            <flux:input wire:model="national_id" label="{{ __('crm.freelancers.national_id') }}" maxlength="14" inputmode="numeric" />
            <p class="text-xs text-zinc-500">{{ __('customers.id_number_hint') }}</p>
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showCreate', false)" variant="ghost">{{ __('common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif
</div>
