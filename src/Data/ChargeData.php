<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Interfaces\DataInterface;

class ChargeData implements DataInterface
{
    public mixed $paymentMethod;

    /**
     * @throws \Exception
     */
    public function __construct(
        public string|int $id,
        public string|float|int $amount,
    ) {
        if (!is_numeric($this->amount)) throw new \Exception('Charge amount must be numeric');
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format(floatval($this->amount), 2, '.', ''),
        ];
    }
}
