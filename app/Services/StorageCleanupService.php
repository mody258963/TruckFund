<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\Document;
use App\Models\Freelancer;
use App\Models\Identification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorageCleanupService
{
    public function retentionCutoff(): Carbon
    {
        return now()->subMonths(config('truckfund.storage_retention_months', 6));
    }

    /**
     * @return Collection<int, array{id: string, path: string, label: string, context: string, uploaded_at: string, size_bytes: int, source: string}>
     */
    public function previewOlderThan(?Carbon $cutoff = null): Collection
    {
        $cutoff ??= $this->retentionCutoff();
        $disk = Storage::disk(ImageStorageService::DISK);
        $items = collect();

        Document::query()
            ->with('customer')
            ->where('uploaded_at', '<', $cutoff)
            ->orderBy('uploaded_at')
            ->each(function (Document $doc) use ($items, $disk) {
                if (! $doc->file_url || ! $disk->exists($doc->file_url)) {
                    return;
                }
                $items->push($this->row(
                    'doc:'.$doc->doc_id,
                    $doc->file_url,
                    $doc->doc_type->label(),
                    $doc->customer?->display_name ?? '—',
                    $doc->uploaded_at,
                    (int) $disk->size($doc->file_url),
                    'documents',
                ));
            });

        ApplicationDocument::query()
            ->with('application.customer')
            ->where('uploaded_at', '<', $cutoff)
            ->orderBy('uploaded_at')
            ->each(function (ApplicationDocument $doc) use ($items, $disk) {
                if (! $doc->file_url || ! $disk->exists($doc->file_url)) {
                    return;
                }
                $items->push($this->row(
                    'appdoc:'.$doc->app_doc_id,
                    $doc->file_url,
                    $doc->doc_type->label(),
                    $doc->application?->app_number ?? '—',
                    $doc->uploaded_at,
                    (int) $disk->size($doc->file_url),
                    'application_documents',
                ));
            });

        Identification::query()
            ->with('customer')
            ->where(fn ($q) => $q->whereNotNull('id_front_url')->orWhereNotNull('id_back_url'))
            ->get()
            ->each(function (Identification $id) use ($items, $disk, $cutoff) {
                foreach (['id_front_url' => __('customers.id_front'), 'id_back_url' => __('customers.id_back')] as $field => $label) {
                    $path = $id->{$field};
                    if (! $path || ! $disk->exists($path)) {
                        continue;
                    }
                    $mtime = Carbon::createFromTimestamp($disk->lastModified($path));
                    if ($mtime->gte($cutoff)) {
                        continue;
                    }
                    $items->push($this->row(
                        'ident:'.$id->id_doc_id.':'.$field,
                        $path,
                        $label,
                        $id->customer?->display_name ?? '—',
                        $mtime,
                        (int) $disk->size($path),
                        'identifications',
                    ));
                }
            });

        Freelancer::query()->each(function (Freelancer $f) use ($items, $disk, $cutoff) {
            if ($f->created_at->gte($cutoff)) {
                return;
            }
            if ($f->id_card_url && $disk->exists($f->id_card_url)) {
                $items->push($this->row(
                    'freelancer:'.$f->freelancer_id.':card',
                    $f->id_card_url,
                    __('nav.freelancers'),
                    $f->full_name,
                    $f->created_at,
                    (int) $disk->size($f->id_card_url),
                    'freelancers',
                ));
            }
            foreach ($f->documents ?? [] as $idx => $meta) {
                $path = is_array($meta) ? ($meta['path'] ?? null) : null;
                if (! $path || ! $disk->exists($path)) {
                    continue;
                }
                $mtime = Carbon::createFromTimestamp($disk->lastModified($path));
                if ($mtime->gte($cutoff)) {
                    continue;
                }
                $items->push($this->row(
                    'freelancer:'.$f->freelancer_id.':doc:'.$idx,
                    $path,
                    __('settings.freelancer_attachment'),
                    $f->full_name,
                    $mtime,
                    (int) $disk->size($path),
                    'freelancers',
                ));
            }
        });

        return $items->sortBy('uploaded_at')->values();
    }

    public function totalBytes(Collection $items): int
    {
        return (int) $items->sum('size_bytes');
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function deleteByIds(array $ids): int
    {
        $preview = $this->previewOlderThan()->keyBy('id');
        $disk = Storage::disk(ImageStorageService::DISK);
        $deleted = 0;

        DB::transaction(function () use ($ids, $preview, $disk, &$deleted) {
            foreach ($ids as $id) {
                $item = $preview->get($id);
                if (! $item) {
                    continue;
                }

                if ($disk->exists($item['path'])) {
                    $disk->delete($item['path']);
                }

                $this->clearDatabaseReference($id, $item['path']);
                $deleted++;
            }
        });

        return $deleted;
    }

    protected function clearDatabaseReference(string $id, string $path): void
    {
        if (str_starts_with($id, 'doc:')) {
            Document::query()->where('doc_id', substr($id, 4))->delete();

            return;
        }

        if (str_starts_with($id, 'appdoc:')) {
            ApplicationDocument::query()->where('app_doc_id', substr($id, 7))->delete();

            return;
        }

        if (str_starts_with($id, 'ident:')) {
            [, $idDocId, $field] = explode(':', $id, 3);
            Identification::query()->where('id_doc_id', $idDocId)->update([$field => null]);

            return;
        }

        if (str_starts_with($id, 'freelancer:')) {
            $parts = explode(':', $id);
            $freelancerId = $parts[1] ?? null;
            $freelancer = $freelancerId ? Freelancer::query()->find($freelancerId) : null;
            if (! $freelancer) {
                return;
            }
            if (($parts[2] ?? '') === 'card') {
                $freelancer->update(['id_card_url' => null]);

                return;
            }
            $docs = collect($freelancer->documents ?? [])
                ->reject(fn ($meta) => is_array($meta) && ($meta['path'] ?? '') === $path)
                ->values()
                ->all();
            $freelancer->update(['documents' => $docs ?: null]);
        }
    }

    protected function row(
        string $id,
        string $path,
        string $label,
        string $context,
        Carbon $uploadedAt,
        int $sizeBytes,
        string $source,
    ): array {
        return [
            'id' => $id,
            'path' => $path,
            'label' => $label,
            'context' => $context,
            'uploaded_at' => $uploadedAt->toDateTimeString(),
            'size_bytes' => $sizeBytes,
            'size_human' => $this->formatBytes($sizeBytes),
            'source' => $source,
            'basename' => basename($path),
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' '.__('common.mb');
        }

        return round($bytes / 1024, 1).' '.__('common.kb');
    }
}
