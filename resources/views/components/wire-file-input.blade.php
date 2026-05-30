@props(['label'])

<div {{ $attributes->only('class')->merge(['class' => 'mt-3']) }}>
    <flux:label>{{ $label }}</flux:label>
    <input
        type="file"
        {{ $attributes->except('class')->class('mt-2 block w-full cursor-pointer text-sm text-zinc-600 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100 dark:text-zinc-400 dark:file:bg-teal-900/30 dark:file:text-teal-300') }}
    />
    @php($wireTarget = $attributes->wire('model')->value() ?? $attributes->whereStartsWith('wire:model')->first())
    <div wire:loading.flex wire:target="{{ $wireTarget }}" class="mt-2 hidden text-xs text-zinc-500">
        {{ __('customers.file_uploading') }}
    </div>
</div>
