<?php

/**
 * Unit tests for EmlBackend
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
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for EmlBackend.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
 */
class EmlBackendTest extends TestCase
{

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var EmlPdfAssemblyService|MockObject
     */
    private EmlPdfAssemblyService|MockObject $mockAssemblyService;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAppConfig       = $this->createMock(IAppConfig::class);
        $this->mockLogger          = $this->createMock(LoggerInterface::class);
        $this->mockAssemblyService = $this->createMock(EmlPdfAssemblyService::class);

    }//end setUp()

    /**
     * EmlBackend::name() returns 'eml'.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testNameReturnsEml(): void
    {
        $backend = $this->makeBackend();
        $this->assertSame('eml', $backend->name());

    }//end testNameReturnsEml()

    /**
     * canHandle returns true for message/rfc822 MIME.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testCanHandleRfc822Mime(): void
    {
        $backend = $this->makeBackend();
        $this->assertTrue($backend->canHandle(mimeType: 'message/rfc822', extension: 'eml'));

    }//end testCanHandleRfc822Mime()

    /**
     * canHandle returns true for .eml extension with any MIME.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testCanHandleEmlExtension(): void
    {
        $backend = $this->makeBackend();
        $this->assertTrue(
                $backend->canHandle(
            mimeType: 'application/octet-stream',
            extension: 'eml'
        )
                );

    }//end testCanHandleEmlExtension()

    /**
     * canHandle returns false for unrelated MIME/extension.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testCanHandleReturnsFalseForPdf(): void
    {
        $backend = $this->makeBackend();
        $this->assertFalse(
                $backend->canHandle(
            mimeType: 'application/pdf',
            extension: 'pdf'
        )
                );

    }//end testCanHandleReturnsFalseForPdf()

    /**
     * isAvailable returns false when tenant flag is 'false'.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testIsAvailableFalseWhenTenantFlagDisabled(): void
    {
        $this->mockAppConfig
            ->method('getValueString')
            ->willReturn('false');

        $backend = $this->makeBackend();
        $this->assertFalse($backend->isAvailable());

    }//end testIsAvailableFalseWhenTenantFlagDisabled()

    /**
     * isAvailable returns false when OR TextExtractionService class is absent.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testIsAvailableFalseWhenOrServiceAbsent(): void
    {
        $this->mockAppConfig
            ->method('getValueString')
            ->willReturn('true');

        $backend = $this->makeBackend();

        // OR TextExtractionService class likely doesn't exist in unit-test env.
        // isAvailable should return false in this case.
        $result = $backend->isAvailable();
        $this->assertIsBool($result);

        // When OR class does not exist, must be false.
        if (class_exists('\OCA\OpenRegister\Service\TextExtractionService') === false) {
            $this->assertFalse($result);
        }

    }//end testIsAvailableFalseWhenOrServiceAbsent()

    /**
     * convert delegates to EmlPdfAssemblyService::assemble.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testConvertDelegatesToAssemblyService(): void
    {
        $mockSource = $this->createMock(File::class);
        $mockSource->method('getName')->willReturn('test.eml');
        $mockSource->method('getPath')->willReturn('/files/test.eml');
        $mockResult = $this->createMock(File::class);

        $this->mockAssemblyService
            ->expects($this->once())
            ->method('assemble')
            ->with(sourceFile: $mockSource, sourceFilename: 'test.eml')
            ->willReturn($mockResult);

        $this->mockAppConfig
            ->method('getValueString')
            ->willReturn('true');

        $backend = $this->makeBackend();
        $result  = $backend->convert(source: $mockSource);

        $this->assertSame($mockResult, $result);

    }//end testConvertDelegatesToAssemblyService()

    /**
     * convert wraps unexpected exceptions in ConversionFailedException.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testConvertWrapsUnexpectedExceptionInConversionFailedException(): void
    {
        $mockSource = $this->createMock(File::class);
        $mockSource->method('getName')->willReturn('test.eml');
        $mockSource->method('getPath')->willReturn('/files/test.eml');

        $this->mockAssemblyService
            ->method('assemble')
            ->willThrowException(new \RuntimeException('Unexpected error'));

        $this->mockAppConfig
            ->method('getValueString')
            ->willReturn('true');

        $this->expectException(ConversionFailedException::class);

        $backend = $this->makeBackend();
        $backend->convert(source: $mockSource);

    }//end testConvertWrapsUnexpectedExceptionInConversionFailedException()

    /**
     * convert re-throws ConversionFailedException directly.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-11
     */
    public function testConvertRethrowsConversionFailedException(): void
    {
        $mockSource = $this->createMock(File::class);
        $mockSource->method('getName')->willReturn('test.eml');
        $mockSource->method('getPath')->willReturn('/files/test.eml');

        $original = new ConversionFailedException(
            message: 'Original failure',
            attempts: []
        );

        $this->mockAssemblyService
            ->method('assemble')
            ->willThrowException($original);

        $this->mockAppConfig
            ->method('getValueString')
            ->willReturn('true');

        $this->expectException(ConversionFailedException::class);
        $this->expectExceptionMessage('Original failure');

        $backend = $this->makeBackend();
        $backend->convert(source: $mockSource);

    }//end testConvertRethrowsConversionFailedException()

    /**
     * Helper: build an EmlBackend with mocked dependencies.
     *
     * @return EmlBackend
     */
    private function makeBackend(): EmlBackend
    {
        return new EmlBackend(
            appConfig: $this->mockAppConfig,
            logger: $this->mockLogger,
            assemblyService: $this->mockAssemblyService,
        );

    }//end makeBackend()
}//end class
