<?php
/**
 * Unit tests for register/schema binding resolution and its fail-closed arm.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
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
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Exception\RegisterNotConfiguredException;
use OCA\DocuDesk\Service\GrondslagProposalService;
use OCA\DocuDesk\Service\OcrService;
use OCA\DocuDesk\Service\OpenRegisterAvailabilityService;
use OCA\DocuDesk\Service\OpenRegisterResolver;
use OCA\DocuDesk\Service\RegisterDiscoveryService;
use OCA\DocuDesk\Service\SettingsInitializer;
use OCA\DocuDesk\Service\SettingsService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * An administrator binds each register/schema pair in the admin settings UI and
 * nothing auto-provisions them. Before this behaviour existed, every consumer
 * read the pair inline with an empty-string default and passed the result
 * straight into saveObject()/find() — so an unbound instance wrote signing
 * requests, financial extractions and GL bookings into register '' and schema
 * '' and carried on, silently.
 *
 * The behaviour is split in two, and each half is tested here:
 *
 *   SettingsService::resolve*Binding()  — READS the pair, returns null when
 *                                         either half is unset.
 *   OpenRegisterResolver::get*()        — turns that null into
 *                                         RegisterNotConfiguredException.
 *
 * Both halves matter. A test that only proved the happy path would pass just as
 * well against the old code, which is the failure mode this whole change exists
 * to remove: the unconfigured arm is the one that was never exercised.
 *
 * @psalm-suppress  PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class RegisterBindingResolutionTest extends TestCase
{

    /**
     * Config double driving the binding reads.
     *
     * @var IAppConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * Service under test.
     *
     * @var SettingsService
     */
    private SettingsService $settings;

    /**
     * Build a SettingsService whose config returns the supplied values.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config   = $this->createMock(IAppConfig::class);
        $this->settings = new SettingsService(
            $this->config,
            $this->createMock(LoggerInterface::class),
            $this->createMock(RegisterDiscoveryService::class),
            $this->createMock(SettingsInitializer::class),
            $this->createMock(OcrService::class),
            $this->createMock(GrondslagProposalService::class),
            $this->createMock(OpenRegisterAvailabilityService::class)
        );

    }//end setUp()

    /**
     * Make every config key resolve to `$value`, except those in `$blank`.
     *
     * @param string        $value Value returned for a configured key.
     * @param array<string> $blank Keys that must come back empty.
     *
     * @return void
     */
    private function withConfig(string $value, array $blank=[]): void
    {
        $this->config->method('getValueString')->willReturnCallback(
            static function (string $app, string $key, string $default='') use ($value, $blank): string {
                if (in_array($key, $blank, true) === true) {
                    return '';
                }

                return $value;
            }
        );

    }//end withConfig()

    /**
     * Every accessor returns the configured pair.
     *
     * @param string $method    Accessor under test.
     * @param string $keyPrefix Config key prefix it reads.
     *
     * @return void
     *
     * @dataProvider bindingProvider
     */
    public function testAccessorReturnsTheConfiguredPair(string $method, string $keyPrefix): void
    {
        $this->withConfig('bound');

        $this->assertSame(
            ['register' => 'bound', 'schema' => 'bound'],
            $this->settings->$method(),
            $method.'() must return the configured register/schema pair.'
        );

    }//end testAccessorReturnsTheConfiguredPair()

    /**
     * An unset REGISTER half yields null, never a pair containing ''.
     *
     * @param string $method    Accessor under test.
     * @param string $keyPrefix Config key prefix it reads.
     *
     * @return void
     *
     * @dataProvider bindingProvider
     */
    public function testAccessorReturnsNullWhenTheRegisterIsUnset(string $method, string $keyPrefix): void
    {
        $this->withConfig('bound', [$keyPrefix.'_register']);

        $this->assertNull(
            $this->settings->$method(),
            $method.'() must return null when the register half is unset — returning '
            ."['register' => '', ...] is what silently wrote to register ''."
        );

    }//end testAccessorReturnsNullWhenTheRegisterIsUnset()

    /**
     * An unset SCHEMA half yields null too — both halves are required.
     *
     * @param string $method    Accessor under test.
     * @param string $keyPrefix Config key prefix it reads.
     *
     * @return void
     *
     * @dataProvider bindingProvider
     */
    public function testAccessorReturnsNullWhenTheSchemaIsUnset(string $method, string $keyPrefix): void
    {
        $this->withConfig('bound', [$keyPrefix.'_schema']);

        $this->assertNull(
            $this->settings->$method(),
            $method.'() must return null when the schema half is unset.'
        );

    }//end testAccessorReturnsNullWhenTheSchemaIsUnset()

    /**
     * The resolver returns the pair when it is configured.
     *
     * @param string $settingsMethod Accessor on SettingsService.
     * @param string $resolverMethod Accessor on OpenRegisterResolver.
     *
     * @return void
     *
     * @dataProvider resolverProvider
     */
    public function testResolverReturnsTheBindingWhenConfigured(
        string $settingsMethod,
        string $resolverMethod
    ): void {
        $settings = $this->createMock(SettingsService::class);
        $settings->method($settingsMethod)->willReturn(['register' => 'r', 'schema' => 's']);

        $resolver = new OpenRegisterResolver(settingsService: $settings);

        $this->assertSame(['register' => 'r', 'schema' => 's'], $resolver->$resolverMethod());

    }//end testResolverReturnsTheBindingWhenConfigured()

    /**
     * The resolver FAILS CLOSED when the binding is unset.
     *
     * This is the arm that never ran before: the old code carried '' forward
     * into OpenRegister and the write went nowhere.
     *
     * @param string $settingsMethod Accessor on SettingsService.
     * @param string $resolverMethod Accessor on OpenRegisterResolver.
     *
     * @return void
     *
     * @dataProvider resolverProvider
     */
    public function testResolverThrowsWhenTheBindingIsUnset(
        string $settingsMethod,
        string $resolverMethod
    ): void {
        $settings = $this->createMock(SettingsService::class);
        $settings->method($settingsMethod)->willReturn(null);

        $resolver = new OpenRegisterResolver(settingsService: $settings);

        $this->expectException(RegisterNotConfiguredException::class);
        $resolver->$resolverMethod();

    }//end testResolverThrowsWhenTheBindingIsUnset()

    /**
     * Accessor name paired with the config key prefix it reads.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function bindingProvider(): array
    {
        return [
            'signingRequest'       => ['resolveSigningRequestBinding', 'signingRequest'],
            'signerRecord'         => ['resolveSignerRecordBinding', 'signerRecord'],
            'financialExtraction'  => ['resolveFinancialExtractionBinding', 'financialExtraction'],
            'glAccountBooking'     => ['resolveGlAccountBookingBinding', 'glAccountBooking'],
            'glAccountMappingRule' => ['resolveGlAccountMappingRuleBinding', 'glAccountMappingRule'],
        ];

    }//end bindingProvider()

    /**
     * SettingsService accessor paired with the resolver method that wraps it.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function resolverProvider(): array
    {
        return [
            'financialExtraction'  => [
                'resolveFinancialExtractionBinding',
                'getFinancialExtractionRegisterAndSchema',
            ],
            'glAccountBooking'     => [
                'resolveGlAccountBookingBinding',
                'getGlAccountBookingRegisterAndSchema',
            ],
            'glAccountMappingRule' => [
                'resolveGlAccountMappingRuleBinding',
                'getGlAccountMappingRuleRegisterAndSchema',
            ],
            'signerRecord'         => [
                'resolveSignerRecordBinding',
                'getSignerRecordRegisterAndSchema',
            ],
        ];

    }//end resolverProvider()
}//end class
