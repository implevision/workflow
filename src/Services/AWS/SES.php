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

    public static function createRequest($from, $subject, $emailTemplate, $payload, $plainEmailTemplate, $jobWorkflowId)
    {
        $isRequireBulkEmailRequest = count($payload) > 1 ? true : false;
        try {
            if ($isRequireBulkEmailRequest) {
                $messageId = self::sendBulkEmail($from, $subject, $emailTemplate, $payload, $plainEmailTemplate, $jobWorkflowId);
            } else {
                $messageId = self::sendEmail($from, $subject, $emailTemplate, $payload[0], $plainEmailTemplate, $jobWorkflowId);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error sending email: ' . $e->getMessage());
        }

        return $messageId;
    }

    public static function sendBulkEmail($from, $subject, $htmlContent, $payload, $textContent = '', $jobWorkflowId = 0)
    {
        try {
            $sesClient = self::getSesClient();
        } catch (\Exception $e) {
            throw new \Exception('Error creating SES client: ' . $e->getMessage());
        }

        // SES dose not support if any placeholder is missing in the email template.
        // So we need to fill the missing placeholders with empty string
        $placeHolders = self::extractPlaceholders($htmlContent);
        $placeHolders = is_array($placeHolders) ? $placeHolders : [];
        $placeHolders = array_fill_keys($placeHolders, '');

        $bulkEmailEntries = [];
        foreach ($payload as $item) {
            if (empty($item['email']) || count($item) < count($placeHolders)) {
                \Log::info('WORKFLOW - Skipping email due to missing placeholders', $item);

                continue;
            }

            \Log::info('WORKFLOW - Sending email to: ' . $item['email']);

            $bulkEmailEntries[] = [
                'Destination' => [
                    'ToAddresses' => [$item['email']],
                ],
                'ReplacementEmailContent' => [
                    'ReplacementTemplate' => [
                        'ReplacementTemplateData' => json_encode(array_replace($placeHolders, $item)),
                    ],
                ],
            ];
        }

        try {

            $bulkEmailPayload = [
                'FromEmailAddress' => $from,
                'DefaultContent' => [
                    'Template' => [
                        'TemplateContent' => [
                            'Html' => $htmlContent,
                            'Subject' => $subject,
                            'Text' => $textContent ?: strip_tags($textContent),
                        ],
                        'TemplateData' => '{}',
                    ],
                ],
                'ConfigurationSetName' => 'farmers',
                'BulkEmailEntries' => $bulkEmailEntries,
            ];

            $response = $sesClient->sendBulkEmail($bulkEmailPayload);

            if ($jobWorkflowId) {
                $jobWorkflowRepo = app(JobWorkflowRepository::class);

                $jobWorkflowInfo = $jobWorkflowRepo->getInfo($jobWorkflowId);
                $countOfProcessedRecord = $jobWorkflowInfo['total_no_of_records_executed'] + count($payload);
                $status = $countOfProcessedRecord == $jobWorkflowInfo['total_no_of_records_to_execute'] ? 'COMPLETED' : 'IN_PROGRESS';
                $payload = [
                    'total_no_of_records_executed' => $countOfProcessedRecord,
                    'status' => $status,
                ];

                event(new JobWorkflowUpdatedEvent($jobWorkflowId, $payload));
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

    public static function sendEmail($from, $subject, $htmlContent, $payload, $textContent = '', $jobWorkflowId = 0)
    {
        try {
            $sesClient = self::getSesClient();
        } catch (\Exception $e) {
            throw new \Exception('Error creating SES client: ' . $e->getMessage());
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

        \Log::info('WORKFLOW - Sending email to: ' . $payload['email']);

        foreach ($payload as $key => $value) {
            $htmlContent = str_replace('{{' . $key . '}}', $value, $htmlContent);
            $textContent = str_replace('{{' . $key . '}}', $value, $textContent);
        }

        try {
            $response = $sesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => [$payload['email']],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $htmlContent,
                        ],
                        'Text' => [
                            'Charset' => 'UTF-8',
                            'Data' => $textContent ?: strip_tags($textContent),
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $subject,
                    ],
                ],
                'Source' => $from,
            ]);

            return $response['MessageId'] ?? 0;
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }
}
