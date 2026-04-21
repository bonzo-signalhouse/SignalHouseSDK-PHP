<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Campaigns
{
    private HttpClient $client;
    private bool $enableAdmin;
    public ?object $admin = null;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->client = $client;
        $this->enableAdmin = $enableAdmin;

        if ($enableAdmin) {
            $this->admin = new class($client) {
                private HttpClient $client;

                public function __construct(HttpClient $client)
                {
                    $this->client = $client;
                }

                /**
                 * Approve a campaign that is pending approval
                 */
                public function approveCampaign(string $campaignId, array $options = []): array
                {
                    $this->client->require(['campaignId' => $campaignId]);
                    $safeCampaignId = rawurlencode($campaignId);
                    return $this->client->request("/campaign/approve/{$safeCampaignId}", array_merge([
                        'method' => 'POST',
                    ], $options));
                }

                /**
                 * Reject a campaign that is pending approval
                 *
                 * @param string $campaignId The ID of the campaign to reject
                 * @param array $options Additional request options (body should include ['rejectionReason' => '...'])
                 * @return array The response from the server
                 */
                public function rejectCampaign(string $campaignId, array $options = []): array
                {
                    $this->client->require(['campaignId' => $campaignId]);
                    $safeCampaignId = rawurlencode($campaignId);
                    return $this->client->request("/campaign/reject/{$safeCampaignId}", array_merge([
                        'method' => 'POST',
                    ], $options));
                }
            };
        }
    }

    /**
     * Get a list of campaigns with optional filters
     *
     * @param array $params Filter parameters (id, brandId, subgroupId, groupId, page, limit, status)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getCampaigns(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/campaign{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a new campaign
     *
     * @param array $campaignData The campaign data (see JS SDK CreateCampaignData for fields)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createCampaign(array $campaignData, array $options = []): array
    {
        $this->client->require(['campaignData' => $campaignData]);
        return $this->client->request('/campaign', array_merge([
            'method' => 'POST',
            'body' => $campaignData,
        ], $options));
    }

    /**
     * Update an existing campaign
     *
     * @param string $campaignId The ID of the campaign
     * @param array $campaignData The data to update
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateCampaign(string $campaignId, array $campaignData, array $options = []): array
    {
        $this->client->require(['campaignId' => $campaignId]);
        $safeCampaignId = rawurlencode($campaignId);
        return $this->client->request("/campaign/{$safeCampaignId}", array_merge([
            'method' => 'PUT',
            'body' => $campaignData,
        ], $options));
    }

    /**
     * Delete a campaign (mark as EXPIRED)
     *
     * @param string $campaignId The ID of the campaign
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteCampaign(string $campaignId, array $options = []): array
    {
        $this->client->require(['campaignId' => $campaignId]);
        $safeCampaignId = rawurlencode($campaignId);
        return $this->client->request("/campaign/{$safeCampaignId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }

    /**
     * Appeal a DCA rejection for a campaign.
     *
     * @param string $campaignId The ID of the campaign
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function appealDcaRejection(string $campaignId, array $options = []): array
    {
        $this->client->require(['campaignId' => $campaignId]);
        $safeCampaignId = rawurlencode($campaignId);
        return $this->client->request("/campaign/appealDcaRejection/{$safeCampaignId}", array_merge([
            'method' => 'POST',
        ], $options));
    }

    /**
     * Nudge a connectivity partner to prioritize review of a campaign.
     *
     * @param string $campaignId The ID of the campaign
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function nudgeDcaForCampaign(string $campaignId, array $options = []): array
    {
        $this->client->require(['campaignId' => $campaignId]);
        $safeCampaignId = rawurlencode($campaignId);
        return $this->client->request("/campaign/nudge/{$safeCampaignId}", array_merge([
            'method' => 'POST',
        ], $options));
    }
}
