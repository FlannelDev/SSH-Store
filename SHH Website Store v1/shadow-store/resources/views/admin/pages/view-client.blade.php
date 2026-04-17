<x-filament-panels::page>
    {{ $this->infolist }}

    <div class="grid gap-6 lg:grid-cols-2 mt-6">
        <section class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <header class="fi-section-header flex flex-col gap-1.5 p-6">
                <h2 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white">Client Actions</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage due dates, apply credit, and jump to this client's orders.</p>
            </header>
            <div class="p-6 pt-0 space-y-6">
                @if(!empty($this->getServerOptions()))
                    <form wire:submit="saveServerDueDate" class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Set / Edit Server Due Date</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select an existing server and assign a due date, server amount, and optional node amount for dedicated-machine cases.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <label class="block text-sm">
                                <span class="mb-1 block text-gray-700 dark:text-gray-300">Server</span>
                                <select wire:model.live="serverId" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                    @foreach($this->getServerOptions() as $server)
                                        <option value="{{ $server['id'] }}">{{ $server['name'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-gray-700 dark:text-gray-300">Server Amount</span>
                                <input type="number" step="0.01" min="0" wire:model="billingAmount" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-gray-700 dark:text-gray-300">Node Amount</span>
                                <input type="number" step="0.01" min="0" wire:model="nodeAmount" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-gray-700 dark:text-gray-300">Bill Due Date</span>
                                <input type="datetime-local" wire:model="billDueAt" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            </label>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-500">Save Billing Details</button>
                        </div>
                    </form>
                @endif

                @if(auth()->user()?->isRootAdmin() ?? false)
                    <form wire:submit="applyClientCredit" class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Apply Credit</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Add manual credit to this client account.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block text-sm">
                                <span class="mb-1 block text-gray-700 dark:text-gray-300">Amount</span>
                                <input type="number" step="0.01" min="0.01" wire:model="creditAmount" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            </label>
                            <label class="block text-sm md:col-span-1">
                                <span class="mb-1 block text-gray-700 dark:text-gray-300">Note</span>
                                <input type="text" wire:model="creditNote" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                            </label>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Apply Credit</button>
                        </div>
                    </form>
                @endif

                <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Orders</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Open filtered store orders for this client.</p>
                    <div class="mt-4">
                        <a href="{{ \App\Plugins\ShadowStore\Filament\Admin\Resources\OrderResource::getUrl('index') . '?tableSearch=' . urlencode($this->record->username) }}" class="inline-flex items-center justify-center rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-600">View Orders</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
