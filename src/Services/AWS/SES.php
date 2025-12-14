<?php

namespace Taurus\Workflow\Services\AWS;

use Aws\Exception\AwsException;
use Aws\SesV2\SesV2Client;
use Taurus\Workflow\Events\JobWorkflowUpdatedEvent;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class SES
{
    public static function extractPlaceholders($html)
    {
        preg_match_all('/{{\s*(.*?)\s*}}/', $html, $matches);

        return $matches[1]; // Return only the extracted placeholders
    }

    public static function getSesClient()
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        if (! $awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        $awsConfig = [
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            'region' => $awsRegion,
            'version' => 'latest',
        ];

        return new SesV2Client($awsConfig);
    }

    public static function createRequest($from, $subject, $emailTemplate, $payload, $plainEmailTemplate, $jobWorkflowId, $replyTo = [], $configurationSetName = '', $tenant = '')
    {
        $isRequireBulkEmailRequest = count($payload) > 1 ? true : false;
        try {
            if ($isRequireBulkEmailRequest) {
                $messageId = self::sendBulkEmail($from, $subject, $emailTemplate, $payload, $plainEmailTemplate, $jobWorkflowId, $replyTo, $configurationSetName, $tenant);
            } else {
                $messageId = self::sendEmail($from, $subject, $emailTemplate, last($payload), $plainEmailTemplate, $jobWorkflowId, $replyTo, $configurationSetName, $tenant);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error sending email: '.$e->getMessage());
        }

        return $messageId;
    }

    public static function sendBulkEmail($from, $subject, $htmlContent, $payload, $textContent = '', $jobWorkflowId = 0, $replyTo = [], $configurationSetName = '', $tenant = '')
    {
        try {
            $sesClient = self::getSesClient();
        } catch (\Exception $e) {
            throw new \Exception('Error creating SES client: '.$e->getMessage());
        }

        // SES dose not support if any placeholder is missing in the email template.
        // So we need to fill the missing placeholders with empty string
        $placeHolders = self::extractPlaceholders($htmlContent);
        $placeHolders = is_array($placeHolders) ? $placeHolders : [];
        $placeHolders = array_fill_keys($placeHolders, '');

        $bulkEmailEntries = [];
        foreach ($payload as $item) {
            $recipient = $item['email'];
            unset($item['email']);
            if (empty($item['email']) || count($item) < count($placeHolders)) {
                \Log::info('WORKFLOW - Skipping email due to missing placeholders', $item);

                continue;
            }

            \Log::info('WORKFLOW - Sending email to: ', (array) $recipient);

            $item = array_map(function ($value) {
                return empty($value) ? '' : $value;
            }, $item);

            $bulkEmailEntries[] = [
                'Destination' => [
                    'ToAddresses' => (array) $item['email'],
                ],
                'ReplacementEmailContent' => [
                    'ReplacementTemplate' => [
                        'ReplacementTemplateData' => json_encode(array_replace($placeHolders, $item)),
                    ],
                ],
            ];
        }

        // $attachments = $payload['attachments'];
        // \Log::info('Attachments ***', $attachments);

        try {

            $bulkEmailPayload = [
                ...(! empty($configurationSetName) ? ['ConfigurationSetName' => $configurationSetName] : []),
                'FromEmailAddress' => $from,
                'DefaultContent' => [
                    'Template' => [
                        'TemplateContent' => [
                            'Html' => $htmlContent,
                            'Subject' => $subject,
                            'Text' => $textContent ?: strip_tags($htmlContent),
                        ],
                        'TemplateData' => '{}',
                        // 'Attachments' => self::processAttachment($attachments),
                    ],
                ],
                'BulkEmailEntries' => $bulkEmailEntries,
                ...[count($replyTo) ? ["ReplyToAddresses => $replyTo"] : []],
                ...[! empty($tenant) ? ["Tenant => $tenant"] : []],
            ];

            $response = $sesClient->sendBulkEmail($bulkEmailPayload);

            if ($jobWorkflowId) {
                self::updateStat($jobWorkflowId, count($payload));
            }

            $response = $response['BulkEmailEntryResults'][0];

            if ($response['Status'] !== 'SUCCESS') {
                \Log::error('WORKFLOW - Error sending SES Bulk Email ', $response);

                return false;
            }

            return $response['MessageId'];
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function sendEmail($from, $subject, $htmlContent, $payload, $textContent = '', $jobWorkflowId = 0, $replyTo = [], $configurationSetName = '', $tenant = '')
    {
        try {
            $sesClient = self::getSesClient();
        } catch (\Exception $e) {
            throw new \Exception('Error creating SES client: '.$e->getMessage());
        }

        // SES dose not support if any placeholder is missing in the email template.
        // So we need to fill the missing placeholders with empty string
        $placeHolders = self::extractPlaceholders($htmlContent);
        $placeHolders = is_array($placeHolders) ? $placeHolders : [];
        $placeHolders = array_fill_keys($placeHolders, '');

        if (empty($payload['email']) || count($payload) < count($placeHolders)) {
            \Log::info('WORKFLOW - Skipping email due to missing placeholders', $payload);

            throw new \Exception('Skipping email due to missing placeholders');
        }

        \Log::info('WORKFLOW - Sending email to: ', (array) $payload['email']);

        $recipient = $payload['email'];
        unset($payload['email']);

        $payload = array_map(function ($value) {
            return empty($value) ? '' : $value;
        }, $payload);

        // REPLACE FROM NAME
        preg_match_all('/{{\s*(.*?)\s*}}/', $from, $fromPlaceholderMatches);

        if (is_array($fromPlaceholderMatches) && ! empty($fromPlaceholderMatches[1])) {
            foreach ($fromPlaceholderMatches[1] as $placeholder) {
                $placeholderValue = $payload[$placeholder] ?? '';
                $from = str_replace('{{'.$placeholder.'}}', $placeholderValue, $from);
            }
        }

        try {
            $response = $sesClient->sendEmail([
                ...(! empty($configurationSetName) ? ['ConfigurationSetName' => $configurationSetName] : []),
                'Destination' => [
                    'ToAddresses' => (array) $recipient,
                ],
                'Content' => [
                    'Template' => [
                        'TemplateContent' => [
                            'Html' => $htmlContent,
                            'Subject' => $subject,
                            'Text' => $textContent ?: strip_tags($htmlContent),
                        ],
                        'TemplateData' => json_encode(array_replace($placeHolders, $payload)),
                        'Attachments' => self::processAttachment($attachments),
                    ],
                ],
                'FromEmailAddress' => $from,
                ...($replyTo ? ['ReplyToAddresses' => (array) $replyTo] : []),
                ...[! empty($tenant) ? ['Tenant' => $tenant] : []],
            ]);

            if ($jobWorkflowId) {
                self::updateStat($jobWorkflowId, count($payload));
            }

            return $response['MessageId'] ?? 0;
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    private static function updateStat($jobWorkflowId, $processedRecord)
    {
        $jobWorkflowRepo = app(JobWorkflowRepository::class);

        $jobWorkflowInfo = $jobWorkflowRepo->getInfo($jobWorkflowId);
        $countOfProcessedRecord = $jobWorkflowInfo['total_no_of_records_executed'] + $processedRecord;
        $status = $countOfProcessedRecord == $jobWorkflowInfo['total_no_of_records_to_execute'] ? 'COMPLETED' : 'IN_PROGRESS';
        $payload = [
            'total_no_of_records_executed' => $countOfProcessedRecord,
            'status' => $status,
        ];

        event(new JobWorkflowUpdatedEvent($jobWorkflowId, $payload));
    }

    public static function getEmailTemplate($templateName)
    {
        try {
            $sesClient = self::getSesClient();
        } catch (\Exception $e) {
            throw new \Exception('Error creating SES client: '.$e->getMessage());
        }

        try {
            $result = $sesClient->getEmailTemplate([
                'TemplateName' => $templateName,
            ]);

            // The response includes TemplateContent (Html, Subject, Text) and TemplateName
            return $result->toArray() ?? [];
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function processAttachment($attachments): array
    {
        if (empty($attachments) || ! is_array($attachments)) {
            return [];
        }

        $processed = [];

        foreach ($attachments as $file) {
            /*if (!isset($file['path']) || !file_exists($file['path'])) {
                continue;
            }*/

            $filePath = $file['path'];
            $fileName = $file['name'] ?? basename($filePath);

            if (strlen($fileName) > 255) {
                $fileName = substr($fileName, -255);
            }

            $rawContent = file_get_contents($filePath);

            $processed[] = [
                'ContentDescription' => $fileName,
                'ContentDisposition' => 'ATTACHMENT',
                // "ContentId"                => uniqid("cid_"),
                'ContentTransferEncoding' => 'BASE64',
                // "ContentType"              => "application/png",
                'FileName' => $fileName,
                'RawContent' => base64_encode($rawContent),
            ];
        }

        return $processed;
    }
}
