<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Agents
{
    private HttpClient $client;
    private bool $enableAdmin;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->client = $client;
        $this->enableAdmin = $enableAdmin;
    }

    /**
     * List the agent profiles under a group.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param array $params Filter parameters. Requires 'groupId'; also accepts 'page' and 'limit' for pagination.
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAgentProfiles(array $params = [], array $options = []): array
    {
        $this->client->require(['groupId' => $params['groupId'] ?? null]);
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/agent/profiles{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a single agent profile by id.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param string $agentProfileId The id of the agent profile to fetch
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAgentProfile(string $agentProfileId, array $options = []): array
    {
        $this->client->require(['agentProfileId' => $agentProfileId]);
        $safeId = rawurlencode($agentProfileId);
        return $this->client->request("/agent/profiles/{$safeId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a new agent profile.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param array $profileData The agent profile data. Required: groupId (string starting with 'G') and name (string).
     *                           Optional: subgroupId (string starting with 'S', or null for a group-level agent),
     *                           status ("active"|"inactive"), systemPrompt, greeting, guardrails,
     *                           voiceId (string|null), llmProvider ("bedrock"|"openai"|"anthropic"|"groq"),
     *                           llmModel (string|null), temperature (number 0-2).
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createAgentProfile(array $profileData, array $options = []): array
    {
        $this->client->require(['profileData' => $profileData]);
        return $this->client->request('/agent/profiles', array_merge([
            'method' => 'POST',
            'body' => $profileData,
        ], $options));
    }

    /**
     * Update an existing agent profile.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $id The id of the agent profile to update
     * @param array $updateData The fields to update. All optional: name, status ("active"|"inactive"),
     *                          systemPrompt, greeting, guardrails, voiceId (string|null),
     *                          llmProvider ("bedrock"|"openai"|"anthropic"|"groq"), llmModel (string|null),
     *                          temperature (number 0-2). The scope fields groupId and subgroupId are immutable.
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateAgentProfile(string $id, array $updateData, array $options = []): array
    {
        $this->client->require(['id' => $id, 'updateData' => $updateData]);
        $safeId = rawurlencode($id);
        return $this->client->request("/agent/profiles/{$safeId}", array_merge([
            'method' => 'PUT',
            'body' => $updateData,
        ], $options));
    }

    /**
     * Delete (inactivate) an agent profile by its id.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $id The id of the agent profile to delete
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteAgentProfile(string $id, array $options = []): array
    {
        $this->client->require(['id' => $id]);
        $safeId = rawurlencode($id);
        return $this->client->request("/agent/profiles/{$safeId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }

    /**
     * Send a message to an agent and get its reply — the app-native conversation
     * endpoint (webchat / SMS). Omit 'conversationId' to start a new conversation, or
     * pass one to continue an existing one. The agent's LLM tool loop runs server-side.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param array $params The message parameters. Required: 'agentProfileId', 'channel'
     *                       ("webchat"|"sms"|"voice"), and 'message'. Optional: 'conversationId'
     *                       (continue an existing conversation) and 'contactIdentifier'
     *                       (webchat visitor id or sender number).
     * @param array $options Additional request options
     * @return array The response from the server: conversationId, conversationSessionId,
     *               reply, toolInvocations, and ok.
     */
    public function sendAgentMessage(array $params, array $options = []): array
    {
        $this->client->require([
            'agentProfileId' => $params['agentProfileId'] ?? null,
            'channel' => $params['channel'] ?? null,
            'message' => $params['message'] ?? null,
        ]);
        $body = [
            'agentProfileId' => $params['agentProfileId'],
            'channel' => $params['channel'],
            'message' => $params['message'],
        ];
        if (isset($params['conversationId'])) {
            $body['conversationId'] = $params['conversationId'];
        }
        if (isset($params['contactIdentifier'])) {
            $body['contactIdentifier'] = $params['contactIdentifier'];
        }
        return $this->client->request('/agent/messages', array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $options));
    }

    /**
     * List an agent's per-channel settings.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param string $agentProfileId The agent whose channel settings to list
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAgentChannelSettings(string $agentProfileId, array $options = []): array
    {
        $this->client->require(['agentProfileId' => $agentProfileId]);
        $safeId = rawurlencode($agentProfileId);
        return $this->client->request("/agent/profiles/{$safeId}/channels", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a single per-channel setting for an agent.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param string $agentProfileId The agent the setting belongs to
     * @param string $channel The channel ("webchat"|"sms"|"voice")
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAgentChannelSetting(string $agentProfileId, string $channel, array $options = []): array
    {
        $this->client->require(['agentProfileId' => $agentProfileId, 'channel' => $channel]);
        $safeId = rawurlencode($agentProfileId);
        $safeChannel = rawurlencode($channel);
        return $this->client->request("/agent/profiles/{$safeId}/channels/{$safeChannel}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create or update (upsert) an agent's per-channel setting. One row per (agent, channel);
     * the channel is taken from the path.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $agentProfileId The agent the setting belongs to
     * @param string $channel The channel ("webchat"|"sms"|"voice")
     * @param array $settingData Fields to set: allowedTools (string[]), enabled (bool),
     *                           channelPrompt (string), greeting (string|null)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function upsertAgentChannelSetting(string $agentProfileId, string $channel, array $settingData = [], array $options = []): array
    {
        $this->client->require(['agentProfileId' => $agentProfileId, 'channel' => $channel]);
        $safeId = rawurlencode($agentProfileId);
        $safeChannel = rawurlencode($channel);
        return $this->client->request("/agent/profiles/{$safeId}/channels/{$safeChannel}", array_merge([
            'method' => 'PUT',
            'body' => $settingData,
        ], $options));
    }

    /**
     * Delete an agent's per-channel setting.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $agentProfileId The agent the setting belongs to
     * @param string $channel The channel ("webchat"|"sms"|"voice")
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteAgentChannelSetting(string $agentProfileId, string $channel, array $options = []): array
    {
        $this->client->require(['agentProfileId' => $agentProfileId, 'channel' => $channel]);
        $safeId = rawurlencode($agentProfileId);
        $safeChannel = rawurlencode($channel);
        return $this->client->request("/agent/profiles/{$safeId}/channels/{$safeChannel}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }

    /**
     * Read the tenant AI settings for a scope (group, or a subgroup within it) — brand, tone,
     * timezone, business hours, and after-hours message. Returns the scope's own row only.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param string $groupId The group (required)
     * @param string|null $subgroupId The subgroup; null for the group-level settings
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getTenantAiSettings(string $groupId, ?string $subgroupId = null, array $options = []): array
    {
        $this->client->require(['groupId' => $groupId]);
        $queryString = $this->client->getQueryString(['groupId' => $groupId, 'subgroupId' => $subgroupId]);
        return $this->client->request("/agent/tenant-settings{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create or update (upsert) the tenant AI settings for a scope. The scope is identified by
     * the query params; the body carries the settings fields.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $groupId The group (required)
     * @param string|null $subgroupId The subgroup; null for the group-level settings
     * @param array $settingsData Fields to set: brandName (string), tone (string), timezone (string),
     *                            businessHours (array of {day, closed, open, close}), afterHoursMessage (string)
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function upsertTenantAiSettings(string $groupId, ?string $subgroupId = null, array $settingsData = [], array $options = []): array
    {
        $this->client->require(['groupId' => $groupId]);
        $queryString = $this->client->getQueryString(['groupId' => $groupId, 'subgroupId' => $subgroupId]);
        return $this->client->request("/agent/tenant-settings{$queryString}", array_merge([
            'method' => 'PUT',
            'body' => $settingsData,
        ], $options));
    }

    /**
     * Delete the tenant AI settings for a scope.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $groupId The group (required)
     * @param string|null $subgroupId The subgroup; null for the group-level settings
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteTenantAiSettings(string $groupId, ?string $subgroupId = null, array $options = []): array
    {
        $this->client->require(['groupId' => $groupId]);
        $queryString = $this->client->getQueryString(['groupId' => $groupId, 'subgroupId' => $subgroupId]);
        return $this->client->request("/agent/tenant-settings{$queryString}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }

    /**
     * List the knowledge-base items an agent can answer from (RAG), scoped to a group and
     * optionally narrowed to a subgroup and/or a single agent.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param array $params Filter parameters. Requires 'groupId'; also accepts 'subgroupId',
     *                      'agentProfileId', and pagination 'page'/'limit' (max 100; server default 25).
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getKnowledgeItems(array $params = [], array $options = []): array
    {
        $this->client->require(['groupId' => $params['groupId'] ?? null]);
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/agent/knowledge{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a single knowledge item by id.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param string $knowledgeItemId The id of the knowledge item to fetch
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getKnowledgeItem(string $knowledgeItemId, array $options = []): array
    {
        $this->client->require(['knowledgeItemId' => $knowledgeItemId]);
        $safeId = rawurlencode($knowledgeItemId);
        return $this->client->request("/agent/knowledge/{$safeId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create a knowledge item. The raw text is stored; Atlas Vector Search owns the embedding.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param array $itemData The knowledge fields. Required: groupId (starts with 'G'), text.
     *                        Optional: subgroupId (starts with 'S', nullable), agentProfileId (nullable),
     *                        title, source ("manual"|"url"|"document"|"import"), enabled (bool), metadata (array|null).
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createKnowledgeItem(array $itemData, array $options = []): array
    {
        $this->client->require(['itemData' => $itemData]);
        return $this->client->request('/agent/knowledge', array_merge([
            'method' => 'POST',
            'body' => $itemData,
        ], $options));
    }

    /**
     * Update a knowledge item. The item's scope (group/subgroup/agent) is immutable.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $id The id of the knowledge item to update
     * @param array $updateData The fields to update: title, text, source, enabled, metadata
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateKnowledgeItem(string $id, array $updateData, array $options = []): array
    {
        $this->client->require(['id' => $id, 'updateData' => $updateData]);
        $safeId = rawurlencode($id);
        return $this->client->request("/agent/knowledge/{$safeId}", array_merge([
            'method' => 'PUT',
            'body' => $updateData,
        ], $options));
    }

    /**
     * Delete a knowledge item by its id.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $id The id of the knowledge item to delete
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteKnowledgeItem(string $id, array $options = []): array
    {
        $this->client->require(['id' => $id]);
        $safeId = rawurlencode($id);
        return $this->client->request("/agent/knowledge/{$safeId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }

    /**
     * List the tools defined under a group (webhook, configured builtin, mcp). webhookAuth
     * secrets are redacted in the response.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param array $params Filter parameters. Requires 'groupId'; also accepts 'subgroupId',
     *                      'agentProfileId', 'type' ("builtin"|"webhook"|"mcp"), and pagination
     *                      'page'/'limit' (max 100; server default 25).
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAgentTools(array $params = [], array $options = []): array
    {
        $this->client->require(['groupId' => $params['groupId'] ?? null]);
        $queryString = $this->client->getQueryString($params);
        return $this->client->request("/agent/tools{$queryString}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Get a single agent tool by id. webhookAuth secrets are redacted.
     *
     * Allowed roles: api, admin, developer, billing, user.
     *
     * @param string $agentToolId The id of the tool to fetch
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function getAgentTool(string $agentToolId, array $options = []): array
    {
        $this->client->require(['agentToolId' => $agentToolId]);
        $safeId = rawurlencode($agentToolId);
        return $this->client->request("/agent/tools/{$safeId}", array_merge([
            'method' => 'GET',
        ], $options));
    }

    /**
     * Create an agent tool. A webhook tool's webhookUrl must be an https URL that does not
     * target a private/internal address. webhookAuth is stored and never echoed back.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param array $toolData The tool fields. Required: groupId (starts with 'G'), name, description.
     *                        Type-specific: type ("builtin"|"webhook"|"mcp"); webhook => webhookUrl,
     *                        builtin => builtinKey, mcp => mcpServerUrl. Optional: subgroupId, agentProfileId,
     *                        parametersSchema, config, channels, enabled, and webhookAuth — which MUST be
     *                        ['headers' => [name => value], 'signingSecret' => string] (any other shape,
     *                        e.g. ['header','value'], is rejected with a 400). headers are sent verbatim on
     *                        every call (reserved/framing and x-signalhouse-* names rejected); signingSecret
     *                        makes us send X-SignalHouse-Signature: sha256=HMAC-SHA256("{X-SignalHouse-Timestamp}.{rawBody}")
     *                        so you can verify the call and reject replays. Stored, never returned.
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function createAgentTool(array $toolData, array $options = []): array
    {
        $this->client->require(['toolData' => $toolData]);
        return $this->client->request('/agent/tools', array_merge([
            'method' => 'POST',
            'body' => $toolData,
        ], $options));
    }

    /**
     * Update an agent tool. The scope is immutable; a supplied webhookUrl is SSRF-checked.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $id The id of the tool to update
     * @param array $updateData The fields to update: name, description, type, builtinKey,
     *                          parametersSchema, config, webhookUrl, webhookAuth, mcpServerUrl, channels, enabled
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function updateAgentTool(string $id, array $updateData, array $options = []): array
    {
        $this->client->require(['id' => $id, 'updateData' => $updateData]);
        $safeId = rawurlencode($id);
        return $this->client->request("/agent/tools/{$safeId}", array_merge([
            'method' => 'PUT',
            'body' => $updateData,
        ], $options));
    }

    /**
     * Delete an agent tool by its id.
     *
     * Allowed roles: api, admin, developer.
     *
     * @param string $id The id of the tool to delete
     * @param array $options Additional request options
     * @return array The response from the server
     */
    public function deleteAgentTool(string $id, array $options = []): array
    {
        $this->client->require(['id' => $id]);
        $safeId = rawurlencode($id);
        return $this->client->request("/agent/tools/{$safeId}", array_merge([
            'method' => 'DELETE',
        ], $options));
    }
}
