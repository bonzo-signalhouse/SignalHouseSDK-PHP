<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

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
     * @param array $params Filter parameters (id, subgroupId, groupId, page, limit, status)
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
     * @param string $brandId The ID of the brand
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
     * Transfer one or more brands to a different subgroup
     *
     * @param string $subgroupId The target subgroup ID
     * @param array $brandIds Array of brand IDs to transfer
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
     * @param string $brandId The ID of the brand
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createExternalVetting(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/brand/externalvetting/{$safeBrandId}", array_merge([
            'method' => 'POST',
        ], $options));
    }

    /**
     * Update a brand's information
     *
     * @param string $brandId The ID of the brand
     * @param array $brandData The data to update
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
     * @param string $brandId The ID of the brand
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
     * @param string $brandId The ID of the brand
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
     * @param string $brandId The ID of the brand
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
     * @param string $brandId The ID of the brand
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
