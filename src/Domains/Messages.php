<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

/**
 * Messages domain.
 *
 * Date-range parameters (startDate, endDate) on list and analytics methods
 * accept ISO-8601 date or timestamp strings. The server normalizes them to
 * full UTC-day boundaries: startDate -> start-of-UTC-day, endDate -> end-of-
 * UTC-day. The end day is always included; hourly resolution is not supported.
 */
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
     * @param array $params Filter parameters (id, campaignId, brandId, subgroupId, groupId, phoneNumber, senderPhoneNumber, recipientPhoneNumber, status, direction, messageType, carrier, startDate, endDate, sortField, sortOrder, page, limit). messageType accepts a single value or array (e.g. ["SMS", "MMS"]); allowed values are SMS, MMS, RCS, WHATSAPP, VIBER, P2P. When omitted, defaults to all non-P2P types.
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
     * Get filter dropdown options (subgroups, brands, campaigns, phone numbers) for the
     * Analytics page, scoped to a single group. Sourced from ClickHouse — only items with
     * at least one message are returned.
     *
     * @param array $params Filter parameters (groupId required)
     * @param array $options Additional request options
     * @return array The response with subgroups, brands, campaigns, and phoneNumbers arrays
     */
    public function getAnalyticsFilterOptions(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/analytics/filter-options{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a paginated breakdown of message metrics grouped by subgroup. Each row carries the
     * full set of sms/mms/p2p metric columns plus smsOptOuts and mmsOptOuts (sourced from
     * the DNC analytics MV — P2P has no opt-outs) so callers can apply channel toggles client-side.
     *
     * @param array $params Filter parameters (groupId required, plus optional subgroupId/brandId/campaignId/phoneNumber/carrier/startDate/endDate/page/limit/channel). limit is capped at 50 per page. channel: "tenDLC" | "p2p" | "both" (default "both") — scopes ORDER BY + row inclusion so a P2P-only caller doesn't get pages dominated by 10DLC-heavy rows with no visible activity.
     * @param array $options Additional request options
     * @return array { rows, totalCount, page, limit }
     */
    public function getAnalyticsBySubgroup(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/analytics/by-subgroup{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a paginated breakdown of failed messages grouped by error code. Each row contains
     * per-channel (sms/mms/p2p) error counts plus an enriched description.
     *
     * @param array $params Filter parameters (groupId required, plus optional subgroupId/brandId/campaignId/phoneNumber/carrier/startDate/endDate/page/limit/channel). limit is capped at 50 per page. channel: "tenDLC" | "p2p" | "both" (default "both") — scopes ORDER BY + totalCount so a P2P-only caller doesn't get pages dominated by codes with only 10DLC errors.
     * @param array $options Additional request options
     * @return array { rows, totalCount, totalErrors: { sms, mms, p2p, all }, page, limit }
     */
    public function getAnalyticsByErrorCode(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/message/analytics/by-error-code{$queryString}", array_merge([
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
     * @param bool $filterLandlinesAndInactiveNumbers Whether to filter out landline and inactive numbers before sending
     * @return array The response from the server
     */
    public function sendSMS(
        string $senderPhoneNumber,
        string|array $recipientPhoneNumbers,
        string $messageBody,
        ?string $statusCallbackUrl = null,
        bool $enableShortlink = false,
        array $options = [],
        bool $filterLandlinesAndInactiveNumbers = false
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

        if ($filterLandlinesAndInactiveNumbers) {
            $body['filterLandlinesAndInactiveNumbers'] = true;
        }

        if ($statusCallbackUrl !== null) {
            $body['statusCallbackUrl'] = $statusCallbackUrl;
        }

        return $this->client->request('/message/sms', array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $options));
    }

    /**
     * Send a P2P message via Rogue Mobile SMPP
     *
     * @param string $senderPhoneNumber The phone number to send from
     * @param string|array $recipientPhoneNumbers The phone number(s) to send to
     * @param string $messageBody The body of the P2P message
     * @param string|null $statusCallbackUrl Optional URL to receive status callbacks
     * @param bool|null $useSignalHouseShortlinks When false, SignalHouse applies no link-shortening or text-spin and your pre-spun links/content are sent verbatim (bring-your-own spinner). Defaults to true.
     * @param array $options Additional request options
     * @return array Standardized response
     */
    public function sendP2P(
        string|array $recipientPhoneNumbers,
        string $messageBody,
        ?string $statusCallbackUrl = null,
        ?bool $useSignalHouseShortlinks = null,
        array $options = []
    ): array {
        $this->client->require([
            'recipientPhoneNumbers' => $recipientPhoneNumbers,
            'messageBody' => $messageBody,
        ]);

        $body = [
            'recipientPhoneNumber' => $recipientPhoneNumbers,
            'messageBody' => $messageBody,
        ];

        if ($statusCallbackUrl !== null) {
            $body['statusCallbackUrl'] = $statusCallbackUrl;
        }

        if ($useSignalHouseShortlinks !== null) {
            $body['useSignalHouseShortlinks'] = $useSignalHouseShortlinks;
        }

        return $this->client->request('/message/p2p', array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $options));
    }

    /**
     * Get the details of a P2P batch by its ID
     *
     * @param string $batchId The ID of the P2P batch to retrieve
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getP2PBatch(string $batchId, array $options = []): array
    {
        $this->client->require(['batchId' => $batchId]);
        $safeBatchId = rawurlencode($batchId);
        return $this->client->request("/message/p2p/batches/{$safeBatchId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Fetch the latest upstream carrier delivery report for a set of messages by ID
     *
     * Useful for reconciling messages stuck in a non-terminal state. SMS and MMS messages are looked
     * up against the carrier; other message types are returned with their stored status.
     *
     * @param string[] $messageIds The message IDs to fetch carrier delivery reports for (1-100)
     * @param array $options Additional request options
     * @return array Standardized response containing an array of carrier delivery reports
     */
    public function getDeliveryReports(array $messageIds, array $options = []): array
    {
        $this->client->require([
            'messageIds' => $messageIds,
        ]);

        return $this->client->request('/message/delivery-report', array_merge([
            'method' => 'POST',
            'body' => ['messageIds' => $messageIds],
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
     * @param bool $filterLandlinesAndInactiveNumbers Whether to filter out landline and inactive numbers before sending
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
        array $options = [],
        bool $filterLandlinesAndInactiveNumbers = false
    ): array {
        $this->client->require([
            'senderPhoneNumber' => $senderPhoneNumber,
            'recipientPhoneNumbers' => $recipientPhoneNumbers,
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
        if ($filterLandlinesAndInactiveNumbers) {
            $multipart[] = ['name' => 'filterLandlinesAndInactiveNumbers', 'contents' => 'true'];
        }

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
     * @param bool $filterLandlinesAndInactiveNumbers Whether to filter out landline and inactive numbers before sending
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
        array $options = [],
        bool $filterLandlinesAndInactiveNumbers = false
    ): array {
        $this->client->require([
            'senderPhoneNumber' => $senderPhoneNumber,
            'recipientPhoneNumbers' => $recipientPhoneNumbers,
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
        if ($filterLandlinesAndInactiveNumbers) {
            $multipart[] = ['name' => 'filterLandlinesAndInactiveNumbers', 'contents' => 'true'];
        }

        return $this->multipartClient->request('/message/groupMessage', array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }

    /**
     * Look up the carrier for a phone number. Always fresh (not cached). Billed per request.
     *
     * @param string $phoneNumber The phone number to look up (10+ digits, no + prefix)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function carrierIdLookup(string $phoneNumber, array $options = []): array
    {
        $this->client->require(['phoneNumber' => $phoneNumber]);

        return $this->client->request('/message/carrier-id', array_merge([
            'method' => 'POST',
            'body' => ['phoneNumber' => $phoneNumber],
        ], $options));
    }
}
