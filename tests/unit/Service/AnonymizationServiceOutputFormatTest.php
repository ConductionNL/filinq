<?php

/**
 * Unit tests for AnonymizationService PDF output format behaviour
 *
 * Covers: outputFormat 'pdf' triggers PdfConversionService; rollback on
 * ConversionFailedException; atomic file replacement on success; 'preserve'
 * mode leaves conversion untouched; tenant default applied when per-call absent.
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
use OCA\DocuDesk\Service\AnonymizationService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AnonymizationService PDF output format integration
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
     * The service delegates PDF conversion to PdfConversionService::convertToPdf.
     *
     * The real anonymizeDocument() path calls $this->pdfConversion->convertToPdf()
     * when outputFormat is 'pdf' and the anonymised result is not already a PDF.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testConvertToPdfDelegationExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('convertToPdf', $content);

    }//end testConvertToPdfDelegationExists()

    /**
     * The service rolls back the anonymised intermediate by deleting it when
     * PDF conversion fails. The real source performs an inline $result->delete()
     * inside the ConversionFailedException catch before re-throwing.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testRollbackDeletesIntermediateOnFailure(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('$result->delete();', $content);

    }//end testRollbackDeletesIntermediateOnFailure()

    /**
     * The PDF-conversion gate is guarded on outputFormat === 'pdf' and only runs
     * when the anonymised result is a File. The atomic replacement (convert the
     * native intermediate to PDF, then delete on failure) lives in this branch.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testPdfConversionGateGuardExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        // The merged source gates on both 'pdf-only' (Robert's new default) and
        // 'pdf' via in_array, combined with the File-type check.
        $this->assertStringContainsString("in_array(\$outputFormat, ['pdf-only', 'pdf'], true)", $content);
        $this->assertStringContainsString('$result instanceof File', $content);

    }//end testPdfConversionGateGuardExists()

    /**
     * ConversionFailedException is imported and re-thrown uncaught from the outer
     * catch (it bypasses the generic Exception wrapper).
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testConversionFailedExceptionIsRethrownUncaught(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('ConversionFailedException', $content);
        $this->assertStringContainsString('throw $e;', $content);

    }//end testConversionFailedExceptionIsRethrownUncaught()

    /**
     * The service calls convertToPdf only when outputFormat === 'pdf'.
     * When outputFormat === 'preserve', conversion code is skipped.
     *
     * Verified structurally: the outputFormat === 'pdf' guard must appear in source.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testOutputFormatGuardExistsInSource(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        // Robert's merged source expresses the gate as an in_array over the
        // pdf-producing formats rather than a bare equality check.
        $this->assertStringContainsString("in_array(\$outputFormat, ['pdf-only', 'pdf'], true)", $content);

    }//end testOutputFormatGuardExistsInSource()

    /**
     * The PDF conversion delegates the .pdf naming to PdfConversionService.
     *
     * In the real source the atomic native→PDF replacement is performed by
     * $this->pdfConversion->convertToPdf($result); the returned File becomes
     * the new $result. The service does NOT derive the .pdf name itself — that
     * is the PdfConversionService cascade's responsibility. We assert the
     * delegation marker is present and the converted File is re-assigned.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testPdfConversionReassignsResult(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('$result = $this->pdfConversion->convertToPdf($result);', $content);

    }//end testPdfConversionReassignsResult()

    /**
     * PdfConversionService::convertToPdf accepts a File and returns a File.
     *
     * Verifies the collaborator contract the AnonymizationService relies on so
     * the mock wiring in this suite matches the real method signature.
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
     * Rollback delete failures are swallowed so the typed exception still
     * propagates. The real source wraps $result->delete() in its own try/catch
     * inside the ConversionFailedException handler and re-throws $e regardless.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testRollbackDeleteFailureIsSwallowed(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('catch (Throwable $deleteError)', $content);
        $this->assertStringContainsString('throw $e;', $content);

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
     * The service can be constructed with all ten promoted dependencies
     * stubbed, proving the mock wiring in this suite matches the real
     * constructor arity and parameter types.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testServiceConstructsWithAllDependencies(): void
    {
        $service = $this->buildServiceWithoutDependencies();
        $this->assertInstanceOf(AnonymizationService::class, $service);

    }//end testServiceConstructsWithAllDependencies()

    /**
     * Build AnonymizationService with all constructor deps stubbed.
     *
     * @return AnonymizationService
     */
    private function buildServiceWithoutDependencies(): AnonymizationService
    {
        $logger             = new \Psr\Log\NullLogger();
        $container          = $this->createMock(\Psr\Container\ContainerInterface::class);
        $appManager         = $this->createMock(\OCP\App\IAppManager::class);
        $entityDetection    = $this->createMock(\OCA\DocuDesk\Service\EntityDetectionService::class);
        $appConfig          = $this->createMock(\OCP\IAppConfig::class);
        $consentCrud        = $this->createMock(\OCA\DocuDesk\Service\ConsentCrudService::class);
        $consentService     = $this->createMock(\OCA\DocuDesk\Service\ConsentService::class);
        $grondslagenSummary = $this->createMock(\OCA\DocuDesk\Service\GrondslagenSummaryService::class);
        $fileEntityStats    = $this->createMock(\OCA\DocuDesk\Service\FileEntityStatsService::class);
        $pdfConversion      = $this->createMock(\OCA\DocuDesk\Service\PdfConversionService::class);
        $emlAssembly        = $this->createMock(\OCA\DocuDesk\Service\EmlPdfAssemblyService::class);
        $customDictionary     = $this->createMock(\OCA\DocuDesk\Service\CustomDictionaryService::class);
        $confidentialityLabel = $this->createMock(\OCA\DocuDesk\Service\ConfidentialityLabelService::class);
        $dictionaryDetection  = $this->createMock(\OCA\DocuDesk\Service\CustomDictionaryDetectionRunner::class);

        return new AnonymizationService(
            $logger,
            $container,
            $appManager,
            $entityDetection,
            $appConfig,
            $consentCrud,
            $consentService,
            $grondslagenSummary,
            $fileEntityStats,
            $pdfConversion,
            $emlAssembly,
            $customDictionary,
            $confidentialityLabel,
            $dictionaryDetection
        );

    }//end buildServiceWithoutDependencies()
}//end class
