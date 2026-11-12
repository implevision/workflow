<?php

namespace Taurus\Workflow\Tests\Services\AWS;

use PHPUnit\Framework\Attributes\DataProvider;
use Taurus\Workflow\Consumer\Taurus\Email\EmailSupressedByConsumerService;
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

    public static function quotedListProvider(): array
    {
        return [
            'comma inside a quoted local part' => [
                '"x,victim@example.net,y"@example.com',
                ['"x,victim@example.net,y"@example.com'],
            ],
            'escaped quote does not close the quoted local part' => [
                '"a\",victim@example.net"@example.com',
                ['"a\",victim@example.net"@example.com'],
            ],
            'quoted address among plain ones' => [
                'a@example.com, "x,y"@example.com ,b@example.com',
                ['a@example.com', '"x,y"@example.com', 'b@example.com'],
            ],
            'unclosed quote runs to the end of the value' => [
                'a@example.com,"x,victim@example.net',
                ['a@example.com', '"x,victim@example.net'],
            ],
            'quoted display name' => [
                '"Smith, John" <john@example.com>',
                ['"Smith, John" <john@example.com>'],
            ],
        ];
    }

    #[DataProvider('quotedListProvider')]
    public function test_split_does_not_break_quoted_strings_on_commas(string $value, array $expected): void
    {
        $this->assertSame($expected, EmailAddressValidator::split($value));
    }

    public function test_a_quoted_address_is_never_turned_into_a_different_recipient(): void
    {
        $this->assertSame(
            [['"x,victim@example.net,y"@example.com'], []],
            EmailAddressValidator::partition('"x,victim@example.net,y"@example.com')
        );
        $this->assertSame(
            [[], ['"x,victim@example.net']],
            EmailAddressValidator::partition('"x,victim@example.net')
        );
        $this->assertSame(
            ['"x,victim@example.net,y"@example.com'],
            SES::validRecipients(['email' => ['"x,victim@example.net,y"@example.com']])
        );
    }

    public function test_unsubscribe_filter_keeps_a_quoted_csv_address_whole(): void
    {
        $splitEmails = new \ReflectionMethod(EmailSupressedByConsumerService::class, 'splitEmails');

        $this->assertSame(
            ['"x,victim@example.net,y"@example.com', 'b@example.com'],
            $splitEmails->invoke(new EmailSupressedByConsumerService, ['email' => '"x,victim@example.net,y"@example.com, b@example.com'])
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
