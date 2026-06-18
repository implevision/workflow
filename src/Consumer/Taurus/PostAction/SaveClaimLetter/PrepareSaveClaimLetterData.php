<?php

namespace Taurus\Workflow\Consumer\Taurus\PostAction\SaveClaimLetter;

use Avatar\Infrastructure\Models\Api\v1\TbClaim;
use Avatar\Infrastructure\Models\Api\v1\TbPolicy;
use Avatar\Infrastructure\Models\Api\v1\TbPotransaction;
use Avatar\Infrastructure\Models\Api\v1\TbPrdoccodedoc;
use Avatar\Infrastructure\Models\Api\v1\TbPrdoclist;
use Taurus\Workflow\Consumer\Taurus\Helper;


/**
 * Class PrepareSaveClaimLetterData
 *
 * This class is responsible for preparing save claim letter data for processing.
 * It handles the necessary transformations and validations required
 * before the save claim letter data is processed.
 */
class PrepareSaveClaimLetterData
{
    /**
     * Prepares the save claim letter data based on the provided payload and placeholders.
     *
     * @param  mixed  $payload  The data to be processed for the save claim letter.
     * @param  array  $placeholders  An associative array of placeholders to be replaced in the save claim letter content.
     * @return mixed The processed save claim letter data.
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

        $claimDocCode = TbPrdoclist::where('workflow_template_id', $payload['actionPayload']['id'])->first()?->s_PRFormID;

        $claimLetterTemplateId = TbPrdoccodedoc::where('s_DocCode', $claimDocCode)
            ->where('n_ProductId_Fk', $policyDetails?->n_ProductId_FK)
            ->where('d_EffectiveFrom', '<=', $effectiveDate)
            ->where('d_Effectiveto', '>=', $effectiveDate)
            ->first()?->s_DocCode ?? null;

        return [
            'htmlContent' => $html,
            'claimLetterTemplateId' => $claimLetterTemplateId,
        ];
    }
}
