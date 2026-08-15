<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $application->app_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.45;
            margin: 0;
            padding: 24px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            color: #0f766e;
        }
        h2 {
            font-size: 13px;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
            color: #0f766e;
        }
        .meta {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            background: #ccfbf1;
            color: #115e59;
            font-size: 10px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        td {
            padding: 5px 8px;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }
        td.label {
            width: 38%;
            color: #6b7280;
        }
        td.value {
            font-weight: 600;
        }
        .money {
            font-family: DejaVu Sans Mono, monospace;
        }
        .doc-block {
            page-break-inside: avoid;
            margin-bottom: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
        }
        .doc-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .doc-image {
            max-width: 100%;
            max-height: 420px;
        }
        .doc-note {
            color: #6b7280;
            font-style: italic;
        }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — {{ __('finance.pdf_title') }}</h1>
    <div class="meta">
        {{ __('finance.app_number') }}: <strong>{{ $application->app_number }}</strong>
        &nbsp;|&nbsp;
        {{ __('finance.status') }}: <span class="badge">{{ $application->status->label() }}</span>
        &nbsp;|&nbsp;
        {{ __('finance.pdf_generated_at') }}: {{ $generatedAt->format('Y-m-d H:i') }}
    </div>

    <h2>{{ __('finance.pdf_application_section') }}</h2>
    <table>
        <tr>
            <td class="label">{{ __('finance.merchant') }}</td>
            <td class="value">{{ $application->merchant?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('finance.truck') }}</td>
            <td class="value">
                @if($application->autoProduct)
                    {{ $application->autoProduct->brand }} — {{ $application->autoProduct->name }}
                    ({{ $application->autoProduct->model_year ?? '—' }}) · {{ $application->autoProduct->type->label() }}
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">{{ __('finance.product') }}</td>
            <td class="value">{{ $application->financialProduct?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('finance.truck_price') }}</td>
            <td class="value money">{{ number_format((float) $application->total_truck_price, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('finance.down_payment') }}</td>
            <td class="value money">{{ number_format((float) $application->down_payment, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('finance.loan_amount') }}</td>
            <td class="value money">{{ number_format((float) $application->total_loan_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('finance.monthly_income') }}</td>
            <td class="value money">{{ $application->monthly_income !== null ? number_format((float) $application->monthly_income, 2) : '—' }}</td>
        </tr>
        @if($application->booking_effective_date)
        <tr>
            <td class="label">{{ __('finance.booking_date') }}</td>
            <td class="value">{{ $application->booking_effective_date->format('Y-m-d') }}</td>
        </tr>
        @endif
        @if($application->customer_comm_notes)
        <tr>
            <td class="label">{{ __('finance.comm_notes') }}</td>
            <td class="value">{{ $application->customer_comm_notes }}</td>
        </tr>
        @endif
    </table>

    @if($customer)
    <h2>{{ __('finance.pdf_customer_section') }}</h2>
    <table>
        <tr>
            <td class="label">{{ __('customers.name') }}</td>
            <td class="value">{{ $customer->display_name }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.mobile') }}</td>
            <td class="value">{{ $customer->mobile_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('auth.email') }}</td>
            <td class="value">{{ $customer->email ?: '—' }}</td>
        </tr>
        @if($customer->lead)
        <tr>
            <td class="label">{{ __('finance.pdf_lead_number') }}</td>
            <td class="value">{{ $customer->lead->lead_number }}</td>
        </tr>
        @endif
    </table>

    @if($customer->identification)
    @php $ident = $customer->identification; @endphp
    <h2>{{ __('customers.identification') }}</h2>
    <table>
        <tr>
            <td class="label">{{ __('customers.id_number') }}</td>
            <td class="value">{{ $ident->id_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.name_en') }}</td>
            <td class="value">{{ $ident->name_en ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.name_ar') }}</td>
            <td class="value">{{ $ident->name_ar ?: '—' }}</td>
        </tr>
        @if($ident->issue_date)
        <tr>
            <td class="label">{{ __('customers.issue_date') }}</td>
            <td class="value">{{ $ident->issue_date->format('Y-m-d') }}</td>
        </tr>
        @endif
        @if($ident->expiry_date)
        <tr>
            <td class="label">{{ __('customers.expiry_date') }}</td>
            <td class="value">{{ $ident->expiry_date->format('Y-m-d') }}</td>
        </tr>
        @endif
    </table>
    @endif

    <h2>{{ __('customers.address_section') }}</h2>
    <table>
        <tr>
            <td class="label">{{ __('customers.city') }}</td>
            <td class="value">{{ $customer->city ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.area') }}</td>
            <td class="value">{{ $customer->area ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.address') }}</td>
            <td class="value">{{ $customer->address ?: '—' }}</td>
        </tr>
    </table>

    @if($customer->references->isNotEmpty())
    <h2>{{ __('customers.references') }}</h2>
    <table>
        @foreach($customer->references as $ref)
        <tr>
            <td class="label">{{ $ref->full_name }}</td>
            <td class="value">{{ $ref->mobile ?: '—' }}@if($ref->relation) ({{ $ref->relation }})@endif</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if($customer->financialData)
    @php $fin = $customer->financialData; @endphp
    <h2>{{ __('customers.financial_title') }}</h2>
    <table>
        <tr>
            <td class="label">{{ __('customers.income_proof') }}</td>
            <td class="value">{{ $fin->has_income_proof ? __('common.yes') : __('common.no') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.org_name') }}</td>
            <td class="value">{{ $fin->org_name ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.annual_sales_1yr') }}</td>
            <td class="value money">{{ $fin->annual_sales_1yr !== null ? number_format((float) $fin->annual_sales_1yr, 2) : '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.annual_sales_2yr') }}</td>
            <td class="value money">{{ $fin->annual_sales_2yr !== null ? number_format((float) $fin->annual_sales_2yr, 2) : '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.commercial_reg_num') }}</td>
            <td class="value">{{ $fin->commercial_reg_num ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.paid_in_capital') }}</td>
            <td class="value money">{{ $fin->paid_in_capital !== null ? number_format((float) $fin->paid_in_capital, 2) : '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.org_city') }}</td>
            <td class="value">{{ $fin->org_city ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('customers.org_address') }}</td>
            <td class="value">{{ $fin->org_address ?: '—' }}</td>
        </tr>
    </table>
    @endif
    @endif

    <h2>{{ __('finance.pdf_documents_section') }}</h2>
    @forelse($attachments as $attachment)
        <div class="doc-block">
            <div class="doc-title">{{ $attachment['label'] }}</div>
            @if($attachment['is_image'] && $attachment['data_uri'])
                <img src="{{ $attachment['data_uri'] }}" class="doc-image" alt="{{ $attachment['label'] }}">
            @else
                <div class="doc-note">{{ __('finance.pdf_non_image_doc', ['file' => basename($attachment['path'])]) }}</div>
            @endif
        </div>
    @empty
        <p class="doc-note">{{ __('finance.no_documents') }}</p>
    @endforelse

    <div class="footer">
        {{ config('app.name') }} — {{ __('finance.pdf_footer') }}
    </div>
</body>
</html>
