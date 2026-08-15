@php
    use App\Enums\ApplicationStatus;
    $status = $application->status;
    $pipelineStep = $status->pipelineStep();
    $isDraft = $status === ApplicationStatus::Draft;
@endphp
<div>
    <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:button href="{{ route('finance.index') }}" variant="ghost" size="sm" icon="arrow-left" class="mb-2">
                {{ __('finance.back_to_list') }}
            </flux:button>
            <flux:heading size="xl">{{ $application->app_number }}</flux:heading>
            @if($application->customer)
                <p class="mt-1 text-sm text-zinc-500">
                    {{ __('customers.name') }}:
                    <a href="{{ route('customers.show', $application->customer) }}" class="text-teal-600 hover:underline" wire:navigate>{{ $application->customer->display_name }}</a>
                </p>
            @endif
        </div>
        <flux:badge color="{{ $status->color() }}" size="lg">{{ $status->label() }}</flux:badge>
    </div>

    @if(session('finance_pdf_ready') && ($canDownloadPdf ?? false))
        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 p-4 dark:border-teal-900 dark:bg-teal-950/30">
            <p class="text-sm text-teal-800 dark:text-teal-200">{{ __('finance.pdf_ready_after_submit') }}</p>
            <flux:button href="{{ route('finance.pdf', $application) }}" variant="primary" size="sm" class="mt-3" icon="arrow-down-tray">
                {{ __('finance.download_pdf') }}
            </flux:button>
        </div>
    @endif

    {{-- Pipeline overview --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('finance.workflow_title') }}</p>
        <div class="mb-4 flex gap-1">
            @for($i = 1; $i <= ApplicationStatus::PIPELINE_STEPS; $i++)
                @php
                    $segmentColor = match (true) {
                        $status->isPipelineFailure() && $i === 3 => 'bg-red-500',
                        $i < $pipelineStep => 'bg-teal-500',
                        $i === $pipelineStep && ! $status->isPipelineFailure() => 'bg-teal-500',
                        $i === $pipelineStep && $status->isPipelineFailure() => 'bg-red-500',
                        default => 'bg-zinc-200 dark:bg-zinc-700',
                    };
                @endphp
                <div class="h-2 min-w-0 flex-1 rounded-full {{ $segmentColor }}" title="{{ __('finance.pipeline.'.$i.'.title') }}"></div>
            @endfor
        </div>
        <ol class="grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
            @for($i = 1; $i <= ApplicationStatus::PIPELINE_STEPS; $i++)
                <li class="@if($i === $pipelineStep) font-semibold text-teal-700 dark:text-teal-400 @elseif($i < $pipelineStep) text-zinc-500 @else text-zinc-400 @endif">
                    <span class="me-1">{{ $i }}.</span>{{ __('finance.pipeline.'.$i.'.title') }}
                </li>
            @endfor
        </ol>
    </div>

    {{-- Current step guidance --}}
    <div @class([
        'mb-6 rounded-xl border p-5',
        'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/40' => $status->isPipelineFailure(),
        'border-teal-200 bg-teal-50 dark:border-teal-900 dark:bg-teal-950/30' => ! $status->isPipelineFailure() && $status !== ApplicationStatus::Completed,
        'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/30' => $status === ApplicationStatus::Completed,
    ])>
        <p class="text-sm font-semibold">
            @if($status->isPipelineFailure())
                {{ __('finance.pipeline.failed_title') }}
            @elseif($status === ApplicationStatus::Completed)
                {{ __('finance.pipeline.done_title') }}
            @else
                {{ __('finance.current_step') }}: {{ __('finance.pipeline.'.$pipelineStep.'.title') }}
            @endif
        </p>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            @if($status->isPipelineFailure())
                {{ __('finance.pipeline.failed_desc', ['status' => $status->label()]) }}
            @elseif($status === ApplicationStatus::Completed)
                {{ __('finance.pipeline.done_desc') }}
            @else
                {{ __('finance.pipeline.'.$pipelineStep.'.desc') }}
            @endif
        </p>
        @unless($status->isPipelineFailure() || $status === ApplicationStatus::Completed)
            <p class="mt-2 text-xs text-zinc-500">
                <span class="font-medium">{{ __('finance.who_acts') }}:</span> {{ __('finance.pipeline.'.$pipelineStep.'.actor') }}
            </p>
        @endunless
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if($isDraft)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('finance.draft_section') }}</flux:heading>
                    <span class="text-sm text-zinc-500">{{ __('finance.draft_step') }} {{ $wizardStep }}/3</span>
                </div>
                <div class="mb-6 flex gap-2">
                    @for($i = 1; $i <= 3; $i++)
                        <div class="h-1.5 flex-1 rounded-full {{ $i <= $wizardStep ? 'bg-teal-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                    @endfor
                </div>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('finance.draft.'.$wizardStep.'.hint') }}</p>

                <form wire:submit="saveDraft" class="grid gap-4 md:grid-cols-2">
                    @if($wizardStep === 1)
                        <flux:select wire:key="draft-merchant" wire:model="form.financial_merchant_id" label="{{ __('finance.merchant') }}" class="md:col-span-2">
                            <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                            @foreach($merchants as $m)
                                <flux:select.option value="{{ $m->merchant_id }}">{{ $m->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:key="draft-auto-product" wire:model="form.auto_product_id" label="{{ __('finance.truck') }}" class="md:col-span-2">
                            <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                            @foreach($autoProducts as $t)
                                <flux:select.option value="{{ $t->id }}">
                                    {{ $t->brand }} — {{ $t->name }} ({{ $t->model_year ?? '—' }}) · {{ $t->type->label() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    @elseif($wizardStep === 2)
                        <flux:select wire:key="draft-financial-product" wire:model="form.financial_product_id" label="{{ __('finance.product') }}" class="md:col-span-2">
                            <flux:select.option value="">{{ __('common.select') }}</flux:select.option>
                            @foreach($financialProducts as $p)
                                <flux:select.option value="{{ $p->product_id }}">{{ $p->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <x-money-input input-key="draft-truck-price" model="form.total_truck_price" :value="$form['total_truck_price']" :label="__('finance.truck_price')" />
                        <x-money-input input-key="draft-down-payment" model="form.down_payment" :value="$form['down_payment']" :label="__('finance.down_payment')" />
                        <x-money-input input-key="draft-loan-amount" model="form.total_loan_amount" :value="$form['total_loan_amount']" :label="__('finance.loan_amount')" />
                        <x-money-input input-key="draft-monthly-income" model="form.monthly_income" :value="$form['monthly_income']" :label="__('finance.monthly_income')" />
                    @else
                        <flux:textarea wire:key="draft-comm-notes" wire:model="form.customer_comm_notes" label="{{ __('finance.comm_notes') }}" class="md:col-span-2" rows="4" />
                        <p class="md:col-span-2 text-xs text-zinc-500">{{ __('finance.draft.3.submit_hint') }}</p>
                    @endif

                    <div class="md:col-span-2 flex flex-wrap justify-between gap-2 pt-2">
                        <div class="flex gap-2">
                            @if($wizardStep > 1)
                                <flux:button type="button" wire:click="previousDraftStep" variant="ghost">{{ __('common.back') }}</flux:button>
                            @endif
                            @if($wizardStep < 3)
                                <flux:button type="button" wire:click="nextDraftStep" variant="primary">{{ __('common.next') }}</flux:button>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <flux:button type="submit" variant="ghost">{{ __('finance.save_draft') }}</flux:button>
                            @if($wizardStep === 3)
                                <flux:button type="button" wire:click="submitForReview" variant="primary" wire:confirm="{{ __('finance.submit_confirm') }}">
                                    {{ __('finance.submit_review') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
            @endif

            @if(in_array($status, [ApplicationStatus::Submitted, ApplicationStatus::UnderReview], true))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/30">
                <flux:heading size="lg" class="mb-2">{{ __('finance.review_section') }}</flux:heading>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('finance.review_hint') }}</p>
                @can('decide', $application)
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="decide('accept')" variant="primary" color="green">{{ __('finance.accept') }}</flux:button>
                    <flux:button wire:click="decide('reject')" variant="danger" wire:confirm="{{ __('finance.reject_confirm') }}">{{ __('finance.reject') }}</flux:button>
                    <flux:button wire:click="decide('cancel')" variant="ghost" wire:confirm="{{ __('finance.cancel_confirm') }}">{{ __('finance.cancel') }}</flux:button>
                </div>
                @else
                <p class="text-sm text-zinc-500">{{ __('finance.review_wait') }}</p>
                @endcan
            </div>
            @endif

            @if($status === ApplicationStatus::Accepted)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-2">{{ __('finance.booking_section') }}</flux:heading>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('finance.booking_hint') }}</p>
                <form wire:submit="confirmBooking" class="flex flex-wrap items-end gap-4">
                    <flux:input wire:model="bookingDate" type="date" label="{{ __('finance.booking_date') }}" />
                    <flux:button type="submit" variant="primary">{{ __('finance.confirm_booking') }}</flux:button>
                </form>
            </div>
            @endif

            @if(in_array($status, [ApplicationStatus::BookingConfirmed, ApplicationStatus::DocsUploaded], true))
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-2">{{ __('finance.docs_section') }}</flux:heading>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('finance.docs_hint') }}</p>
                <form wire:submit="uploadDoc" class="space-y-3">
                    <x-wire-file-input wire:model="acceptanceDoc" accept="image/*,application/pdf" :label="__('finance.acceptance_doc')" />
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="acceptanceDoc,uploadDoc">{{ __('common.upload') }}</flux:button>
                </form>
            </div>
            @endif

            @if($status === ApplicationStatus::DocsUploaded)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-2">{{ __('finance.complete_section') }}</flux:heading>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('finance.complete_hint') }}</p>
                <flux:button wire:click="complete" variant="primary" wire:confirm="{{ __('finance.complete_confirm') }}">{{ __('finance.mark_completed') }}</flux:button>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('finance.summary') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.merchant') }}</dt><dd class="text-end">{{ $application->merchant?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-500">{{ __('finance.truck') }}</dt>
                        <dd class="text-end">
                            @if($application->autoProduct)
                                {{ $application->autoProduct->brand }} — {{ $application->autoProduct->name }}
                                ({{ $application->autoProduct->model_year ?? '—' }}) · {{ $application->autoProduct->type->label() }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.product') }}</dt><dd class="text-end">{{ $application->financialProduct?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.truck_price') }}</dt><dd>{{ $application->total_truck_price !== null ? number_format((float) $application->total_truck_price, 2) : '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.down_payment') }}</dt><dd>{{ $application->down_payment !== null ? number_format((float) $application->down_payment, 2) : '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.loan_amount') }}</dt><dd class="font-medium">{{ $application->total_loan_amount !== null ? number_format((float) $application->total_loan_amount, 2) : '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.monthly_income') }}</dt><dd>{{ $application->monthly_income !== null ? number_format((float) $application->monthly_income, 2) : '—' }}</dd></div>
                    @if($application->booking_effective_date)
                    <div class="flex justify-between gap-2"><dt class="text-zinc-500">{{ __('finance.booking_date') }}</dt><dd>{{ $application->booking_effective_date->format('Y-m-d') }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-3">{{ __('finance.documents') }}</flux:heading>
                @forelse($application->applicationDocuments as $doc)
                    <div class="mb-2 text-sm">
                        <span class="font-medium">{{ $doc->doc_type->label() }}</span>
                        <span class="text-zinc-500"> — {{ $doc->uploaded_at?->format('Y-m-d H:i') ?? '—' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('finance.no_documents') }}</p>
                @endforelse
            </div>

            @if($canDownloadPdf ?? false)
            <div class="rounded-xl border border-teal-200 bg-teal-50 p-6 dark:border-teal-900 dark:bg-teal-950/30">
                <flux:heading size="lg" class="mb-2">{{ __('finance.download_pdf') }}</flux:heading>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('finance.pdf_hint') }}</p>
                <flux:button href="{{ route('finance.pdf', $application) }}" variant="primary" icon="arrow-down-tray">
                    {{ __('finance.download_pdf') }}
                </flux:button>
            </div>
            @endif
        </div>
    </div>
</div>
