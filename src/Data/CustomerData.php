<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Interfaces\DataInterface;

class CustomerData implements DataInterface
{
    public function __construct(
        public ?string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
    ){}

    public function getData(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];
    }
}
