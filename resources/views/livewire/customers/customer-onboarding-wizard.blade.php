<div>
    <flux:heading size="xl" class="mb-2">{{ __('customers.onboarding') }}</flux:heading>
    <p class="mb-6 text-sm text-zinc-500">{{ $customer->display_name }} — {{ __('customers.step') }} {{ $step }}/{{ $totalSteps }}</p>
    <div class="mb-6 flex gap-2">
        @for($i = 1; $i <= $totalSteps; $i++)
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
                <flux:input wire:model="form.id_number" label="{{ __('customers.id_number') }}" maxlength="13" inputmode="numeric" />
                <p class="mt-1 text-xs text-zinc-500">{{ __('customers.id_number_hint') }}</p>
                <flux:input wire:model="form.name_en" label="{{ __('customers.name_en') }}" class="mt-3" />
                <flux:input wire:model="form.name_ar" label="{{ __('customers.name_ar') }}" class="mt-3" />
                <p class="mt-3 text-xs text-zinc-500">{{ __('settings.upload_hint', ['max' => config('truckfund.image_max_kb', 2048)]) }}</p>
                <x-wire-file-input wire:model="idFront" accept="image/*" :label="__('customers.id_front')" />
                <x-wire-file-input wire:model="idBack" accept="image/*" :label="__('customers.id_back')" />
            @elseif($step === 3)
                <flux:input wire:model="form.city" label="{{ __('customers.city') }}" class="mb-3" />
                <flux:input wire:model="form.area" label="{{ __('customers.area') }}" class="mb-3" />
                <flux:textarea wire:model="form.address" label="{{ __('customers.address') }}" />
            @elseif($step === 4)
                <p class="mb-3 text-sm text-zinc-500">{{ __('customers.references_hint') }}</p>
                @foreach($form['references'] as $idx => $ref)
                    <div class="mb-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:input wire:model="form.references.{{ $idx }}.full_name" label="{{ __('customers.ref_name') }}" />
                        <flux:input wire:model="form.references.{{ $idx }}.mobile" label="{{ __('customers.mobile') }}" class="mt-2" />
                    </div>
                @endforeach
            @elseif($step === 5)
                <p class="mb-4 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('customers.financial_title') }}</p>
                <flux:checkbox wire:model="form.has_income_proof" label="{{ __('customers.income_proof') }}" />
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="form.annual_sales_1yr" type="number" step="0.01" label="{{ __('customers.annual_sales_1yr') }}" />
                    <flux:input wire:model="form.annual_sales_2yr" type="number" step="0.01" label="{{ __('customers.annual_sales_2yr') }}" />
                </div>
                <flux:input wire:model="form.org_name" label="{{ __('customers.org_name') }}" class="mt-3" />
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="form.commercial_reg_type" type="number" label="{{ __('customers.commercial_reg_type') }}" />
                    <flux:input wire:model="form.commercial_reg_num" label="{{ __('customers.commercial_reg_num') }}" />
                </div>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="form.reg_start_date" type="date" label="{{ __('customers.reg_start_date') }}" />
                    <flux:input wire:model="form.reg_expiry_date" type="date" label="{{ __('customers.reg_expiry_date') }}" />
                </div>
                <flux:input wire:model="form.paid_in_capital" type="number" step="0.01" label="{{ __('customers.paid_in_capital') }}" class="mt-3" />
                <flux:input wire:model="form.org_city" label="{{ __('customers.org_city') }}" class="mt-3" />
                <flux:textarea wire:model="form.org_address" label="{{ __('customers.org_address') }}" class="mt-3" />
                <div class="mt-6 space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('customers.attachments') }}</p>
                    <x-wire-file-input wire:model="incomeProofFile" accept="image/*,application/pdf" :label="__('customers.income_proof_file')" />
                    @if($uploadedDocs->has('IncomeProof'))
                        <ul class="text-xs text-teal-600">
                            @foreach($uploadedDocs->get('IncomeProof') as $doc)
                                <li>✓ {{ $doc->doc_type->label() }} — {{ basename($doc->file_url) }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <x-wire-file-input wire:model="commercialRegFile" accept="image/*,application/pdf" :label="__('customers.commercial_reg_file')" />
                    @if($uploadedDocs->has('CommercialReg'))
                        <ul class="text-xs text-teal-600">
                            @foreach($uploadedDocs->get('CommercialReg') as $doc)
                                <li>✓ {{ $doc->doc_type->label() }} — {{ basename($doc->file_url) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @elseif($step === 6)
                <p class="mb-4 text-sm text-zinc-500">{{ __('customers.extra_docs_hint') }}</p>
                <p class="mb-2 text-xs text-zinc-500">{{ __('settings.upload_hint', ['max' => config('truckfund.image_max_kb', 2048)]) }}</p>
                <flux:select wire:model="extraDocType" label="{{ __('customers.extra_doc_type') }}">
                    @foreach($extraDocTypes as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <x-wire-file-input wire:model="extraDocFile" accept="image/*,application/pdf" :label="__('customers.extra_doc_file')" />
                <div class="mt-3 flex items-center gap-3">
                    <flux:button type="button" wire:click="uploadExtraDocument" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="uploadExtraDocument">
                        {{ __('common.upload') }}
                    </flux:button>
                    <span wire:loading wire:target="extraDocFile" class="text-xs text-zinc-500">{{ __('customers.file_uploading') }}</span>
                </div>
                <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-3 text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('customers.extra_docs_list') }}</p>
                    @forelse($extraDocuments as $doc)
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <div>
                                <span class="font-medium">{{ $doc->doc_type->label() }}</span>
                                <span class="text-zinc-500"> — {{ basename($doc->file_url) }}</span>
                            </div>
                            <div class="flex gap-2">
                                <flux:button href="{{ $images->url($doc->file_url) }}" size="sm" variant="ghost" target="_blank">{{ __('common.view') }}</flux:button>
                                <flux:button type="button" wire:click="removeDocument(@js($doc->doc_id))" wire:confirm="{{ __('customers.remove_doc_confirm') }}" size="sm" variant="danger">{{ __('common.remove') }}</flux:button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('customers.no_extra_docs') }}</p>
                    @endforelse
                </div>
            @endif
            <div class="mt-6 flex justify-between">
                @if($step > 1)
                    <flux:button type="button" wire:click="previous" variant="ghost">{{ __('common.back') }}</flux:button>
                @else
                    <span></span>
                @endif
                <flux:button type="submit" variant="primary">{{ $step < $totalSteps ? __('common.next') : __('customers.complete') }}</flux:button>
            </div>
        </form>
    </div>
</div>
