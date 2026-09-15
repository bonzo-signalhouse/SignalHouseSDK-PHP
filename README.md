# SignalHouse PHP SDK

Official PHP SDK for the SignalHouse platform.

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client 7.x

## Installation

```bash
composer require signalhouse/sdk
```

## Quick Start

```php
use SignalHouse\SDK\SignalHouseSDK;

$sdk = new SignalHouseSDK(
    apiKey: 'your-api-key',
    baseUrl: 'https://v2.signalhouse.io'
);

// Login
$response = $sdk->auth->login('user@example.com', 'password');

// Search for available phone numbers
$numbers = $sdk->numbers->getAvailablePhoneNumbers([
    'country' => 'US',
    'state' => 'IL',
    'npa' => '217',
]);

// Purchase a phone number
$result = $sdk->numbers->purchasePhoneNumber(
    phoneNumbers: ['2175551234'],
    subgroupId: 'SG12345678'
);

// Send an SMS
$result = $sdk->messages->sendSMS(
    senderPhoneNumber: '2175551234',
    recipientPhoneNumbers: '3125559876',
    messageBody: 'Hello from SignalHouse!'
);

// Get brands
$brands = $sdk->brands->getBrands(['groupId' => 'G12345678']);

// Check wallet balance
$wallet = $sdk->billing->getWallet('G12345678');
```

## Available Domains

| Domain | Description |
|--------|-------------|
| `$sdk->auth` | Authentication and login history |
| `$sdk->billing` | Wallet, payment methods, transactions, invoices, fees |
| `$sdk->brands` | 10DLC brand registration and management |
| `$sdk->campaigns` | 10DLC campaign management |
| `$sdk->groups` | Group management |
| `$sdk->landings` | Landing page management (with file uploads) |
| `$sdk->messages` | Send SMS/MMS/Group MMS, message logs, analytics |
| `$sdk->notifications` | Notification management |
| `$sdk->numbers` | Phone number search, purchase, assign, release, transfer |
| `$sdk->shortlinks` | URL shortener |
| `$sdk->subgroups` | Subgroup management |
| `$sdk->subscriptions` | Subscription and plan management |
| `$sdk->users` | User management and notification preferences |
| `$sdk->webhooks` | Webhook management |

## Admin Endpoints

To access admin-only endpoints, initialize with `enableAdmin: true`:

```php
$sdk = new SignalHouseSDK(
    apiKey: 'your-admin-api-key',
    baseUrl: 'https://v2.signalhouse.io',
    enableAdmin: true
);

// Admin: approve a campaign
$sdk->campaigns->admin->approveCampaign('campaign-id');

// Admin: list all groups
$sdk->groups->admin->getGroups(['page' => 1, 'limit' => 20]);
```

## Response Format

All methods return a standardized response array:

```php
// Success
[
    'success' => true,
    'data' => [...],  // Response data
    'status' => 200,  // HTTP status code
]

// Error
[
    'success' => false,
    'error' => 'Error message',
    'status' => 400,  // HTTP status code
]
```

## License

ISC - Signal House LLC


## Canada (SHGHL-3190)

The number purchase methods accept optional ISO-2 `country` (US by default, CA for Canada). For example, `numbers->purchasePhoneNumber($numbers, $subgroupId, [], 'CA')`. Toll-Free quantity purchases accept the same country. A 202 purchase response means queued; use existing status polling/webhooks. Canadian Virtual Long Codes become READY on successful provisioning without a brand or campaign. Canadian Toll-Free uses the shared approved brand/campaign records.

Estimate without sending or charging: `messages->estimateMessage($sender, $recipients, $body, [], 'MMS')`. Estimates accept 1–100 Canadian recipients, return per-recipient rates and totals in microdollars, and use the same retail rate function as dispatch. SMS is the default type; MMS/group-MMS bill one segment per recipient. Standard carrier rates are 75,500; Ice Wireless/Iristel and unknown or unpriced carriers use 81,000. Carrier cache misses or disabled lookup use the upper fallback. Estimates return pricing fields only (phoneNumber, rate, amount, fallback); carrier names are not exposed. Estimates can change when carrier information changes. Canadian sends reject US +1 numbers based on the NANP country assignment. Uploaded media must be an image under 1 MiB; existing media URL support remains available.

Available-number searches return `{ numbers, numberCount? }`. `numberCount` is omitted when the total is unknown, including Canadian geographic and US city searches; do not treat the page length as a total. These filtered searches have a 20-second discovery deadline and return HTTP 503 when incomplete. Narrow the location/NPA/NXX or retry later; an error does not mean no stock. Successful Infobip discovery samples may be reused for 15 seconds across pages. Availability is rechecked during purchase.


Brand, campaign, phone-number, message and opt-out records carry `region` (ISO-2). Treat absent, null or blank historical regions as US. Canadian Virtual Long Code messages use `channel: virtualLongCode` and nullable `brandId`/`campaignId`; consumers must tolerate these values. STOP/START consent for these numbers is scoped to the owning group and sender number. Existing US calls remain compatible.


### Geographic availability search

The availability path supports optional `city` as a case-insensitive prefix within an explicit `country` (`CA` or `US`) and `state` (province/state code). City must be supplied with both country and state. Example: `getAvailablePhoneNumbers(["country" => "CA", "state" => "QC", "city" => "Charny", "npa" => "367", "nxx" => "883", "limit" => 10])`. Canadian province, city, NPA and NXX matches are checked before the result limit; unrelated substring matches are excluded. Availability may change before purchase.
