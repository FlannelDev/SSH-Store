<?php

namespace App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource\Pages;

use App\Plugins\ShadowStore\Filament\Admin\Resources\ClientResource;
use App\Plugins\ShadowStore\Models\ClientCredit;
use App\Plugins\ShadowStore\Models\ServerBilling;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;
    protected string $view = 'shadow-store::admin.pages.view-client';

    public ?int $serverId = null;

    public ?string $billingAmount = null;

    public ?string $nodeAmount = null;

    public ?string $billDueAt = null;

    public ?string $creditAmount = null;

    public ?string $creditNote = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->serverId = $this->getServerOptions()[0]['id'] ?? null;
        $this->loadServerDueDate();
    }

    public function getServerOptions(): array
    {
        return $this->record->servers()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($server) => ['id' => $server->id, 'name' => $server->name])
            ->all();
    }

    public function updatedServerId(): void
    {
        $this->loadServerDueDate();
    }

    public function loadServerDueDate(): void
    {
        if (!SchemaFacade::hasTable('store_server_billings') || !$this->serverId) {
            $this->billingAmount = null;
            $this->nodeAmount = null;
            $this->billDueAt = null;

            return;
        }

        $billing = ServerBilling::query()->where('server_id', $this->serverId)->first();
        $this->billingAmount = filled($billing?->billing_amount) ? number_format((float) $billing->billing_amount, 2, '.', '') : null;
        $this->nodeAmount = filled($billing?->node_amount) ? number_format((float) $billing->node_amount, 2, '.', '') : null;
        $this->billDueAt = $billing?->bill_due_at?->format('Y-m-d\TH:i');
    }

    public function saveServerDueDate(): void
    {
        if (!SchemaFacade::hasTable('store_server_billings')) {
            return;
        }

        $server = $this->record->servers()->whereKey($this->serverId)->firstOrFail();

        ServerBilling::query()->updateOrCreate(
            ['server_id' => $server->id],
            [
                'user_id' => $this->record->id,
                'billing_amount' => filled($this->billingAmount) ? round((float) $this->billingAmount, 2) : null,
                'node_amount' => filled($this->nodeAmount) ? round((float) $this->nodeAmount, 2) : null,
                'bill_due_at' => filled($this->billDueAt) ? $this->billDueAt : null,
            ]
        );

        Notification::make()
            ->success()
            ->title('Server due date updated.')
            ->send();

        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]), navigate: true);
    }

    public function applyClientCredit(): void
    {
        abort_unless(auth()->user()?->isRootAdmin() ?? false, 403);

        ClientCredit::create([
            'user_id' => $this->record->id,
            'applied_by' => auth()->id(),
            'amount' => round((float) $this->creditAmount, 2),
            'note' => filled($this->creditNote) ? trim($this->creditNote) : null,
        ]);

        Notification::make()
            ->success()
            ->title('Credit applied successfully.')
            ->send();

        $this->creditAmount = null;
        $this->creditNote = null;

        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]), navigate: true);
    }
}
