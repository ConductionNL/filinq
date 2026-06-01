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
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
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

// Ensure OCP\Files\Node is loaded so the Folder mock sees getPath() and other inherited methods.
if (interface_exists('OCP\Files\Node', false) === false
    && file_exists('/srv/nextcloud/lib/public/Files/Node.php') === true
) {
    include_once '/srv/nextcloud/lib/public/Files/Node.php';
}

/**
 * Unit tests for OutputLayoutResolver.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OutputLayoutResolverTest extends TestCase
{

    /**
     * The resolver under test.
     *
     * @var OutputLayoutResolver
     */
    private OutputLayoutResolver $resolver;

    /**
     * Mocked IAppConfig.
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * Mocked LoggerInterface.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Create a minimal Folder stub whose getPath() returns $path.
     *
     * @param string $path The absolute NC path.
     *
     * @return Folder&\PHPUnit\Framework\MockObject\MockObject
     */
    private function makeFolderStub(string $path): Folder
    {
        $stub = $this->createMock(Folder::class);
        $stub->method('getPath')->willReturn($path);
        return $stub;

    }//end makeFolderStub()

    /**
     * Set up test environment.
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
     * Clean filename (no suffix) is returned unchanged.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testCleanFilenameNoSuffixStripped(): void
    {
        $this->assertSame('report', $this->resolver->stripAnonymizedSuffix(baseName: 'report'));
        $this->assertSame('my-document', $this->resolver->stripAnonymizedSuffix(baseName: 'my-document'));

    }//end testCleanFilenameNoSuffixStripped()

    /**
     * Trailing `_anonymized` suffix is stripped.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testLegacyAnonymizedSuffixStripped(): void
    {
        $this->assertSame('foo', $this->resolver->stripAnonymizedSuffix(baseName: 'foo_anonymized'));
        $this->assertSame('report', $this->resolver->stripAnonymizedSuffix(baseName: 'report_anonymized'));

    }//end testLegacyAnonymizedSuffixStripped()

    /**
     * `_anonymized` in the middle of a name is preserved.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testAnonymizedMidNamePreserved(): void
    {
        $this->assertSame(
            'foo_anonymized_v2',
            $this->resolver->stripAnonymizedSuffix(baseName: 'foo_anonymized_v2')
        );

    }//end testAnonymizedMidNamePreserved()

    /**
     * Invalid configured subfolder name falls back to default with a warning.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testInvalidSubfolderNameFallsBackToDefault(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn('../traversal');

        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('invalid value'));

        $result = $this->resolver->readSubfolderName();
        $this->assertSame(OutputLayoutResolver::DEFAULT_SUBFOLDER, $result);

    }//end testInvalidSubfolderNameFallsBackToDefault()

    /**
     * Valid configured subfolder name is returned as-is.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testValidSubfolderNameReturned(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn('redacted');

        $this->mockLogger->expects($this->never())->method('warning');

        $result = $this->resolver->readSubfolderName();
        $this->assertSame('redacted', $result);

    }//end testValidSubfolderNameReturned()

    /**
     * resolveBatchDestination returns the expected absolute path.
     *
     * Uses a minimal Folder stub that exposes getPath() to work around the
     * standalone bootstrap not fully loading OCP\Files\Node parent methods.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testResolveBatchDestinationBuildsCorrectPath(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn('anonymised');

        $mockFolder = $this->makeFolderStub('/admin/files/dossier');

        $result = $this->resolver->resolveBatchDestination(
            sourceFolder: $mockFolder,
            sourceBaseName: 'report',
            extension: '.pdf'
        );

        $this->assertSame('/admin/files/dossier/anonymised/report.pdf', $result);

    }//end testResolveBatchDestinationBuildsCorrectPath()

    /**
     * resolveBatchDestination strips trailing _anonymized from source base name.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-9
     */
    public function testResolveBatchDestinationStripsAnonymizedSuffix(): void
    {
        $this->mockAppConfig->method('getValueString')
            ->willReturn('anonymised');

        $mockFolder = $this->makeFolderStub('/admin/files/dossier');

        $result = $this->resolver->resolveBatchDestination(
            sourceFolder: $mockFolder,
            sourceBaseName: 'foo_anonymized',
            extension: '.pdf'
        );

        $this->assertSame('/admin/files/dossier/anonymised/foo.pdf', $result);

    }//end testResolveBatchDestinationStripsAnonymizedSuffix()

    /**
     * isValidSubfolderName rejects empty strings.
     *
     * @return void
     */
    public function testIsValidSubfolderNameRejectsEmpty(): void
    {
        $this->assertFalse($this->resolver->isValidSubfolderName(name: ''));

    }//end testIsValidSubfolderNameRejectsEmpty()

    /**
     * isValidSubfolderName rejects strings with dots, slashes, or spaces.
     *
     * @return void
     */
    public function testIsValidSubfolderNameRejectsDisallowedChars(): void
    {
        $this->assertFalse($this->resolver->isValidSubfolderName(name: '../traversal'));
        $this->assertFalse($this->resolver->isValidSubfolderName(name: 'has space'));
        $this->assertFalse($this->resolver->isValidSubfolderName(name: 'has.dot'));
        $this->assertFalse($this->resolver->isValidSubfolderName(name: 'UPPER'));

    }//end testIsValidSubfolderNameRejectsDisallowedChars()

    /**
     * isValidSubfolderName accepts valid names.
     *
     * @return void
     */
    public function testIsValidSubfolderNameAcceptsValidNames(): void
    {
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'anonymised'));
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'redacted'));
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'output-123'));
        $this->assertTrue($this->resolver->isValidSubfolderName(name: 'my_folder'));

    }//end testIsValidSubfolderNameAcceptsValidNames()
}//end class
