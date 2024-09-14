<?php

namespace AhmedTaha\PayBridge\Data;

use AhmedTaha\PayBridge\Interfaces\DataInterface;

class CustomerData implements DataInterface
{
    public function __construct(
        protected string|int|null $id = null,
        protected ?string $name = null,
        protected ?string $phone = null,
        protected ?string $email = null,
        protected ?string $address = null,
    ) {}

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];
    }
}
