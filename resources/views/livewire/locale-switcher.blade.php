<div class="flex gap-1">
    <flux:button wire:click="setLocale('en')" size="sm" variant="{{ app()->getLocale() === 'en' ? 'primary' : 'ghost' }}">{{ __('common.locale_en') }}</flux:button>
    <flux:button wire:click="setLocale('ar')" size="sm" variant="{{ app()->getLocale() === 'ar' ? 'primary' : 'ghost' }}">{{ __('common.locale_ar') }}</flux:button>
</div>
