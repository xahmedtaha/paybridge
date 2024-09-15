<?php

use AhmedTaha\PayBridge\Enums\PaymentMethod;

it('charge data is correctly set and transformed', function ($method, $id, $amount, $currency) {
    $data = new \AhmedTaha\PayBridge\Data\ChargeData($method, $id, $amount, $currency);
    expect($data)
        ->toHaveProperties(['paymentMethod', 'id', 'amount', 'currency'])
        ->and($data->getData())->toHaveKeys(['paymentMethod', 'id', 'amount', 'currency'])
        ->and($data->getData()['amount'])->toBeString()->toMatch('/^[0-9]*\.[0-9]{2}$/');
})->with([
    [PaymentMethod::CREDIT_CARD, '123', '123', 'USD'],
    [PaymentMethod::CREDIT_CARD, 123, 13.4, null],
    [PaymentMethod::CREDIT_CARD, 123, '123.125', 'EUR'],
]);

it('charge data is validated', function ($method, $id, $amount, $currency) {
    new \AhmedTaha\PayBridge\Data\ChargeData($method, $id, $amount, $currency);
})->throws(Exception::class)->with([
    [PaymentMethod::ANY, '123', '123', 'NAH'],
    [PaymentMethod::CREDIT_CARD, 123, 'nope', 'EUR'],
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
        ->and($data->getData())->toBe([
            'walletNumber' => $phoneNumber,
        ]);
})->with([
    ['123'],
]);

it('placeholder payment data is correctly set', function () {
    $data = new \AhmedTaha\PayBridge\Data\Payment\EmptyPaymentData();
    expect($data->getData())->toBe([]);
});
