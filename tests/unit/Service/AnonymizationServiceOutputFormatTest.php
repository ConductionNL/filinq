<?php

/**
 * Unit tests for the anonymised-PDF output behaviour
 *
 * Covers: outputFormat 'pdf'/'pdf-only' triggers PdfConversionService; rollback on
 * ConversionFailedException; atomic file replacement on success; 'preserve'
 * mode leaves conversion untouched; pdf-only cleans up the native intermediate.
 *
 * The gate itself is owned by AnonymisedPdfOutputService — the collaborator the
 * anonymise pipeline delegates the PDF-output step to — so the behaviour is
 * asserted against its public API instead of against AnonymizationService's
 * source text.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\AnonymisedPdfOutputService;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\PdfConversionService;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for the PDF output-format gate on an anonymised intermediate
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceOutputFormatTest extends TestCase
{
    use BuildsAnonymizationService;

    /**
     * Build the gate under test around the supplied cascade double.
     *
     * @param PdfConversionService $pdfConversion The cascade double.
     *
     * @return AnonymisedPdfOutputService
     */
    private function makeGate(PdfConversionService $pdfConversion): AnonymisedPdfOutputService
    {
        return new AnonymisedPdfOutputService(
            logger: new NullLogger(),
            pdfConversion: $pdfConversion
        );

    }//end makeGate()

    /**
     * Build a File double with the given mime type.
     *
     * @param string $mime The mime type the node reports.
     *
     * @return File The node double.
     */
    private function makeNode(string $mime): File
    {
        $node = $this->createMock(File::class);
        $node->method('getMimeType')->willReturn($mime);

        return $node;

    }//end makeNode()

    /**
     * Verify the source file exists and the class can be loaded.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-7
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__.'/../../../lib/Service/AnonymizationService.php'
        );

    }//end testSourceFileExists()

    /**
     * The gate delegates the conversion to PdfConversionService::convertToPdf
     * and returns the converted node.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testConvertToPdfDelegationExists(): void
    {
        $native    = $this->makeNode('application/vnd.oasis.opendocument.text');
        $converted = $this->makeNode('application/pdf');

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->expects($this->once())->method('convertToPdf')->with($native)->willReturn($converted);

        $result = $this->makeGate($cascade)->convertResultToPdf(
            result: $native,
            outputFormat: 'pdf',
            fileId: 7
        );

        $this->assertSame($converted, $result, 'the converted PDF node must be returned');

    }//end testConvertToPdfDelegationExists()

    /**
     * The gate rolls back the anonymised intermediate by deleting it when PDF
     * conversion fails.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testRollbackDeletesIntermediateOnFailure(): void
    {
        $native = $this->makeNode('application/vnd.oasis.opendocument.text');
        $native->expects($this->once())->method('delete');

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')->willThrowException(new ConversionFailedException());

        $this->expectException(ConversionFailedException::class);
        $this->makeGate($cascade)->convertResultToPdf(result: $native, outputFormat: 'pdf', fileId: 7);

    }//end testRollbackDeletesIntermediateOnFailure()

    /**
     * The gate fires for BOTH 'pdf-only' and 'pdf', is skipped for 'preserve',
     * and is a no-op when the result is already a PDF or is not a File node.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testPdfConversionGateGuardExists(): void
    {
        foreach (['pdf-only', 'pdf'] as $format) {
            $converted = $this->makeNode('application/pdf');
            $cascade   = $this->createMock(PdfConversionService::class);
            $cascade->expects($this->once())->method('convertToPdf')->willReturn($converted);

            $this->makeGate($cascade)->convertResultToPdf(
                result: $this->makeNode('application/msword'),
                outputFormat: $format,
                fileId: 7
            );
        }

        // 'preserve' never converts.
        $preserveCascade = $this->createMock(PdfConversionService::class);
        $preserveCascade->expects($this->never())->method('convertToPdf');
        $native = $this->makeNode('application/msword');
        $this->assertSame(
            $native,
            $this->makeGate($preserveCascade)->convertResultToPdf($native, 'preserve', 7)
        );

        // Already a PDF: no cascade, no delete.
        $pdfCascade = $this->createMock(PdfConversionService::class);
        $pdfCascade->expects($this->never())->method('convertToPdf');
        $alreadyPdf = $this->makeNode('application/pdf');
        $alreadyPdf->expects($this->never())->method('delete');
        $this->assertSame(
            $alreadyPdf,
            $this->makeGate($pdfCascade)->convertResultToPdf($alreadyPdf, 'pdf-only', 7)
        );

        // Not a File node: returned untouched.
        $nonFileCascade = $this->createMock(PdfConversionService::class);
        $nonFileCascade->expects($this->never())->method('convertToPdf');
        $notAFile = new \stdClass();
        $this->assertSame(
            $notAFile,
            $this->makeGate($nonFileCascade)->convertResultToPdf($notAFile, 'pdf', 7)
        );

    }//end testPdfConversionGateGuardExists()

    /**
     * ConversionFailedException propagates unchanged (it is not wrapped in the
     * generic Exception the pipeline uses for other failures).
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testConversionFailedExceptionIsRethrownUncaught(): void
    {
        $thrown  = new ConversionFailedException(message: 'cascade exhausted', attempts: [['backend' => 'x']]);
        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')->willThrowException($thrown);

        try {
            $this->makeGate($cascade)->convertResultToPdf(
                result: $this->makeNode('application/msword'),
                outputFormat: 'pdf',
                fileId: 7
            );
            $this->fail('ConversionFailedException must propagate.');
        } catch (ConversionFailedException $e) {
            $this->assertSame($thrown, $e, 'the typed exception must propagate as-is, unwrapped');
        }

    }//end testConversionFailedExceptionIsRethrownUncaught()

    /**
     * 'pdf-only' best-effort deletes the NATIVE intermediate after a successful
     * conversion (never the converted PDF), and 'pdf' keeps it.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testOutputFormatGuardExistsInSource(): void
    {
        // pdf-only: native deleted, converted kept.
        $native    = $this->makeNode('application/msword');
        $converted = $this->makeNode('application/pdf');
        $native->expects($this->once())->method('delete');
        $converted->expects($this->never())->method('delete');

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')->willReturn($converted);

        $this->makeGate($cascade)->convertResultToPdf($native, 'pdf-only', 7);

        // pdf: native kept.
        $keptNative    = $this->makeNode('application/msword');
        $keptConverted = $this->makeNode('application/pdf');
        $keptNative->expects($this->never())->method('delete');

        $keepCascade = $this->createMock(PdfConversionService::class);
        $keepCascade->method('convertToPdf')->willReturn($keptConverted);

        $this->makeGate($keepCascade)->convertResultToPdf($keptNative, 'pdf', 7);

    }//end testOutputFormatGuardExistsInSource()

    /**
     * The gate returns the node produced by the cascade, not the native input —
     * the native reference is captured before the reassignment so 'pdf-only'
     * still has something to clean up.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testPdfConversionReassignsResult(): void
    {
        $native    = $this->makeNode('application/msword');
        $converted = $this->makeNode('application/pdf');

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')->willReturn($converted);

        $result = $this->makeGate($cascade)->convertResultToPdf($native, 'pdf-only', 7);

        $this->assertSame($converted, $result);
        $this->assertNotSame($native, $result);

    }//end testPdfConversionReassignsResult()

    /**
     * PdfConversionService::convertToPdf accepts a File and returns a File.
     *
     * Verifies the collaborator contract the gate relies on so the mock wiring
     * in this suite matches the real method signature.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testPdfConversionServiceConvertToPdfSignature(): void
    {
        $method = new \ReflectionMethod(
            \OCA\DocuDesk\Service\PdfConversionService::class,
            'convertToPdf'
        );

        $params = $method->getParameters();
        $this->assertSame('source', $params[0]->getName());
        $this->assertSame(
            'OCP\\Files\\File',
            (string) $params[0]->getType(),
            'convertToPdf must accept an OCP\\Files\\File source.'
        );

    }//end testPdfConversionServiceConvertToPdfSignature()

    /**
     * A failing rollback delete is swallowed so the typed exception still
     * propagates, and a failing pdf-only cleanup delete does not fail an
     * otherwise-successful run.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testRollbackDeleteFailureIsSwallowed(): void
    {
        // Rollback delete blows up: the ConversionFailedException still wins.
        $native = $this->makeNode('application/msword');
        $native->method('delete')->willThrowException(new \RuntimeException('locked'));

        $cascade = $this->createMock(PdfConversionService::class);
        $cascade->method('convertToPdf')->willThrowException(new ConversionFailedException());

        try {
            $this->makeGate($cascade)->convertResultToPdf($native, 'pdf', 7);
            $this->fail('ConversionFailedException must still propagate.');
        } catch (ConversionFailedException $e) {
            $this->addToAssertionCount(1);
        }

        // Cleanup delete blows up on the success path: the PDF is still returned.
        $cleanupNative = $this->makeNode('application/msword');
        $cleanupNative->method('delete')->willThrowException(new \RuntimeException('locked'));
        $converted = $this->makeNode('application/pdf');

        $okCascade = $this->createMock(PdfConversionService::class);
        $okCascade->method('convertToPdf')->willReturn($converted);

        $this->assertSame(
            $converted,
            $this->makeGate($okCascade)->convertResultToPdf($cleanupNative, 'pdf-only', 7),
            'a failed pdf-only cleanup must not fail an otherwise-successful run'
        );

    }//end testRollbackDeleteFailureIsSwallowed()

    /**
     * ConversionFailedException carries getAttempts() per the exception contract.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testConversionFailedExceptionGetAttempts(): void
    {
        $attempts = [
            ['backend' => 'office_app', 'available' => false, 'supports' => true, 'reason' => 'Not installed'],
            ['backend' => 'libreoffice_headless', 'available' => false, 'supports' => true, 'reason' => 'Binary not found'],
        ];

        $e = new ConversionFailedException(
            message: 'No backend could convert the file.',
            attempts: $attempts
        );

        $this->assertSame($attempts, $e->getAttempts());
        $this->assertSame(422, $e->getCode());

    }//end testConversionFailedExceptionGetAttempts()

    /**
     * ConversionFailedException with no attempts returns an empty array.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testConversionFailedExceptionEmptyAttempts(): void
    {
        $e = new ConversionFailedException();
        $this->assertSame([], $e->getAttempts());

    }//end testConversionFailedExceptionEmptyAttempts()

    /**
     * The service can be constructed from its collaborators, proving the mock
     * wiring in this suite matches the real constructor arity and types.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testServiceConstructsWithAllDependencies(): void
    {
        $this->assertInstanceOf(AnonymizationService::class, $this->makeAnonymizationServiceFrom());

    }//end testServiceConstructsWithAllDependencies()
}//end class
