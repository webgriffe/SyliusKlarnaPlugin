<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPlugin\Client\ValueObject\Response;

use Webgriffe\SyliusKlarnaPlugin\Client\Enum\OrderStatus;

final readonly class OrderDetails
{
    public function __construct(
        private string $orderId,
        private string $fraudStatus,
        private OrderStatus $status,
    ) {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getFraudStatus(): string
    {
        return $this->fraudStatus;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }
}
