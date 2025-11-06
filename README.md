## Omnipay Windcave (Maintained fork)

This is a maintained fork of the Windcave REST API driver for Omnipay, published as `joseph-bing-han/omnipay-windcave`.  
It keeps the original PHP namespace `Omnipay\Windcave` for maximum compatibility.

Why this fork?
- Visa 3DS requirement: at least one of Cardholder Email Address or Cardholder Phone Number must be provided during authentication.
- The library enforces this rule at session creation time by requiring at least one of email or phone to be supplied.

References:
- Packagist: `https://packagist.org/`
- Windcave REST 3DS fields: `https://www.windcave.com/developer-e-commerce-api-rest#3DSecure_Fields`
- Windcave PxPay required fields: `https://www.windcave.com/developer-e-commerce-hosted-pxpay#Required_Fields`

### Install

```bash
composer require joseph-bing-han/omnipay-windcave
```

### Basic Usage (Laravel / Omnipay)

```php
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;

// 网关初始化（示例）
$gateway = Omnipay::create('Windcave');
$gateway->setUsername('your-windcave-username');   // 商户号
$gateway->setApiKey('your-windcave-api-key');      // API Key
$gateway->setTestMode(true);                       // 测试环境：uat，生产：sec

// 准备持卡人信息（推荐通过CreditCard提供Email/Phone）
$card = new CreditCard([
    'firstName' => 'John',
    'lastName'  => 'Doe',
    'number'    => '4111111111111111',
    'expiryMonth' => '12',
    'expiryYear'  => '2030',
    'cvv'         => '123',
    'email'       => 'john@example.com', // 满足3DS要求的方式之一
    // 'billingPhone' => '+64-21-000-0000', // 或者提供电话号码
]);

// 创建会话（遵循3DS要求：至少提供邮箱或电话）
$request = $gateway->createSession([
    'amount'            => '10.00',
    'currency'          => 'NZD',
    'merchantReference' => 'ORDER-123456',
    'callbackUrls'      => [
        'approved'  => 'https://example.com/pay/callback?status=approved',
        'declined'  => 'https://example.com/pay/callback?status=declined',
        'cancelled' => 'https://example.com/pay/callback?status=cancelled',
    ],
    'card' => $card,

    // 也可通过参数直接提供（将覆盖CreditCard中的值）：
    // 'cardholderEmail' => 'john@example.com',
    // 'cardholderPhone' => '+64-21-000-0000',

    // 可选：关闭强制（不建议）
    // 'enforce3dsContact' => false,
]);

$response = $request->send();
if ($response->isSuccessful()) {
    // 成功创建会话
    $sessionId   = $response->getSessionId();
    $purchaseUrl = $response->getPurchaseUrl();
    // 将用户引导至 $purchaseUrl 完成支付
} else {
    // 处理错误
    // logger('Windcave createSession failed: '.$response->getMessage());
}
```

### Enforcing 3DS Contact

- The library validates cardholder contact on createSession: either email or phone must be present.
- By default it is enforced (`enforce3dsContact = true`). You can turn it off per request by passing `enforce3dsContact => false` (not recommended).

### Notes

- Namespace remains `Omnipay\Windcave`, so existing integrations should keep working after switching the package name.
- For production, ensure you are using the `sec` environment by disabling test mode.
- Always provide at least one of email or phone to satisfy Visa 3DS.
