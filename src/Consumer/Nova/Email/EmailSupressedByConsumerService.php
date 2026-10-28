<?php

namespace Taurus\Workflow\Consumer\Nova\Email;

class EmailSupressedByConsumerService
{
    /**
     * Nova has no unsubscribe support, so every recipient is allowed.
     *
     * @param  array  $emails  List of recipient email addresses.
     * @param  bool  $isPromotionalEmail  Whether the email template is promotional.
     * @return array The same emails.
     */
    public function applyEmailSupressedByConsumerFilter(array $emails, bool $isPromotionalEmail = false): array
    {
        return $emails;
    }
}
