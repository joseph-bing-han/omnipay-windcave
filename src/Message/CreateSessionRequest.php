<?php

declare(strict_types=1);

namespace Omnipay\Windcave\Message;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\RequestInterface;

/**
 * @link https://px5.docs.apiary.io/#reference/0/sessions/create-session
 */
class CreateSessionRequest extends AbstractRequest implements RequestInterface
{
    /**
     * @throws InvalidRequestException
     */
    public function getData(): array
    {
        $data = [
            'type' => 'purchase',
            'currency' => $this->getCurrency(),
            'merchantReference' => substr((string) $this->getMerchantReference(), 0, 64),
            'storeCard' => 0,
            'callbackUrls' => $this->getCallbackUrls(),
        ];

        // Has the Money class been used to set the amount?
        if ($this->getAmount() instanceof Money) {
            // Ensure principal amount is formatted as decimal string e.g. 50.00
            $data['amount'] = (new DecimalMoneyFormatter(new ISOCurrencies()))->format($this->getAmount());
        } else {
            $data['amount'] = $this->getAmount();
        }

        // 3DS 信息校验：至少提供邮箱或电话
        $email = $this->getCardholderEmail();
        $phone = $this->getCardholderPhone();

        if ($this->getEnforce3dsContact() && !$email && !$phone) {
            throw new InvalidRequestException(
                'Windcave 3DS requires either cardholder email or phone (Visa requirement).'
            );
        }

        // 按Windcave REST会话创建字段约定写入
        if ($email || $phone) {
            $data['cardholder'] = array_filter([
                'emailAddress' => $email,
                'phoneNumber'  => $phone,
            ], static fn($v) => $v !== null && $v !== '');
        }

        return $data;
    }

    public function getEndpoint(): string
    {
        return $this->baseEndpoint() . '/sessions';
    }

    public function getHttpMethod(): string
    {
        return 'POST';
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getResponseClass(): string
    {
        return CreateSessionResponse::class;
    }
}
