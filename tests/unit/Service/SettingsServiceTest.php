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
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\QpdfDecryptor;
use OCA\Filinq\Service\RegisterDiscoveryService;
use OCA\Filinq\Service\Review\CertaintyThresholds;
use OCA\Filinq\Service\Review\ReviewSettingsRules;
use OCA\Filinq\Service\SettingsInitializer;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Support\RegisterSetup;
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
			new RegisterSetup(discovery: $this->mockDiscoveryService, initializer: $this->mockInitializer),
			$this->mockOcrService,
			$this->mockBasisProposal,
			$this->mockOpenRegister
		);

	}//end setUp()

	/**
	 * The settings service with this qpdf probe (null: none wired).
	 *
	 * @param QpdfDecryptor|null $qpdf The probe.
	 *
	 * @return SettingsService
	 */
	private function withQpdf(?QpdfDecryptor $qpdf): SettingsService {
		return new SettingsService(
			$this->mockConfig,
			$this->mockLogger,
			new RegisterSetup(discovery: $this->mockDiscoveryService, initializer: $this->mockInitializer),
			$this->mockOcrService,
			$this->mockBasisProposal,
			$this->mockOpenRegister,
			qpdf: $qpdf
		);
	}//end withQpdf()

	/**
	 * An installed qpdf is reported with its version line.
	 *
	 * @return void
	 */
	public function testQpdfStatusNamesTheInstalledVersion(): void {
		$qpdf = $this->createMock(QpdfDecryptor::class);
		$qpdf->method('version')->willReturn('qpdf version 11.9.0');

		$this->assertSame(['available' => true, 'version' => 'qpdf version 11.9.0'], $this->withQpdf(qpdf: $qpdf)->getQpdfStatus());
	}//end testQpdfStatusNamesTheInstalledVersion()

	/**
	 * qpdf that does not answer, or no probe at all, is reported as not installed.
	 *
	 * @return void
	 */
	public function testQpdfStatusWithoutQpdfIsNotInstalled(): void {
		$qpdf = $this->createMock(QpdfDecryptor::class);
		$qpdf->method('version')->willReturn(null);

		$this->assertSame(['available' => false, 'version' => null], $this->withQpdf(qpdf: $qpdf)->getQpdfStatus());
		$this->assertSame(['available' => false, 'version' => null], $this->withQpdf(qpdf: null)->getQpdfStatus());
	}//end testQpdfStatusWithoutQpdfIsNotInstalled()

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
	 * The review gate mode is writable as `enforced` or `advisory`.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function testCheckedGateModeIsWritable(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'filinq.review.checked_gate', 'advisory');
		$this->mockConfig->method('getValueString')->willReturn('advisory');

		$result = $this->settingsService->updateSettings(['filinq.review.checked_gate' => 'advisory']);

		$this->assertSame('advisory', $result['filinq.review.checked_gate']);

	}//end testCheckedGateModeIsWritable()

	/**
	 * Any other review gate mode is refused before anything is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function testAnUnknownCheckedGateModeIsRefused(): void {
		$this->mockConfig->expects($this->never())->method('setValueString');

		try {
			$this->settingsService->updateSettings(['signing_provider' => 'native', 'filinq.review.checked_gate' => 'off']);
			$this->fail('An unknown gate mode was accepted.');
		} catch (\RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertSame('The review gate mode must be "enforced" or "advisory".', $e->getMessage());
		}

	}//end testAnUnknownCheckedGateModeIsRefused()

	/**
	 * The settings service with the review thresholds over OpenRegister's floor.
	 *
	 * @param float $floor OpenRegister's detection floor.
	 *
	 * @return SettingsService The service.
	 */
	private function withThresholds(float $floor): SettingsService {
		$handler = new class ($floor) {
			/**
			 * Constructor.
			 *
			 * @param float $floor The floor.
			 */
			public function __construct(private float $floor) {
			}

			/**
			 * The floor.
			 *
			 * @return float The floor.
			 */
			public function confidenceThreshold(): float {
				return $this->floor;
			}
		};
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		$locator->method('get')->with('OCA\OpenRegister\Service\Settings\FileSettingsHandler')->willReturn($handler);

		return new SettingsService(
			$this->mockConfig,
			$this->mockLogger,
			new RegisterSetup(discovery: $this->mockDiscoveryService, initializer: $this->mockInitializer),
			$this->mockOcrService,
			$this->mockBasisProposal,
			$this->mockOpenRegister,
			new ReviewSettingsRules(thresholds: new CertaintyThresholds(appConfig: $this->mockConfig, locator: $locator, logger: $this->mockLogger))
		);
	}//end withThresholds()

	/**
	 * Certain-from below OpenRegister's floor is refused naming the floor, and nothing is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function testCertainFromBelowTheFloorIsRefused(): void {
		$this->mockConfig->expects($this->never())->method('setValueString');

		try {
			$this->withThresholds(floor: 0.6)->updateSettings(['signing_provider' => 'native', 'filinq.review.certain_from' => 0.5]);
			$this->fail('A certain-from below the floor was accepted.');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
			$this->assertSame('Certain-from cannot be below the detection floor of 0.6 set in OpenRegister.', $e->getMessage());
		}
	}//end testCertainFromBelowTheFloorIsRefused()

	/**
	 * Certain-from at or above the floor is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function testCertainFromAtOrAboveTheFloorIsWritten(): void {
		$this->mockConfig->expects($this->once())->method('setValueString')
			->with('filinq', 'filinq.review.certain_from', '0.9');
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($key === 'filinq.review.certain_from' ? '0.9' : $default)
		);

		$result = $this->withThresholds(floor: 0.6)->updateSettings(['filinq.review.certain_from' => 0.9]);

		$this->assertSame('0.9', $result['filinq.review.certain_from']);
		$this->assertSame(0.9, $this->withThresholds(floor: 0.6)->getFeatureToggles()['filinq.review.certain_from']);
	}//end testCertainFromAtOrAboveTheFloorIsWritten()

	/**
	 * The default Woo publisher is writable from the admin settings page.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md
	 */
	public function testTheDefaultWooPublisherIsWritable(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'woo.default_publisher', 'https://identifier.overheid.nl/tooi/id/gemeente/gm0363');
		$this->mockConfig->method('getValueString')->willReturn('https://identifier.overheid.nl/tooi/id/gemeente/gm0363');

		$result = $this->settingsService->updateSettings(['woo.default_publisher' => 'https://identifier.overheid.nl/tooi/id/gemeente/gm0363']);

		$this->assertSame('https://identifier.overheid.nl/tooi/id/gemeente/gm0363', $result['woo.default_publisher']);

	}//end testTheDefaultWooPublisherIsWritable()

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
	 * The unattended threshold is saved from 0.6 to 1.0 (REQ-ERP-005).
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-3.3
	 */
	public function testTheUnattendedThresholdIsWritableWithinItsBounds(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'extraction_unattended_threshold', '0.95');
		$this->mockConfig->method('getValueString')->willReturn('0.95');

		$result = $this->settingsService->updateSettings(['extraction_unattended_threshold' => '0.95']);

		$this->assertSame('0.95', $result['extraction_unattended_threshold']);

	}//end testTheUnattendedThresholdIsWritableWithinItsBounds()

	/**
	 * A threshold of 0.5, above 1.0 or not a number is refused before anything is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-3.3
	 */
	public function testAnUnattendedThresholdOutsideItsBoundsIsRefused(): void {
		$this->mockConfig->expects($this->never())->method('setValueString');

		foreach (['0.5', '1.01', 'high', ''] as $value) {
			try {
				$this->settingsService->updateSettings(['signing_provider' => 'native', 'extraction_unattended_threshold' => $value]);
				$this->fail('Threshold ' . $value . ' was accepted.');
			} catch (\RuntimeException $e) {
				$this->assertSame([400, 'Choose a value between 0.6 and 1.0.'], [$e->getCode(), $e->getMessage()]);
			}
		}

	}//end testAnUnattendedThresholdOutsideItsBoundsIsRefused()

	/**
	 * An empty subfolder name is refused too.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-2
	 */
	public function testEmptyOutputSubfolderNameIsRefused(): void {
		$this->expectException(exception: \RuntimeException::class);
		$this->expectExceptionCode(400);
		$this->settingsService->updateSettings(['anonymisation.output_subfolder_name' => '']);

	}//end testEmptyOutputSubfolderNameIsRefused()

	/**
	 * An install that never chose reads `woo`, a stored choice reads as stored,
	 * and a stored value that is no choice reads as the default.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/tasks.md#task-1
	 */
	public function testTheFourEyesChoiceDefaultsToWoo(): void {
		$stored = null;
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default) use (&$stored): string {
				if ($key !== 'redaction_four_eyes') {
					return $default;
				}

				return ($stored ?? $default);
			}
		);

		$this->assertSame('woo', $this->settingsService->getFourEyesMode());

		$stored = 'always';
		$this->assertSame('always', $this->settingsService->getFourEyesMode());

		$stored = ' off ';
		$this->assertSame('off', $this->settingsService->getFourEyesMode());

		$stored = 'sometimes';
		$this->assertSame('woo', $this->settingsService->getFourEyesMode());

	}//end testTheFourEyesChoiceDefaultsToWoo()

	/**
	 * Each of the three choices is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/tasks.md#task-1
	 */
	public function testTheFourEyesChoiceIsWritable(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'redaction_four_eyes', 'off');
		$this->mockConfig->method('getValueString')->willReturn('off');

		$result = $this->settingsService->updateSettings(['redaction_four_eyes' => 'off']);

		$this->assertSame('off', $result['redaction_four_eyes']);

	}//end testTheFourEyesChoiceIsWritable()

	/**
	 * An unknown choice is refused with 400 before anything is written.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/tasks.md#task-1
	 */
	public function testAnUnknownFourEyesChoiceIsRefusedAndNothingIsWritten(): void {
		$this->mockConfig->expects($this->never())->method('setValueString');

		foreach (['sometimes', 'WOO', '', true] as $value) {
			try {
				$this->settingsService->updateSettings(['signing_provider' => 'native', 'redaction_four_eyes' => $value]);
				$this->fail('An unknown four-eyes choice was accepted: ' . var_export($value, true));
			} catch (\RuntimeException $e) {
				$this->assertSame(400, $e->getCode());
				$this->assertSame('redaction_four_eyes must be one of woo, always, off.', $e->getMessage());
			}
		}

	}//end testAnUnknownFourEyesChoiceIsRefusedAndNothingIsWritten()

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
	 * @spec openspec/changes/archive/2026-11-25-inbound-auto-classification/tasks.md#2-5
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

	/**
	 * With OpenRegister installed the available registers are listed; when
	 * listing them fails the settings still load with none.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testGetAllSettingsListsRegistersAndSurvivesADiscoveryFailure(): void {
		$this->mockOpenRegister->method('isInstalled')->willReturn(true);
		$this->mockConfig->method('getValueString')->willReturn('1');
		$this->mockDiscoveryService->method('fetchAvailableRegisters')
			->willReturnOnConsecutiveCalls(
				[['id' => 1]],
				$this->throwException(new RuntimeException('down'))
			);

		$listed = $this->settingsService->getAllSettings();
		$this->assertTrue($listed['openRegisters']);
		$this->assertSame([['id' => 1]], $listed['availableRegisters']);

		$this->mockLogger->expects($this->once())->method('info');
		$failed = $this->settingsService->getAllSettings();
		$this->assertTrue($failed['openRegisters']);
		$this->assertSame([], $failed['availableRegisters']);

	}//end testGetAllSettingsListsRegistersAndSurvivesADiscoveryFailure()

	/**
	 * A failure while loading the settings surfaces as a RuntimeException.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testGetAllSettingsWrapsALoadFailure(): void {
		$this->mockOpenRegister->method('isInstalled')->willReturn(false);
		$this->mockDiscoveryService->method('loadObjectTypeConfiguration')
			->willThrowException(new \Exception('broken'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Failed to retrieve settings: broken');
		$this->settingsService->getAllSettings();

	}//end testGetAllSettingsWrapsALoadFailure()

	/**
	 * A stored confidentiality vocabulary replaces the default one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-read-a-files-confidentiality-label-availability-guarded-req-ddfcl-001
	 */
	public function testAStoredConfidentialityVocabularyIsReturned(): void {
		$this->mockConfig->method('getValueString')->willReturnCallback(
			static function (string $app, string $key, string $default = ''): string {
				if ($key === 'filinq.confidentiality.label_vocabulary') {
					return '{"Open":0,"Closed":1}';
				}

				return $default;
			}
		);

		$toggles = $this->settingsService->getFeatureToggles();
		$this->assertSame(['Open' => 0, 'Closed' => 1], $toggles['filinq.confidentiality.label_vocabulary']);

	}//end testAStoredConfidentialityVocabularyIsReturned()

	/**
	 * An array value is stored as JSON.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testUpdateSettingsStoresAnArrayAsJson(): void {
		$this->mockConfig->expects($this->once())
			->method('setValueString')
			->with('filinq', 'filinq.confidentiality.label_vocabulary', '{"Open":0}');

		$this->settingsService->updateSettings(['filinq.confidentiality.label_vocabulary' => ['Open' => 0]]);

	}//end testUpdateSettingsStoresAnArrayAsJson()

	/**
	 * A failure while writing surfaces as a RuntimeException.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function testUpdateSettingsWrapsAWriteFailure(): void {
		$this->mockConfig->method('setValueString')->willThrowException(new \Exception('readonly'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Failed to update settings: readonly');
		$this->settingsService->updateSettings(['signing_provider' => 'docusign']);

	}//end testUpdateSettingsWrapsAWriteFailure()

	/**
	 * Without an ObjectService the slug search refuses instead of failing on null.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/openregister-bridge/spec.md
	 */
	public function testSlugSearchRefusesWithoutAnObjectService(): void {
		$this->mockOpenRegister->method('getObjectService')->willReturn(null);

		$this->expectException(exception: \RuntimeException::class);
		$this->settingsService->slugSearch();

	}//end testSlugSearchRefusesWithoutAnObjectService()

}//end class
