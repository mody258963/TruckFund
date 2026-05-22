<div>
    <flux:heading size="xl" class="mb-2">{{ $application->app_number }}</flux:heading>
    <flux:badge color="{{ $application->status->color() }}" class="mb-6">{{ $application->status->label() }}</flux:badge>
    @if($application->status === \App\Enums\ApplicationStatus::Draft)
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="saveDraft" class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="form.financial_merchant_id" label="{{ __('finance.merchant') }}">
                <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                @foreach($merchants as $m)
                    <flux:select.option value="{{ $m->merchant_id }}">{{ $m->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="form.auto_product_id" label="{{ __('finance.truck') }}">
                <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                @foreach($autoProducts as $t)
                    <flux:select.option value="{{ $t->id }}">{{ $t->brand }} — {{ $t->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="form.financial_product_id" label="{{ __('finance.product') }}">
                <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                @foreach($financialProducts as $p)
                    <flux:select.option value="{{ $p->product_id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="form.total_truck_price" type="number" step="0.01" label="{{ __('finance.truck_price') }}" />
            <flux:input wire:model="form.down_payment" type="number" step="0.01" label="{{ __('finance.down_payment') }}" />
            <flux:input wire:model="form.total_loan_amount" type="number" step="0.01" label="{{ __('finance.loan_amount') }}" />
            <flux:input wire:model="form.monthly_income" type="number" step="0.01" label="{{ __('finance.monthly_income') }}" />
            <flux:textarea wire:model="form.customer_comm_notes" label="{{ __('finance.comm_notes') }}" class="md:col-span-2" />
            <div class="md:col-span-2 flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
                <flux:button type="button" wire:click="submitForReview" variant="filled">{{ __('finance.submit_review') }}</flux:button>
            </div>
        </form>
    </div>
    @endif
    @if($application->status === \App\Enums\ApplicationStatus::UnderReview)
    <div class="mb-6 flex gap-2">
        <flux:button wire:click="decide('accept')" variant="primary" color="green">{{ __('finance.accept') }}</flux:button>
        <flux:button wire:click="decide('reject')" variant="danger">{{ __('finance.reject') }}</flux:button>
        <flux:button wire:click="decide('cancel')" variant="ghost">{{ __('finance.cancel') }}</flux:button>
    </div>
    @endif
    @if($application->status === \App\Enums\ApplicationStatus::Accepted)
    <form wire:submit="confirmBooking" class="mb-4 flex gap-2 items-end">
        <flux:input wire:model="bookingDate" type="date" label="{{ __('finance.booking_date') }}" />
        <flux:button type="submit">{{ __('finance.confirm_booking') }}</flux:button>
    </form>
    @endif
    @if(in_array($application->status, [\App\Enums\ApplicationStatus::BookingConfirmed, \App\Enums\ApplicationStatus::DocsUploaded]))
    <form wire:submit="uploadDoc" class="mb-4 flex gap-2 items-end">
        <flux:input type="file" wire:model="acceptanceDoc" label="{{ __('finance.acceptance_doc') }}" />
        <flux:button type="submit">{{ __('common.upload') }}</flux:button>
    </form>
    @endif
    @if($application->status === \App\Enums\ApplicationStatus::DocsUploaded)
    <flux:button wire:click="complete" variant="primary" class="mb-6">{{ __('finance.mark_completed') }}</flux:button>
    @endif
    <flux:heading size="lg" class="mb-3">{{ __('finance.documents') }}</flux:heading>
    <ul class="text-sm space-y-1">
        @foreach($application->applicationDocuments as $doc)
        <li>{{ $doc->doc_type->label() }} — {{ $doc->uploaded_at?->format('Y-m-d') }}</li>
        @endforeach
    </ul>
</div>
