<?php

namespace AhmedTaha\PayBridge\Gateways;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;
use AhmedTaha\PayBridge\Enums\PaymentEnvironment;
use AhmedTaha\PayBridge\Enums\PaymentMethod;
use AhmedTaha\PayBridge\Enums\PaymentStatus;
use AhmedTaha\PayBridge\Gateways\AbstractGateway;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FawryPayGateway extends AbstractGateway
{
    protected array $supportedIntegrationTypes = ['hosted', 'api'];
    protected string $apiUrl;
    protected string $integrationType;

    /**
     * @throws \Exception
     */
    public function __construct(?array $credentials = null, $environment = null)
    {
        parent::__construct($credentials, $environment);
        $this->integrationType = $this->getIntegrationType();
        $this->apiUrl = $this->getApiUrl();
    }

    /**
     * @throws \Exception
     */
    protected function getIntegrationType(): string
    {
        $type = config('paybridge.integration_type');
        if (! in_array($type, $this->supportedIntegrationTypes)) throw new \Exception('Unsupported integration type');
        return $type;
    }

    protected function getApiUrl(): string
    {
        return match ($this->integrationType) {
            'hosted' => match ($this->environment) {
                PaymentEnvironment::PRODUCTION => 'https://atfawry.com/fawrypay-api/api/payments/init',
                PaymentEnvironment::TESTING => 'https://atfawry.fawrystaging.com/fawrypay-api/api/payments/init',
            },
            'api' => match ($this->environment) {
                PaymentEnvironment::PRODUCTION => 'https://www.atfawry.com/ECommerceWeb/Fawry/payments/charge',
                PaymentEnvironment::TESTING => 'https://atfawry.fawrystaging.com/ECommerceWeb/api/payments/charge',
            },
        };
    }

    protected function getDefaultCredentials(): array
    {
        return [
            'merchant_id' => config('paybridge.gateways.fawrypay.merchant_id'),
            'access_key' => config('paybridge.gateways.fawrypay.access_key'),
            'secret_key' => config('paybridge.gateways.fawrypay.secret_key'),
        ];
    }

    protected function getCallbackUrl(): string
    {
        return config('paybridge.gateways.fawrypay.callback_url');
    }

    protected function getPaymentEnvironment(): PaymentEnvironment
    {
        return PaymentEnvironment::from(config('paybridge.gateways.fawrypay.environment'));
    }

    /**
     * @throws RequestException
     */
    public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData): array
    {
        $requestData = [
            'merchantRefNum' => $chargeData->id.'-'.uniqid(),
            'customerMobile' => $customerData->phone,
            'customerEmail' => $customerData->email,
            'chargeItems' => [
                [
                    "itemId" => (string) rand(000000, 999999),
                    "price" => $chargeData->amount,
                    "quantity" => "1",
                ]
            ],
            'language' => 'en-gb',
            'returnUrl' => $this->getCallbackUrl(),
        ];
        if ($this->integrationType == 'hosted') return $this->payViaHostedCheckout($requestData);
        else if ($this->integrationType == 'api') return $this->payViaApi($requestData, $chargeData, $paymentData);
        else return [
            'status' => PaymentStatus::UNPAID,
            'error' => 'Something went wrong',
        ];
    }

    /**
     * @throws RequestException
     * @throws \Exception
     */
    protected function payViaHostedCheckout(array $requestData): array
    {
        $data = $requestData;

        $signature = $requestData['merchantCode'].$requestData['merchantRefNum'].$requestData['returnUrl'];
        $signature .= array_reduce($requestData['chargeItems'], function ($carry, $item) {
            return $carry . $item['itemId'] . $item['quantity'] . $item['price'];
        });
        $signature .= $this->credentials['secret_key'];
        $data['signature'] = hash('sha256', $signature);

        $response = Http::post($this->apiUrl, $data)->throw();

        $redirectionUrl = $response->body();
        if (!$redirectionUrl) throw new \Exception('Fawry Redirection URL Not Available');

        return [
            'status' => PaymentStatus::PENDING,
            'redirectUrl' => $redirectionUrl,
        ];
    }

    /**
     * @throws RequestException
     * @throws \Exception
     */
    protected function payViaApi(array $requestData, ChargeData $chargeData, AbstractPaymentData $paymentData): array
    {
        $data = $data = array_merge($requestData, [
            'amount' => $chargeData->amount,
            'paymentMethod' => 'PayUsingCC',
            'authCaptureModePayment' => false,
            'enable3DS' => true,
        ]);

        if ($paymentData->paymentMethod == PaymentMethod::CREDIT_CARD) {
            $ccData = $paymentData->getData();
            $data = array_merge($data, [
                'cardNumber' => $ccData['cardNumber'],
                'cardExpiryYear' => $ccData['expiryYear'],
                'cardExpiryMonth' => $ccData['expiryMonth'],
                'cvv' => $ccData['cvv'],
            ]);
        }

        $signature = $requestData['merchantCode']
            .$requestData['merchantRefNum']
            .$requestData['paymentMethod']
            .$requestData['amount']
            .($requestData['paymentMethod'] === 'PayUsingCC' ? $requestData['cardNumber'] .$requestData['cardExpiryYear'] .$requestData['cardExpiryMonth'] .$requestData['cvv'] : '')
            .$requestData['returnUrl']
            .($requestData['paymentMethod'] === 'MWALLET' ? $requestData['debitMobileWalletNo'] : '')
            .$this->credentials['secret_key'];

        $data['signature'] = hash('sha256', $signature);

        $response = Http::post($this->apiUrl, $data)->throw();
        $redirectionUrl = data_get($response->json(), 'nextAction.redirectUrl');
        if (!$redirectionUrl) throw new \Exception('Fawry Redirection URL Not Available');

        return [
            'status' => PaymentStatus::PENDING,
            'redirectUrl' => $redirectionUrl,
        ];
    }

    protected function generateSignature(array $fields): string
    {
        return hash('sha256', implode('', $fields));
    }

    public function verify()
    {
        // TODO: Implement verify() method.
    }

    public function callback()
    {
        // TODO: Implement callback() method.
    }
}
