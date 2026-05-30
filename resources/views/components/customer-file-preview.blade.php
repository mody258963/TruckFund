@props(['url', 'label', 'isImage' => true])

<div class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
    <p class="border-b border-zinc-200 px-3 py-2 text-xs font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">{{ $label }}</p>
    <div class="p-3">
        @if($isImage)
            <a href="{{ $url }}" target="_blank" rel="noopener" class="block">
                <img src="{{ $url }}" alt="{{ $label }}" class="mx-auto max-h-56 w-full rounded-md object-contain" loading="lazy" />
            </a>
        @else
            <flux:button href="{{ $url }}" target="_blank" variant="ghost" size="sm" icon="document">
                {{ __('common.view') }}
            </flux:button>
        @endif
    </div>
</div>
