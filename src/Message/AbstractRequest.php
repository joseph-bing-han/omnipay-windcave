<?php

declare(strict_types=1);

namespace Omnipay\Windcave\Message;

use GuzzleHttp\Psr7\Response;
use Money\Money;
use Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;
use Omnipay\Common\Message\ResponseInterface;

/**
 * @link https://www.windcave.com.au/rest-docs/index.html
 */
abstract class AbstractRequest extends CommonAbstractRequest
{
    protected string $endpoint = 'https://{{environment}}.windcave.com/api/v1';

    abstract public function getEndpoint(): string;

    abstract public function getResponseClass(): string;

    protected function baseEndpoint(): string
    {
        return str_replace('{{environment}}', $this->getTestMode() ? 'uat' : 'sec', $this->endpoint);
    }

    protected function wantsJson(): bool
    {
        return true;
    }

    /**
     * Get API publishable key
     */
    public function getApiKey(): ?string
    {
        return $this->getParameter('apiKey');
    }

    /**
     * Set API publishable key
     */
    public function setApiKey(string $value): self
    {
        return $this->setParameter('apiKey', $value);
    }

    /**
     * Get Callback URLs associative array (approved, declined, cancelled)
     */
    public function getCallbackUrls(): mixed
    {
        return $this->getParameter('callbackUrls');
    }

    /**
     * Set Callback URLs associative array (approved, declined, cancelled)
     */
    public function setCallbackUrls(mixed $value): self
    {
        return $this->setParameter('callbackUrls', $value);
    }

    /**
     * Get Merchant
     */
    public function getUsername(): string
    {
        return $this->getParameter('username');
    }

    /**
     * Set Merchant
     */
    public function setUsername(string $value): self
    {
        return $this->setParameter('username', $value);
    }

    public function getAmount(): string|Money|null
    {
        return $this->getParameter('amount');
    }

    /**
     * Retaining the original method signature
     * @param string|Money $value
     * @return self
     */
    public function setAmount($value): self
    {
        return $this->setParameter('amount', $value);
    }

    public function getCurrency(): ?string
    {
        return $this->getParameter('currency');
    }

    /**
     * Retaining the original method signature
     * @param string $value
     * @return self
     */
    public function setCurrency($value): self
    {
        return $this->setParameter('currency', $value);
    }

    public function getMerchantReference(): string
    {
        return $this->getParameter('merchantReference');
    }

    public function setMerchantReference(string $value): self
    {
        return $this->setParameter('merchantReference', $value);
    }

    /**
     * 获取持卡人邮箱（优先使用参数，其次尝试从CreditCard读取）
     */
    public function getCardholderEmail(): ?string
    {
        $email = $this->getParameter('cardholderEmail');
        if ($email !== null && $email !== '') {
            return $email;
        }

        $card = $this->getCard();
        return $card ? $card->getEmail() : null;
    }

    public function setCardholderEmail(string $value): self
    {
        return $this->setParameter('cardholderEmail', $value);
    }

    /**
     * 获取持卡人电话（优先使用参数，其次尝试从CreditCard读取）
     */
    public function getCardholderPhone(): ?string
    {
        $phone = $this->getParameter('cardholderPhone');
        if ($phone !== null && $phone !== '') {
            return $phone;
        }

        $card = $this->getCard();
        if ($card) {
            // 优先billing phone，回退到通用phone
            $billingPhone = method_exists($card, 'getBillingPhone') ? $card->getBillingPhone() : null;
            return $billingPhone ?: (method_exists($card, 'getPhone') ? $card->getPhone() : null);
        }

        return null;
    }

    public function setCardholderPhone(string $value): self
    {
        return $this->setParameter('cardholderPhone', $value);
    }

    /**
     * 是否强制要求提供cardholder email或phone（二选一）
     */
    public function getEnforce3dsContact(): bool
    {
        $value = $this->getParameter('enforce3dsContact');
        return $value === null ? true : (bool) $value;
    }

    public function setEnforce3dsContact(bool $value): self
    {
        return $this->setParameter('enforce3dsContact', $value);
    }

    abstract public function getContentType(): ?string;

    public function setContentType(string $value): self
    {
        return $this->setParameter('contentType', $value);
    }

    /**
     * Get HTTP method
     */
    public function getHttpMethod(): string
    {
        return 'GET';
    }

    /**
     * Get request headers
     */
    public function getRequestHeaders(): array
    {
        // common headers
        $headers = [
            'Content-Type' => $this->getContentType(), 
        ];

        if ($this->wantsJson()) {
            $headers['Accept'] = 'application/json';
        }

        return $headers;
    }

    /**
     * Send data request
     */
    public function sendData(mixed $data): ResponseInterface
    {
        $username = $this->getUsername();
        $apiKey = $this->getApiKey();

        $headers = $this->getRequestHeaders();
        $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $apiKey);

        // 编码请求体：JSON 或 x-www-form-urlencoded（支持数组编码）
        if ($this->wantsJson()) {
            $body = json_encode($data) ?: null;
        } else {
            $body = is_array($data) ? http_build_query($data) : $data;
            $body = $body !== '' ? $body : null;
        }

        $httpResponse = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            $headers,
            $body
        );

        $responseClass = $this->getResponseClass();

        $responseData = $httpResponse->getBody()->getContents();
        $statusCode = $httpResponse->getStatusCode();

        if ($this->wantsJson()) {
            $responseData = json_decode($responseData, true);
        }

        $response = new $responseClass($this, $responseData);

        // save additional info
        /** @var AbstractResponse $response */
        $response->setHttpResponseCode((string) $statusCode);
        $response->setHeaders($response->getHeaders());

        $this->response = $response;

        return $this->response;
    }
}
