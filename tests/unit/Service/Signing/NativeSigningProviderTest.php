<?php

/**
 * Unit tests for NativeSigningProvider — wave-9 C1 mitigation coverage.
 *
 * Verifies that `initiateSigning` and `downloadSignedDocument` throw
 * a descriptive RuntimeException referencing issue #304 until the full
 * request↔provider wiring ships.  Also guards the `checkStatus` not-found
 * path retained from finding #287 regression coverage.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Signing;

use OCA\DocuDesk\Service\Signing\NativeSigningProvider;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for NativeSigningProvider C1 mitigation (issue #304)
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NativeSigningProviderTest extends TestCase
{
    /**
     * Build a minimal NativeSigningProvider for testing the guard paths.
     *
     * @return NativeSigningProvider
     */
    private function buildProvider(): NativeSigningProvider
    {
        $logger = $this->createMock(originalClassName: LoggerInterface::class);

        $config = $this->createMock(originalClassName: IAppConfig::class);
        $config->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default=''): string {
                return $default;
            }
        );

        $objectService = $this->getMockBuilder(className: ObjectService::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['findAll', 'saveObject'])
            ->getMock();

        $objectService->method('findAll')->willReturn([]);

        $settingsService = $this->createMock(originalClassName: SettingsService::class);
        $settingsService->method('getObjectService')->willReturn($objectService);

        return new NativeSigningProvider(
            logger: $logger,
            settingsService: $settingsService,
            config: $config
        );

    }//end buildProvider()

    /**
     * Reset test state before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

    }//end setUp()

    /**
     * C1 mitigation (issue #304): initiateSigning throws immediately with a
     * descriptive error because the signing pipeline is not yet wired.
     * Admins who enable signing_enabled=1 see the gap at once.
     *
     * @return void
     */
    public function testInitiateThrowsBecausePipelineNotIntegrated(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(exception: RuntimeException::class);
        $this->expectExceptionMessage(message: 'ConductionNL/docudesk#304');

        $provider->initiateSigning(
            documentPath: '/foo.pdf',
            documentName: 'foo.pdf',
            signers: [['userId' => 'alice']],
            level: 'SES'
        );

    }//end testInitiateThrowsBecausePipelineNotIntegrated()

    /**
     * C1 mitigation (issue #304): downloadSignedDocument throws immediately
     * with a descriptive error because no session can ever reach 'completed'
     * while the pipeline is not yet wired.
     *
     * @return void
     */
    public function testDownloadThrowsBecausePipelineNotIntegrated(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(exception: RuntimeException::class);
        $this->expectExceptionMessage(message: 'ConductionNL/docudesk#304');

        $provider->downloadSignedDocument(externalId: 'native-any-id');

    }//end testDownloadThrowsBecausePipelineNotIntegrated()

    /**
     * CheckStatus on an unknown externalId throws (not silently returns).
     *
     * @return void
     */
    public function testCheckStatusOnMissingSessionThrows(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(exception: RuntimeException::class);
        $provider->checkStatus(externalId: 'native-does-not-exist');

    }//end testCheckStatusOnMissingSessionThrows()
}//end class
