<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ $lead->lead_number }}</flux:heading>
        @if(!$lead->customer_id)
        <flux:button wire:click="convert" variant="primary">{{ __('leads.convert') }}</flux:button>
        @else
        <flux:badge color="green">{{ __('leads.converted') }}</flux:badge>
        @endif
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('leads.details') }}</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('leads.customer') }}</dt><dd>{{ $lead->customer_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('leads.phone') }}</dt><dd>{{ $lead->phone }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('leads.ai_score') }}</dt><dd>{{ $lead->ai_score ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('leads.value') }}</dt><dd>{{ $lead->value?->label() }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('crm.leads.source') }}</dt><dd>{{ $lead->source?->label() ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('crm.leads.created_by') }}</dt><dd>{{ $lead->createdBy?->full_name ?? '—' }}</dd></div>
                @if($lead->freelancer)
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('crm.leads.reference') }}</dt><dd>{{ $lead->freelancer->full_name }}</dd></div>
                @endif
            </dl>
            <div class="mt-4 space-y-3">
                @if($canAssign)
                <flux:select wire:model="assigned_user_id" label="{{ __('leads.assign') }}">
                    <flux:select.option value="">{{ __('common.none') }}</flux:select.option>
                    @foreach($agents as $agent)
                        <flux:select.option value="{{ $agent->user_id }}">{{ $agent->full_name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button wire:click="saveAssignment" size="sm">{{ __('common.save') }}</flux:button>
                @else
                <p class="text-sm text-zinc-500">{{ __('leads.assign') }}: {{ $lead->assignedUser?->full_name ?? __('common.none') }}</p>
                @endif
                <flux:select wire:model="status" label="{{ __('leads.status') }}">
                    @foreach($statuses as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button wire:click="saveStatus" size="sm">{{ __('leads.update_status') }}</flux:button>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            @if($pendingTransfer)
            <flux:heading size="lg" class="mb-2">{{ __('crm.transfer.title') }}</flux:heading>
            <p class="mb-3 text-sm text-zinc-600">{{ __('crm.transfer.pending') }} → {{ $pendingTransfer->toUser?->full_name }}</p>
            @if(auth()->user()->role->canAssignLeads())
            <div class="mb-6 flex gap-2">
                <flux:button wire:click="approveTransfer('{{ $pendingTransfer->transfer_id }}')" variant="primary" size="sm">{{ __('crm.transfer.approve') }}</flux:button>
                <flux:button wire:click="rejectTransfer('{{ $pendingTransfer->transfer_id }}')" variant="danger" size="sm">{{ __('crm.transfer.reject') }}</flux:button>
            </div>
            @endif
            @elseif($canRequestTransfer && $transferTargets->isNotEmpty())
            <flux:heading size="lg" class="mb-2">{{ __('crm.transfer.title') }}</flux:heading>
            <form wire:submit="requestTransfer" class="mb-6 space-y-3">
                <flux:select wire:model="transfer_to_user_id" label="{{ __('crm.transfer.to_user') }}">
                    <flux:select.option value="">{{ __('common.none') }}</flux:select.option>
                    @foreach($transferTargets as $t)
                        <flux:select.option value="{{ $t->user_id }}">{{ $t->full_name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:textarea wire:model="transfer_reason" label="{{ __('crm.transfer.reason') }}" rows="2" />
                <flux:button type="submit" size="sm" variant="primary">{{ __('crm.transfer.request') }}</flux:button>
            </form>
            @endif
            <flux:heading size="lg" class="mb-4">{{ __('leads.communications') }}</flux:heading>
            <form wire:submit="addNote" class="mb-4 flex gap-2">
                <flux:textarea wire:model="commContent" class="flex-1" rows="2" />
                <flux:button type="submit" size="sm">{{ __('common.add') }}</flux:button>
            </form>
            <ul class="space-y-2 text-sm">
                @foreach($lead->communicationLogs as $log)
                <li class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                    <p>{{ $log->content }}</p>
                    <p class="mt-1 text-xs text-zinc-400">{{ $log->created_at?->format('Y-m-d H:i') }} — {{ $log->user?->full_name }}</p>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
