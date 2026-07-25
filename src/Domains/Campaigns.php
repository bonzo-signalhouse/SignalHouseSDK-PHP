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

                /**
                 * Transition a Short Code campaign's review status (SHGHL-2225). A customer-visible
                 * reason is required for both rejection states ("REJECTED" = Signal House Rejected,
                 * "DCA_REJECTED" = Rejected). Fulfillment and carrier submission are separate staff operations.
                 *
                 * @param string $campaignId The ID of the campaign to transition
                 * @param string $status The target status: "PENDING_REVIEW", "REJECTED",
                 *                      "PENDING_CREATION", "DCA_REJECTED", or "ACTIVE"; use
                 *                      submitShortCodeCampaignToCarrier for "PENDING_DCA_APPROVAL"
                 * @param string|null $rejectionReason Required for "REJECTED"/"DCA_REJECTED" (10-1024 characters)
                 * @param array $options Additional request options
                 * @return array The updated campaign
                 */
                public function updateShortCodeCampaignStatus(string $campaignId, string $status, ?string $rejectionReason = null, array $options = []): array
                {
                    $this->client->require(['campaignId' => $campaignId, 'status' => $status]);
                    $safeCampaignId = rawurlencode($campaignId);
                    return $this->client->request("/campaign/short-code/{$safeCampaignId}/status", array_merge([
                        'method' => 'PUT',
                        'body' => ['status' => $status, 'rejectionReason' => $rejectionReason],
                    ], $options));
                }

                /** Fulfill a campaign-bound Signal House Short Code request. */
                public function fulfillShortCodeCampaign(string $campaignId, string $actualCode, ?string $internalNotes = null, array $options = []): array
                {
                    $this->client->require(['campaignId' => $campaignId, 'actualCode' => $actualCode]);
                    $safeCampaignId = rawurlencode($campaignId);
                    return $this->client->request("/campaign/short-code/{$safeCampaignId}/fulfill", array_merge([
                        'method' => 'POST', 'body' => ['actualCode' => $actualCode, 'internalNotes' => $internalNotes],
                    ], $options));
                }

                /** Submit an internally approved Short Code campaign to carrier review. */
                public function submitShortCodeCampaignToCarrier(string $campaignId, array $options = []): array
                {
                    $this->client->require(['campaignId' => $campaignId]);
                    $safeCampaignId = rawurlencode($campaignId);
                    return $this->client->request("/campaign/short-code/{$safeCampaignId}/submit-to-carrier", array_merge(['method' => 'POST'], $options));
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
     * Create a new Short Code campaign and submit it for Signal House review (SHGHL-2225).
     *
    * Requires an approved (VERIFIED) Short Code brand. `shortCode.optInUrl` is optional; Signal House
    * uses the brand's `optInLink` when omitted and captures the screenshot asynchronously, retrying up to three times. Request or register the
    * campaign's Short Code separately through `numbers->requestShortCodeAcquisition()` after creation.
     *
     * @param array $campaignData The Short Code campaign data: brandId, privacyPolicyLink,
     *                            termsAndConditionsLink, optinMessage, optoutMessage, helpMessage,
     *                            sample1-3, autoRenewal, tag, and a 'shortCode' sub-array
     *                            (useCases, optInMethods, optInMethodDescriptions,
     *                            messageFrequency, pricingTier, adultContent, doubleOptInMessage,
    *                            programSummary, optInConfirmationMessage, optInUrl).
     * @param array $options Additional request options
     * @return array The response from the server containing the created campaign
     */
    public function createShortCodeCampaign(array $campaignData, array $options = []): array
    {
        $this->client->require(['campaignData' => $campaignData]);

        $multipart = [
            [
                'name' => 'campaignData',
                'contents' => json_encode($campaignData),
            ],
        ];
        return $this->multipartClient->request('/campaign/short-code', array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }

    /**
     * Update a Short Code campaign's editable fields (SHGHL-2225). Only permitted while the
     * campaign is in Signal House Review or Signal House Rejected status; the number source
     * cannot be changed once submitted.
     *
     * @param string $campaignId The ID of the campaign to update
     * @param array $updateData The fields to update (top-level campaign fields plus an optional
     *                          'shortCode' array of editable Short Code fields)
     * @param array $options Additional request options
     * @return array The response from the server containing the updated campaign
     */
    public function updateShortCodeCampaign(string $campaignId, array $updateData, array $options = []): array
    {
        $this->client->require(['campaignId' => $campaignId]);
        $safeCampaignId = rawurlencode($campaignId);
        return $this->client->request("/campaign/short-code/{$safeCampaignId}", array_merge([
            'method' => 'PUT',
            'body' => $updateData,
        ], $options));
    }

    /**
     * Cancel a Short Code campaign (SHGHL-2225). Customers may cancel only while the campaign is
     * in Signal House Review or Signal House Rejected status; Signal House staff may cancel from
     * any non-terminal status. Persists as "EXPIRED" (displayed as "Cancelled"). A real Registry
     * lease (external or an already-fulfilled Signal House request) is never auto-released — see
     * SHGHL-2228 for offboarding.
     *
     * @param string $campaignId The ID of the campaign to cancel
     * @param array $options Additional request options
     * @return array The response from the server containing the cancelled campaign
     */
    public function cancelShortCodeCampaign(string $campaignId, array $options = []): array
    {
        $this->client->require(['campaignId' => $campaignId]);
        $safeCampaignId = rawurlencode($campaignId);
        return $this->client->request("/campaign/short-code/{$safeCampaignId}/cancel", array_merge([
            'method' => 'POST',
        ], $options));
    }

    /**
    * Download a private Short Code external lease receipt. Opt-in screenshots are public at the
    * shortCode.screenshotUrl returned on the campaign.
     *
    * @param string $artifactId The receipt identifier from numberSource.externalLease.receiptArtifactId
     * @param array $options Additional request options
     * @return array The response from the server containing the raw file bytes
     */
    public function readCampaignArtifact(string $artifactId, array $options = []): array
    {
        $this->client->require(['artifactId' => $artifactId]);
        $safeArtifactId = rawurlencode($artifactId);
        return $this->client->request("/campaign/short-code/artifact/{$safeArtifactId}", array_merge([
            'method' => 'GET',
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
