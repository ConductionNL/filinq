<?php

/**
 * The admin settings row for the PDF/A validator reads the real probe.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\VeraPdf;

use OCA\Filinq\Service\LegalBasisProposalService;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\OpenRegisterAvailabilityService;
use OCA\Filinq\Service\RegisterDiscoveryService;
use OCA\Filinq\Service\SettingsInitializer;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class VeraPdfSettingsStatusTest extends TestCase {
	use VeraPdfDoubles;

	/**
	 * The settings service, with the validator when given.
	 *
	 * @param VeraPdfService|null $veraPdf The validator.
	 *
	 * @return SettingsService The service.
	 */
	private function settings(?VeraPdfService $veraPdf): SettingsService {
		return new SettingsService(
			$this->appConfig(),
			new NullLogger(),
			$this->createMock(RegisterDiscoveryService::class),
			$this->createMock(SettingsInitializer::class),
			$this->createMock(OcrService::class),
			$this->createMock(LegalBasisProposalService::class),
			$this->createMock(OpenRegisterAvailabilityService::class),
			$veraPdf
		);

	}//end settings()

	/**
	 * Installed, switched off, absent, and not wired each read as such.
	 *
	 * @return void
	 */
	public function testTheRowReadsTheProbe(): void {
		$this->assertSame(['enabled' => true, 'available' => true, 'version' => 'veraPDF 1.30.2'], array_slice($this->settings(veraPdf: $this->veraPdf())->getVeraPdfStatus(), 0, 3));

		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';
		$this->assertSame(['enabled' => true, 'available' => false, 'version' => ''], array_slice($this->settings(veraPdf: $this->veraPdf())->getVeraPdfStatus(), 0, 3));

		$this->config[VeraPdfService::CFG_ENABLED] = 'false';
		$this->assertFalse($this->settings(veraPdf: $this->veraPdf())->getVeraPdfStatus()['enabled']);

		$this->assertFalse($this->settings(veraPdf: null)->getVeraPdfStatus()['available']);

	}//end testTheRowReadsTheProbe()
}//end class
