<?php

namespace Taurus\Workflow\Services\AWS;

use Aws\Exception\AwsException;
use Aws\Iam\IamClient;

class CheckUserCapabilities
{
    public static function check()
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');
        $requiredActions = config('workflow.required_actions');

        // if (!$awsProfile) {
        //     throw new \Exception('AWS Profile not found in config/workflow.php');
        // }

        if (! $awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        if (! $requiredActions) {
            throw new \Exception('Required Actions not found in config/workflow.php');
        }

        // GET USER ARN
        $awsUser = AWSUserInfo::getInfo();
        $roleArn = $awsUser['Arn'];

        if (strpos($roleArn, 'assumed-role') !== false) {
            $roleArn = str_replace('assumed-role/', 'role/', $roleArn); // Convert assumed-role ARN
            $roleArn = str_replace('arn:aws:sts::', 'arn:aws:iam::', $roleArn);
            $roleArn = explode('/', $roleArn);
            array_pop($roleArn);
            $roleArn = implode('/', $roleArn);
        }

        $awsConfig = [
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            'region' => $awsRegion,
            'version' => 'latest',
        ];

        try {
            $iamClient = new IamClient($awsConfig);

            $result = $iamClient->simulatePrincipalPolicy([
                'PolicySourceArn' => $roleArn,
                'ActionNames' => array_keys($requiredActions),
            ]);

            $actionAllowed = [];
            foreach ($result['EvaluationResults'] as $evaluation) {
                $actionAllowed[$evaluation['EvalActionName']] = $evaluation['EvalDecision'] === 'allowed' ? 'YES' : 'NO';
            }
        } catch (AwsException $e) {
            throw new \Exception($e->getMessage());
        }

        return $actionAllowed;
    }
}
