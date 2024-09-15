<?php

use AhmedTaha\PayBridge\Enums\PaymentGateway;
use AhmedTaha\PayBridge\Enums\PaymentStatus;
use AhmedTaha\PayBridge\PayBridge;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

$mockUrl = fake()->url();

beforeEach(function () use ($mockUrl) {
    Config::set('paybridge.gateways.fawrypay.integration_type', 'api');
    Config::set('paybridge.gateways.fawrypay.environment', \AhmedTaha\PayBridge\Enums\PaymentEnvironment::TESTING->value);
    Config::set('paybridge.gateways.fawrypay.callback_url', $mockUrl);
});

it('can pay using credit card api checkout', function () use ($mockUrl) {
    $faker = fake();
    $charge = new \AhmedTaha\PayBridge\Data\ChargeData($faker->uuid(), $faker->numberBetween(1000, 9000), $faker->randomElement(array_keys(config('paybridge.currencies'))));
    $customer = new \AhmedTaha\PayBridge\Data\CustomerData($faker->uuid(), $faker->name(), $faker->phoneNumber(), $faker->safeEmail(), $faker->address());
    $paymentData = new \AhmedTaha\PayBridge\Data\Payment\CreditCardData($faker->creditCardNumber(), $faker->creditCardExpirationDate->format('y'), $faker->creditCardExpirationDate->format('m'), '123');

    Http::fake([
        '*' => Http::response([
            "type" => "ChargeResponse",
            "nextAction" => [
                "type" => "THREE_D_SECURE",
                "redirectUrl" => $mockUrl
            ],
            "statusCode" => 200,
            "statusDescription" => "Operation done successfully",
            "basketPayment" => false
        ])
    ]);

    $paybridge = app(PayBridge::class);
    expect($paybridge->gateway(PaymentGateway::FawryPay)->pay($charge, $customer, $paymentData))
        ->toBeArray()
        ->toBe([
            'success' => true,
            'status' => PaymentStatus::PENDING,
            'shouldRedirect' => true,
            'redirectUrl' => $mockUrl,
        ]);
});

it('can throw errors on failed credit card api checkout', function () {
    $faker = fake();
    $charge = new \AhmedTaha\PayBridge\Data\ChargeData($faker->uuid(), $faker->numberBetween(1000, 9000), $faker->randomElement(array_keys(config('paybridge.currencies'))));
    $customer = new \AhmedTaha\PayBridge\Data\CustomerData($faker->uuid(), $faker->name(), $faker->phoneNumber(), $faker->safeEmail(), $faker->address());
    $paymentData = new \AhmedTaha\PayBridge\Data\Payment\CreditCardData($faker->creditCardNumber(), $faker->creditCardExpirationDate->format('y'), $faker->creditCardExpirationDate->format('m'), '123');

    Http::fake([
        '*' => Http::response(['statusCode' => 500, 'statusDescription' => 'Random Error'], 500)
    ]);

    $paybridge = app(PayBridge::class);
    expect(fn () => $paybridge->gateway(PaymentGateway::FawryPay)->pay($charge, $customer, $paymentData))
        ->toThrow(Exception::class);
});

it('can handle fawry credit card api checkout callback', function () {
    $faker = fake();

    $fawry = app(PayBridge::class)->gateway(PaymentGateway::FawryPay);

    $request = request()->merge([
        "type" => "ChargeResponse",
        "referenceNumber" => "963455678",
        "merchantRefNumber" => "9990d0642040",
        "orderAmount" => "20.00",
        "paymentAmount" => "20.00",
        "fawryFees" => "1.00",
        "paymentMethod" => "CARD",
        "orderStatus" => "PAID",
        "paymentTime" => 1607879720568,
        "customerMobile" => "01234567891",
        "customerMail" => "example@gmail.com",
        "customerProfileId" => "1212",
        "statusCode" => 200,
        "statusDescription" => "Operation done successfully"
    ]);
    $request->merge([
        'signature' => invade($fawry)->generateSignature($request->toArray(), [
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
        ])
    ]);

    $response = $fawry->callback($request);
    expect($response)
        ->toHaveKeys(['success', 'charge', 'customer', 'referenceNumber', 'status'])
        ->toMatchArray([
            'success' => true,
            'status' => PaymentStatus::PAID,
        ])
        ->and($response['charge'])->toBeInstanceOf(\AhmedTaha\PayBridge\Data\ChargeData::class)
        ->and($response['customer'])->toBeInstanceOf(\AhmedTaha\PayBridge\Data\CustomerData::class);
});
