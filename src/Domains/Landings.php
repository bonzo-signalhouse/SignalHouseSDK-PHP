<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Landings
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
     * Get details of a landing page by its ID
     *
     * @param string $landingId The ID of the landing page
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getLandings(string $landingId, array $options = []): array
    {
        $this->client->require(['landingId' => $landingId]);
        $safeLandingId = rawurlencode($landingId);
        return $this->client->request("/landing/{$safeLandingId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a landing page by its associated brand ID
     *
     * @param string $brandId The brand ID
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getLandingByBrandId(string $brandId, array $options = []): array
    {
        $this->client->require(['brandId' => $brandId]);
        $safeBrandId = rawurlencode($brandId);
        return $this->client->request("/landing/brand/{$safeBrandId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a new landing page with data and optional logo file
     *
     * @param array $landingData The landing page data (brandId, description, colors, etc.)
     * @param string|resource|null $file Optional logo file path or resource
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createLanding(array $landingData, mixed $file = null, array $options = []): array
    {
        $multipart = [];

        if ($file !== null) {
            $multipart[] = [
                'name' => 'file',
                'contents' => is_string($file) ? fopen($file, 'r') : $file,
            ];
        }

        foreach ($landingData as $key => $value) {
            if ($value !== null) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : (string) $value,
                ];
            }
        }

        return $this->multipartClient->request('/landing', array_merge([
            'method' => 'POST',
            'multipart' => $multipart,
        ], $options));
    }

    /**
     * Update an existing landing page
     *
     * @param string $landingId The ID of the landing page
     * @param array $landingData The data to update
     * @param string|resource|null $file Optional new logo file
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateLanding(string $landingId, array $landingData, mixed $file = null, array $options = []): array
    {
        $this->client->require(['landingId' => $landingId]);
        $safeLandingId = rawurlencode($landingId);

        $multipart = [];

        if ($file !== null) {
            $multipart[] = [
                'name' => 'file',
                'contents' => is_string($file) ? fopen($file, 'r') : $file,
            ];
        }

        foreach ($landingData as $key => $value) {
            if ($value !== null) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : (string) $value,
                ];
            }
        }

        return $this->multipartClient->request("/landing/{$safeLandingId}", array_merge([
            'method' => 'PUT',
            'multipart' => $multipart,
        ], $options));
    }

    /**
     * Delete a landing page by its ID
     *
     * @param string $landingId The ID of the landing page
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteLanding(string $landingId, array $options = []): array
    {
        $this->client->require(['landingId' => $landingId]);
        $safeLandingId = rawurlencode($landingId);
        return $this->client->request("/landing/{$safeLandingId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }
}
