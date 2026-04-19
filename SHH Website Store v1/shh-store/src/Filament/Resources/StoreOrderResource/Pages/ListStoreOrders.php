<?php

namespace ShhStore\Filament\Resources\StoreOrderResource\Pages;

use App\Models\User;
use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use ShhStore\Filament\Resources\StoreOrderResource;
use ShhStore\Models\StoreOrder;
use ShhStore\Models\StoreProduct;

class ListStoreOrders extends ListRecords
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;

    protected static string $resource = StoreOrderResource::class;

    /** @return array<Action|ActionGroup> */
    protected function getDefaultHeaderActions(): array
    {
        return [
            Action::make('processUnpaidSuspensions')
                ->label('Process Unpaid Suspensions')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Process Unpaid Suspensions')
                ->modalDescription('Suspend linked servers with unpaid bills that are past the configured delay.')
                ->action(function (): void {
                    Artisan::call('shh-store:process-unpaid-suspensions');

                    Notification::make()
                        ->title('Suspension processing complete')
                        ->body(trim(Artisan::output()))
                        ->success()
                        ->send();
                }),
            Action::make('linkOrphanedOrders')
                ->label('Link Orphaned Orders')
                ->icon('heroicon-o-link')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Link Orphaned Orders to Clients')
                ->modalDescription('This matches orders with no linked client (`user_id`) to users by customer email.')
                ->action(function (): void {
                    $orphanedOrders = StoreOrder::query()
                        ->whereNull('user_id')
                        ->whereNotNull('customer_email')
                        ->get();

                    if ($orphanedOrders->isEmpty()) {
                        Notification::make()
                            ->title('No orphaned orders found')
                            ->body('All orders are already linked to clients, or no customer emails were available.')
                            ->info()
                            ->send();

                        return;
                    }

                    $candidateEmails = $orphanedOrders
                        ->pluck('customer_email')
                        ->filter()
                        ->map(fn ($email) => strtolower(trim((string) $email)))
                        ->unique()
                        ->values();

                    $usersByNormalizedEmail = User::query()
                        ->whereIn('email', $candidateEmails->all())
                        ->get()
                        ->keyBy(fn (User $user) => strtolower((string) $user->email));

                    $linked = 0;
                    $unmatched = 0;

                    foreach ($orphanedOrders as $order) {
                        $normalizedEmail = strtolower(trim((string) $order->customer_email));
                        $user = $usersByNormalizedEmail->get($normalizedEmail);

                        if (!$user) {
                            $unmatched++;
                            continue;
                        }

                        $order->forceFill(['user_id' => $user->id])->save();
                        $linked++;
                    }

                    Notification::make()
                        ->title('Orphaned order link complete')
                        ->body("Linked: {$linked} | Unmatched emails: {$unmatched}")
                        ->success()
                        ->send();
                }),
            Action::make('generateTestOrders')
                ->label('Generate Test Orders')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate Test Orders')
                ->modalDescription('This will create 5 randomized test orders using existing users and products. Continue?')
                ->action(function () {
                    $users = User::inRandomOrder()->limit(10)->get();
                    $products = StoreProduct::all();

                    if ($users->isEmpty() || $products->isEmpty()) {
                        Notification::make()
                            ->title('Cannot generate test orders')
                            ->body('You need at least one user and one product.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $cycles = ['monthly', 'quarterly', 'annually'];
                    $statuses = ['pending', 'paid', 'active', 'unpaid', 'cancelled'];
                    $methods = ['stripe', 'paypal'];
                    $created = 0;

                    for ($i = 0; $i < 5; $i++) {
                        $user = $users->random();
                        $product = $products->random();
                        $cycle = $cycles[array_rand($cycles)];
                        $status = $statuses[array_rand($statuses)];

                        $amount = $product->calculatePrice($cycle);

                        $paidAt = in_array($status, ['paid', 'active'])
                            ? now()->subDays(random_int(1, 60))
                            : null;

                        $billDueAt = $paidAt
                            ? $paidAt->copy()->addMonth()
                            : null;

                        StoreOrder::create([
                            'order_number' => StoreOrder::generateOrderNumber(),
                            'user_id' => $user->id,
                            'product_id' => $product->id,
                            'billing_cycle' => $cycle,
                            'amount' => $amount,
                            'currency' => 'USD',
                            'status' => $status,
                            'payment_method' => $methods[array_rand($methods)],
                            'payment_id' => 'test_' . bin2hex(random_bytes(8)),
                            'transaction_id' => 'txn_test_' . bin2hex(random_bytes(6)),
                            'customer_email' => $user->email,
                            'customer_name' => $user->username,
                            'paid_at' => $paidAt,
                            'bill_due_at' => $billDueAt,
                        ]);

                        $created++;
                    }

                    Notification::make()
                        ->title("Created {$created} test orders")
                        ->success()
                        ->send();
                }),
        ];
    }
}
