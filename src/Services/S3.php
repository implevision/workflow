<?php

namespace Taurus\Workflow\Services;


use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3
{
    public static function getInfo($bucketName, $templateKey)
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        if (!$awsProfile) {
            throw new \Exception('AWS Profile not found in config/workflow.php');
        }

        if (!$awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        $awsConfig = [
            'profile' => $awsProfile,
            'region' => $awsRegion,
            'version' => 'latest'
        ];

        try {
            // Initialize S3 client
            $s3Client = new S3Client($awsConfig);

            $result = $s3Client->getObject([
                'Bucket' => $bucketName,
                'Key'    => $templateKey,
            ]);

            return (string) $result['Body']; // Convert stream to string

        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }
}
