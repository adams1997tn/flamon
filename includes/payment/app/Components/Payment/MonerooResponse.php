<?php

namespace App\Components\Payment;

use App\Service\MonerooService;

class MonerooResponse
{
    /**
     * @var MonerooService
     */
    protected $monerooService;

    public function __construct()
    {
        $this->monerooService = new MonerooService();
    }

    public function getMonerooPaymentData(array $requestData): array
    {
        return $this->monerooService->prepareIpnRequestData($requestData);
    }
}
