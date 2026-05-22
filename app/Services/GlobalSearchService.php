<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinanceApplication;
use App\Models\Lead;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    public function search(string $query, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $s = '%'.$query.'%';

        $leads = Lead::query()
            ->where('lead_number', 'like', $s)
            ->orWhere('phone', 'like', $s)
            ->limit($limit)
            ->get()
            ->map(fn ($l) => ['type' => 'lead', 'id' => $l->lead_id, 'label' => $l->lead_number, 'url' => route('leads.show', $l)]);

        $customers = Customer::query()
            ->where('display_name', 'like', $s)
            ->orWhere('mobile_number', 'like', $s)
            ->limit($limit)
            ->get()
            ->map(fn ($c) => ['type' => 'customer', 'id' => $c->customer_id, 'label' => $c->display_name, 'url' => route('customers.onboarding', $c)]);

        $apps = FinanceApplication::query()
            ->where('app_number', 'like', $s)
            ->limit($limit)
            ->get()
            ->map(fn ($a) => ['type' => 'application', 'id' => $a->app_id, 'label' => $a->app_number, 'url' => route('finance.show', $a)]);

        return $leads->concat($customers)->concat($apps)->take($limit);
    }
}
