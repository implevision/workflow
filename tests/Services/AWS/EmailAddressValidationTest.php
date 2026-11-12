<?php

namespace Taurus\Workflow\Tests\Services\AWS;

use PHPUnit\Framework\Attributes\DataProvider;
use Taurus\Workflow\Services\AWS\SES;
use Taurus\Workflow\Services\WorkflowActions\Helpers\Email\EmailAddressValidator;
use Taurus\Workflow\Services\WorkflowActions\Helpers\Email\EmailClient;
use Taurus\Workflow\Services\WorkflowService;
use Taurus\Workflow\Tests\Support\FakeWorkflowService;
use Taurus\Workflow\Tests\TestCase;

class EmailAddressValidationTest extends TestCase
{
    public static function addressProvider(): array
    {
        return [
            'plain' => ['agent@example.com', true],
            'plus and subdomain' => ['first.last+tag@mail.example.co.uk', true],
            'no at sign' => ['agent.example.com', false],
            'no domain' => ['agent@', false],
            'no top-level domain' => ['agent@example', false],
            'double at sign' => ['agent@@example.com', false],
            'space' => ['first last@example.com', false],
            'unrendered placeholder' => ['{{AgentEmail}}', false],
        ];
    }

    #[DataProvider('addressProvider')]
    public function test_addresses_are_validated_by_format(string $email, bool $isValid): void
    {
        $this->assertSame($isValid, EmailAddressValidator::isValid($email));
    }

    public function test_split_accepts_comma_separated_strings_and_lists(): void
    {
        $this->assertSame(
            ['a@example.com', 'b@example.com', 'c@example.com'],
            EmailAddressValidator::split(['a@example.com, b@example.com', ' ', 'c@example.com', null])
        );
        $this->assertSame([], EmailAddressValidator::split(null));
    }

    public function test_partition_separates_valid_and_invalid_addresses(): void
    {
        $this->assertSame(
            [['a@example.com'], ['nope', 'b@']],
            EmailAddressValidator::partition('a@example.com,nope, b@')
        );
    }

    public function test_ses_keeps_only_valid_recipients(): void
    {
        $this->assertSame(['a@example.com'], SES::validRecipients(['email' => ['a@example.com', 'broken']]));
        $this->assertSame([], SES::validRecipients(['email' => 'broken']));
        $this->assertSame([], SES::validRecipients([]));
    }

    public function test_ses_does_not_send_an_email_without_a_valid_recipient(): void
    {
        config(['workflow.aws_region' => 'us-east-1']);

        $this->expectExceptionMessage('Skipping email due to missing or invalid recipient email address');

        SES::sendEmail('from@example.com', 'Subject', '<p>Hi</p>', ['email' => 'not-an-email']);
    }

    public function test_ses_skips_a_bulk_batch_without_any_valid_recipient(): void
    {
        config(['workflow.aws_region' => 'us-east-1']);

        $this->assertNull(SES::sendBulkEmail('from@example.com', 'Subject', '<p>Hi</p>', [
            ['email' => 'not-an-email'],
            ['email' => ''],
        ]));
    }

    public function test_csv_rows_without_a_valid_recipient_are_dropped_before_dispatch(): void
    {
        $this->app->instance(WorkflowService::class, $workflowService = new FakeWorkflowService);

        $filter = new \ReflectionMethod(EmailClient::class, 'filterInvalidRecipientRows');
        $rows = $filter->invoke(new EmailClient, [
            'workflowId' => 3,
            'jobWorkflowId' => 9,
            'payload' => [
                ['email' => 'a@example.com, broken', 'Name' => 'A'],
                ['email' => 'broken', 'Name' => 'B'],
                ['email' => '', 'Name' => 'C'],
                ['email' => ['d@example.com'], 'Name' => 'D'],
            ],
        ]);

        $this->assertSame([
            ['email' => ['a@example.com'], 'Name' => 'A'],
            ['email' => ['d@example.com'], 'Name' => 'D'],
        ], $rows);
        $this->assertSame([[
            'errorType' => 'INVALID_EMAIL_ADDRESS',
            'exception' => 'Skipped 2 invalid email address(es); 2 CSV row(s) had no valid email address',
        ]], $workflowService->errors);
    }
}
