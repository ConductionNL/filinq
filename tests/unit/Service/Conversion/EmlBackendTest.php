<?php

/**
 * Unit tests for EmlBackend
 *
 * Covers: isAvailable() reflecting both dependencies (tenant flag,
 * OpenRegister installed + anonymize-EML API present, assembly service);
 * canHandle() claiming message/rfc822; convert() calling OR's anonymise-EML
 * API (NOT parseEmlStructured) and delegating to the assembly service; and
 * OR API failure surfacing as ConversionFailedException with no raw-parse
 * fallback.
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
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCA\DocuDesk\Service\EmlPdfAssemblyService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * A test double exposing anonymizeEmlStructured(), mimicking OR's FileService
 * surface so the backend's reflection / method_exists checks pass.
 */
class FakeOrFileService
{
    /** @var mixed */
    public mixed $toReturn = null;

    /** @var \Throwable|null */
    public ?\Throwable $toThrow = null;

    /** @var bool */
    public bool $called = false;


    /**
     * Mimic OR's anonymise-EML entry point.
     *
     * @param mixed  $node       File node.
     * @param array  $entities   Entities.
     * @param string $scope      Scope.
     * @param mixed  $dossierKey Dossier key.
     *
     * @return mixed
     */
    public function anonymizeEmlStructured(mixed $node, array $entities, string $scope='document', mixed $dossierKey=null): mixed
    {
        $this->called = true;
        if ($this->toThrow !== null) {
            throw $this->toThrow;
        }

        return $this->toReturn;
    }
}

/**
 * A test double WITHOUT the anonymise-EML method — isAvailable() must be false.
 */
class FakeOrFileServiceWithoutApi
{
}

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmlBackendTest extends TestCase
{

    /**
     * @var IAppConfig&MockObject
     */
    private IAppConfig $appConfig;

    /**
     * @var IAppManager&MockObject
     */
    private IAppManager $appManager;

    /**
     * @var ContainerInterface&MockObject
     */
    private ContainerInterface $container;

    /**
     * @var EmlPdfAssemblyService&MockObject
     */
    private EmlPdfAssemblyService $assembly;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;


    /**
     * Set up common mocks.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->appConfig  = $this->createMock(IAppConfig::class);
        $this->appManager = $this->createMock(IAppManager::class);
        $this->container  = $this->createMock(ContainerInterface::class);
        $this->assembly   = $this->createMock(EmlPdfAssemblyService::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

    }//end setUp()


    /**
     * Build the backend under test.
     *
     * @return EmlBackend
     */
    private function backend(): EmlBackend
    {
        return new EmlBackend(
            $this->appConfig,
            $this->appManager,
            $this->container,
            $this->assembly,
            $this->logger
        );

    }//end backend()


    /**
     * name() is the stable 'eml' identifier.
     *
     * @return void
     */
    public function testNameIsEml(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $this->assertSame('eml', $this->backend()->name());

    }//end testNameIsEml()


    /**
     * canHandle() claims message/rfc822 and the .eml extension.
     *
     * @return void
     */
    public function testCanHandleEml(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $backend = $this->backend();
        $this->assertTrue($backend->canHandle('message/rfc822', ''));
        $this->assertTrue($backend->canHandle('application/octet-stream', 'eml'));
        $this->assertFalse($backend->canHandle('application/pdf', 'pdf'));

    }//end testCanHandleEml()


    /**
     * isAvailable() is true when the flag is set, OR is installed, the
     * anonymise-EML API is present, and the assembly service is wired.
     *
     * @return void
     */
    public function testIsAvailableWhenBothDepsPresent(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $this->appManager->method('getInstalledApps')->willReturn(['openregister', 'docudesk']);
        $this->container->method('get')->willReturn(new FakeOrFileService());

        $this->assertTrue($this->backend()->isAvailable());

    }//end testIsAvailableWhenBothDepsPresent()


    /**
     * isAvailable() is false when the OR anonymise-EML API is absent.
     *
     * @return void
     */
    public function testIsUnavailableWhenOrApiAbsent(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $this->appManager->method('getInstalledApps')->willReturn(['openregister']);
        $this->container->method('get')->willReturn(new FakeOrFileServiceWithoutApi());

        $this->assertFalse($this->backend()->isAvailable());

    }//end testIsUnavailableWhenOrApiAbsent()


    /**
     * isAvailable() is false when OpenRegister is not installed.
     *
     * @return void
     */
    public function testIsUnavailableWhenOrNotInstalled(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $this->appManager->method('getInstalledApps')->willReturn(['docudesk']);

        $this->assertFalse($this->backend()->isAvailable());

    }//end testIsUnavailableWhenOrNotInstalled()


    /**
     * isAvailable() is false when the tenant flag is explicitly disabled.
     *
     * @return void
     */
    public function testIsUnavailableWhenFlagDisabled(): void
    {
        $this->appConfig->method('getValueString')->willReturn('false');
        // OR/container should not even be consulted, but tolerate if they are.
        $this->assertFalse($this->backend()->isAvailable());

    }//end testIsUnavailableWhenFlagDisabled()


    /**
     * convert() calls OR's anonymise-EML API (NOT parseEmlStructured) and
     * delegates to the assembly service, writing the PDF beside the source.
     *
     * @return void
     */
    public function testConvertCallsOrApiAndDelegatesToAssembly(): void
    {
        $structure = (object) ['headers' => [], 'body' => (object) ['plain' => null, 'html' => null], 'attachments' => [], 'inlineImages' => []];

        $or           = new FakeOrFileService();
        $or->toReturn = $structure;
        $this->container->method('get')->willReturn($or);

        $this->assembly->expects($this->once())
            ->method('assemble')
            ->with($structure, 'message.eml')
            ->willReturn('%PDF-1.4 fake');

        $outFile = $this->createMock(File::class);
        $parent  = $this->createMock(Folder::class);
        $parent->method('nodeExists')->willReturn(false);
        $parent->expects($this->once())
            ->method('newFile')
            ->with('message_anonymized.pdf', '%PDF-1.4 fake')
            ->willReturn($outFile);

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('message.eml');
        $source->method('getParent')->willReturn($parent);

        $result = $this->backend()->convert($source);

        $this->assertSame($outFile, $result);
        $this->assertTrue($or->called, 'OR anonymise-EML API must be called');

    }//end testConvertCallsOrApiAndDelegatesToAssembly()


    /**
     * OR API failure surfaces as ConversionFailedException with NO raw-parse
     * fallback (the assembly service is never invoked).
     *
     * @return void
     */
    public function testConvertOrFailureThrowsNoFallback(): void
    {
        $or          = new FakeOrFileService();
        $or->toThrow = new RuntimeException('OR exploded');
        $this->container->method('get')->willReturn($or);

        $this->assembly->expects($this->never())->method('assemble');

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('message.eml');
        $source->method('getPath')->willReturn('/u/admin/message.eml');

        $this->expectException(ConversionFailedException::class);
        $this->backend()->convert($source);

    }//end testConvertOrFailureThrowsNoFallback()


    /**
     * When OR returns a non-object, convert() throws ConversionFailedException
     * rather than passing junk to the assembler.
     *
     * @return void
     */
    public function testConvertNonObjectResultThrows(): void
    {
        $or           = new FakeOrFileService();
        $or->toReturn = null;
        $this->container->method('get')->willReturn($or);

        $this->assembly->expects($this->never())->method('assemble');

        $source = $this->createMock(File::class);
        $source->method('getName')->willReturn('message.eml');
        $source->method('getPath')->willReturn('/u/admin/message.eml');

        $this->expectException(ConversionFailedException::class);
        $this->backend()->convert($source);

    }//end testConvertNonObjectResultThrows()


}//end class
