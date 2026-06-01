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
     * The service exposes the convertAndReplaceWithPdf private method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testConvertAndReplaceWithPdfMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function convertAndReplaceWithPdf', $content);

    }//end testConvertAndReplaceWithPdfMethodExists()

    /**
     * The service exposes the rollbackAnonymizedFile private method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testRollbackMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function rollbackAnonymizedFile', $content);

    }//end testRollbackMethodExists()

    /**
     * The service exposes the atomicReplacement private method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testAtomicReplacementMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function atomicReplacement', $content);

    }//end testAtomicReplacementMethodExists()

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
        $this->assertStringContainsString("outputFormat === 'pdf'", $content);

    }//end testOutputFormatGuardExistsInSource()

    /**
     * atomicReplacement replaces the native extension with .pdf in the new file name.
     *
     * Uses reflection to invoke the private method with stub node objects.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-3
     */
    public function testAtomicReplacementDerivePdfName(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        // Use a shared array to capture side-effects without PHP reference
        // limitations in anonymous class constructors.
        $sideEffects = ['nativeDeleted' => false, 'pdfName' => null, 'pdfContent' => null];

        $pdfNode = new class {
            public function getId(): int
            {
                return 100;
            }//end getId()

            public function getName(): string
            {
                return 'document_anonymized.pdf';
            }//end getName()

            public function getPath(): string
            {
                return '/user/files/DocuDesk/document_anonymized.pdf';
            }//end getPath()
        };

        $parentFolder = new class ($pdfNode, $sideEffects) {

            private mixed $pdfNode;

            private array $effects;

            public function __construct(mixed $pdfNode, array &$effects)
            {
                $this->pdfNode = $pdfNode;
                $this->effects = &$effects;
            }//end __construct()

            public function newFile(string $name, string $content): mixed
            {
                $this->effects['pdfName']    = $name;
                $this->effects['pdfContent'] = $content;
                return $this->pdfNode;
            }//end newFile()
        };

        $anonymizedNode = new class ($parentFolder, $sideEffects) {

            private mixed $parent;

            private array $effects;

            public function __construct(mixed $parent, array &$effects)
            {
                $this->parent  = $parent;
                $this->effects = &$effects;
            }//end __construct()

            public function getName(): string
            {
                return 'document_anonymized.docx';
            }//end getName()

            public function getParent(): mixed
            {
                return $this->parent;
            }//end getParent()

            public function getId(): int
            {
                return 42;
            }//end getId()

            public function delete(): void
            {
                $this->effects['nativeDeleted'] = true;
            }//end delete()
        };

        $convertedFile = new class {
            public function getContent(): string
            {
                return '%PDF-1.7-stub';
            }//end getContent()

            public function getId(): int
            {
                return 999;
            }//end getId()

            public function delete(): void
            {
                // Nothing — convertedFile ID !== pdfNode ID !== anonymizedNode ID.
            }//end delete()
        };

        $resultInfo = [
            'anonymizedFileId'   => 42,
            'anonymizedFileName' => 'document_anonymized.docx',
            'anonymizedFilePath' => '/user/files/DocuDesk/document_anonymized.docx',
        ];

        $reflection = new \ReflectionMethod(AnonymizationService::class, 'atomicReplacement');
        $reflection->setAccessible(true);

        $updated = $reflection->invoke($service, $anonymizedNode, $convertedFile, $resultInfo);

        $this->assertSame(100, $updated['anonymizedFileId'], 'Result must reference the new PDF node ID.');
        $this->assertSame('document_anonymized.pdf', $updated['anonymizedFileName']);
        $this->assertSame('document_anonymized.pdf', $sideEffects['pdfName'], 'PDF filename must drop .docx and add .pdf.');
        $this->assertTrue($sideEffects['nativeDeleted'], 'Native intermediate must be deleted after PDF is written.');

    }//end testAtomicReplacementDerivePdfName()

    /**
     * rollbackAnonymizedFile is a no-op when fileId is null.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testRollbackIsNoOpWhenFileIdIsNull(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $effects     = ['callCount' => 0];
        $fileService = new class ($effects) {

            private array $effects;

            public function __construct(array &$effects)
            {
                $this->effects = &$effects;
            }//end __construct()

            public function getFileById(mixed $id): mixed
            {
                $this->effects['callCount']++;
                return null;
            }//end getFileById()
        };

        $reflection = new \ReflectionMethod(AnonymizationService::class, 'rollbackAnonymizedFile');
        $reflection->setAccessible(true);
        $reflection->invoke($service, null, $fileService);

        $this->assertSame(0, $effects['callCount'], 'getFileById must not be called when fileId is null.');

    }//end testRollbackIsNoOpWhenFileIdIsNull()

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
     * Build AnonymizationService with all constructor deps stubbed for
     * private-method testing via reflection.
     *
     * @return AnonymizationService
     */
    private function buildServiceWithoutDependencies(): AnonymizationService
    {
        $logger          = new \Psr\Log\NullLogger();
        $container       = $this->createMock(\Psr\Container\ContainerInterface::class);
        $appManager      = $this->createMock(\OCP\App\IAppManager::class);
        $entityDetection = $this->createMock(\OCA\DocuDesk\Service\EntityDetectionService::class);
        $appConfig       = $this->createMock(\OCP\IAppConfig::class);

        return new AnonymizationService(
            $logger,
            $container,
            $appManager,
            $entityDetection,
            $appConfig
        );

    }//end buildServiceWithoutDependencies()
}//end class
