<?php

/**
 * Unit tests for SigningProviderFactory
 *
 * Covers provider resolution, active provider selection based on config,
 * and error handling for unknown providers per REQ-SIGN-03.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-signing/tasks.md#2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Signing;

use OCA\DocuDesk\Service\Signing\NativeSigningProvider;
use OCA\DocuDesk\Service\Signing\SigningProviderFactory;
use OCA\DocuDesk\Service\Signing\ValidSignProvider;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for SigningProviderFactory provider resolution
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningProviderFactoryTest extends TestCase
{

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $config;

    /**
     * @var NativeSigningProvider|MockObject
     */
    private NativeSigningProvider|MockObject $nativeProvider;

    /**
     * @var ValidSignProvider|MockObject
     */
    private ValidSignProvider|MockObject $validSignProvider;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(IAppConfig::class);

        $this->nativeProvider = $this->createMock(NativeSigningProvider::class);
        $this->nativeProvider->method('getIdentifier')->willReturn('native');

        $this->validSignProvider = $this->createMock(ValidSignProvider::class);
        $this->validSignProvider->method('getIdentifier')->willReturn('validsign');

    }//end setUp()

    /**
     * Build a fresh SigningProviderFactory for each test.
     *
     * @param string $configuredProvider The provider name stored in app config.
     *
     * @return SigningProviderFactory
     */
    private function buildFactory(string $configuredProvider='native'): SigningProviderFactory
    {
        $this->config->method('getValueString')
            ->with('docudesk', 'signing_provider', 'native')
            ->willReturn($configuredProvider);

        return new SigningProviderFactory(
            config: $this->config,
            nativeProvider: $this->nativeProvider,
            validSignProvider: $this->validSignProvider
        );

    }//end buildFactory()

    /**
     * getActiveProvider() returns the native provider when config is 'native'.
     *
     * @return void
     */
    public function testGetActiveProviderReturnsNativeByDefault(): void
    {
        $factory = $this->buildFactory(configuredProvider: 'native');

        $provider = $factory->getActiveProvider();

        $this->assertSame($this->nativeProvider, $provider);

    }//end testGetActiveProviderReturnsNativeByDefault()

    /**
     * getActiveProvider() returns the validsign provider when configured.
     *
     * @return void
     */
    public function testGetActiveProviderReturnsValidsignWhenConfigured(): void
    {
        $factory = $this->buildFactory(configuredProvider: 'validsign');

        $provider = $factory->getActiveProvider();

        $this->assertSame($this->validSignProvider, $provider);

    }//end testGetActiveProviderReturnsValidsignWhenConfigured()

    /**
     * getActiveProvider() falls back to native for unknown provider names.
     *
     * @return void
     */
    public function testGetActiveProviderFallsBackToNativeForUnknown(): void
    {
        $factory = $this->buildFactory(configuredProvider: 'does-not-exist');

        $provider = $factory->getActiveProvider();

        $this->assertSame($this->nativeProvider, $provider);

    }//end testGetActiveProviderFallsBackToNativeForUnknown()

    /**
     * getProvider('validsign') returns the ValidSign provider instance.
     *
     * @return void
     */
    public function testGetProviderReturnsRequestedProvider(): void
    {
        $factory = $this->buildFactory();

        $provider = $factory->getProvider(identifier: 'validsign');

        $this->assertSame($this->validSignProvider, $provider);

    }//end testGetProviderReturnsRequestedProvider()

    /**
     * getProvider() throws RuntimeException for unknown identifiers.
     *
     * @return void
     */
    public function testGetProviderThrowsForUnknownIdentifier(): void
    {
        $factory = $this->buildFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Signing provider not available');

        $factory->getProvider(identifier: 'docusign');

    }//end testGetProviderThrowsForUnknownIdentifier()

    /**
     * getAvailableProviders() lists both registered providers.
     *
     * @return void
     */
    public function testGetAvailableProvidersListsBothProviders(): void
    {
        $factory = $this->buildFactory();

        $providers = $factory->getAvailableProviders();

        $this->assertContains('native', $providers);
        $this->assertContains('validsign', $providers);
        $this->assertCount(2, $providers);

    }//end testGetAvailableProvidersListsBothProviders()
}//end class
