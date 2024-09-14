<?php

namespace AhmedTaha\PayBridge;

use AhmedTaha\PayBridge\Enums\PaymentEnvironment;
use AhmedTaha\PayBridge\Enums\PaymentGateway;
use AhmedTaha\PayBridge\Gateways\AbstractGateway;
use AhmedTaha\PayBridge\Gateways\FawryPayGateway;

class PayBridge {
    // Used to bypass config file values for dynamic purposes
    protected ?PaymentEnvironment $environment = null;
    protected ?array $credentials = null;


    public function gateway(PaymentGateway $gateway): AbstractGateway
    {
        return app($gateway->value, ['credentials' => $this->credentials, 'environment' => $this->environment]);
    }

    public function setPaymentEnvironment(PaymentEnvironment $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    public function setCredentials(array $credentials): self
    {
        $this->credentials = $credentials;
        return $this;
    }
}
