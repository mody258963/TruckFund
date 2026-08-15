<?php

namespace App\Livewire\Concerns;

trait WithLocale
{
    public function setLocale(string $locale): void
    {
        if (! in_array($locale, ['en', 'ar'], true)) {
            return;
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        $this->redirect(url()->previous() ?: route('dashboard'));
    }
}
