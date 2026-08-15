<div>
    <div class="mb-6 flex justify-between">
        <flux:heading size="xl">{{ __($titleKey) }}</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('common.add') }}</flux:button>
    </div>
    <flux:input wire:model.live.debounce.300ms="search" class="mb-4 max-w-xs" placeholder="{{ __('nav.search') }}" />
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    @foreach($fields as $field)
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __("catalog.{$field}") }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($items as $item)
                <tr>
                    @foreach($fields as $field)
                    <td class="px-4 py-3 text-sm">
                        @if($routeName === 'auto-products' && $field === 'type')
                            {{ $item->type->label() }}
                        @elseif($routeName === 'auto-products' && $field === 'price')
                            {{ number_format((float) $item->price, 2) }}
                        @else
                            {{ $item->{$field} }}
                        @endif
                    </td>
                    @endforeach
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $item->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-end">
                        <flux:button wire:click="edit('{{ $item->getKey() }}')" size="sm" variant="ghost">{{ __('common.edit') }}</flux:button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ count($fields)+2 }}" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>

    <flux:modal wire:model="showForm" class="md:w-lg">
        <flux:heading>{{ $editingId ? __('common.edit') : __('common.add') }}</flux:heading>
        <form wire:submit="save" class="mt-4 space-y-4 max-h-[70vh] overflow-y-auto pe-1">
            @foreach($fields as $field)
                @php
                    $isNumeric = in_array($field, $numericFields, true) || in_array($field, $decimalFields, true);
                    $isTextarea = $field === 'description';
                @endphp
                <div wire:key="catalog-field-{{ $routeName }}-{{ $field }}">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __("catalog.{$field}") }}
                    </label>
                    @if($routeName === 'auto-products' && $field === 'type')
                        <select
                            wire:model="form.type"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            @foreach($autoProductTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    @elseif($isTextarea)
                        <textarea
                            wire:model="form.{{ $field }}"
                            rows="3"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        ></textarea>
                    @elseif($routeName === 'auto-products' && $field === 'price')
                        <x-money-input
                            model="form.price"
                            :value="$form['price']"
                            :input-key="'catalog-price-'.($showForm ? 'open' : 'closed').'-'.($editingId ?? 'new')"
                        />
                    @else
                        <input
                            type="{{ $isNumeric ? 'number' : 'text' }}"
                            wire:model="form.{{ $field }}"
                            @if(in_array($field, $decimalFields, true)) step="0.01" @elseif(in_array($field, $numericFields, true)) step="1" @endif
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    @endif
                    @unless($routeName === 'auto-products' && $field === 'price')
                        @error('form.'.$field)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endunless
                </div>
            @endforeach

            @if($hasIsActive)
                <flux:checkbox wire:model="form.is_active" label="{{ __('catalog.is_active') }}" />
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" wire:click="$set('showForm', false)" variant="ghost">{{ __('common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
