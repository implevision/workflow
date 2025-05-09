<?php

namespace Taurus\Workflow\Services;


use Aws\SesV2\SesV2Client;
use Aws\Exception\AwsException;
use Taurus\Workflow\Events\JobWorkflowUpdated;
use Taurus\Workflow\Repositories\Eloquent\JobWorkflowRepository;

class SES
{
    public static function extractPlaceholders($html)
    {
        preg_match_all('/{{\s*(.*?)\s*}}/', $html, $matches);
        return $matches[1]; // Return only the extracted placeholders
    }

    public static function sendEmail($from, $subject, $htmlContent, $payload, $textContent = "", $jobWorkflowId = 0)
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        // if (!$awsProfile) {
        //     throw new \Exception('AWS Profile not found in config/workflow.php');
        // }

        if (!$awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        $awsConfig = [
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            'region' => $awsRegion,
            'version' => 'latest'
        ];

        // SES dose not support if any placeholder is missing in the email template. 
        // So we need to fill the missing placeholders with empty string        
        $placeHolders = self::extractPlaceholders($htmlContent);
        $placeHolders = is_array($placeHolders) ? $placeHolders : [];
        $placeHolders = array_fill_keys($placeHolders, '');

        $bulkEmailEntries = [];
        foreach ($payload as $item) {
            \Log::info("Sending email to: " . $item['email']);

            $bulkEmailEntries[] = [
                'Destination' => [
                    'ToAddresses' => [$item['email']]
                ],
                'ReplacementEmailContent' => [
                    'ReplacementTemplate' => [
                        'ReplacementTemplateData' => json_encode(array_replace($placeHolders, $item))
                    ]
                ]
            ];
        }

        try {
            // Initialize S3 client
            $sesClient = new SesV2Client($awsConfig);

            $bulkEmailPayload = [
                'FromEmailAddress' => $from,
                'DefaultContent' => [
                    'Template' => [
                        'TemplateContent' => [
                            "Html" => $htmlContent,
                            "Subject" =>  $subject,
                            "Text" => $textContent
                        ],
                        'TemplateData' => '{}',
                    ],
                ],
                'ConfigurationSetName' => 'farmers',
                'BulkEmailEntries' => $bulkEmailEntries
            ];

            $response = $sesClient->sendBulkEmail($bulkEmailPayload);
            //\Log::info($response);


            if ($jobWorkflowId) {
                $jobWorkflowRepo = app(JobWorkflowRepository::class);

                $jobWorkflowInfo = $jobWorkflowRepo->getInfo($jobWorkflowId);
                $countOfProcessedRecord = $jobWorkflowInfo['total_no_of_records_executed'] + count($payload);
                $status = $countOfProcessedRecord == $jobWorkflowInfo['total_no_of_records_to_execute'] ? 'COMPLETED' : 'IN_PROGRESS';
                $payload = [
                    'total_no_of_records_executed' => $countOfProcessedRecord,
                    'status' => $status
                ];

                event(new JobWorkflowUpdated($jobWorkflowId, $payload));
            }
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }
}
