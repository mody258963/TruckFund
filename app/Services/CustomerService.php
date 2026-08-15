<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerService
{
    public function delete(Customer $customer): void
    {
        $customer->loadMissing([
            'identification',
            'documents',
            'financeApplications.applicationDocuments',
        ]);

        $paths = collect([
            $customer->identification?->id_front_url,
            $customer->identification?->id_back_url,
        ])
            ->merge($customer->documents->pluck('file_url'))
            ->merge(
                $customer->financeApplications
                    ->flatMap->applicationDocuments
                    ->pluck('file_url')
            )
            ->filter()
            ->unique()
            ->values();

        DB::transaction(fn () => $customer->deleteOrFail());

        Storage::disk(ImageStorageService::DISK)->delete($paths->all());
    }
}
