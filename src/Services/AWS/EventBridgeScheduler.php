<?php

namespace Taurus\Workflow\Services\AWS;

use Aws\Exception\AwsException;
use Aws\Scheduler\SchedulerClient;

class EventBridgeScheduler
{
    public static function getAwsConfig()
    {
        $awsProfile = config('workflow.aws_profile');
        $awsRegion = config('workflow.aws_region');

        // if (!$awsProfile) {
        //     throw new \Exception('AWS Profile not found in config/workflow.php');
        // }

        if (! $awsRegion) {
            throw new \Exception('AWS Region not found in config/workflow.php');
        }

        return [
            ...($awsProfile ? ['profile' => $awsProfile] : []),
            'region' => $awsRegion,
            'version' => 'latest',
        ];
    }

    public static function createScheduleGroup($targetGroupName, $tags = [])
    {
        try {
            $awsConfig = self::getAwsConfig();
            $schedulerClient = new SchedulerClient($awsConfig);

            // List all schedule groups (pagination-aware)
            $exists = false;
            $nextToken = null;

            do {
                $params = [];
                if ($nextToken) {
                    $params['NextToken'] = $nextToken;
                }

                $result = $schedulerClient->listScheduleGroups($params);

                $scheduleGroupsArn = '';
                foreach ($result['ScheduleGroups'] as $group) {
                    if ($group['Name'] === $targetGroupName) {
                        $exists = true;
                        $scheduleGroupsArn = $group['Arn'];
                        break 2;
                    }
                }

                $nextToken = $result['NextToken'] ?? null;
            } while ($nextToken);

            if ($exists) {
                \Log::info("Schedule Group '{$targetGroupName}' already exists!");

                return $scheduleGroupsArn;
            }

            // Create a new schedule group
            $result = $schedulerClient->createScheduleGroup([
                'Name' => $targetGroupName,
            ]);

            \Log::info('Schedule Group Created! ARN: ' . $result['ScheduleGroupArn']);

            if (count($tags)) {
                $schedulerClient->tagResource([
                    'ResourceArn' => $result['ScheduleGroupArn'],
                    'Tags' => $tags,
                ]);
            }

            return $result['ScheduleGroupArn'];
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }

    public static function createSchedule($scheduleName, $scheduleExpression, $target = [], $groupName = 'default', $flexibleTimeWindow = 'OFF')
    {
        try {
            $awsConfig = self::getAwsConfig();
            $schedulerClient = new SchedulerClient($awsConfig);

            try {
                $params = [
                    'Name' => $scheduleName,
                    'ScheduleExpression' => $scheduleExpression,
                    // 'ScheduleExpressionTimezone'  => 'UTC',
                    'GroupName' => $groupName,
                    'FlexibleTimeWindow' => [
                        'Mode' => $flexibleTimeWindow,
                    ],
                ];

                if (count($target)) {
                    $params['Target'] = [
                        'Arn' => $target['arn'] ?? null,
                        'RoleArn' => $target['roleArn'] ?? null,
                        'Input' => $target['input'] ?? null,
                    ];
                }

                return $schedulerClient->createSchedule($params);
            } catch (AwsException $e) {
                throw new \Exception('Error creating schedule: ' . $e->getMessage());
            }
        } catch (AwsException $e) {
            throw new \Exception($e->getAwsErrorMessage());
        }
    }
}
