<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Groups
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
                 * Get a list of all groups with optional pagination
                 */
                public function getGroups(array $params = [], array $options = []): array
                {
                    $queryString = $this->client->getQueryString($params);
                    return $this->client->request("/group{$queryString}", array_merge([
                        'method' => 'GET',
                    ], $options));
                }

                /**
                 * Create a new group
                 */
                public function createGroup(array $groupData, array $options = []): array
                {
                    $this->client->require(['groupData' => $groupData]);
                    return $this->client->request('/group', array_merge([
                        'method' => 'POST',
                        'body' => $groupData,
                    ], $options));
                }

                /**
                 * Delete a group
                 */
                public function deleteGroup(string $groupId, array $options = []): array
                {
                    $this->client->require(['groupId' => $groupId]);
                    $safeGroupId = rawurlencode($groupId);
                    return $this->client->request("/group/{$safeGroupId}", array_merge([
                        'method' => 'DELETE',
                    ], $options));
                }
            };
        }
    }

    /**
     * Get details of a group by its ID
     *
     * @param string $id The ID of the group
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getGroup(string $id, array $options = []): array
    {
        $queryString = $this->client->getQueryString(['id' => $id]);
        return $this->client->request("/group{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Update a group with the specified data
     *
     * @param string $id The ID of the group
     * @param array $groupData The data to update
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateGroup(string $id, array $groupData, array $options = []): array
    {
        $this->client->require(['id' => $id, 'groupData' => $groupData]);
        $safeId = rawurlencode($id);
        return $this->client->request("/group/{$safeId}", array_merge([
            'method' => 'PUT',
            'body' => $groupData,
        ], $options));
    }
}
