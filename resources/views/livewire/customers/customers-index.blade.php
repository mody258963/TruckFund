<div>
    <flux:heading size="xl" class="mb-6">{{ __('nav.customers') }}</flux:heading>
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('nav.search') }}" class="max-w-xs" />
        <label class="flex items-center gap-2 pb-2 text-sm text-zinc-700 dark:text-zinc-300">
            <input type="checkbox" wire:model.live="reentryDueOnly" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500" />
            {{ __('finance.reentry_due_only') }}
        </label>
    </div>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.name') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.mobile') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.profile') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($customers as $customer)
                <tr @class([
                    'hover:bg-zinc-50 dark:hover:bg-zinc-800/30',
                    'bg-orange-50 dark:bg-orange-950/20' => (bool) ($customer->has_reentry_due ?? false),
                ])>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('customers.show', $customer) }}" class="font-medium text-teal-600 hover:underline dark:text-teal-400" wire:navigate>{{ $customer->display_name }}</a>
                            @if($customer->has_reentry_due ?? false)
                                <flux:badge color="red" size="sm">{{ __('finance.reentry_overdue') }}</flux:badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $customer->mobile_number }}</td>
                    <td class="px-4 py-3">
                        @if($customer->profile_completed)
                            <flux:badge color="green">{{ __('customers.completed') }}</flux:badge>
                        @else
                            <flux:badge color="amber">{{ __('customers.step') }} {{ min($customer->onboarding_step, 6) }}/6</flux:badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $customer->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-end">
                        <div class="flex justify-end gap-2">
                            <flux:button href="{{ route('customers.show', $customer) }}" size="sm" variant="ghost" wire:navigate>{{ __('customers.view_profile') }}</flux:button>
                            @can('update', $customer)
                            <flux:button href="{{ route('customers.onboarding', $customer) }}" size="sm" variant="ghost" wire:navigate>{{ __('customers.onboarding') }}</flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</div>
