<?php

namespace Taurus\Workflow\Services\AWS;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class S3
{
    public static function initializeS3Client()
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        /*if (!$awsProfile) {
            throw new \Exception('AWS Profile not found in config/workflow.php');
        }*/

        if (! $awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        $awsConfig = [
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            'region' => $awsRegion,
            'version' => 'latest',
        ];

        return new S3Client($awsConfig);
    }

    public static function getInfo($bucketName, $templateKey)
    {
        try {
            // Initialize S3 client
            $s3Client = self::initializeS3Client();

            $result = $s3Client->getObject([
                'Bucket' => $bucketName,
                'Key' => $templateKey,
            ]);

            return (string) $result['Body']; // Convert stream to string

        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function getPresignedUploadUrl(string $fileName, string $bucketName): string
    {
        try {
            $s3Client = self::initializeS3Client();
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            $filePath = self::getPath() . $fileName;

            $cmd = $s3Client->getCommand('PutObject', [
                'Bucket' => $bucketName,
                'Key' => $filePath,
                'ACL' => 'private',
                'ContentType' => self::getMIMEType($extension),
            ]);

            $request = $s3Client->createPresignedRequest($cmd, '+10 minutes');

            return (string) $request->getUri();
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function generateTemporaryFileUrl(string $filePath, string $bucketName, int $expiresInMinutes = 5): string
    {
        $s3Client = self::initializeS3Client();

        try {
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $bucketName,
                'Key' => $filePath,
            ]);

            $request = $s3Client->createPresignedRequest($cmd, "+{$expiresInMinutes} minutes");

            return (string) $request->getUri();
        } catch (\AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    private static function getPath()
    {
        return getTenant() . '/workflow/';
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

    public static function downloadFile($bucketName, $filePath, $saveAs)
    {
        try {
            $s3Client = self::initializeS3Client();

            $s3Client->getObject([
                'Bucket' => $bucketName,
                'Key' => trim($filePath, '/'),
                'SaveAs' => $saveAs,
            ]);

            return true; // Convert stream to string

        } catch (AwsException $e) {
            \Log::info($e);
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function uploadFile($bucketName, $filePath, $fileContent)
    {
        try {
            $s3Client = self::initializeS3Client();

            $s3Client->putObject([
                'Bucket' => $bucketName,
                'Key' => trim($filePath, '/'),
                'Body' => $fileContent,
                'ACL' => 'private',
            ]);

            return true;
        } catch (AwsException $e) {
            \Log::info($e);
            throw new \Exception($e->getAwsErrorMessage());
        }
    }
}
