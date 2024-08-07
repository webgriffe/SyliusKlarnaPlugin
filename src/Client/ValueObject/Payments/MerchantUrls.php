<?php

declare(strict_types=1);

namespace Webgriffe\SyliusKlarnaPaymentsPlugin\Client\ValueObject\Payments;

use JsonSerializable;

final readonly class MerchantUrls implements JsonSerializable
{
    public function __construct(
        private ?string $confirmation = null,
        private ?string $notification = null,
        private ?string $push = null,
        private ?string $authorization = null,
    ) {
    }

    public function getConfirmation(): ?string
    {
        return $this->confirmation;
    }

    public function getNotification(): ?string
    {
        return $this->notification;
    }

    public function getPush(): ?string
    {
        return $this->push;
    }

    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'confirmation' => $this->getConfirmation(),
            'notification' => $this->getNotification(),
            'push' => $this->getPush(),
            'authorization' => $this->getAuthorization(),
        ], static fn ($value) => $value !== null);
    }
}
