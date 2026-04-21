<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Subgroups
{
    private HttpClient $client;
    private bool $enableAdmin;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->client = $client;
        $this->enableAdmin = $enableAdmin;
    }

    /**
     * Get a list of subgroups with optional filters
     *
     * @param array $params Filter parameters (id, groupId, page, limit)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getSubgroups(array $params = [], array $options = []): array
    {
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/subgroup{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a new subgroup
     *
     * @param array $subgroupData The subgroup data (groupId, subgroupName, contact info, address, etc.)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createSubgroup(array $subgroupData, array $options = []): array
    {
        $this->client->require(['subgroupData' => $subgroupData]);
        return $this->client->request('/subgroup', array_merge([
            'method' => 'POST',
            'body' => $subgroupData,
        ], $options));
    }

    /**
     * Update an existing subgroup
     *
     * @param string $id The subgroup ID
     * @param array $updateData The data to update
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateSubgroup(string $id, array $updateData, array $options = []): array
    {
        $this->client->require(['id' => $id, 'updateData' => $updateData]);
        $safeId = rawurlencode($id);
        return $this->client->request("/subgroup/{$safeId}", array_merge([
            'method' => 'PUT',
            'body' => $updateData,
        ], $options));
    }

    /**
     * Delete a subgroup (mark as inactive)
     *
     * @param string $id The subgroup ID
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteSubgroup(string $id, array $options = []): array
    {
        $this->client->require(['id' => $id]);
        $safeId = rawurlencode($id);
        return $this->client->request("/subgroup/{$safeId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }
}
