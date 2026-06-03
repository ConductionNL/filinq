<?php

/**
 * Unit tests for OutputLayoutResolver
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for OutputLayoutResolver
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress  PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class OutputLayoutResolverTest extends TestCase
{

    /**
     * The OutputLayoutResolver under test
     *
     * @var OutputLayoutResolver
     */
    private OutputLayoutResolver $resolver;

    /**
     * Mocked IAppConfig
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAppConfig = $this->createMock(IAppConfig::class);
        $this->mockLogger    = $this->createMock(LoggerInterface::class);

        $this->resolver = new OutputLayoutResolver(
            appConfig: $this->mockAppConfig,
            logger: $this->mockLogger
        );

    }//end setUp()

    /**
     * Test that a clean filename (no _anonymized suffix) passes through unchanged
     *
     * @return void
     */
    public function testStripAnonymizedSuffixDoesNothingForCleanName(): void
    {
        $result = $this->resolver->stripAnonymizedSuffix(baseName: 'document');
        $this->assertEquals('document', $result);

    }//end testStripAnonymizedSuffixDoesNothingForCleanName()

    /**
     * Test that a trailing _anonymized suffix is stripped
     *
     * @return void
     */
    public function testStripAnonymizedSuffixRemovesTrailingSuffix(): void
    {
        $result = $this->resolver->stripAnonymizedSuffix(baseName: 'document_anonymized');
        $this->assertEquals('document', $result);

    }//end testStripAnonymizedSuffixRemovesTrailingSuffix()

    /**
     * Test that _anonymized mid-name is preserved (only trailing suffix is stripped)
     *
     * @return void
     */
    public function testStripAnonymizedSuffixPreservesMidNameOccurrence(): void
    {
        $result = $this->resolver->stripAnonymizedSuffix(baseName: 'foo_anonymized_v2');
        $this->assertEquals('foo_anonymized_v2', $result);

    }//end testStripAnonymizedSuffixPreservesMidNameOccurrence()

    /**
     * Test that an invalid configured subfolder name falls back to the default
     *
     * @return void
     */
    public function testReadSubfolderNameFallsBackToDefaultOnInvalidConfig(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn('INVALID NAME WITH SPACES!');

        $this->mockLogger->expects($this->once())->method('warning');

        $result = $this->resolver->readSubfolderName();
        $this->assertEquals(OutputLayoutResolver::DEFAULT_SUBFOLDER, $result);

    }//end testReadSubfolderNameFallsBackToDefaultOnInvalidConfig()

    /**
     * Test that a valid configured subfolder name is returned as-is
     *
     * @return void
     */
    public function testReadSubfolderNameReturnsValidConfigValue(): void
    {
        $this->mockAppConfig->method('getValueString')->willReturn('redacted');

        $result = $this->resolver->readSubfolderName();
        $this->assertEquals('redacted', $result);

    }//end testReadSubfolderNameReturnsValidConfigValue()

    /**
     * Test that resolveBatchDestination returns the expected path
     *
     * @return void
     */
    public function testResolveBatchDestinationReturnsExpectedPath(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn(OutputLayoutResolver::DEFAULT_SUBFOLDER);

        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getPath')->willReturn('/alice/files/dossier');

        $result = $this->resolver->resolveBatchDestination(
            sourceFolder: $mockFolder,
            sourceBaseName: 'report',
            extension: '.pdf'
        );

        $this->assertEquals('/alice/files/dossier/anonymised/report.pdf', $result);

    }//end testResolveBatchDestinationReturnsExpectedPath()

    /**
     * Test that resolveBatchDestination strips _anonymized suffix from base name
     *
     * @return void
     */
    public function testResolveBatchDestinationStripsAnonymizedSuffix(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn(OutputLayoutResolver::DEFAULT_SUBFOLDER);

        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getPath')->willReturn('/alice/files/dossier');

        $result = $this->resolver->resolveBatchDestination(
            sourceFolder: $mockFolder,
            sourceBaseName: 'report_anonymized',
            extension: '.pdf'
        );

        $this->assertEquals('/alice/files/dossier/anonymised/report.pdf', $result);

    }//end testResolveBatchDestinationStripsAnonymizedSuffix()

    /**
     * Test hasAnonymizedSuffix returns true for _anonymized files
     *
     * @return void
     */
    public function testHasAnonymizedSuffixReturnsTrueForLegacyFile(): void
    {
        $this->assertTrue($this->resolver->hasAnonymizedSuffix(fileName: 'document_anonymized.pdf'));

    }//end testHasAnonymizedSuffixReturnsTrueForLegacyFile()

    /**
     * Test hasAnonymizedSuffix returns false for clean files
     *
     * @return void
     */
    public function testHasAnonymizedSuffixReturnsFalseForCleanFile(): void
    {
        $this->assertFalse($this->resolver->hasAnonymizedSuffix(fileName: 'document.pdf'));

    }//end testHasAnonymizedSuffixReturnsFalseForCleanFile()

    /**
     * Test hasAnonymizedSuffix returns false for _anonymized mid-name
     *
     * @return void
     */
    public function testHasAnonymizedSuffixReturnsFalseForMidNameOccurrence(): void
    {
        $this->assertFalse($this->resolver->hasAnonymizedSuffix(fileName: 'foo_anonymized_v2.pdf'));

    }//end testHasAnonymizedSuffixReturnsFalseForMidNameOccurrence()

    /**
     * Test isValidSubfolderName returns false for empty string
     *
     * @return void
     */
    public function testIsValidSubfolderNameReturnsFalseForEmpty(): void
    {
        $this->assertFalse($this->resolver->isValidSubfolderName(name: ''));

    }//end testIsValidSubfolderNameReturnsFalseForEmpty()

    /**
     * Test isValidSubfolderName returns false for names with uppercase or spaces
     *
     * @return void
     */
    public function testIsValidSubfolderNameReturnsFalseForInvalidChars(): void
    {
        $this->assertFalse($this->resolver->isValidSubfolderName(name: 'My Folder'));
        $this->assertFalse($this->resolver->isValidSubfolderName(name: 'UPPER'));
        $this->assertFalse($this->resolver->isValidSubfolderName(name: 'has/slash'));

    }//end testIsValidSubfolderNameReturnsFalseForInvalidChars()

    /**
     * Test isValidSubfolderName returns true for valid names
     *
     * @return void
     */
    public function testIsValidSubfolderNameReturnsTrueForValidNames(): void
    {
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'anonymised'));
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'redacted'));
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'my-output'));
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'folder_2'));

    }//end testIsValidSubfolderNameReturnsTrueForValidNames()
}//end class
