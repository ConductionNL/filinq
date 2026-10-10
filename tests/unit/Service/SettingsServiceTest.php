<?php

/**
 * Unit tests for SettingsService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\LegalBasisProposalService;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\OpenRegisterAvailabilityService;
use OCA\Filinq\Service\RegisterDiscoveryService;
use OCA\Filinq\Service\SettingsInitializer;
use OCA\Filinq\Service\SettingsService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for SettingsService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SettingsServiceTest extends TestCase {

	/**
	 * @var SettingsService
	 */
	private SettingsService $settingsService;

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $mockConfig;

	/**
	 * @var OpenRegisterAvailabilityService|MockObject
	 */
	private OpenRegisterAvailabilityService|MockObject $mockOpenRegister;

	/**
	 * @var LoggerInterface|MockObject
	 */
	private LoggerInterface|MockObject $mockLogger;

	/**
	 * @var RegisterDiscoveryService|MockObject
	 */
	private RegisterDiscoveryService|MockObject $mockDiscoveryService;

	/**
	 * @var SettingsInitializer|MockObject
	 */
	private SettingsInitializer|MockObject $mockInitializer;

	/**
	 * @var OcrService|MockObject
	 */
	private OcrService|MockObject $mockOcrService;

	/**
	 * @var LegalBasisProposalService|MockObject
	 */
	private LegalBasisProposalService|MockObject $mockBasisProposal;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockConfig = $this->createMock(IAppConfig::class);
		$this->mockLogger = $this->createMock(LoggerInterface::class);
		$this->mockDiscoveryService = $this->createMock(RegisterDiscoveryService::class);
		$this->mockInitializer = $this->createMock(SettingsInitializer::class);
		$this->mockOcrService = $this->createMock(OcrService::class);
		$this->mockBasisProposal = $this->createMock(LegalBasisProposalService::class);
		$this->mockOpenRegister = $this->createMock(OpenRegisterAvailabilityService::class);

		$this->settingsService = new SettingsService(
			$this->mockConfig,
			$this->mockLogger,
			$this->mockDiscoveryService,
			$this->mockInitializer,
			$this->mockOcrService,
			$this->mockBasisProposal,
			$this->mockOpenRegister
		);

	}//end setUp()

	/**
	 * Test getObjectService throws when OpenRegister not installed
	 *
	 * @return void
	 */
	public function testGetObjectServiceThrowsWhenNotInstalled(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister service is not available');

		$this->mockOpenRegister->method('getObjectService')
			->willThrowException(new RuntimeException('OpenRegister service is not available.'));

		$this->settingsService->getObjectService();

	}//end testGetObjectServiceThrowsWhenNotInstalled()

	/**
	 * Test initialize delegates to SettingsInitializer
	 *
	 * @return void
	 */
	public function testInitializeDelegatesToInitializer(): void {
		$expected = ['registers' => 1, 'schemas' => 2];
		$this->mockInitializer->method('initialize')
			->willReturn($expected);

		$result = $this->settingsService->initialize();
		$this->assertEquals($expected, $result);

	}//end testInitializeDelegatesToInitializer()

	/**
	 * Test getAllSettings returns expected structure including ocrStatus
	 *
	 * @return void
	 *
	 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.3
	 */
	public function testGetAllSettingsReturnsExpectedStructure(): void {
		$this->mockOpenRegister->method('isInstalled')
			->willReturn(false);

		$this->mockDiscoveryService->method('loadObjectTypeConfiguration')
			->willReturn(['publicationConsent_register' => '', 'publicationConsent_schema' => '']);

		$this->mockConfig->method('getValueString')
			->willReturn('1');

		$this->mockOcrService->method('isTesseractAvailable')
			->willReturn(false);

		$this->mockOcrService->method('getTesseractVersion')
			->willReturn(null);

		$result = $this->settingsService->getAllSettings();

		$this->assertArrayHasKey('objectTypes', $result);
		$this->assertArrayHasKey('openRegisters', $result);
		$this->assertArrayHasKey('configuration', $result);
		$this->assertArrayHasKey('ocrStatus', $result);
		$this->assertFalse($result['openRegisters']);
		$this->assertArrayHasKey('tesseractAvailable', $result['ocrStatus']);

	}//end testGetAllSettingsReturnsExpectedStructure()

	/**
	 * Test getOcrStatus returns Tesseract availability from OcrService
	 *
	 * @return void
	 *
	 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.2
	 */
	public function testGetOcrStatusReturnsAvailabilityInfo(): void {
		$this->mockOcrService->method('isTesseractAvailable')
			->willReturn(true);

		$this->mockOcrService->method('getTesseractVersion')
			->willReturn('tesseract 5.3.0');

		$result = $this->settingsService->getOcrStatus();

		$this->assertTrue($result['tesseractAvailable']);
		$this->assertSame('tesseract 5.3.0', $result['tesseractVersion']);

	}//end testGetOcrStatusReturnsAvailabilityInfo()

	/**
	 * Test that OCR keys are accepted by updateSettings
	 *
	 * @return void
	 *
	 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.4
	 */
	public function testUpdateSettingsAcceptsOcrKeys(): void {
		$this->mockConfig->expects($this->atLeastOnce())
			->method('setValueString')
			->with('filinq', 'ocr_enabled', '1');

		$this->mockConfig->method('getValueString')
			->willReturn('1');

		$result = $this->settingsService->updateSettings(['ocr_enabled' => '1']);
		$this->assertArrayHasKey('ocr_enabled', $result);

	}//end testUpdateSettingsAcceptsOcrKeys()

	/**
	 * The admin may mark LibreSign's certificate qualified.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-2.2
	 */
	public function testUpdateSettingsAcceptsTheLibreSignQualifiedSwitch(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'libresign_qualified', '1');
		$this->mockConfig->method('getValueString')->willReturn('1');

		$result = $this->settingsService->updateSettings(['libresign_qualified' => true]);

		$this->assertSame('1', $result['libresign_qualified']);

	}//end testUpdateSettingsAcceptsTheLibreSignQualifiedSwitch()

	/**
	 * Test updateSettings persists values for allowlisted keys
	 *
	 * @return void
	 */
	public function testUpdateSettingsPersistsValues(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'signing_provider', 'docusign');

		$this->mockConfig->method('getValueString')
			->willReturn('docusign');

		$result = $this->settingsService->updateSettings(['signing_provider' => 'docusign']);
		$this->assertEquals('docusign', $result['signing_provider']);

	}//end testUpdateSettingsPersistsValues()

	/**
	 * The output subfolder name is writable when it is one safe path segment.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
	 */
	public function testOutputSubfolderNameIsWritable(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'anonymisation.output_subfolder_name', 'redacted');
		$this->mockConfig->method('getValueString')->willReturn('redacted');

		$result = $this->settingsService->updateSettings(['anonymisation.output_subfolder_name' => 'redacted']);

		$this->assertSame('redacted', $result['anonymisation.output_subfolder_name']);

	}//end testOutputSubfolderNameIsWritable()

	/**
	 * A traversal attempt is refused before anything is written, naming the characters.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
	 */
	public function testInvalidOutputSubfolderNameIsRefusedAndNothingIsWritten(): void {
		$this->mockConfig->expects($this->never())->method('setValueString');

		try {
			$this->settingsService->updateSettings(
				[
					'signing_provider' => 'native',
					'anonymisation.output_subfolder_name' => '../traversal',
				]
			);
			$this->fail('An invalid subfolder name was accepted.');
		} catch (\RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertStringContainsString('"."', $e->getMessage());
			$this->assertStringContainsString('"/"', $e->getMessage());
		}

	}//end testInvalidOutputSubfolderNameIsRefusedAndNothingIsWritten()

	/**
	 * An empty subfolder name is refused too.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
	 */
	public function testEmptyOutputSubfolderNameIsRefused(): void {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(400);
		$this->settingsService->updateSettings(['anonymisation.output_subfolder_name' => '']);

	}//end testEmptyOutputSubfolderNameIsRefused()

	/**
	 * The settings key is the one the resolver reads.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
	 */
	public function testSubfolderKeyMatchesTheResolver(): void {
		$this->assertSame(
			\OCA\Filinq\Service\Conversion\OutputLayoutResolver::SUBFOLDER_CONFIG_KEY,
			'anonymisation.output_subfolder_name'
		);

	}//end testSubfolderKeyMatchesTheResolver()

	/**
	 * The guardian consent age is an admin setting, 16 until someone changes it
	 * (signer-identity-rails REQ-DDSIR-008).
	 *
	 * @return void
	 */
	public function testTheGuardianConsentAgeIsAWritableSettingThatDefaultsToSixteen(): void {
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = ''): string {
				return $default;
			}
		);
		$this->assertSame('16', $this->settingsService->getFeatureToggles()['signing_guardian_consent_age'] ?? null);

		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'signing_guardian_consent_age', '18');

		$this->settingsService->updateSettings(['signing_guardian_consent_age' => '18']);

	}//end testTheGuardianConsentAgeIsAWritableSettingThatDefaultsToSixteen()

	/**
	 * Reading on arrival is on by default and an admin can switch it off.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-1.4
	 */
	public function testReadingOnArrivalDefaultsOnAndIsWritable(): void {
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);
		$this->assertTrue($this->settingsService->getAllSettings()['ocr_on_arrival'] ?? null);

		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'ocr_on_arrival', '0');

		$this->settingsService->updateSettings(['ocr_on_arrival' => '0']);

	}//end testReadingOnArrivalDefaultsOnAndIsWritable()

	/**
	 * Contract term suggestions are on by default, and an admin can switch them
	 * off under the key the suggestion pass reads.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#3-1
	 */
	public function testContractTermsDefaultOnAndAreWritable(): void {
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);
		$this->assertTrue($this->settingsService->getAllSettings()[\OCA\Filinq\Service\Contract\ContractTermSuggestionService::TOGGLE] ?? null);

		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'enable_contract_term_extraction', '0');

		$this->settingsService->updateSettings(['enable_contract_term_extraction' => '0']);

	}//end testContractTermsDefaultOnAndAreWritable()

	/**
	 * Inbound classification is on by default, and an admin can switch it off
	 * under the key InboundClassificationService reads.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-5
	 */
	public function testInboundClassificationDefaultsOnAndIsWritable(): void {
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);
		$this->assertTrue($this->settingsService->getFeatureToggles()[\OCA\Filinq\Service\InboundClassificationService::TOGGLE] ?? null);
		$this->assertTrue($this->settingsService->getAllSettings()[\OCA\Filinq\Service\InboundClassificationService::TOGGLE] ?? null);

		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'enable_inbound_classification', '0');

		$this->settingsService->updateSettings(['enable_inbound_classification' => '0']);

	}//end testInboundClassificationDefaultsOnAndIsWritable()

	/**
	 * Test updateSettings silently rejects unknown keys
	 *
	 * Keys not present in WRITABLE_KEYS must be dropped from the result and
	 * must never reach setValueString (wave-3 C1 allowlist behaviour).
	 *
	 * @return void
	 */
	public function testUpdateSettingsSilentlyRejectsUnknownKeys(): void {
		$this->mockConfig->expects($this->never())
			->method('setValueString');

		$this->mockLogger->expects($this->once())
			->method('warning');

		$result = $this->settingsService->updateSettings(['unknown_key' => 'some_value']);
		$this->assertArrayNotHasKey('unknown_key', $result);

	}//end testUpdateSettingsSilentlyRejectsUnknownKeys()

	/**
	 * Test updateSettings skips empty keys
	 *
	 * @return void
	 */
	public function testUpdateSettingsSkipsEmptyKeys(): void {
		$this->mockConfig->expects($this->never())
			->method('setValueString');

		$this->mockLogger->expects($this->once())
			->method('warning');

		$result = $this->settingsService->updateSettings(['' => 'value']);
		$this->assertArrayHasKey('', $result);

	}//end testUpdateSettingsSkipsEmptyKeys()

}//end class
