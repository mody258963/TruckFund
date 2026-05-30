<?php

namespace App\Livewire\Admin;

use App\Services\StorageCleanupService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    public array $items = [];

    public array $selected = [];

    public int $totalBytes = 0;

    public string $cutoffDate = '';

    public bool $scanned = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $cutoff = app(StorageCleanupService::class)->retentionCutoff();
        $this->cutoffDate = $cutoff->toDateString();
    }

    public function scan(StorageCleanupService $cleanup): void
    {
        $collection = $cleanup->previewOlderThan();
        $this->items = $collection->all();
        $this->totalBytes = $cleanup->totalBytes($collection);
        $this->selected = $collection->pluck('id')->all();
        $this->scanned = true;
    }

    public function toggleSelectAll(): void
    {
        if (count($this->selected) === count($this->items)) {
            $this->selected = [];
        } else {
            $this->selected = collect($this->items)->pluck('id')->all();
        }
    }

    public function deleteSelected(StorageCleanupService $cleanup): void
    {
        if ($this->selected === []) {
            $this->dispatch('notify', message: __('settings.nothing_selected'));

            return;
        }

        $count = $cleanup->deleteByIds($this->selected);
        $this->scan($cleanup);
        $this->dispatch('notify', message: __('settings.deleted_count', ['count' => $count]));
    }

    public function render(StorageCleanupService $cleanup)
    {
        return view('livewire.admin.settings', [
            'totalHuman' => $cleanup->formatBytes($this->totalBytes),
            'retentionMonths' => config('truckfund.storage_retention_months', 6),
            'maxImageKb' => config('truckfund.image_max_kb', 2048),
        ]);
    }
}
