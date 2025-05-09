<?php

namespace Taurus\Workflow\Services;


use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3
{
    public static function initializeS3Client()
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        /*if (!$awsProfile) {
            throw new \Exception('AWS Profile not found in config/workflow.php');
        }*/

        if (!$awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        $awsConfig = [
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            //'credentials' => false,
            'region' => $awsRegion,
            'version' => 'latest'
        ];

        print_r($awsConfig);
        return new S3Client($awsConfig);
    }

    public static function getInfo($bucketName, $templateKey)
    {
        try {
            // Initialize S3 client
            $s3Client = self::initializeS3Client();

            $result = $s3Client->getObject([
                'Bucket' => $bucketName,
                'Key'    => $templateKey,
            ]);

            return (string) $result['Body']; // Convert stream to string

        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function getPresignedUploadUrl(string $fileName): string
    {
        try {
            $s3Client = self::initializeS3Client();
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            $filePath = self::getPath() . $fileName;

            $cmd = $s3Client->getCommand('PutObject', [
                'Bucket' => config('workflow.aws_bucket'),
                'Key'    => $filePath,
                'ACL'    => 'private',
                'ContentType' => self::getMIMEType($extension),
            ]);

            $request = $s3Client->createPresignedRequest($cmd, '+10 minutes');

            return (string) $request->getUri();
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    private static function getPath()
    {
        $tenant = config('workflow.single_tenant');

        if (!$tenant && function_exists('tenant')) {
            $tenant = tenant('id');
        } else {
            $tenant = 'misc';
        }

        return $tenant . '/';
    }

    private static function getMIMEType($extension)
    {
        // Common mapping of extensions to MIME types
        $mimeTypes = [
            'csv' => 'text/csv',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}
