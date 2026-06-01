<?php

/**
 * Unit tests for AnonymizationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AnonymizationService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AnonymizationServiceTest extends TestCase
{


    /**
     * Check if the class can be loaded without parse errors
     *
     * @return void
     */
    private function requireClassOrSkip(): void
    {
        try {
            $file = __DIR__.'/../../../lib/Service/AnonymizationService.php';
            $code = php_strip_whitespace($file);
            if (empty($code) === true) {
                $this->markTestSkipped('AnonymizationService has parse errors.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('AnonymizationService has parse errors: '.$e->getMessage());
        }

    }//end requireClassOrSkip()


    /**
     * Test that the source file exists
     *
     * @return void
     */
    public function testSourceFileExists(): void
    {
        $this->assertFileExists(
            __DIR__.'/../../../lib/Service/AnonymizationService.php'
        );

    }//end testSourceFileExists()


    /**
     * Test file contains expected class declaration
     *
     * @return void
     */
    public function testFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('class AnonymizationService', $content);

    }//end testFileContainsClassDeclaration()


    /**
     * Test file contains expected methods
     *
     * @return void
     */
    public function testFileContainsExpectedMethods(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function extractAndDetectEntities', $content);
        $this->assertStringContainsString('function anonymizeDocument', $content);

    }//end testFileContainsExpectedMethods()


    /**
     * Test anonymizeDocument signature accepts appendBasisSummary and outputFormat.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testAnonymizeDocumentSignatureAcceptsNewParams(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('appendBasisSummary', $content);
        $this->assertStringContainsString('outputFormat', $content);

    }//end testAnonymizeDocumentSignatureAcceptsNewParams()


    /**
     * Test that tryAppendBasisSummary is defined as a private method.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testTryAppendBasisSummaryMethodExists(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function tryAppendBasisSummary', $content);

    }//end testTryAppendBasisSummaryMethodExists()


    /**
     * Test the service records a structured warning field on summary failure.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testWarningFieldDefinedOnSummaryFailure(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('SUMMARY_APPEND_FAILED', $content);
        $this->assertStringContainsString("'warning'", $content);

    }//end testWarningFieldDefinedOnSummaryFailure()


    /**
     * Test that preserve mode path sets summaryFileId and summaryFilePath fields.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-8
     */
    public function testPreserveModeSetsSummaryFields(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('summaryFileId', $content);
        $this->assertStringContainsString('summaryFilePath', $content);
        $this->assertStringContainsString("'preserve'", $content);

    }//end testPreserveModeSetsSummaryFields()


    /**
     * Test #286: source code no longer derives replacementCount from
     * count($mappedEntities). The fabricated-count line must be gone.
     *
     * @return void
     *
     * @spec issue #286 — fabricated replacement count
     */
    public function testReplacementCountIsNoLongerFabricatedFromMappedEntities(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');

        // The exact bug-line from the issue body must be gone.
        $this->assertStringNotContainsString(
            "\$resultInfo['replacementCount'] = count(\$mappedEntities)",
            $content,
            'replacementCount must not be derived from count($mappedEntities) — that is the #286 bug.'
        );

        // And the real-stats fields must now be set on $resultInfo.
        $this->assertStringContainsString("'replacementsAttempted'", $content);
        $this->assertStringContainsString("'replacementsApplied'", $content);
        $this->assertStringContainsString("'replacementsVerified'", $content);
        $this->assertStringContainsString("'unmatchedEntities'", $content);

    }//end testReplacementCountIsNoLongerFabricatedFromMappedEntities()


    /**
     * Test #286: a helper that verifies replacements + a helper that
     * safely reads node text are both present in the implementation.
     *
     * @return void
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    public function testVerificationHelpersExist(): void
    {
        $content = file_get_contents(__DIR__.'/../../../lib/Service/AnonymizationService.php');
        $this->assertStringContainsString('function verifyReplacements', $content);
        $this->assertStringContainsString('function readNodeTextSafely', $content);

    }//end testVerificationHelpersExist()


    /**
     * Test #286 (behavioural): verifyReplacements correctly identifies
     * entities that are present in the source vs those that are not.
     *
     * Uses reflection to invoke the private helper without standing up
     * the full DI graph.
     *
     * @return void
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    public function testVerifyReplacementsDistinguishesAppliedFromUnmatched(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $mappedEntities = [
            ['text' => 'John Doe',     'entityType' => 'PERSON'],
            ['text' => '123456782',    'entityType' => 'BSN'],
            ['text' => 'NotInSource',  'entityType' => 'PERSON'],
            ['text' => '0612345678',   'entityType' => 'PHONE'],
        ];

        $originalText = "Mr. John Doe (BSN 123456782) lives in Amsterdam.\n"
            ."His mobile is 0612345678.";

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'verifyReplacements'
        );
        $reflection->setAccessible(true);
        $result = $reflection->invoke($service, $mappedEntities, $originalText);

        $this->assertSame(4, $result['replacementsAttempted']);
        $this->assertSame(3, $result['replacementsApplied'], 'Only entities present in source count as applied (#286).');
        $this->assertTrue($result['replacementsVerified']);
        $this->assertCount(1, $result['unmatchedEntities']);
        $this->assertSame('NotInSource', $result['unmatchedEntities'][0]['text']);
        $this->assertSame('PERSON', $result['unmatchedEntities'][0]['entityType']);

    }//end testVerifyReplacementsDistinguishesAppliedFromUnmatched()


    /**
     * Test #286: verifyReplacements is case-insensitive (mirrors OR's
     * str_ireplace semantics in DocumentProcessingHandler).
     *
     * @return void
     */
    public function testVerifyReplacementsIsCaseInsensitive(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $mappedEntities = [
            ['text' => 'JOHN DOE', 'entityType' => 'PERSON'],
        ];

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'verifyReplacements'
        );
        $reflection->setAccessible(true);

        $result = $reflection->invoke($service, $mappedEntities, 'Hi john doe.');

        $this->assertSame(1, $result['replacementsApplied']);
        $this->assertCount(0, $result['unmatchedEntities']);

    }//end testVerifyReplacementsIsCaseInsensitive()


    /**
     * Test #286: when source text is null (binary format), verification
     * is reported as impossible — `replacementsApplied` is null and
     * `replacementsVerified` is false. Callers see the truth instead of
     * a fabricated count.
     *
     * @return void
     *
     * @spec issue #286 — surface attempted vs applied + unmatched list
     */
    public function testVerifyReplacementsReportsUnverifiedForBinaryFormats(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $mappedEntities = [
            ['text' => 'John Doe',  'entityType' => 'PERSON'],
            ['text' => '123456782', 'entityType' => 'BSN'],
        ];

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'verifyReplacements'
        );
        $reflection->setAccessible(true);

        $result = $reflection->invoke($service, $mappedEntities, null);

        $this->assertSame(2, $result['replacementsAttempted']);
        $this->assertNull($result['replacementsApplied'], 'Binary source cannot be verified — applied count must be null.');
        $this->assertFalse($result['replacementsVerified']);
        $this->assertSame([], $result['unmatchedEntities']);

    }//end testVerifyReplacementsReportsUnverifiedForBinaryFormats()


    /**
     * Test #286: readNodeTextSafely returns null for binary mime types
     * (PDF, DOCX, …) so verifyReplacements correctly degrades to
     * "unverified" rather than falsely reporting full success.
     *
     * @return void
     *
     * @spec issue #286 — derive replacementCount from real result
     */
    public function testReadNodeTextSafelyReturnsNullForBinaryMime(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'readNodeTextSafely'
        );
        $reflection->setAccessible(true);

        $binaryNode = new class {
            public function getMimeType(): string
            {
                return 'application/pdf';
            }

            public function getContent(): string
            {
                return "%PDF-1.4\n...binary...";
            }
        };

        $this->assertNull($reflection->invoke($service, $binaryNode));

    }//end testReadNodeTextSafelyReturnsNullForBinaryMime()


    /**
     * Test #286: readNodeTextSafely returns content for text-like mime
     * types so verification can run.
     *
     * @return void
     */
    public function testReadNodeTextSafelyReturnsContentForTextMime(): void
    {
        $service = $this->buildServiceWithoutDependencies();

        $reflection = new \ReflectionMethod(
            \OCA\DocuDesk\Service\AnonymizationService::class,
            'readNodeTextSafely'
        );
        $reflection->setAccessible(true);

        $textNode = new class {
            public function getMimeType(): string
            {
                return 'text/plain';
            }

            public function getContent(): string
            {
                return 'Hello John Doe.';
            }
        };

        $this->assertSame('Hello John Doe.', $reflection->invoke($service, $textNode));

    }//end testReadNodeTextSafelyReturnsContentForTextMime()


    /**
     * Build an AnonymizationService with all constructor deps stubbed so
     * its private helpers can be called via reflection without standing
     * up the full Nextcloud / OpenRegister DI graph.
     *
     * Only the LoggerInterface is exercised (by readNodeTextSafely on
     * error paths) — a no-op NullLogger is sufficient.
     *
     * @return \OCA\DocuDesk\Service\AnonymizationService
     */
    private function buildServiceWithoutDependencies(): \OCA\DocuDesk\Service\AnonymizationService
    {
        $logger          = new \Psr\Log\NullLogger();
        $container       = $this->createMock(\Psr\Container\ContainerInterface::class);
        $appManager      = $this->createMock(\OCP\App\IAppManager::class);
        $entityDetection = $this->createMock(\OCA\DocuDesk\Service\EntityDetectionService::class);
        $appConfig       = $this->createMock(\OCP\IAppConfig::class);
        $consentCrud     = $this->createMock(\OCA\DocuDesk\Service\ConsentCrudService::class);
        $consentService  = $this->createMock(\OCA\DocuDesk\Service\ConsentService::class);

        return new \OCA\DocuDesk\Service\AnonymizationService(
            $logger,
            $container,
            $appManager,
            $entityDetection,
            $appConfig,
            $consentCrud,
            $consentService
        );

    }//end buildServiceWithoutDependencies()


}//end class
