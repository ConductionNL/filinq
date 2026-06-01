<?php

/**
 * Unit tests for PdfConversionService
 *
 * Covers the cascade orchestration: ordered backend walk,
 * first-success short-circuit, attempt-aggregation on total failure,
 * and the per-backend isAvailable / canHandle / convert dispatch
 * sequence.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\Conversion\ConversionBackendInterface;
use OCA\DocuDesk\Service\PdfConversionService;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PdfConversionServiceTest extends TestCase
{


    /**
     * Builds a stub backend whose name + isAvailable + canHandle +
     * convert behaviour is fully controlled by the test. `convert` may
     * either return a stub File or throw — callers pass `$throw` for
     * the failure case.
     *
     * @param string $name      Backend identifier.
     * @param bool   $available isAvailable() return.
     * @param bool   $supports  canHandle() return (called only when available).
     * @param mixed  $outcome   Return-value File mock, or a Throwable to throw, or null when
     *                          the backend is never expected to be called (asserts no-op).
     *
     * @return ConversionBackendInterface&MockObject
     */
    private function stubBackend(
        string $name,
        bool $available,
        bool $supports,
        mixed $outcome
    ): ConversionBackendInterface {
        $mock = $this->createMock(ConversionBackendInterface::class);
        $mock->method('name')->willReturn($name);
        $mock->method('isAvailable')->willReturn($available);
        $mock->method('canHandle')->willReturn($supports);
        if ($outcome instanceof \Throwable) {
            $mock->method('convert')->willThrowException($outcome);
        } elseif ($outcome === null) {
            $mock->expects($this->never())->method('convert');
        } else {
            $mock->method('convert')->willReturn($outcome);
        }
        return $mock;

    }//end stubBackend()


    /**
     * Build a minimal File mock with the MIME / name / path the service
     * reads during convertToPdf.
     */
    private function fileMock(string $mime, string $name, string $path): File
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn($mime);
        $file->method('getName')->willReturn($name);
        $file->method('getPath')->willReturn($path);
        return $file;

    }//end fileMock()


    /**
     * First backend that says yes (available + supports) wins; later
     * backends are not consulted.
     *
     * @return void
     */
    public function testFirstSuccessShortCircuitsCascade(): void
    {
        $source = $this->fileMock('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'doc.docx', '/u/admin/doc.docx');
        $output = $this->fileMock('application/pdf', 'doc.pdf', '/u/admin/doc.pdf');

        // First backend handles it.
        $first = $this->stubBackend(name: 'office_app', available: true, supports: true, outcome: $output);
        // Second backend must NOT be consulted at all.
        $second = $this->createMock(ConversionBackendInterface::class);
        $second->expects($this->never())->method('isAvailable');
        $second->expects($this->never())->method('canHandle');
        $second->expects($this->never())->method('convert');

        $service = new PdfConversionService(
            backends: [$first, $second],
            logger: $this->createMock(LoggerInterface::class)
        );

        $this->assertSame($output, $service->convertToPdf($source));

    }//end testFirstSuccessShortCircuitsCascade()


    /**
     * When a backend is unavailable, the cascade moves on without
     * calling canHandle / convert and records the attempt as
     * `available: false`.
     *
     * @return void
     */
    public function testUnavailableBackendFallsThroughToNext(): void
    {
        $source = $this->fileMock('text/html', 'page.html', '/u/admin/page.html');
        $output = $this->fileMock('application/pdf', 'page.pdf', '/u/admin/page.pdf');

        $unavailable = $this->createMock(ConversionBackendInterface::class);
        $unavailable->method('name')->willReturn('office_app');
        $unavailable->method('isAvailable')->willReturn(false);
        $unavailable->expects($this->never())->method('canHandle');
        $unavailable->expects($this->never())->method('convert');

        $next = $this->stubBackend(name: 'mpdf', available: true, supports: true, outcome: $output);

        $service = new PdfConversionService(
            backends: [$unavailable, $next],
            logger: $this->createMock(LoggerInterface::class)
        );

        $this->assertSame($output, $service->convertToPdf($source));

    }//end testUnavailableBackendFallsThroughToNext()


    /**
     * When a backend is available but doesn't claim the input, the
     * cascade moves on without calling convert and records the
     * attempt as `supports: false`.
     *
     * @return void
     */
    public function testUnsupportedMimeFallsThroughToNext(): void
    {
        $source = $this->fileMock('text/plain', 'note.txt', '/u/admin/note.txt');
        $output = $this->fileMock('application/pdf', 'note.pdf', '/u/admin/note.pdf');

        $skipped = $this->createMock(ConversionBackendInterface::class);
        $skipped->method('name')->willReturn('phpword');
        $skipped->method('isAvailable')->willReturn(true);
        $skipped->method('canHandle')->willReturn(false);
        $skipped->expects($this->never())->method('convert');

        $next = $this->stubBackend(name: 'mpdf', available: true, supports: true, outcome: $output);

        $service = new PdfConversionService(
            backends: [$skipped, $next],
            logger: $this->createMock(LoggerInterface::class)
        );

        $this->assertSame($output, $service->convertToPdf($source));

    }//end testUnsupportedMimeFallsThroughToNext()


    /**
     * A backend that throws during convert is recorded as available +
     * supports but with the exception's message in the reason, and the
     * cascade continues to the next backend.
     *
     * @return void
     */
    public function testConvertExceptionFallsThroughToNextBackend(): void
    {
        $source = $this->fileMock('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'x.docx', '/u/admin/x.docx');
        $output = $this->fileMock('application/pdf', 'x.pdf', '/u/admin/x.pdf');

        $failing  = $this->stubBackend(
            name: 'phpword',
            available: true,
            supports: true,
            outcome: new RuntimeException('IOFactory load failed')
        );
        $recovery = $this->stubBackend(name: 'mpdf', available: true, supports: true, outcome: $output);

        $service = new PdfConversionService(
            backends: [$failing, $recovery],
            logger: $this->createMock(LoggerInterface::class)
        );

        $this->assertSame($output, $service->convertToPdf($source));

    }//end testConvertExceptionFallsThroughToNextBackend()


    /**
     * When no backend succeeds, the service throws
     * ConversionFailedException carrying one attempt-record per
     * consulted backend, in cascade order, with the documented
     * available/supports/reason shape.
     *
     * @return void
     */
    public function testNoSuccessThrowsAggregateException(): void
    {
        $source = $this->fileMock('application/vnd.ms-excel', 'sheet.xls', '/u/admin/sheet.xls');

        $disabled = $this->createMock(ConversionBackendInterface::class);
        $disabled->method('name')->willReturn('office_app');
        $disabled->method('isAvailable')->willReturn(false);

        $skipped = $this->createMock(ConversionBackendInterface::class);
        $skipped->method('name')->willReturn('phpword');
        $skipped->method('isAvailable')->willReturn(true);
        $skipped->method('canHandle')->willReturn(false);

        $broken = $this->stubBackend(
            name: 'mpdf',
            available: true,
            supports: true,
            outcome: new RuntimeException('mPDF crashed')
        );

        $service = new PdfConversionService(
            backends: [$disabled, $skipped, $broken],
            logger: $this->createMock(LoggerInterface::class)
        );

        try {
            $service->convertToPdf($source);
            $this->fail('Expected ConversionFailedException to be thrown.');
        } catch (ConversionFailedException $e) {
            $attempts = $e->getAttempts();
            $this->assertCount(3, $attempts);

            // Cascade order preserved.
            $this->assertSame('office_app', $attempts[0]['name']);
            $this->assertFalse($attempts[0]['available']);
            $this->assertFalse($attempts[0]['supports']);

            $this->assertSame('phpword', $attempts[1]['name']);
            $this->assertTrue($attempts[1]['available']);
            $this->assertFalse($attempts[1]['supports']);

            $this->assertSame('mpdf', $attempts[2]['name']);
            $this->assertTrue($attempts[2]['available']);
            $this->assertTrue($attempts[2]['supports']);
            $this->assertStringContainsString('mPDF crashed', $attempts[2]['reason']);
        }//end try

    }//end testNoSuccessThrowsAggregateException()


    /**
     * Entries in the backends array that aren't ConversionBackendInterface
     * implementations are skipped defensively rather than crashing the
     * cascade. Guards against DI misregistration.
     *
     * @return void
     */
    public function testNonInterfaceBackendIsSkipped(): void
    {
        $source = $this->fileMock('text/html', 'page.html', '/u/admin/page.html');
        $output = $this->fileMock('application/pdf', 'page.pdf', '/u/admin/page.pdf');

        $bogus = new \stdClass();
        $real  = $this->stubBackend(name: 'mpdf', available: true, supports: true, outcome: $output);

        $service = new PdfConversionService(
            // @phpstan-ignore-next-line — mixed-typed array on purpose
            backends: [$bogus, $real],
            logger: $this->createMock(LoggerInterface::class)
        );

        $this->assertSame($output, $service->convertToPdf($source));

    }//end testNonInterfaceBackendIsSkipped()


}//end class
