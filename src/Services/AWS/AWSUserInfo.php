<?php

namespace Taurus\Workflow\Services\AWS;

use Aws\Exception\AwsException;
use Aws\Sts\StsClient;

class AWSUserInfo
{
    public static function getInfo()
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        // if (!$awsProfile) {
        //     throw new \Exception('AWS Profile not found in config/workflow.php');
        // }

        if (! $awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        $stsClient = new StsClient([
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            'region' => $awsRegion,
            'version' => 'latest',
        ]);

        try {
            // Get the caller identity
            return $stsClient->getCallerIdentity();
        } catch (AwsException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
