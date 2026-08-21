<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinanceApplication;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    public function search(string $query, int $limit = 10, ?User $viewer = null): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $viewer ??= auth()->user();
        $s = '%'.$query.'%';
        $results = collect();

        if ($viewer && ! $viewer->isMerchantAgent() && $viewer->can('viewAny', Lead::class)) {
            $results = $results->concat(
                Lead::query()
                    ->where(fn ($q) => $q->where('lead_number', 'like', $s)->orWhere('phone', 'like', $s))
                    ->limit($limit)
                    ->get()
                    ->map(fn ($l) => [
                        'type' => 'lead',
                        'type_label' => __('nav.leads'),
                        'id' => $l->lead_id,
                        'label' => $l->lead_number,
                        'url' => route('leads.show', $l),
                    ])
            );
        }

        if ($viewer?->can('viewAny', Customer::class)) {
            $results = $results->concat(
                Customer::query()
                    ->where(fn ($q) => $q
                        ->where('display_name', 'like', $s)
                        ->orWhere('mobile_number', 'like', $s))
                    ->limit($limit)
                    ->get()
                    ->map(fn ($c) => [
                        'type' => 'customer',
                        'type_label' => __('nav.customers'),
                        'id' => $c->customer_id,
                        'label' => $c->display_name,
                        'url' => route('customers.show', $c),
                    ])
            );
        }

        if ($viewer?->can('viewAny', FinanceApplication::class)) {
            $results = $results->concat(
                FinanceApplication::query()
                    ->where('app_number', 'like', $s)
                    ->limit($limit)
                    ->get()
                    ->map(fn ($a) => [
                        'type' => 'application',
                        'type_label' => __('nav.finance'),
                        'id' => $a->app_id,
                        'label' => $a->app_number,
                        'url' => route('finance.show', $a),
                    ])
            );
        }

        return $results->take($limit);
    }
}
