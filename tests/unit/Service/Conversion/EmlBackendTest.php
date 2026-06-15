<?php

/**
 * Unit tests for EmlBackend (the EML→PDF cascade entry point).
 *
 * Exercises:
 *   - name() returns 'eml'.
 *   - canHandle('message/rfc822' / .eml) returns true; other formats false.
 *   - isAvailable() returns false when the tenant flag is 'false'.
 *   - isAvailable() returns false when OR's TextExtractionService is
 *     not present on the classpath (default unit-test environment).
 *   - convert() throws ConversionFailedException when the assembly
 *     service cannot resolve the OR service.
 *
 * Verifying the full happy path requires a running OR (or a heavier
 * dependency stub); we cover it from the assembly side instead in
 * `EmlPdfAssemblyServiceTest`.
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EmlBackendTest extends TestCase
{


    /**
     * @var EmlPdfAssemblyService|MockObject
     */
    private EmlPdfAssemblyService|MockObject $assemblyService;


    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $appConfig;


    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;


    private EmlBackend $backend;


    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->assemblyService = $this->createMock(EmlPdfAssemblyService::class);
        $this->appConfig       = $this->createMock(IAppConfig::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->appConfig->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default='') {
                return $default;
            }
        );

        $this->backend = new EmlBackend(
            assemblyService: $this->assemblyService,
            appConfig: $this->appConfig,
            logger: $this->logger
        );

    }//end setUp()


    /**
     * @return void
     */
    public function testNameIsEml(): void
    {
        self::assertSame('eml', $this->backend->name());

    }//end testNameIsEml()


    /**
     * @return void
     */
    public function testCanHandleEmlByMime(): void
    {
        self::assertTrue($this->backend->canHandle('message/rfc822', 'eml'));

    }//end testCanHandleEmlByMime()


    /**
     * @return void
     */
    public function testCanHandleEmlByExtensionOnly(): void
    {
        self::assertTrue($this->backend->canHandle('application/octet-stream', 'eml'));

    }//end testCanHandleEmlByExtensionOnly()


    /**
     * @return void
     */
    public function testCanHandleRejectsNonEml(): void
    {
        self::assertFalse($this->backend->canHandle('application/pdf', 'pdf'));
        self::assertFalse($this->backend->canHandle('text/html', 'html'));

    }//end testCanHandleRejectsNonEml()


    /**
     * @return void
     */
    public function testIsAvailableReturnsFalseWhenTenantFlagDisabled(): void
    {
        $appConfig = $this->createMock(IAppConfig::class);
        $appConfig->method('getValueString')->willReturn('false');

        $backend = new EmlBackend(
            assemblyService: $this->assemblyService,
            appConfig: $appConfig,
            logger: $this->logger
        );

        self::assertFalse($backend->isAvailable());

    }//end testIsAvailableReturnsFalseWhenTenantFlagDisabled()


    /**
     * @return void
     */
    public function testIsAvailableReturnsFalseWhenORClassMissing(): void
    {
        // In unit-test context OR's TextExtractionService is not on
        // the classpath, so isAvailable must report false even with
        // the tenant flag enabled (which is the default).
        self::assertFalse($this->backend->isAvailable());

    }//end testIsAvailableReturnsFalseWhenORClassMissing()


    /**
     * @return void
     */
    public function testConvertThrowsConversionFailedWhenORServiceUnresolved(): void
    {
        $this->assemblyService
            ->method('resolveTextExtractionService')
            ->willReturn(null);

        $source = $this->createMock(File::class);
        $source->method('getPath')->willReturn('/admin/files/email.eml');

        $this->expectException(ConversionFailedException::class);
        $this->backend->convert($source);

    }//end testConvertThrowsConversionFailedWhenORServiceUnresolved()


    /**
     * @return void
     */
    public function testConvertReturnsNewFileOnHappyPath(): void
    {
        // Stub OR service: a stdClass with parseEmlStructured returning
        // a minimal structure.
        $structure = new \stdClass();
        $structure->headers = ['from' => 'a@b'];
        $structure->body = new \stdClass();
        $structure->body->plainText = 'hi';
        $structure->body->html = null;
        $structure->attachments = [];

        $orService = new class($structure) {
            private $structure;
            public function __construct($s)
            {
                $this->structure = $s;
            }
            public function parseEmlStructured($file)
            {
                return $this->structure;
            }
        };

        $this->assemblyService
            ->method('resolveTextExtractionService')
            ->willReturn($orService);

        $this->assemblyService
            ->method('assemble')
            ->willReturn('%PDF-BINARY');

        $newFile = $this->createMock(File::class);
        $parent  = $this->createMock(\OCP\Files\Folder::class);
        $parent->method('nodeExists')->willReturn(false);
        $parent->method('newFile')->willReturn($newFile);

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('message.eml');
        $source->method('getParent')->willReturn($parent);

        $result = $this->backend->convert($source);
        self::assertSame($newFile, $result);

    }//end testConvertReturnsNewFileOnHappyPath()


    /**
     * @return void
     */
    public function testConvertWrapsParseFailureInConversionFailed(): void
    {
        $orService = new class {
            public function parseEmlStructured($file)
            {
                throw new \RuntimeException('bogus eml');
            }
        };

        $this->assemblyService
            ->method('resolveTextExtractionService')
            ->willReturn($orService);

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('bad.eml');
        $source->method('getPath')->willReturn('/x/bad.eml');

        $this->expectException(ConversionFailedException::class);
        $this->backend->convert($source);

    }//end testConvertWrapsParseFailureInConversionFailed()


    /**
     * @return void
     */
    public function testConvertWrapsAssemblyFailureInConversionFailed(): void
    {
        $structure = new \stdClass();
        $orService = new class($structure) {
            private $s;
            public function __construct($s)
            {
                $this->s = $s;
            }
            public function parseEmlStructured($file)
            {
                return $this->s;
            }
        };

        $this->assemblyService
            ->method('resolveTextExtractionService')
            ->willReturn($orService);
        $this->assemblyService
            ->method('assemble')
            ->willThrowException(new \RuntimeException('assemble kaput'));

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('boom.eml');
        $source->method('getPath')->willReturn('/x/boom.eml');

        $this->expectException(ConversionFailedException::class);
        $this->backend->convert($source);

    }//end testConvertWrapsAssemblyFailureInConversionFailed()


    /**
     * @return void
     */
    public function testConvertOverwritesExistingPdfBesideSource(): void
    {
        $structure = new \stdClass();
        $structure->headers = [];
        $structure->body = new \stdClass();
        $structure->body->plainText = null;
        $structure->body->html = null;
        $structure->attachments = [];

        $orService = new class($structure) {
            private $s;
            public function __construct($s)
            {
                $this->s = $s;
            }
            public function parseEmlStructured($file)
            {
                return $this->s;
            }
        };

        $this->assemblyService
            ->method('resolveTextExtractionService')
            ->willReturn($orService);
        $this->assemblyService
            ->method('assemble')
            ->willReturn('%PDF-1.4');

        $existing = $this->createMock(File::class);
        $existing->expects(self::once())->method('delete');

        $parent = $this->createMock(\OCP\Files\Folder::class);
        $parent->method('nodeExists')->willReturn(true);
        $parent->method('get')->willReturn($existing);
        $newFile = $this->createMock(File::class);
        $parent->method('newFile')->willReturn($newFile);

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('replace.eml');
        $source->method('getParent')->willReturn($parent);

        $result = $this->backend->convert($source);
        self::assertSame($newFile, $result);

    }//end testConvertOverwritesExistingPdfBesideSource()
}//end class
