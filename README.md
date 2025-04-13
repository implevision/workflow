# Prerequisites

- Create a lambda function to execute command.
- Create a ROLE in AWS which has a permission to invoke lambda function

```json
#Trusted entities

{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Principal": {
                "Service": "scheduler.amazonaws.com"
            },
            "Action": "sts:AssumeRole"
        }
    ]
}
```

```json
#Permission

{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Action": "lambda:InvokeFunction",
            "Resource": "ARM_FOR_LAMBDA FUNCTION",
            "Effect": "Allow"
        }
    ]
}
```

- Update `.env`

```
AWS_PROFILE
AWS_DEFAULT_REGION
WORKFLOW_TABLE_PREFIX
WORKFLOW_TIMEZONE
WORKFLOW_ROLE_ARN_TO_INVOKE_LAMBDA_BY_EVENT_BRIDGE
```

- Update `config/workflow.php`

```

```

- Run `php artisan` and make sure the following commands appear

```
taurus:dispatch-workflow
taurus:health-check
```
