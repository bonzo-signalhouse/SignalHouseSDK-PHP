<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

/**
 * 10DLC Brand registration operations.
 *
 * Brand lookup id: carrier Brand ID (B… or TFNB…), Mongo _id, or internal reference UUID.
 * Read/create responses return brandId: null for pending brands — poll GET /brand?id=<Mongo _id>.
 */
class Brands
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
     * Get a list of brands with optional filters
     *
     * @param array $params Filter parameters (id, subgroupId, groupId, page, limit, status, registrationType, currentTollFreeCarrier).
    *                      id is a brand lookup id. registrationType filters by "TEN_DLC", "TOLL_FREE", or "SHORT_CODE".
     *                      currentTollFreeCarrier (bool), with groupId or subgroupId: when true, return only the
     *                      toll-free brands a new toll-free campaign in that group can use.
     *                      Responses may include brandId: null for pending brands.
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getBrands(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/brand{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get external vetting information for a brand
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getExternalVetting(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/externalvetting/{$safeBrandId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a new brand
     *
     * @param array $brandData The brand data (see JS SDK CreateBrandData for fields)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createBrand(array $brandData, array $options = []): array
    {
        $this->client->require(['brandData' => $brandData]);
        return $this->client->request('/brand', array_merge([
            'method' => 'POST',
            'body' => $brandData,
        ], $options));
    }

    /**
     * Create a new Toll-Free (TFN) brand
     *
     * registrationType is forced to TOLL_FREE server-side; TFN-specific fields live under
     * $brandData['tollFree'] (businessRegistrationType, legalEntityType, taxId, countryCode,
     * supportPhone, and optional taxIdIssuingCountry / businessDBA / businessWebsite).
     * supportPhone must be E.164 with an optional leading '+' and no spaces, parentheses or
     * hyphens (e.g. '17025550100').
     *
     * @param array $brandData The toll-free brand data (see JS SDK CreateTollFreeBrandData for fields)
     * @param array $options Additional request options
     * @return array The response from the server. For Toll-Free, brandId is a TFNB-prefixed id when
     *               assigned; otherwise null — poll by _id.
     */
    public function createTollFreeBrand(array $brandData, array $options = []): array
    {
        $this->client->require(['brandData' => $brandData]);
        return $this->client->request('/brand/toll-free', array_merge([
            'method' => 'POST',
            'body' => $brandData,
        ], $options));
    }

    /**
     * Get a group's business verification: whether its toll-free brands need one, its status and the
     * submitted details.
     *
     * The response data holds groupId, required (whether the group's new toll-free brands need one),
     * status (NOT_STARTED, SUBMITTING, IN_REVIEW, VERIFIED or NEEDS_CHANGES), details, submittedAt,
     * reviewedAt and rejectionReason (why it needs changes, or null). When required is true and status is NOT_STARTED, submit it before creating the
     * group's first toll-free brand.
     *
     * @param string $groupId The ID of the group whose business verification to get
     * @param array $options Additional request options
     * @return array The response from the server
     * @throws \SignalHouse\SDK\Exceptions\ValidationException If groupId is missing
     */
    public function getBusinessVerification(string $groupId, array $options = []): array
    {
        $this->client->require(['groupId' => $groupId]);
        $safeGroupId = rawurlencode($groupId);
        return $this->client->request("/brand/business-verification/{$safeGroupId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Submit or update a group's business verification. Allowed when it is not started or needs changes.
     * Review usually takes 1–2 business days; the group's toll-free brands stay pending until it is verified.
     *
     * $details keys: businessName, businessType, industry, registrationType, registrationNumber, website,
     * street, optional street2, city, state (2-letter state or province code), postalCode, country ('US'
     * or 'CA') and representative (firstName, lastName, email, phone, jobTitle, jobLevel).
     * representative.phone must be E.164 with an optional leading '+' and no spaces, parentheses or
     * hyphens (e.g. '+17025550100').
     *
     * @param string $groupId The ID of the group whose business verification to submit (the caller's active group)
     * @param array $details The business verification details (see JS SDK BusinessVerificationDetails for fields)
     * @param array $options Additional request options
     * @return array The response from the server: a 202 with the business verification, now SUBMITTING
     *               (the same shape as getBusinessVerification)
     * @throws \SignalHouse\SDK\Exceptions\ValidationException If groupId is missing
     */
    public function submitBusinessVerification(string $groupId, array $details, array $options = []): array
    {
        $this->client->require(['groupId' => $groupId, 'details' => $details]);
        $safeGroupId = rawurlencode($groupId);
        return $this->client->request("/brand/business-verification/{$safeGroupId}", array_merge([
            'method' => 'PUT',
            'body' => $details,
        ], $options));
    }

    /**
     * Create a local Short Code brand for manual Signal House review.
     * The route forces SHORT_CODE and returns a synchronous 201 PENDING_APPROVAL record with an
     * SCB-prefixed brandId.
     *
     * @param array $brandData Short Code payload with a shortCode sub-array.
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createShortCodeBrand(array $brandData, array $options = []): array
    {
        $this->client->require(['brandData' => $brandData]);
        return $this->client->request('/brand/short-code', array_merge([
            'method' => 'POST',
            'body' => $brandData,
        ], $options));
    }

    /** Approve a pending Short Code brand. signalhouse_admin only. */
    public function approveShortCodeBrand(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/approve/{$safeBrandId}", array_merge(['method' => 'POST'], $options));
    }

    /** Reject a pending Short Code brand with a required customer-visible reason. signalhouse_admin only. */
    public function rejectShortCodeBrand(string $brandId, string $internalRejectionReason, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId, 'internalRejectionReason' => $internalRejectionReason]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/reject/{$safeBrandId}", array_merge([
            'method' => 'POST',
            'body' => ['internalRejectionReason' => $internalRejectionReason],
        ], $options));
    }

    /**
     * Transfer one or more brands to a different subgroup
     *
     * @param string $subgroupId The target subgroup ID
     * @param array $brandIds Brand lookup ids to transfer
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function transferBrand(string $subgroupId, array $brandIds, array $options = []): array
    {
        $this->client->require(['subgroupId' => $subgroupId, 'brandIds' => $brandIds]);
        $safeSubgroupId = rawurlencode($subgroupId);
        return $this->client->request("/brand/transfer/{$safeSubgroupId}", array_merge([
            'method' => 'POST',
            'body' => ['brandIds' => $brandIds],
        ], $options));
    }

    /**
     * Create external vetting for a brand
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param string $vettingProviderId The external vetting provider (AEGIS or WMC)
     * @param string $vettingClass The vetting class: STANDARD, ENHANCED, AUTHPLUS, or RCS (AEGIS accepts all four; WMC accepts STANDARD only)
     * @param array|null $additionalData Provider-specific additional data forwarded to TCR
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createExternalVetting(string $brandId, string $vettingProviderId, string $vettingClass, ?array $additionalData = null, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId, 'vettingProviderId' => $vettingProviderId, 'vettingClass' => $vettingClass]);
        $safeBrandId = rawurlencode($brandId);
        $body = ['vettingProviderId' => $vettingProviderId, 'vettingClass' => $vettingClass];
        if ($additionalData !== null) {
            $body['additionalData'] = $additionalData;
        }
        return $this->client->request("/brand/externalvetting/{$safeBrandId}", array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $options));
    }

    /**
     * Import an existing external vetting record for a brand
     *
     * Unlike createExternalVetting (which orders a new, billable vetting), this attaches a vetting
     * the brand already completed directly with the provider, using the provider-issued vettingId
     * and vettingToken. It is synchronous and not billable.
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param string $vettingProviderId The external vetting provider (AEGIS, WMC, CV)
     * @param string $vettingId The provider-issued vetting / transaction ID to import
     * @param string|null $vettingToken The provider-issued vetting token (required by some providers, e.g. AEGIS)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function importExternalVetting(string $brandId, string $vettingProviderId, string $vettingId, ?string $vettingToken = null, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId, 'vettingProviderId' => $vettingProviderId, 'vettingId' => $vettingId]);
        $safeBrandId = rawurlencode($brandId);
        $body = ['vettingProviderId' => $vettingProviderId, 'vettingId' => $vettingId];
        if ($vettingToken !== null) {
            $body['vettingToken'] = $vettingToken;
        }
        return $this->client->request("/brand/externalvetting/import/{$safeBrandId}", array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $options));
    }

    /**
     * Update a brand's information
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param array $brandData The data to update. For a Toll-Free brand, pass the editable Toll-Free
     *                         fields under a 'tollFree' sub-array (legalEntityType,
     *                         businessRegistrationType, taxId, countryCode, supportPhone,
     *                         taxIdIssuingCountry, businessDBA, businessWebsite); supportPhone must
     *                         be E.164 with an optional leading '+' and no spaces, parentheses or
     *                         hyphens (e.g. '17025550100'); subgroupId is immutable.
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateBrand(string $brandId, array $brandData, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/{$safeBrandId}", array_merge([
            'method' => 'PUT',
            'body' => $brandData,
        ], $options));
    }

    /**
     * Revet a brand that is in UNVERIFIED status
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function revetBrand(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/revet/{$safeBrandId}", array_merge([
            'method' => 'PUT',
        ], $options));
    }

    /**
     * Delete a brand (mark as DELETED)
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteBrand(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/{$safeBrandId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }

    /**
     * Get appeal history for a brand
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAppealHistory(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/appeal/{$safeBrandId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Submit an appeal for a brand
     *
     * @param string $brandId Brand lookup id (carrier Brand ID, Mongo _id, or internal reference)
     * @param array $appealCategories Array of appeal category strings
     * @param string $explanation The appeal explanation
     * @param string|resource $file The appeal file path or file resource
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function submitAppeal(
        string $brandId,
        array $appealCategories,
        string $explanation,
        mixed $file,
        array $options = []
    ): array {
        $this->client->require([
            'brandId' => $brandId,
            'appealCategories' => $appealCategories,
            'explanation' => $explanation,
            'file' => $file,
        ]);
        $safeBrandId = rawurlencode($brandId);

        $multipart = [];

        $multipart[] = [
            'name' => 'file',
            'contents' => is_string($file) ? fopen($file, 'r') : $file,
        ];

        $multipart[] = ['name' => 'appealCategories', 'contents' => json_encode($appealCategories)];
        $multipart[] = ['name' => 'explanation', 'contents' => $explanation];

        return $this->multipartClient->request("/brand/appeal/{$safeBrandId}", array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }
}
