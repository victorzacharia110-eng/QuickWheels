<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickPesaService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $clientId;

    public function __construct()
    {
        $this->baseUrl = config('services.clickpesa.base_url', 'https://api.clickpesa.com');
        $this->apiKey = config('services.clickpesa.api_key');
        $this->clientId = config('services.clickpesa.client_id');
    }

    protected function headers(): array
    {
        return array_filter([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->apiKey,
            'X-Client-ID' => $this->clientId,
        ]);
    }

    public function initiatePayment(float $amount, string $phone, string $reference, string $description = 'QuickWheels Payment'): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post($this->baseUrl . '/api/payments', [
                    'amount' => $amount,
                    'msisdn' => $phone,
                    'reference' => $reference,
                    'description' => $description,
                    'webhook' => route('customer.payments.clickpesa.webhook'),
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('ClickPesa payment initiation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment initiation failed',
            ];
        } catch (\Exception $e) {
            Log::error('ClickPesa exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment service unavailable',
            ];
        }
    }

    public function verifyTransaction(string $transactionId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get($this->baseUrl . '/api/payments/' . $transactionId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Transaction verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('ClickPesa verification exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Verification service unavailable',
            ];
        }
    }

    public function handleWebhook(array $payload): array
    {
        $transactionId = $payload['transaction_id'] ?? null;
        $status = $payload['status'] ?? null;
        $reference = $payload['reference'] ?? null;
        $amount = $payload['amount'] ?? null;

        if (!$transactionId || !$status || !$reference) {
            return ['success' => false, 'message' => 'Invalid webhook payload'];
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'status' => $status,
            'reference' => $reference,
            'amount' => $amount,
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get($this->baseUrl . '/api/payments/' . $transactionId . '/status');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Status check failed',
            ];
        } catch (\Exception $e) {
            Log::error('ClickPesa status exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Status service unavailable',
            ];
        }
    }
}
