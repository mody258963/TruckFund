<div>
    <flux:heading size="xl" class="mb-6">{{ __('nav.customers') }}</flux:heading>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('nav.search') }}" class="mb-4 max-w-xs" />
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.name') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.mobile') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('customers.profile') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($customers as $customer)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                    <td class="px-4 py-3">{{ $customer->display_name }}</td>
                    <td class="px-4 py-3">{{ $customer->mobile_number }}</td>
                    <td class="px-4 py-3">
                        @if($customer->profile_completed)
                            <flux:badge color="green">{{ __('customers.completed') }}</flux:badge>
                        @else
                            <flux:badge color="amber">{{ __('customers.step') }} {{ $customer->onboarding_step }}/8</flux:badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end">
                        <flux:button href="{{ route('customers.onboarding', $customer) }}" size="sm" variant="ghost">{{ __('customers.onboarding') }}</flux:button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</div>
