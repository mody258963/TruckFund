<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithLocale;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    use WithLocale;

    public function render()
    {
        return view('livewire.locale-switcher');
    }
}
