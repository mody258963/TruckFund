<div class="flex gap-1">
    <flux:button wire:click="setLocale('en')" size="sm" variant="{{ app()->getLocale() === 'en' ? 'primary' : 'ghost' }}">EN</flux:button>
    <flux:button wire:click="setLocale('ar')" size="sm" variant="{{ app()->getLocale() === 'ar' ? 'primary' : 'ghost' }}">عربي</flux:button>
</div>
