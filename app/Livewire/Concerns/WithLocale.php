<?php

namespace App\Livewire\Concerns;

trait WithLocale
{
    public function setLocale(string $locale): void
    {
        if (in_array($locale, ['en', 'ar'], true)) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }
    }
}
