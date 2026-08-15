<div class="relative w-64">
    <flux:input wire:model.live.debounce.300ms="query" placeholder="{{ __('nav.search') }}" icon="magnifying-glass" />
    @if(count($results) > 0)
        <div class="absolute z-50 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            @foreach($results as $item)
                <a href="{{ $item['url'] }}" class="block px-4 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ $item['label'] }} <span class="text-zinc-400">({{ $item['type_label'] }})</span></a>
            @endforeach
        </div>
    @endif
</div>
