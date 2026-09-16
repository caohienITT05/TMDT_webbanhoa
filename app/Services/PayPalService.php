<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.paypal.base_url');
    }

    /**
     * Lấy Access Token từ PayPal Sandbox.
     */
    protected function getAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(
                config('services.paypal.client_id'),
                config('services.paypal.client_secret')
            )
            ->post(
                $this->baseUrl . '/v1/oauth2/token',
                [
                    'grant_type' => 'client_credentials',
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Không thể lấy PayPal Access Token: ' . $response->body()
            );
        }

        $accessToken = $response->json('access_token');

        if (!$accessToken) {
            throw new RuntimeException(
                'PayPal không trả về Access Token.'
            );
        }

        return $accessToken;
    }

    /**
     * Tạo PayPal Order.
     */
    public function createOrder(
        string $orderCode,
        float $amount,
        string $returnUrl,
        string $cancelUrl
    ): array {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post(
                $this->baseUrl . '/v2/checkout/orders',
                [
                    'intent' => 'CAPTURE',

                    'purchase_units' => [
                        [
                            'reference_id' => $orderCode,

                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format(
                                    $amount,
                                    2,
                                    '.',
                                    ''
                                ),
                            ],
                        ],
                    ],

                    'application_context' => [
                        'return_url' => $returnUrl,
                        'cancel_url' => $cancelUrl,
                        'user_action' => 'PAY_NOW',
                    ],
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Không thể tạo PayPal Order: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Capture PayPal Order sau khi khách thanh toán.
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $accessToken = $this->getAccessToken();

        $url = $this->baseUrl .
            '/v2/checkout/orders/' .
            $paypalOrderId .
            '/capture';

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->withBody('', 'application/json')
            ->post($url);

        if ($response->failed()) {
            throw new RuntimeException(
                'Không thể capture PayPal Order: ' . $response->body()
            );
        }

        return $response->json();
    }
}
