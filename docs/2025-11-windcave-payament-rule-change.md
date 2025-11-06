Dear Merchant

Visa have updated their requirements for collecting cardholder data submitted for 3D Secure authentication.

Visa now require at least one (1) of the fields “Cardholder Email Address” or “Cardholder Phone Number” to be provided in the authentication request.

Windcave has supported exceptions through an ad hoc framework until now to allow for merchants who do not currently provide this data, however Visa have recently advised Windcave that we can no longer use this framework.

What You Need to Do

Update your API requests to pass one, both or any of the recommended additional cardholder data fields in your API request to Windcave.

Documentation on how to implement this can be found on our website.

REST API:

https://www.windcave.com/developer-e-commerce-api-rest#3DSecure_Fields

PxPay API:

https://www.windcave.com/developer-e-commerce-hosted-pxpay#Required_Fields

If you are unable to provide this data in your API request by 10 November 2025 Windcave will add a “Cardholder Email Address” field to the Hosted Payment Page to collect this information in order to meet Visa’s 3DS requirements.

Please contact us here if you have any questions https://www.windcave.com/contact-us

Regards

Windcave