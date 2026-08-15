<div class="w-full max-w-md rounded-2xl border border-zinc-700/50 bg-white/95 p-8 shadow-2xl backdrop-blur dark:bg-zinc-900/95">
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-teal-700 dark:text-teal-400">{{ __('app.name') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('auth.login_subtitle') }}</p>
    </div>
    <form wire:submit="login" class="space-y-4">
        <flux:field>
            <flux:label>{{ __('auth.email') }}</flux:label>
            <flux:input wire:model="email" type="email" autocomplete="username" />
            <flux:error name="email" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('auth.password') }}</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>
        <flux:checkbox wire:model="remember" label="{{ __('auth.remember') }}" />
        <flux:button type="submit" variant="primary" class="w-full">{{ __('auth.login') }}</flux:button>
    </form>
    <div class="mt-4 flex justify-center gap-2">
        <flux:button wire:click="setLocale('en')" size="sm" variant="ghost">{{ __('common.locale_en') }}</flux:button>
        <flux:button wire:click="setLocale('ar')" size="sm" variant="ghost">{{ __('common.locale_ar') }}</flux:button>
    </div>
</div>
