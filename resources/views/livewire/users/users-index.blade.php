<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('crm.users.title') }}</flux:heading>
        @can('create', App\Models\User::class)
        <flux:button wire:click="$set('showCreate', true)" variant="primary" icon="plus">{{ __('crm.users.create') }}</flux:button>
        @endcan
    </div>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('nav.search') }}" class="mb-4 max-w-xs" />
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.customer') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('auth.email') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('leads.status') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('crm.users.manager') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-zinc-500">{{ __('common.created_at') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($users as $user)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $user->full_name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3"><flux:badge>{{ $user->role->label() }}</flux:badge></td>
                    <td class="px-4 py-3 text-sm text-zinc-500">{{ $user->manager?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500">{{ $user->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('common.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
    @if($showCreate)
    <flux:modal wire:model="showCreate" class="md:w-lg">
        <flux:heading>{{ __('crm.users.create') }}</flux:heading>
        <form wire:submit="create" class="mt-4 space-y-4">
            <flux:input wire:model="full_name" label="{{ __('leads.customer') }}" required />
            <flux:input wire:model="email" type="email" label="{{ __('auth.email') }}" required />
            <flux:input wire:model="phone" label="{{ __('leads.phone') }}" />
            <flux:select wire:model.live="role" label="{{ __('leads.status') }}">
                @foreach($creatableRoles as $r)
                    <flux:select.option value="{{ $r->value }}">{{ $r->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            @if($role !== App\Enums\UserRole::Admin->value && $role !== App\Enums\UserRole::Manager->value)
            <flux:select wire:model="reports_to_user_id" label="{{ __('crm.users.manager') }}">
                <flux:select.option value="">{{ __('common.none') }}</flux:select.option>
                @foreach($managers as $m)
                    <flux:select.option value="{{ $m->user_id }}">{{ $m->full_name }} ({{ $m->role->label() }})</flux:select.option>
                @endforeach
            </flux:select>
            @endif
            <flux:input wire:model="password" type="password" label="{{ __('auth.password') }}" required />
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="$set('showCreate', false)" variant="ghost">{{ __('common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('common.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif
</div>
