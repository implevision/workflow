<?php

namespace Taurus\Workflow\Tests\Services\AWS;

use PHPUnit\Framework\Attributes\DataProvider;
use Taurus\Workflow\Services\AWS\SES;
use Taurus\Workflow\Services\WorkflowActions\Helpers\Email\EmailClient;
use Taurus\Workflow\Tests\TestCase;

class SESAttachmentTest extends TestCase
{
    private const PNG_BYTES = "\x89PNG\r\n\x1a\n\x00\x00\x00\x0dIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89";

    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
            @rmdir(dirname($file));
        }

        parent::tearDown();
    }

    public static function namedFileProvider(): array
    {
        return [
            'jpg image' => ['photo.jpg', 'image/jpeg'],
            'upper-case extension' => ['PHOTO.JPEG', 'image/jpeg'],
            'png image' => ['scan.png', 'image/png'],
            'pdf' => ['Claim Form.pdf', 'application/pdf'],
            'docx' => ['letter.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['statement.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
    }

    #[DataProvider('namedFileProvider')]
    public function test_content_type_comes_from_the_file_name_extension(string $fileName, string $contentType): void
    {
        $this->assertSame(
            [$fileName, $contentType],
            SES::resolveAttachmentType($fileName, 'other-key.bin', 'irrelevant bytes')
        );
    }

    public function test_name_without_extension_takes_the_extension_from_the_path(): void
    {
        $this->assertSame(
            ['Commission Statement Sheet.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            SES::resolveAttachmentType('Commission Statement Sheet', 'agent-123.xlsx', 'irrelevant bytes')
        );
    }

    public function test_bytes_are_inspected_only_when_neither_name_nor_path_has_an_extension(): void
    {
        $this->assertSame(
            ['photo.png', 'image/png'],
            SES::resolveAttachmentType('photo', 'object-key', self::PNG_BYTES)
        );
    }

    public function test_unknown_type_falls_back_to_octet_stream(): void
    {
        $this->assertSame(
            ['archive.xyz', 'application/octet-stream'],
            SES::resolveAttachmentType('archive.xyz', 'object-key', "\x00\x01\x02\x03")
        );
    }

    public function test_image_attachment_is_no_longer_sent_as_pdf(): void
    {
        $path = $this->tempFile('upload', self::PNG_BYTES);

        $processed = SES::processAttachment([['name' => 'scan.png', 'path' => $path]]);

        $this->assertCount(1, $processed);
        $this->assertSame('image/png', $processed[0]['ContentType']);
        $this->assertSame('scan.png', $processed[0]['FileName']);
        $this->assertSame(self::PNG_BYTES, $processed[0]['RawContent']);
    }

    public function test_missing_name_uses_the_path_basename(): void
    {
        $path = $this->tempFile('report.pdf', '%PDF-1.4');

        $processed = SES::processAttachment([['path' => $path]]);

        $this->assertSame('report.pdf', $processed[0]['FileName']);
        $this->assertSame('application/pdf', $processed[0]['ContentType']);
    }

    public static function missingAttachmentProvider(): array
    {
        return [
            'field with no document' => [null],
            'presigned url not generated' => [['name' => 'Commission Statement Sheet', 'path' => null]],
        ];
    }

    #[DataProvider('missingAttachmentProvider')]
    public function test_a_missing_attachment_stops_the_email(?array $attachment): void
    {
        $path = $this->tempFile('report.pdf', '%PDF-1.4');

        $this->expectExceptionMessage('Attachment AttachStatementSheet has no file');

        SES::processAttachment([
            'AttachLetter' => ['name' => 'report.pdf', 'path' => $path],
            'AttachStatementSheet' => $attachment,
        ]);
    }

    public function test_an_attachment_that_cannot_be_downloaded_stops_the_email(): void
    {
        $this->expectExceptionMessage('Attachment AttachLetter could not be downloaded');

        SES::processAttachment([
            'AttachLetter' => ['name' => 'report.pdf', 'path' => sys_get_temp_dir().'/does-not-exist-'.uniqid().'.pdf'],
        ]);
    }

    public function test_attach_fields_with_no_document_are_kept_as_missing(): void
    {
        $attachments = (new EmailClient)->extractAttachments([
            'email' => 'allowed@example.com',
            'AttachClaimDocs' => [],
            'AttachmentPolicy' => null,
            'attachLetter' => [['name' => 'letter.pdf', 'path' => 'https://bucket.test/letter.pdf']],
        ]);

        $this->assertSame([
            'AttachClaimDocs' => null,
            'AttachmentPolicy' => null,
            'attachLetter' => ['name' => 'letter.pdf', 'path' => 'https://bucket.test/letter.pdf'],
        ], $attachments);
    }

    public function test_empty_attachments_return_nothing(): void
    {
        $this->assertSame([], SES::processAttachment([]));
        $this->assertSame([], SES::processAttachment(null));
    }

    private function tempFile(string $name, string $content): string
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('ses-attachment-', true);
        mkdir($dir);
        $path = $dir.DIRECTORY_SEPARATOR.$name;
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }
}
