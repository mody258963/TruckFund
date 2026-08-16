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
        @if($agents->isNotEmpty())
        <flux:select wire:model.live="assignedFilter" placeholder="{{ __('leads.assign') }}">
            <flux:select.option value="">{{ __('common.all') }}</flux:select.option>
            @foreach($agents as $agent)
                <flux:select.option value="{{ $agent->user_id }}">{{ $agent->full_name }}</flux:select.option>
            @endforeach
        </flux:select>
        @endif
    </div>

    @if($canBulkAssign)
        @php
            $bulkAssignDisabled = count($selectedLeads) === 0 || blank($bulkAssignUserId);
        @endphp
        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-teal-200 bg-teal-50/50 p-4 dark:border-teal-900 dark:bg-teal-950/30">
            <div class="min-w-[12rem] flex-1">
                <flux:select wire:model="bulkAssignUserId" label="{{ __('leads.bulk_assign_to') }}">
                    <flux:select.option value="">{{ __('leads.bulk_choose_sales') }}</flux:select.option>
                    @foreach($salesAgents as $agent)
                        <flux:select.option value="{{ $agent->user_id }}">{{ $agent->full_name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if($bulkAssignDisabled)
                <flux:button variant="primary" disabled>
                    {{ __('leads.bulk_assign_btn', ['count' => count($selectedLeads)]) }}
                </flux:button>
            @else
                <flux:button wire:click="bulkAssign" variant="primary">
                    {{ __('leads.bulk_assign_btn', ['count' => count($selectedLeads)]) }}
                </flux:button>
            @endif
            @if(count($selectedLeads) > 0)
                <flux:button wire:click="clearSelection" variant="ghost" size="sm">{{ __('leads.bulk_clear') }}</flux:button>
            @endif
            <p class="w-full text-xs text-zinc-500">{{ __('leads.bulk_hint') }}</p>
        </div>
    @endif

    @php
        $pageLeadIds = $leads->pluck('lead_id')->all();
        $allPageSelected = $pageLeadIds !== [] && count(array_intersect($pageLeadIds, $selectedLeads)) === count($pageLeadIds);
    @endphp

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    @if($canBulkAssign)
                    <th class="w-10 px-4 py-3">
                        <input
                            type="checkbox"
                            class="rounded border-zinc-300"
                            {{ $allPageSelected ? 'checked' : '' }}
                            wire:click="toggleSelectAllOnPage(@js($pageLeadIds))"
                            title="{{ __('leads.bulk_select_page') }}"
                        />
                    </th>
                    @endif
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.number') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.customer') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.phone') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.assigned_to') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.value') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.status') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.follow_up') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($leads as $lead)
                @php($followUpDue = $lead->follow_up_on?->lte(today()) ?? false)
                <tr class="{{ $followUpDue ? 'bg-red-50/70 dark:bg-red-950/20' : ($lead->is_priority ? 'bg-amber-50/50 dark:bg-amber-950/20' : '') }} hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                    @if($canBulkAssign)
                    <td class="px-4 py-3">
                        <input type="checkbox" wire:model="selectedLeads" value="{{ $lead->lead_id }}" class="rounded border-zinc-300" />
                    </td>
                    @endif
                    <td class="px-4 py-3"><a href="{{ route('leads.show', $lead) }}" class="font-medium text-teal-600 hover:underline">{{ $lead->lead_number }}</a></td>
                    <td class="px-4 py-3">{{ $lead->customer_name }}</td>
                    <td class="px-4 py-3">{{ $lead->phone }}</td>
                    <td class="px-4 py-3 text-sm text-zinc-600">{{ $lead->assignedUser?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        {{ $lead->value?->label() ?? '—' }}
                        @if($lead->is_priority)
                            <flux:badge color="amber" size="sm">{{ __('leads.priority') }}</flux:badge>
                        @endif
                    </td>
                    <td class="px-4 py-3"><flux:badge color="{{ $lead->status->color() }}">{{ $lead->status->label() }}</flux:badge></td>
                    <td class="min-w-40 px-4 py-3">
                        @can('update', $lead)
                            <input
                                type="date"
                                value="{{ $lead->follow_up_on?->format('Y-m-d') }}"
                                wire:change="saveFollowUp('{{ $lead->lead_id }}', $event.target.value)"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                aria-label="{{ __('leads.follow_up') }}"
                            />
                        @else
                            <span class="text-sm text-zinc-600">{{ $lead->follow_up_on?->format('Y-m-d') ?? '—' }}</span>
                        @endcan
                        @if($lead->follow_up_on)
                            @php($daysUntilCall = (int) today()->diffInDays($lead->follow_up_on, false))
                            <div class="mt-1">
                                @if($daysUntilCall < 0)
                                    <flux:badge color="red" size="sm">{{ __('leads.overdue_days', ['days' => abs($daysUntilCall)]) }}</flux:badge>
                                @elseif($daysUntilCall === 0)
                                    <flux:badge color="red" size="sm">{{ __('leads.call_today') }}</flux:badge>
                                @elseif($daysUntilCall === 1)
                                    <flux:badge color="amber" size="sm">{{ __('leads.call_tomorrow') }}</flux:badge>
                                @else
                                    <flux:badge color="blue" size="sm">{{ __('leads.call_in_days', ['days' => $daysUntilCall]) }}</flux:badge>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $lead->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ $canBulkAssign ? 9 : 8 }}" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
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
            <x-money-input model="price" :value="$price" :label="__('leads.price')" />
            <flux:input wire:model="follow_up_on" type="date" label="{{ __('leads.follow_up') }}" />
            @if($freelancers->isNotEmpty())
            <flux:select wire:model="freelancer_id" label="{{ __('crm.leads.reference') }}">
                <flux:select.option value="">{{ __('common.none') }}</flux:select.option>
                @foreach($freelancers as $ref)
                    <flux:select.option value="{{ $ref->freelancer_id }}">{{ $ref->full_name }}</flux:select.option>
                @endforeach
            </flux:select>
            @endif
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showCreate', false)" variant="ghost">{{ __('common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif
</div>
