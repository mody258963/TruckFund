<div>
    <flux:heading size="xl" class="mb-2">{{ __('customers.onboarding') }}</flux:heading>
    <p class="mb-6 text-sm text-zinc-500">{{ $customer->display_name }} — {{ __('customers.step') }} {{ $step }}/8</p>
    <div class="mb-6 flex gap-2">
        @for($i = 1; $i <= 8; $i++)
            <div class="h-2 flex-1 rounded-full {{ $i <= $step ? 'bg-teal-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
        @endfor
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="saveStep">
            @if($step === 1)
                <flux:input wire:model="form.display_name" label="{{ __('customers.name') }}" />
                <flux:input wire:model="form.mobile_number" label="{{ __('customers.mobile') }}" class="mt-3" />
                <flux:input wire:model="form.email" type="email" label="{{ __('auth.email') }}" class="mt-3" />
            @elseif($step === 2)
                <flux:input wire:model="form.id_number" label="{{ __('customers.id_number') }}" />
                <flux:input wire:model="form.name_en" label="{{ __('customers.name_en') }}" class="mt-3" />
                <flux:input wire:model="form.name_ar" label="{{ __('customers.name_ar') }}" class="mt-3" />
                <flux:input type="file" wire:model="idFront" label="{{ __('customers.id_front') }}" class="mt-3" />
                <flux:input type="file" wire:model="idBack" label="{{ __('customers.id_back') }}" class="mt-3" />
            @elseif($step === 3)
                <flux:input wire:model="form.source" type="number" label="{{ __('customers.source') }}" />
            @elseif($step === 4)
                <flux:input wire:model="form.nationality" label="{{ __('customers.nationality') }}" />
            @elseif($step === 5)
                <flux:input wire:model="form.city" label="{{ __('customers.city') }}" class="mb-3" />
                <flux:input wire:model="form.area" label="{{ __('customers.area') }}" class="mb-3" />
                <flux:textarea wire:model="form.address" label="{{ __('customers.address') }}" />
            @elseif($step === 6)
                <p class="text-sm text-zinc-500 mb-3">{{ __('customers.references_hint') }}</p>
                @foreach($form['references'] as $idx => $ref)
                    <div class="mb-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:input wire:model="form.references.{{ $idx }}.full_name" label="{{ __('customers.ref_name') }}" />
                        <flux:input wire:model="form.references.{{ $idx }}.mobile" label="{{ __('customers.mobile') }}" class="mt-2" />
                    </div>
                @endforeach
            @elseif($step === 7)
                <flux:checkbox wire:model="form.has_income_proof" label="{{ __('customers.income_proof') }}" />
                <flux:input wire:model="form.org_name" label="{{ __('customers.org_name') }}" class="mt-3" />
            @else
                <p class="text-zinc-500">{{ __('customers.extra_docs_hint') }}</p>
            @endif
            <div class="mt-6 flex justify-between">
                @if($step > 1)
                    <flux:button type="button" wire:click="previous" variant="ghost">{{ __('common.back') }}</flux:button>
                @else
                    <span></span>
                @endif
                <flux:button type="submit" variant="primary">{{ $step < 8 ? __('common.next') : __('customers.complete') }}</flux:button>
            </div>
        </form>
    </div>
</div>
