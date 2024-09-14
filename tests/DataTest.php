<?php

use AhmedTaha\PayBridge\Enums\PaymentMethod;

it('charge data is correctly set and transformed', function ($id, $amount, $currency) {
    $data = new \AhmedTaha\PayBridge\Data\ChargeData($id, $amount, $currency);
    expect($data)
        ->toHaveProperties(['id', 'amount', 'currency'])
        ->and($data->getData())->toHaveKeys(['id', 'amount', 'currency'])
        ->and($data->getData()['amount'])->toBeString()->toMatch('/^[0-9]*\.[0-9]{2}$/');
})->with([
    ['123', '123', 'USD'],
    [123, 13.4, null],
    [123, '123.125', 'EUR'],
]);

it('charge data is validated', function ($id, $amount, $currency) {
    new \AhmedTaha\PayBridge\Data\ChargeData($id, $amount, $currency);
})->throws(Exception::class)->with([
    ['123', '123', 'NAH'],
    [123, 'nope', 'EUR'],
]);

it('customer data is correctly set', function ($id, $name, $phone, $email, $address) {
    $data = new \AhmedTaha\PayBridge\Data\CustomerData($id, $name, $phone, $email, $address);
    expect($data)
        ->toHaveProperties(['name', 'phone', 'email', 'address'])
        ->and($data->getData())->toBe([
            'id' => $id,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
        ]);
})->with([
    ['id', 'Ahmed', '123', 'test@test.com', 'hell'],
]);

it('credit card data is correctly set', function ($card, $year, $month, $cvv) {
    $data = new \AhmedTaha\PayBridge\Data\Payment\CreditCardData($card, $year, $month, $cvv);
    expect($data)
        ->toHaveProperties(['cardNumber', 'expiryYear', 'expiryMonth', 'cvv'])
        ->and($data::METHOD)->toBe(\AhmedTaha\PayBridge\Enums\PaymentMethod::CREDIT_CARD)
        ->and($data->getData())->toBe([
            'cardNumber' => $card,
            'expiryYear' => $year,
            'expiryMonth' => $month,
            'cvv' => $cvv,
        ]);
})->with([
    ['123', '24', '05', '123'],
]);

it('mobile wallet data is correctly set', function ($phoneNumber) {
    $data = new \AhmedTaha\PayBridge\Data\Payment\MobileWalletData($phoneNumber);
    expect($data)
        ->toHaveProperties(['walletNumber'])
        ->and($data::METHOD)->toBe(\AhmedTaha\PayBridge\Enums\PaymentMethod::MOBILE_WALLET)
        ->and($data->getData())->toBe([
            'walletNumber' => $phoneNumber,
        ]);
})->with([
    ['123'],
]);

it('placeholder payment data is correctly set', function () {
    $data = new \AhmedTaha\PayBridge\Data\Payment\NoPaymentData();
    expect($data::METHOD)
        ->toBe(PaymentMethod::NONE)
        ->and($data->getData())->toBe([]);
});
