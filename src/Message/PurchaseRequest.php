<?php

declare(strict_types=1);

namespace Omnipay\Windcave\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\RequestInterface;

class PurchaseRequest extends AbstractRequest implements RequestInterface
{
    public function getData(): array
    {
        if (!$this->getParameter('card')) {
            throw new InvalidRequestException('You must pass a "card" parameter.');
        }

        $this->getCard()->validate();

        $expiryMonth = str_pad((string) $this->getCard()->getExpiryMonth(), 2, '0', STR_PAD_LEFT);
        $expiryYear = substr((string) $this->getCard()->getExpiryYear(), -2);

        return [
            'CardNumber' => $this->getCard()->getNumber(),
            'ExpiryMonth' => $expiryMonth,
            'ExpiryYear' => $expiryYear,
            'CardHolderName' => $this->getCard()->getName(),
            'Cvc2' => $this->getCard()->getCvv(),
            'MerchantReference' => substr($this->getDescription(), 0, 64),
        ];
    }

    public function getDescription(): string
    {
        return $this->getParameter('description') ?? '';
    }

    public function getEndpoint(): string
    {
        return $this->getParameter('endpoint');
    }

    public function setEndpoint(string $value): self
    {
        $this->setParameter('endpoint', $value);

        return $this;
    }

    public function getHttpMethod(): string
    {
        return 'POST';
    }

    public function getContentType(): string
    {
        return 'multipart/form-data';
    }

    protected function wantsJson(): bool
    {
        return false;
    }

    public function getResponseClass(): string
    {
        return PurchaseResponse::class;
    }
}
