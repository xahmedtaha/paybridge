<?php

namespace AhmedTaha\PayBridge\Gateways;

use AhmedTaha\PayBridge\Data\ChargeData;
use AhmedTaha\PayBridge\Data\CustomerData;
use AhmedTaha\PayBridge\Data\Payment\AbstractPaymentData;
use AhmedTaha\PayBridge\Data\Payment\CreditCardData;
use AhmedTaha\PayBridge\Data\Payment\EmptyPaymentData;
use AhmedTaha\PayBridge\Data\Payment\MobileWalletData;
use AhmedTaha\PayBridge\Enums\PaymentEnvironment;
use AhmedTaha\PayBridge\Enums\PaymentMethod;
use AhmedTaha\PayBridge\Enums\PaymentStatus;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FawryPayGateway extends AbstractGateway
{
    protected array $supportedIntegrationTypes = ['hosted', 'api'];

    protected array $supportedPaymentMethods = [PaymentMethod::CREDIT_CARD, PaymentMethod::MOBILE_WALLET, PaymentMethod::PAY_AT_FAWRY, PaymentMethod::ANY];

    protected string $apiUrl;

    protected string $integrationType;

    /**
     * @throws Exception
     */
    public function __construct(?array $credentials = null, $environment = null)
    {
        parent::__construct($credentials, $environment);
        $this->integrationType = $this->getIntegrationType();
        $this->apiUrl = $this->getApiUrl();
    }

    /**
     * @throws Exception
     */
    protected function getIntegrationType(): string
    {
        $type = config('paybridge.gateways.fawrypay.integration_type');
        if (! in_array($type, $this->supportedIntegrationTypes)) {
            throw new Exception('Unsupported integration type');
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
     * @throws Exception
     */
    protected function generateRequestData(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData, string $integrationType): array
    {
        $data = [
            'description' => '',
            'merchantCode' => $this->credentials['merchant_id'],
            'merchantRefNum' => $this->generatePaymentId($chargeData->id),
            'customerProfileId' => $customerData->id,
            'customerName' => $customerData->name,
            'customerMobile' => $customerData->phone,
            'customerEmail' => $customerData->email,
            'language' => 'en-gb',
            'chargeItems' => [
                [
                    'itemId' => bin2hex(random_bytes(5)),
                    'price' => $chargeData->amount,
                    'quantity' => '1',
                ],
            ],
            'returnUrl' => $this->getCallbackUrl(),
        ];
        if ($integrationType == 'hosted') {
            if ($chargeData->paymentMethod != PaymentMethod::ANY) {
                $data['paymentMethod'] = match ($chargeData->paymentMethod) {
                    PaymentMethod::CREDIT_CARD => 'CARD',
                    PaymentMethod::MOBILE_WALLET => 'MWALLET',
                    PaymentMethod::PAY_AT_FAWRY => 'PayAtFawry',
                    default => null,
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
                'amount' => $chargeData->amount,
                'authCaptureModePayment' => false,
                'enable3DS' => true,
            ]);
            if ($chargeData->paymentMethod == PaymentMethod::CREDIT_CARD) {
                if (! $paymentData instanceof CreditCardData) throw new Exception('Payment data must be credit card data');
                $data = array_merge($data, [
                    'paymentMethod' => 'PayUsingCC',
                    'cardNumber' => $paymentData->cardNumber,
                    'cardExpiryYear' => $paymentData->expiryYear,
                    'cardExpiryMonth' => $paymentData->expiryMonth,
                    'cvv' => $paymentData->cvv,
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
            } elseif ($chargeData->paymentMethod == PaymentMethod::MOBILE_WALLET) {
                if (! $paymentData instanceof MobileWalletData) throw new Exception('Payment data must be mobile wallet data');
                $data = array_merge($data, [
                    'paymentMethod' => 'MWALLET',
                    'debitMobileWalletNo' => $paymentData->walletNumber,
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
            } else if ($chargeData->paymentMethod == PaymentMethod::PAY_AT_FAWRY) {
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
     * @throws Exception
     */
    public function pay(ChargeData $chargeData, CustomerData $customerData, AbstractPaymentData $paymentData = new EmptyPaymentData): array
    {
        if (! in_array($chargeData->paymentMethod, $this->supportedPaymentMethods)) {
            throw new Exception('Unsupported Payment Method For FawryPay');
        }

        if ($chargeData->paymentMethod == PaymentMethod::ANY && $this->integrationType == 'api'){
            throw new Exception('Payment method must be specified for the api fawry integration');
        }

        $requestData = $this->generateRequestData($chargeData, $customerData, $paymentData, $this->integrationType);
        if ($this->integrationType == 'hosted') {
            return $this->payViaHostedCheckout($requestData, $chargeData);
        } elseif ($this->integrationType == 'api') {
            return $this->payViaApi($requestData, $chargeData);
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
     * @throws Exception
     */
    protected function payViaHostedCheckout(array $requestData, ChargeData $chargeData): array
    {
        $response = Http::post($this->apiUrl, $requestData)->throw();

        $redirectionUrl = $response->body();
        if (! Str::isUrl($redirectionUrl)) {
            throw new Exception('Fawry Redirection URL Not Available Or Invalid (given: '.$redirectionUrl.')');
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
     * @throws Exception
     */
    protected function payViaApi(array $requestData, ChargeData $chargeData): array
    {
        $response = Http::post($this->apiUrl, $requestData)->throw();

        $responseData = $response->json();

        if ($responseData['statusCode'] != '200') {
            throw new Exception($responseData['statusDescription']);
        }

        if ($chargeData->paymentMethod == PaymentMethod::CREDIT_CARD) {
            $redirectionUrl = data_get($responseData, 'nextAction.redirectUrl');
            if (! $redirectionUrl) {
                throw new Exception('Fawry Redirection URL Not Available');
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
            throw new Exception('Fawry invoice reference number not available');
        }

        return [
            'success' => true,
            'status' => PaymentStatus::PENDING,
            'shouldRedirect' => false,
            'referenceNumber' => $referenceNumber,
        ];
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
            throw new Exception('Invalid Signature');
        }
    }

    /**
     * @throws Exception
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
            throw new Exception('Invalid Signature');
        }
    }

    protected function formatNumber($number): string
    {
        if (is_numeric($number)) return number_format($number, 2, '.', '');
        return $number;
    }

    /**
     * @throws Exception
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
            'charge' => new ChargeData(PaymentMethod::ANY, $this->extractPaymentId($request->merchantRefNumber), $request->orderAmount),
            'customer' => new CustomerData($request->customerProfileId, null, $request->customerMobile, $request->customerMail),
            'referenceNumber' => $request->referenceNumber,
            'status' => PaymentStatus::PAID,
        ];
    }

    /**
     * @throws Exception
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
            'charge' => new ChargeData(PaymentMethod::ANY, $this->extractPaymentId($request->merchantRefNumber), $request->orderAmount),
            'customer' => new CustomerData($request->customerMerchantId, $request->customerName, $request->customerMobile, $request->customerMail),
            'referenceNumber' => $request->fawryRefNumber,
        ];
    }
}
