# Prerequisites

- Create a lambda function to execute command
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
            "Resource": "arn:aws:lambda:us-east-1:358884819536:function:refreshReports",
            "Effect": "Allow"
        }
    ]
}
```
