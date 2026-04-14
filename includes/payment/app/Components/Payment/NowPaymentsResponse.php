<?php

namespace App\Components\Payment;

use App\Service\NowPaymentsService;

class NowPaymentsResponse
{
    /**
     * @var NowPaymentsService
     */
    protected $nowPaymentsService;

    public function __construct()
    {
        $this->nowPaymentsService = new NowPaymentsService();
    }

    public function getNowPaymentsPaymentData(array $requestData): array
    {
        return $this->nowPaymentsService->prepareIpnRequestData($requestData);
    }
}

