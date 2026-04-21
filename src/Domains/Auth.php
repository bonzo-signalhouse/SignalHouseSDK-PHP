<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Auth
{
    private HttpClient $client;
    private bool $enableAdmin;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->client = $client;
        $this->enableAdmin = $enableAdmin;
    }

    /**
     * Login with email and password
     *
     * @param string $email The user's email address
     * @param string $password The user's password
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function login(string $email, string $password, array $options = []): array
    {
        return $this->client->request('/auth', array_merge([
            'method' => 'POST',
            'body' => ['email' => $email, 'password' => $password],
        ], $options));
    }

    /**
     * Reset a user's password
     *
     * @param string $userId The ID of the user
     * @param string $newPassword The new password to set
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function resetPassword(string $userId, string $newPassword, array $options = []): array
    {
        $this->client->require(['userId' => $userId, 'newPassword' => $newPassword]);
        $safeUserId = rawurlencode($userId);
        return $this->client->request("/auth/resetpassword/{$safeUserId}", array_merge([
            'method' => 'PUT',
            'body' => ['newPassword' => $newPassword],
        ], $options));
    }

    /**
     * Get token login history for a group or user
     *
     * @param array $params Filter parameters (groupId, userId, page, limit)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAuthHistory(array $params = [], array $options = []): array
    {
        $this->client->require(['groupId or userId' => $params['groupId'] ?? $params['userId'] ?? null]);
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/auth/history{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }
}
