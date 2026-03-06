<?php

namespace Taurus\Workflow\Consumer\Taurus\Modules;

use Illuminate\Support\Facades\Mail;

class RenewalService extends ModuleService
{
    public function sendRenewalEmails(array $data)
    {
        $policies = $data['PolicyRenewal']['PoliciesExpiredInLast15Days'] ?? [];

        foreach ($policies as $agentData) {

            $email = $agentData['agentEmail'];

            Mail::send('emails.policy-renewal', [
                'data' => $agentData
            ], function ($message) use ($email) {

                $message->to($email)
                    ->subject('Flood Policy Renewal Reminder');
            });
        }
    }
}