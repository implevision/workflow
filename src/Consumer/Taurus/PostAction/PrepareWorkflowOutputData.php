<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction;

use Avatar\Infrastructure\Models\Api\v1\TbClaim;
use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Avatar\Infrastructure\Models\Api\v1\TbPotransaction;
use Avatar\Infrastructure\Models\Api\v1\TbPrdoccodedoc;
use Taurus\Workflow\Consumer\Taurus\Helper;

class PrepareWorkflowOutputData
/**
 * Class PrepareWorkflowOutputData
 *
 * This class is responsible for preparing workflow output data for processing.
 * It handles the necessary transformations and validations required
 * before the workflow output is processed.
 */
{
    /**
     * Prepares the workflow output data based on the provided payload and placeholders.
     *
     * @param  mixed  $payload  The data to be processed for the workflow output.
     * @param  array  $placeholders  An associative array of placeholders to be replaced in the workflow output content.
     * @return mixed The processed workflow output data.
     */
    public static function prepare($payload, $placeholders, $messageId)
    {
        if (array_key_exists('CompanyLogo', $placeholders)) {
            $placeholders['CompanyLogo'] = Helper::generateDataImage($placeholders['CompanyLogo']);
        }

        $isPdf = ($payload['letterEditorMode'] ?? '') === 'PDF';
        $html = '';

        try {
            if (! $isPdf) {
                $html = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($placeholders) {
                    $key = trim($matches[1]);

                    return $placeholders[$key] ?? ''; // fallback to empty if key not found. $matches[0] will have actual placeholder with {{}}
                }, $payload['emailTemplate']);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $claimDetails = TbClaim::where('ClaimId_PK', $payload['recordIdentifier'])->first();
        $effectiveDate = $claimDetails ? date('Y-m-d', strtotime($claimDetails?->Date_Of_Loss)) : null;

        $transactionDetails = TbPotransaction::where('n_potransaction_PK', $claimDetails?->n_potransaction_FK)->first();

        $policyDetails = TbPolicy::where('n_PolicyNoId_PK', $transactionDetails?->n_PolicyMaster_FK)->first();

        $templateId = TbPrdoccodedoc::where('workflow_email_template_id', $payload['actionPayload']['id'])
            ->where('n_ProductId_Fk', $policyDetails?->n_ProductId_FK)
            ->where('d_EffectiveFrom', '<=', $effectiveDate)
            ->where('d_Effectiveto', '>=', $effectiveDate)
            ->first()?->s_DocCode ?? null;

        return [
            'htmlContent' => $html,
            'templateId' => $templateId,
        ];
    }
}
