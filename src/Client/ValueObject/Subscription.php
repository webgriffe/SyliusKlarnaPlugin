<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject;

use JsonSerializable;

final readonly class Subscription implements JsonSerializable
{
    public function __construct(
        private string $name,
        private string $interval,
        private int $intervalCount,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function getIntervalCount(): int
    {
        return $this->intervalCount;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'interval' => $this->getInterval(),
            'interval_count' => $this->getIntervalCount(),
        ];
    }
}
