<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Enums\PaymentMethod;
use AhmedTaha\PayBridge\Interfaces\DataInterface;
use Exception;

class ChargeData implements DataInterface
{
    public readonly string|float|int $amount;
    public readonly ?string $currency;

    /**
     * @throws Exception
     */
    public function __construct(
        public readonly PaymentMethod $paymentMethod,
        public readonly string|int $id,
        string|float|int $amount,
        ?string $currency = null,
    ) {
        if (! is_numeric($amount)) {
            throw new Exception('Charge amount must be numeric');
        }
        $this->amount = number_format(floatval($amount), 2, '.', '');

        if ($currency) {
            if (! in_array($currency, array_keys(config('paybridge.currencies')))) {
                throw new Exception('Currency is invalid or not supported');
            }
            $this->currency = $currency;
        } elseif (! config('paybridge.default_currency') || ! in_array(config('paybridge.default_currency'), array_keys(config('paybridge.currencies')))) {
            throw new Exception('Currency is invalid or not supported');
        } elseif (! empty(config('paybridge.default_currency'))) {
            $this->currency = config('paybridge.default_currency');
        } else {
            $this->currency = null;
        }
    }

    public function getData(): array
    {
        return [
            'paymentMethod' => $this->paymentMethod,
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
