<?php

namespace ShhStore\Filament\Resources\StoreOrderResource\Pages;

use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use ShhStore\Filament\Resources\StoreOrderResource;

class EditStoreOrder extends EditRecord
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;

    protected static string $resource = StoreOrderResource::class;

    /** @return array<Action|ActionGroup> */
    protected function getDefaultHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Orders')
                ->url(StoreOrderResource::getUrl('index'))
                ->color('gray'),
            Action::make('save')
                ->hiddenLabel()
                ->action('save')
                ->keyBindings(['mod+s'])
                ->tooltip(trans('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->icon('heroicon-o-check'),
            Action::make('suspendLinkedServer')
                ->label('Suspend Linked Server')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => filled($this->record->server_id))
                ->disabled(fn (): bool => !$this->record->isUnpaidForSuspension())
                ->tooltip(fn (): string => $this->record->isUnpaidForSuspension()
                    ? 'Suspend this linked server now for non-payment.'
                    : 'Server can only be suspended after the configured unpaid delay.')
                ->action(function (): void {
                    if ($this->record->suspendForNonPayment()) {
                        Notification::make()
                            ->title('Server suspended')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'suspended_for_nonpayment_at']);
                        return;
                    }

                    Notification::make()
                        ->title('Unable to suspend server')
                        ->body('Verify a linked server exists and the bill is overdue based on your suspension settings.')
                        ->danger()
                        ->send();
                }),
            Action::make('unsuspendLinkedServer')
                ->label('Unsuspend Linked Server')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => filled($this->record->server_id) && filled($this->record->suspended_for_nonpayment_at))
                ->action(function (): void {
                    if ($this->record->releaseNonPaymentSuspension()) {
                        Notification::make()
                            ->title('Server unsuspended')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'suspended_for_nonpayment_at']);
                        return;
                    }

                    Notification::make()
                        ->title('Unable to unsuspend server')
                        ->danger()
                        ->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
