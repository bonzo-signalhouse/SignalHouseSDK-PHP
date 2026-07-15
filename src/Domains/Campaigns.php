<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Campaigns
{
    private HttpClient $client;
    private HttpClient $multipartClient;
    private bool $enableAdmin;
    public ?object $admin = null;

    public function __construct(HttpClient $client, HttpClient $multipartClient, bool $enableAdmin)
    {
        $this->client = $client;
        $this->multipartClient = $multipartClient;
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
     * @param array $params Filter parameters (id, brandId, subgroupId, groupId, page, limit, status,
     *                       registrationType). registrationType filters by "TEN_DLC" or "TOLL_FREE".
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
     * Get aggregated campaign health (7-day and 30-day windows) for a campaign.
     *
     * @param string $campaignId The campaign identifier
     * @param bool|null $includeNumbers When true, include per-number health entries (default: omitted)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getCampaignHealth(string $campaignId, ?bool $includeNumbers = null, array $options = []): array
    {
        $queryString = $this->client->getQueryString([
            'campaignId' => $campaignId,
            'includeNumbers' => $includeNumbers,
        ]);

        return $this->client->request("/campaign/health{$queryString}", array_merge([
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
     * Create a new Toll-Free (TFN) campaign and submit it for Signal House review
     *
     * registrationType is forced to TOLL_FREE server-side; TFN-specific fields live under
     * $campaignData['tollFree'] (useCase, messageVolume, programSummary, exampleMessage,
     * customerCareEmail, optInImageURLs, optional optIns / multiNumberReason). phoneNumbers must
     * list 1-5 Toll-Free numbers, locked to the campaign once assigned.
     *
     * @param array $campaignData The toll-free campaign data (see JS SDK CreateTollFreeCampaignData for fields)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createTollFreeCampaign(array $campaignData, array $options = []): array
    {
        $this->client->require(['campaignData' => $campaignData]);
        return $this->client->request('/campaign/toll-free', array_merge([
            'method' => 'POST',
            'body' => $campaignData,
        ], $options));
    }

    /**
     * Upload an opt-in proof image (multipart/form-data), returning the hosted image's id and URL
     * suitable for a Toll-Free campaign's optInImageURLs.
     *
     * @param string|resource $file The image file path or file resource
     * @param array $options Additional request options
     * @return array The response from the server containing the hosted image's id and url
     */
    public function uploadOptInImage(mixed $file, array $options = []): array
    {
        $this->client->require(['file' => $file]);

        $multipart = [
            [
                'name' => 'file',
                'contents' => is_string($file) ? fopen($file, 'r') : $file,
            ],
        ];

        return $this->multipartClient->request('/campaign/opt-in-image', array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }

    /**
     * Auto-capture an opt-in proof image from a brand's generated landing page and host it
     * on-platform, returning the hosted image's id and URL suitable for a Toll-Free campaign's
     * optInImageURLs.
     *
     * @param string $brandId The ID of the brand whose generated landing page should be captured
     * @param array $options Additional request options
     * @return array The response from the server containing the hosted image's id and url
     */
    public function captureOptInImageFromLanding(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        return $this->client->request('/campaign/opt-in-image/from-landing', array_merge([
            'method' => 'POST',
            'body' => ['brandId' => $brandId],
        ], $options));
    }

    /**
     * Update an existing campaign
     *
     * @param string $campaignId The ID of the campaign
     * @param array $campaignData The data to update. For a Toll-Free campaign, pass the editable
     *                            Toll-Free fields under a 'tollFree' sub-array (useCase,
     *                            messageVolume, programSummary, exampleMessage, customerCareEmail,
     *                            optInImageURLs, optIns, multiNumberReason); phoneNumbers cannot be
     *                            changed — Toll-Free numbers are locked to their campaign.
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
