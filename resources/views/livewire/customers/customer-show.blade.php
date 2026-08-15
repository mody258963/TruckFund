@php
    $ident = $customer->identification;
    $fin = $customer->financialData;
@endphp
<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:button href="{{ route('customers.index') }}" variant="ghost" size="sm" icon="arrow-left" class="mb-2">
                {{ __('customers.back_to_list') }}
            </flux:button>
            <flux:heading size="xl">{{ $customer->display_name }}</flux:heading>
            <div class="mt-2 flex flex-wrap gap-2">
                @if($customer->profile_completed)
                    <flux:badge color="green">{{ __('customers.completed') }}</flux:badge>
                @else
                    <flux:badge color="amber">{{ __('customers.step') }} {{ min($customer->onboarding_step, 6) }}/6</flux:badge>
                @endif
                @if($customer->lead)
                    <flux:badge color="zinc">{{ $customer->lead->lead_number }}</flux:badge>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('create', \App\Models\FinanceApplication::class)
                <flux:button wire:click="createFinanceApplication" variant="primary" icon="banknotes">
                    {{ __('finance.create_for_customer') }}
                </flux:button>
            @endcan
            <flux:button href="{{ route('customers.onboarding', $customer) }}" variant="{{ $customer->profile_completed ? 'ghost' : 'primary' }}">
                {{ $customer->profile_completed ? __('customers.edit_profile') : __('customers.onboarding') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.contact') }}</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">{{ __('customers.name') }}</dt><dd class="font-medium">{{ $customer->display_name }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.mobile') }}</dt><dd>{{ $customer->mobile_number ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('auth.email') }}</dt><dd>{{ $customer->email ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('common.created_at') }}</dt><dd>{{ $customer->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                </dl>
            </div>

            @if($ident)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.identification') }}</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">{{ __('customers.id_number') }}</dt><dd>{{ $ident->id_number ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.name_en') }}</dt><dd>{{ $ident->name_en ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.name_ar') }}</dt><dd>{{ $ident->name_ar ?: '—' }}</dd></div>
                    @if($ident->issue_date)
                    <div><dt class="text-zinc-500">{{ __('customers.issue_date') }}</dt><dd>{{ $ident->issue_date->format('Y-m-d') }}</dd></div>
                    @endif
                    @if($ident->expiry_date)
                    <div><dt class="text-zinc-500">{{ __('customers.expiry_date') }}</dt><dd>{{ $ident->expiry_date->format('Y-m-d') }}</dd></div>
                    @endif
                </dl>
            </div>
            @endif

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.address_section') }}</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">{{ __('customers.city') }}</dt><dd>{{ $customer->city ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.area') }}</dt><dd>{{ $customer->area ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-zinc-500">{{ __('customers.address') }}</dt><dd>{{ $customer->address ?: '—' }}</dd></div>
                </dl>
            </div>

            @if($customer->references->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.references') }}</flux:heading>
                <ul class="space-y-3 text-sm">
                    @foreach($customer->references as $ref)
                    <li class="rounded-lg border border-zinc-100 px-3 py-2 dark:border-zinc-800">
                        <span class="font-medium">{{ $ref->full_name }}</span>
                        <span class="text-zinc-500"> — {{ $ref->mobile ?: '—' }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($fin)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.financial_title') }}</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">{{ __('customers.income_proof') }}</dt><dd>{{ $fin->has_income_proof ? __('common.yes') : __('common.no') }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.org_name') }}</dt><dd>{{ $fin->org_name ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.annual_sales_1yr') }}</dt><dd>{{ $fin->annual_sales_1yr !== null ? number_format((float) $fin->annual_sales_1yr, 2) : '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.annual_sales_2yr') }}</dt><dd>{{ $fin->annual_sales_2yr !== null ? number_format((float) $fin->annual_sales_2yr, 2) : '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.commercial_reg_num') }}</dt><dd>{{ $fin->commercial_reg_num ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.paid_in_capital') }}</dt><dd>{{ $fin->paid_in_capital !== null ? number_format((float) $fin->paid_in_capital, 2) : '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('customers.org_city') }}</dt><dd>{{ $fin->org_city ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-zinc-500">{{ __('customers.org_address') }}</dt><dd>{{ $fin->org_address ?: '—' }}</dd></div>
                </dl>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($ident && ($ident->id_front_url || $ident->id_back_url))
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.id_photos') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    @if($ident->id_front_url)
                        <x-customer-file-preview
                            :url="$images->url($ident->id_front_url)"
                            :label="__('customers.id_front')"
                            :is-image="$images->isImagePath($ident->id_front_url)"
                        />
                    @endif
                    @if($ident->id_back_url)
                        <x-customer-file-preview
                            :url="$images->url($ident->id_back_url)"
                            :label="__('customers.id_back')"
                            :is-image="$images->isImagePath($ident->id_back_url)"
                        />
                    @endif
                </div>
            </div>
            @endif

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('customers.documents') }}</flux:heading>
                @if($customer->documents->isEmpty())
                    <p class="text-sm text-zinc-500">{{ __('customers.no_documents') }}</p>
                @else
                    <div class="grid gap-4">
                        @foreach($customer->documents as $doc)
                            <x-customer-file-preview
                                :url="$images->url($doc->file_url)"
                                :label="$doc->doc_type->label()"
                                :is-image="$images->isImagePath($doc->file_url)"
                            />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
