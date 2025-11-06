<?php

declare(strict_types=1);

namespace Omnipay\Windcave\Test\Message;

use Money\Currency;
use Money\Money;
use Omnipay\Tests\TestCase;
use Omnipay\Windcave\Message\CreateSessionRequest;

class CreateSessionRequestTest extends TestCase
{
    protected CreateSessionRequest $request;

    public function setUp(): void
    {
        $this->request = new CreateSessionRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->setMoney(new Money(1000, new Currency('NZD')));
    }

    public function testEndpoint(): void
    {
        $this->request->setTestMode(true);
        $this->assertSame('https://uat.windcave.com/api/v1/sessions', $this->request->getEndpoint());
        $this->request->setTestMode(false);
        $this->assertSame('https://sec.windcave.com/api/v1/sessions', $this->request->getEndpoint());
    }

    public function testGetData(): void
    {
        $this->request->setMerchantReference('ABC123');
        $this->request->setCardholderEmail('john@example.com');

        $data = $this->request->getData();

        $this->assertEquals('purchase', $data['type']);
        $this->assertEquals('10.00', $data['amount']);
        $this->assertEquals('NZD', $data['currency']);
        $this->assertEquals('ABC123', $data['merchantReference']);
        $this->assertEquals(0, $data['storeCard']);
        $this->assertArrayHasKey('cardholder', $data);
        $this->assertEquals('john@example.com', $data['cardholder']['emailAddress']);
    }

    public function testGetDataWithPhoneOnly(): void
    {
        $this->request->setMerchantReference('XYZ999');
        $this->request->setCardholderPhone('+64-21-000-0000');

        $data = $this->request->getData();

        $this->assertArrayHasKey('cardholder', $data);
        $this->assertEquals('+64-21-000-0000', $data['cardholder']['phoneNumber']);
        $this->assertArrayNotHasKey('emailAddress', $data['cardholder']);
    }

    public function testGetDataThrowsWhenNoEmailOrPhone(): void
    {
        $this->request->setMerchantReference('NO-CONTACT');
        $this->request->setEnforce3dsContact(true);

        $this->expectException(\Omnipay\Common\Exception\InvalidRequestException::class);
        $this->expectExceptionMessage('Windcave 3DS requires either cardholder email or phone');

        $this->request->getData();
    }
}
