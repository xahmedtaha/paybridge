<?php

namespace AhmedTaha\PayBridge\Gateways;

use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;
use AhmedTaha\PayBridge\Data\Payment\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\Payment\EmptyPaymentData;
use AhmedTaha\PayBridge\Enums\PaymentEnvironment;
use AhmedTaha\PayBridge\Enums\PaymentMethod;
use AhmedTaha\PayBridge\Enums\PaymentStatus;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Random\RandomException;

class FawryPayGateway extends AbstractGateway
{
    protected array $supportedIntegrationTypes = ['hosted', 'api'];

    protected array $supportedPaymentMethods = [PaymentMethod::CREDIT_CARD, PaymentMethod::MOBILE_WALLET, PaymentMethod::PAY_AT_FAWRY, PaymentMethod::ANY];

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
        $type = config('paybridge.gateways.fawrypay.integration_type');
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

    /**
     * @throws RandomException
     */
    protected function generateRequestData(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData, string $integrationType): array
    {
        $customer = $customerData->getData();
        $charge = $chargeData->getData();
        $data = [
            'description' => '',
            'merchantCode' => $this->credentials['merchant_id'],
            'merchantRefNum' => $this->generatePaymentId($charge['id']),
            'customerProfileId' => $customer['id'],
            'customerName' => $customer['name'],
            'customerMobile' => $customer['phone'],
            'customerEmail' => $customer['email'],
            'language' => 'en-gb',
            'chargeItems' => [
                [
                    'itemId' => (string) bin2hex(random_bytes(5)),
                    'price' => $charge['amount'],
                    'quantity' => '1',
                ],
            ],
            'returnUrl' => $this->getCallbackUrl(),
        ];
        if ($integrationType == 'hosted') {
            if ($paymentData::METHOD != PaymentMethod::ANY) {
                $data['paymentMethod'] = match ($paymentData::METHOD) {
                    PaymentMethod::CREDIT_CARD => 'CARD',
                    PaymentMethod::MOBILE_WALLET => 'MWALLET',
                    PaymentMethod::PAY_AT_FAWRY => 'PayAtFawry',
                };
            }
            $data['signature'] = $this->generateSignature($data, [
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
                $data['signature'] = $this->generateSignature($data, [
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
                $data['signature'] = $this->generateSignature($data, [
                    'merchantCode',
                    'merchantRefNum',
                    'customerProfileId',
                    'paymentMethod',
                    'amount',
                    'debitMobileWalletNo',
                    'secretKey',
                ]);
            } else if ($paymentData::METHOD == PaymentMethod::PAY_AT_FAWRY) {
                $data['paymentMethod'] = 'PAYATFAWRY';
                $data['signature'] = $this->generateSignature($data, [
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
    public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData = new EmptyPaymentData): array
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

    /**
     * @throws RandomException
     */
    protected function generatePaymentId(string $id): string
    {
        return $id . bin2hex(random_bytes(5));
    }

    protected function extractPaymentId(string $paymentId): string
    {
        return substr($paymentId, 0, -10);
    }

    protected function generateSignature(array $data, ?array $fieldNames = null): string
    {
        if (! $fieldNames) {
            $plainTextSignature = implode('', $data);
        } else {
            $plainTextSignature = array_reduce($fieldNames, function ($carry, $fieldName) use ($data) {
                return $carry.data_get($data, $fieldName);
            }, '');
        }
        $plainTextSignature .= $this->credentials['secret_key'];
        return hash('sha256', $plainTextSignature);
    }

    /**
     * @throws \Exception
     */
    protected function verifyCallbackSignature(string $signature, array $data): void
    {
        foreach (['paymentAmount', 'orderAmount', 'fawryFees', 'shippingFees'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = $this->formatNumber($data[$key]);
            }
        }
        if ($signature !== $this->generateSignature($data, [
                'referenceNumber',
                'merchantRefNum',
                'paymentAmount',
                'orderAmount',
                'orderStatus',
                'paymentMethod',
                'fawryFees',
                'shippingFees',
                'authNumber',
                'customerMail',
                'customerMobile',
            ])) {
            throw new \Exception('Invalid Signature');
        }
    }

    /**
     * @throws \Exception
     */
    protected function verifyWebhookSignature(string $signature, array $data): void
    {
        foreach (['paymentAmount', 'orderAmount'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = $this->formatNumber($data[$key]);
            }
        }
        if ($signature !== $this->generateSignature($data, [
                'fawryRefNumber',
                'merchantRefNum',
                'paymentAmount',
                'orderAmount',
                'orderStatus',
                'paymentMethod',
                'paymentReferenceNumber',
            ])) {
            throw new \Exception('Invalid Signature');
        }
    }

    protected function formatNumber($number): string
    {
        if (is_numeric($number)) return number_format($number, 2, '.', '');
        return $number;
    }

    /**
     * @throws \Exception
     */
    public function callback(Request $request): array
    {
        $this->verifyCallbackSignature($request->signature, $request->toArray());
        if ($request->statusCode != '200' || $request->orderStatus != 'PAID') {
            return [
                'success' => false,
                'status' => PaymentStatus::UNPAID,
                'message' => $request->statusDescription,
            ];
        }

        return [
            'success' => true,
            'charge' => new ChargeData($this->extractPaymentId($request->merchantRefNumber), $request->orderAmount),
            'customer' => new CustomerData($request->customerProfileId, null, $request->customerMobile, $request->customerMail),
            'referenceNumber' => $request->referenceNumber,
            'status' => PaymentStatus::PAID,
        ];
    }

    /**
     * @throws \Exception
     */
    public function handleWebhook(Request $request): array
    {
        $this->verifyWebhookSignature($request->messageSignature, $request->toArray());
        if ($request->orderStatus != 'PAID') {
            return [
                'success' => $request->orderStatus != 'PAID' && $request->orderStatus != 'NEW',
                'status' => match ($request->orderStatus) {
                    'UNPAID', 'CANCELED', 'REFUNDED', 'EXPIRED', 'PARTIAL_REFUNDED', 'FAILED' => PaymentStatus::UNPAID,
                    default => PaymentStatus::PENDING
                },
                'message' => $request->statusDescription,
            ];
        }

        return [
            'success' => true,
            'status' => PaymentStatus::PAID,
            'charge' => new ChargeData($this->extractPaymentId($request->merchantRefNumber), $request->orderAmount),
            'customer' => new CustomerData($request->customerMerchantId, $request->customerName, $request->customerMobile, $request->customerMail),
            'referenceNumber' => $request->fawryRefNumber,
        ];
    }
}
