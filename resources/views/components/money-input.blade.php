@props([
    'model',
    'value' => null,
    'label' => null,
    'inputKey' => null,
])

<div
    x-data="moneyInput(@js($model), @js($value))"
    x-init="init()"
    wire:key="{{ $inputKey ?? 'money-'.$model }}"
>
    <flux:input
        x-model="display"
        x-on:input="formatInput()"
        x-on:blur="commit($wire)"
        type="text"
        inputmode="decimal"
        :label="$label"
        {{ $attributes }}
    />
    @error($model)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
