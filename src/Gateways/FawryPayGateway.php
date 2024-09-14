<?php

namespace AhmedTaha\PayBridge\Gateways;

use AhmedTaha\PayBridge\Data\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;
use AhmedTaha\PayBridge\Enums\PaymentEnvironment;
use AhmedTaha\PayBridge\Enums\PaymentMethod;
use AhmedTaha\PayBridge\Enums\PaymentStatus;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FawryPayGateway extends AbstractGateway
{
    protected array $supportedIntegrationTypes = ['hosted', 'api'];

    // The "NONE" method corresponds to the PayAtFawry method
    protected array $supportedPaymentMethods = [PaymentMethod::CREDIT_CARD, PaymentMethod::MOBILE_WALLET, PaymentMethod::NONE];

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
        if (! in_array($type, $this->supportedIntegrationTypes)) {
            throw new \Exception('Unsupported integration type');
        }

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

    protected function generateRequestData(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData, string $integrationType): array
    {
        $customer = $customerData->getData();
        $charge = $chargeData->getData();
        $data = [
            'description' => '',
            'merchantCode' => $this->credentials['merchant_id'],
            'merchantRefNum' => $charge['id'].'-'.uniqid(),
            'customerProfileId' => $customer['id'],
            'customerName' => $customer['name'],
            'customerMobile' => $customer['phone'],
            'customerEmail' => $customer['email'],
            'language' => 'en-gb',
            'chargeItems' => [
                [
                    'itemId' => (string) rand(000000, 999999),
                    'price' => $charge['amount'],
                    'quantity' => '1',
                ],
            ],
            'returnUrl' => $this->getCallbackUrl(),
        ];
        if ($integrationType == 'hosted') {
            $data['signature'] = $this->generateSignature([...$data, 'secretKey' => $this->credentials['secret_key']], [
                'merchantCode',
                'merchantRefNum',
                'customerProfileId',
                'returnUrl',
                'chargeItems.0.itemId',
                'chargeItems.0.quantity',
                'chargeItems.0.price',
                'secretKey',
            ]);
        } elseif ($integrationType == 'api') {
            $data = array_merge($data, [
                'amount' => $charge['amount'],
                'authCaptureModePayment' => false,
                'enable3DS' => true,
            ]);
            if ($paymentData::METHOD == PaymentMethod::CREDIT_CARD) {
                $ccData = $paymentData->getData();
                $data = array_merge($data, [
                    'paymentMethod' => 'PayUsingCC',
                    'cardNumber' => $ccData['cardNumber'],
                    'cardExpiryYear' => $ccData['expiryYear'],
                    'cardExpiryMonth' => $ccData['expiryMonth'],
                    'cvv' => $ccData['cvv'],
                ]);
                $data['signature'] = $this->generateSignature([...$data, 'secretKey' => $this->credentials['secret_key']], [
                    'merchantCode',
                    'merchantRefNum',
                    'customerProfileId',
                    'paymentMethod',
                    'amount',
                    'cardNumber',
                    'cardExpiryYear',
                    'cardExpiryMonth',
                    'cvv',
                    'returnUrl',
                    'secretKey',
                ]);
            } elseif ($paymentData::METHOD == PaymentMethod::MOBILE_WALLET) {
                $walletData = $paymentData->getData();
                $data = array_merge($data, [
                    'paymentMethod' => 'MWALLET',
                    'debitMobileWalletNo' => $walletData['walletNumber'],
                ]);
                $data['signature'] = $this->generateSignature([...$data, 'secretKey' => $this->credentials['secret_key']], [
                    'merchantCode',
                    'merchantRefNum',
                    'customerProfileId',
                    'paymentMethod',
                    'amount',
                    'debitMobileWalletNo',
                    'secretKey',
                ]);
            } else {
                $data['paymentMethod'] = 'PAYATFAWRY';
                $data['signature'] = $this->generateSignature([...$data, 'secretKey' => $this->credentials['secret_key']], [
                    'merchantCode',
                    'merchantRefNum',
                    'customerProfileId',
                    'paymentMethod',
                    'amount',
                    'secretKey',
                ]);
            }
        }

        return $data;
    }

    /**
     * @throws RequestException
     * @throws \Exception
     */
    public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData): array
    {
        if (! in_array($paymentData::METHOD, $this->supportedPaymentMethods)) {
            throw new \Exception('Unsupported Payment Method For FawryPay');
        }

        $requestData = $this->generateRequestData($chargeData, $customerData, $paymentData, $this->integrationType);
        if ($this->integrationType == 'hosted') {
            return $this->payViaHostedCheckout($requestData, $paymentData);
        } elseif ($this->integrationType == 'api') {
            return $this->payViaApi($requestData, $paymentData);
        } else {
            return [
                'success' => false,
                'status' => PaymentStatus::UNPAID,
                'error' => 'Something went wrong',
            ];
        }
    }

    /**
     * @throws RequestException
     * @throws \Exception
     */
    protected function payViaHostedCheckout(array $requestData, AbstractPaymentData $paymentData): array
    {
        $response = Http::post($this->apiUrl, $requestData)->throw();

        $redirectionUrl = $response->body();
        if (! Str::isUrl($redirectionUrl)) {
            throw new \Exception('Fawry Redirection URL Not Available Or Invalid (given: '.$redirectionUrl.')');
        }

        return [
            'success' => true,
            'status' => PaymentStatus::PENDING,
            'shouldRedirect' => true,
            'redirectUrl' => $redirectionUrl,
        ];
    }

    /**
     * @throws RequestException
     * @throws \Exception
     */
    protected function payViaApi(array $requestData, AbstractPaymentData $paymentData): array
    {
        $response = Http::post($this->apiUrl, $requestData)->throw();

        $responseData = $response->json();

        if ($responseData['statusCode'] != '200') {
            throw new \Exception($responseData['statusDescription']);
        }

        if ($paymentData::METHOD == PaymentMethod::CREDIT_CARD) {
            $redirectionUrl = data_get($responseData, 'nextAction.redirectUrl');
            if (! $redirectionUrl) {
                throw new \Exception('Fawry Redirection URL Not Available');
            }

            return [
                'success' => true,
                'status' => PaymentStatus::PENDING,
                'shouldRedirect' => true,
                'redirectUrl' => $redirectionUrl,
            ];
        }

        $referenceNumber = data_get($responseData, 'referenceNumber');
        if (! $referenceNumber || $responseData['statusCode'] != '200') {
            throw new \Exception('Fawry invoice reference number not available');
        }

        return [
            'success' => true,
            'status' => PaymentStatus::PENDING,
            'shouldRedirect' => false,
            'referenceNumber' => $referenceNumber,
        ];
    }

    protected function generateSignature(array $data, ?array $fieldNames = null): string
    {
        if (! $fieldNames) {
            return hash('sha256', implode('', $data));
        }

        return hash('sha256', array_reduce($fieldNames, function ($carry, $fieldName) use ($data) {
            return $carry.data_get($data, $fieldName);
        }, ''));
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
