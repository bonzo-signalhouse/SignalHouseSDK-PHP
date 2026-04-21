<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Messages
{
    private HttpClient $client;
    private HttpClient $multipartClient;
    private bool $enableAdmin;

    public function __construct(HttpClient $client, HttpClient $multipartClient, bool $enableAdmin)
    {
        $this->client = $client;
        $this->multipartClient = $multipartClient;
        $this->enableAdmin = $enableAdmin;
    }

    /**
     * Get a list of messages with optional filters and pagination
     *
     * @param array $params Filter parameters (id, campaignId, brandId, subgroupId, groupId, phoneNumber, senderPhoneNumber, recipientPhoneNumber, status, direction, messageType, carrier, startDate, endDate, sortField, sortOrder, page, limit)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getMessages(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get aggregated analytics for messages with optional filters
     *
     * @param array $params Filter parameters (groupId, subgroupId, brandId, campaignId, phoneNumber, carrier, startDate, endDate)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAnalytics(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/analytics{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get detailed analytics snapshot records for charting and aggregation
     *
     * @param array $params Filter parameters (groupId, subgroupId, brandId, campaignId, phoneNumber, carrier, startDate, endDate)
     * @param array $options Additional request options
     * @return array The response from the server with an array of analytics snapshot records
     */
    public function getAnalyticsDetail(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/analytics/detail{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get aggregated DNC (Do Not Contact) opt-out analytics with optional filters
     *
     * @param array $params Filter parameters (groupId, subgroupId, brandId, campaignId, phoneNumber, carrier, startDate, endDate)
     * @param array $options Additional request options
     * @return array The response from the server with totals, byDate, byPhoneNumber, byCarrier
     */
    public function getDncAnalytics(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/dnc/analytics{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get paginated Do Not Call records with optional filters
     *
     * @param array $params Filter parameters (groupId, subgroupId, brandId, campaignId, phoneNumber, carrier, startDate, endDate, page, limit, sortField, sortOrder)
     * @param array $options Additional request options
     * @return array The response from the server with paginated DNC records
     */
    public function getDncRecords(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/dnc{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Send an SMS message
     *
     * @param string $senderPhoneNumber The phone number to send from
     * @param string|array $recipientPhoneNumbers The recipient phone number(s)
     * @param string $messageBody The message body
     * @param string|null $statusCallbackUrl Optional callback URL for status updates
     * @param bool $enableShortlink Whether to enable shortlinks
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function sendSMS(
        string $senderPhoneNumber,
        string|array $recipientPhoneNumbers,
        string $messageBody,
        ?string $statusCallbackUrl = null,
        bool $enableShortlink = false,
        array $options = []
    ): array {
        $this->client->require([
            'senderPhoneNumber' => $senderPhoneNumber,
            'recipientPhoneNumbers' => $recipientPhoneNumbers,
            'messageBody' => $messageBody,
        ]);

        $body = [
            'senderPhoneNumber' => $senderPhoneNumber,
            'recipientPhoneNumber' => $recipientPhoneNumbers,
            'messageBody' => $messageBody,
            'enableShortlink' => $enableShortlink,
        ];

        if ($statusCallbackUrl !== null) {
            $body['statusCallbackUrl'] = $statusCallbackUrl;
        }

        return $this->client->request('/message/sms', array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $options));
    }

    /**
     * Send an MMS message with optional media attachments
     *
     * @param string $senderPhoneNumber The phone number to send from
     * @param array $recipientPhoneNumbers The recipient phone numbers
     * @param string $messageBody The message body
     * @param array|null $mediaUrls Optional media URLs
     * @param string|null $statusCallbackUrl Optional callback URL
     * @param bool $enableShortlink Whether to enable shortlinks
     * @param bool $enableCompression Whether to enable image compression
     * @param array|null $images Optional image file paths
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function sendMMS(
        string $senderPhoneNumber,
        array $recipientPhoneNumbers,
        string $messageBody,
        ?array $mediaUrls = null,
        ?string $statusCallbackUrl = null,
        bool $enableShortlink = false,
        bool $enableCompression = true,
        ?array $images = null,
        array $options = []
    ): array {
        $this->client->require([
            'senderPhoneNumber' => $senderPhoneNumber,
            'recipientPhoneNumbers' => $recipientPhoneNumbers,
            'messageBody' => $messageBody,
        ]);

        $multipart = [];

        if ($images !== null) {
            foreach ($images as $image) {
                $multipart[] = [
                    'name' => 'images',
                    'contents' => is_string($image) ? fopen($image, 'r') : $image,
                ];
            }
        }

        $multipart[] = ['name' => 'senderPhoneNumber', 'contents' => $senderPhoneNumber];
        $multipart[] = ['name' => 'recipientPhoneNumber', 'contents' => json_encode($recipientPhoneNumbers)];
        $multipart[] = ['name' => 'messageBody', 'contents' => $messageBody];

        if ($mediaUrls !== null && count($mediaUrls) > 0) {
            $multipart[] = ['name' => 'mediaUrls', 'contents' => json_encode($mediaUrls)];
        }

        if ($statusCallbackUrl !== null) {
            $multipart[] = ['name' => 'statusCallbackUrl', 'contents' => $statusCallbackUrl];
        }

        $multipart[] = ['name' => 'enableShortlink', 'contents' => $enableShortlink ? 'true' : 'false'];
        $multipart[] = ['name' => 'enableCompression', 'contents' => $enableCompression ? 'true' : 'false'];

        return $this->multipartClient->request('/message/mms', array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }

    /**
     * Send a group MMS message
     *
     * @param string $senderPhoneNumber The phone number to send from
     * @param array $recipientPhoneNumbers The recipient phone numbers
     * @param string $messageBody The message body
     * @param array|null $mediaUrls Optional media URLs
     * @param string|null $statusCallbackUrl Optional callback URL
     * @param bool $enableShortlink Whether to enable shortlinks
     * @param bool $enableCompression Whether to enable image compression
     * @param array|null $images Optional image file paths
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function sendGroupMessage(
        string $senderPhoneNumber,
        array $recipientPhoneNumbers,
        string $messageBody,
        ?array $mediaUrls = null,
        ?string $statusCallbackUrl = null,
        bool $enableShortlink = false,
        bool $enableCompression = true,
        ?array $images = null,
        array $options = []
    ): array {
        $this->client->require([
            'senderPhoneNumber' => $senderPhoneNumber,
            'recipientPhoneNumbers' => $recipientPhoneNumbers,
            'messageBody' => $messageBody,
        ]);

        $multipart = [];

        if ($images !== null) {
            foreach ($images as $image) {
                $multipart[] = [
                    'name' => 'images',
                    'contents' => is_string($image) ? fopen($image, 'r') : $image,
                ];
            }
        }

        $multipart[] = ['name' => 'senderPhoneNumber', 'contents' => $senderPhoneNumber];
        $multipart[] = ['name' => 'recipientPhoneNumber', 'contents' => json_encode($recipientPhoneNumbers)];
        $multipart[] = ['name' => 'messageBody', 'contents' => $messageBody];

        if ($mediaUrls !== null && count($mediaUrls) > 0) {
            $multipart[] = ['name' => 'mediaUrls', 'contents' => json_encode($mediaUrls)];
        }

        if ($statusCallbackUrl !== null) {
            $multipart[] = ['name' => 'statusCallbackUrl', 'contents' => $statusCallbackUrl];
        }

        $multipart[] = ['name' => 'enableShortlink', 'contents' => $enableShortlink ? 'true' : 'false'];
        $multipart[] = ['name' => 'enableCompression', 'contents' => $enableCompression ? 'true' : 'false'];

        return $this->multipartClient->request('/message/groupMessage', array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }
}
