<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;
use SignalHouse\SDK\Domains\Voice\SipTrunks;
use SignalHouse\SDK\Domains\Voice\SipProfiles;
use SignalHouse\SDK\Domains\Voice\ProgrammableVoiceProfiles;
use SignalHouse\SDK\Domains\Voice\Calls;
use SignalHouse\SDK\Domains\Voice\CallLogs;
use SignalHouse\SDK\Domains\Voice\GlobalVoiceSettings;
use SignalHouse\SDK\Domains\Voice\Tokens;

/**
 * Voice domain — wraps the voice-backend service (mounted under /voice).
 *
 * Sub-resources:
 *  - $sdk->voice->sipTrunks                 — SIP trunk (peer-to-peer connections to PBX/carrier)
 *  - $sdk->voice->sipProfiles               — SIP profile / endpoint (single registerable UA)
 *  - $sdk->voice->programmableVoiceProfiles — Programmable Voice Profile (route a set of numbers through Signal House)
 *  - $sdk->voice->calls                     — Outbound call origination + call log queries
 *  - $sdk->voice->callLogs                  — Account-scoped call history: list/get + presigned recording
 *  - $sdk->voice->globalVoiceSettings       — Account-wide voice defaults (accepted regions, max spend, E911)
 *  - $sdk->voice->tokens                    — Mint ephemeral SIP credentials for the browser voice SDK
 */
class Voice
{
    public SipTrunks $sipTrunks;
    public SipProfiles $sipProfiles;
    public ProgrammableVoiceProfiles $programmableVoiceProfiles;
    public Calls $calls;
    public CallLogs $callLogs;
    public GlobalVoiceSettings $globalVoiceSettings;
    public Tokens $tokens;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->sipTrunks = new SipTrunks($client, $enableAdmin);
        $this->sipProfiles = new SipProfiles($client, $enableAdmin);
        $this->programmableVoiceProfiles = new ProgrammableVoiceProfiles($client, $enableAdmin);
        $this->calls = new Calls($client, $enableAdmin);
        $this->callLogs = new CallLogs($client, $enableAdmin);
        $this->globalVoiceSettings = new GlobalVoiceSettings($client, $enableAdmin);
        $this->tokens = new Tokens($client, $enableAdmin);
    }
}
