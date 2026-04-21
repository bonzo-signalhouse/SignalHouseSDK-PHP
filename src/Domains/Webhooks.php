<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Webhooks
{
    private HttpClient $client;
    private bool $enableAdmin;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->client = $client;
        $this->enableAdmin = $enableAdmin;
    }

    /**
     * Get a list of webhooks with optional filters
     *
     * @param array $params Filter parameters (id, groupId, endpointType, phoneNumber, page, limit)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getWebhooks(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/webhook{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a new webhook
     *
     * @param array $webhookData The webhook data
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createWebhook(array $webhookData, array $options = []): array
    {
        $this->client->require(['webhookData' => $webhookData]);
        return $this->client->request('/webhook', array_merge([
            'method' => 'POST',
            'body' => $webhookData,
        ], $options));
    }

    /**
     * Update an existing webhook
     *
     * @param string $id The webhook ID
     * @param array $updateData The data to update
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateWebhook(string $id, array $updateData, array $options = []): array
    {
        $this->client->require(['id' => $id, 'updateData' => $updateData]);
        $safeId = rawurlencode($id);
        return $this->client->request("/webhook/{$safeId}", array_merge([
            'method' => 'PUT',
            'body' => $updateData,
        ], $options));
    }

    /**
     * Delete a webhook (mark as inactive)
     *
     * @param string $id The webhook ID
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteWebhook(string $id, array $options = []): array
    {
        $this->client->require(['id' => $id]);
        $safeId = rawurlencode($id);
        return $this->client->request("/webhook/{$safeId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }
}
