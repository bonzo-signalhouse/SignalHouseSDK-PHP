<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;
use SignalHouse\SDK\Domains\Voice\SipTrunks;
use SignalHouse\SDK\Domains\Voice\SipProfiles;
use SignalHouse\SDK\Domains\Voice\Calls;
use SignalHouse\SDK\Domains\Voice\Tokens;

/**
 * Voice domain — wraps the voice-backend service (mounted under /voice).
 *
 * Sub-resources:
 *  - $sdk->voice->sipTrunks   — SIP trunk (peer-to-peer connections to PBX/carrier)
 *  - $sdk->voice->sipProfiles — SIP profile / endpoint (single registerable UA)
 *  - $sdk->voice->calls       — Outbound call origination + call log queries
 *  - $sdk->voice->tokens      — Mint ephemeral SIP credentials for the browser voice SDK
 */
class Voice
{
    public SipTrunks $sipTrunks;
    public SipProfiles $sipProfiles;
    public Calls $calls;
    public Tokens $tokens;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->sipTrunks = new SipTrunks($client, $enableAdmin);
        $this->sipProfiles = new SipProfiles($client, $enableAdmin);
        $this->calls = new Calls($client, $enableAdmin);
        $this->tokens = new Tokens($client, $enableAdmin);
    }
}
