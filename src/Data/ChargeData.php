<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Interfaces\DataInterface;

class ChargeData implements DataInterface
{
    /**
     * @throws \Exception
     */
    public function __construct(
        protected string|int $id,
        protected string|float|int $amount,
        protected ?string $currency = null,
    ) {
        if (! is_numeric($this->amount)) {
            throw new \Exception('Charge amount must be numeric');
        }
        if ($this->currency) {
            if (! in_array($this->currency, array_keys(config('paybridge.currencies'))))
                throw new \Exception('Currency is invalid or not supported');
        } else if (! config('paybridge.default_currency') || ! in_array(config('paybridge.default_currency'), array_keys(config('paybridge.currencies')))) {
            throw new \Exception('Currency is invalid or not supported');
        } else $this->currency = config('paybridge.default_currency');
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format(floatval($this->amount), 2, '.', ''),
            'currency' => $this->currency,
        ];
    }
}
