<?php

namespace App\Plugins\ShadowStore\Services;

use App\Plugins\ShadowStore\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    protected string $baseUrl;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = config('shadow-store.paypal.mode') === 'live' 
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    protected function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = Http::withBasicAuth(
                config('shadow-store.paypal.client_id'),
                config('shadow-store.paypal.client_secret')
            )->asForm()->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials'
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                return $this->accessToken;
            }
        } catch (\Exception $e) {
            Log::error('PayPal auth error: ' . $e->getMessage());
        }

        return null;
    }

    public function createOrder(Order $order, string $returnUrl, string $cancelUrl): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        try {
            $response = Http::withToken($token)->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'description' => $order->product->name,
                    'amount' => [
                        'currency_code' => $order->currency,
                        'value' => number_format($order->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'brand_name' => 'Shadow Haven Hosting',
                    'user_action' => 'PAY_NOW',
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $order->update(['payment_id' => $data['id']]);
                
                // Find approval URL
                $approvalUrl = collect($data['links'])->firstWhere('rel', 'approve')['href'] ?? null;
                
                return [
                    'id' => $data['id'],
                    'approval_url' => $approvalUrl,
                ];
            }
        } catch (\Exception $e) {
            Log::error('PayPal create order error: ' . $e->getMessage());
        }

        return null;
    }

    public function captureOrder(string $paypalOrderId): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        try {
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}/capture");

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'COMPLETED') {
                    $referenceId = $data['purchase_units'][0]['reference_id'];
                    $order = Order::where('order_number', $referenceId)->first();
                    
                    if ($order) {
                        $order->update(['status' => 'paid']);
                        app(ProvisioningService::class)->provision($order);
                    }
                    
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::error('PayPal capture error: ' . $e->getMessage());
        }

        return false;
    }
}
