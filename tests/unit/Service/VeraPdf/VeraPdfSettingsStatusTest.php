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

use OCA\Filinq\Controller\ConformanceController;
use OCA\Filinq\Service\VeraPdf\ConformanceGuidance;
use OCA\Filinq\Service\VeraPdf\ConformanceReportRepository;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class VeraPdfSettingsStatusTest extends TestCase {
	use VeraPdfDoubles;

	/**
	 * What GET api/validation/conformance-status answers.
	 *
	 * @return array<string, mixed> The status.
	 */
	private function statusRow(): array {
		$conformance = new ConformanceService(
			$this->veraPdf(),
			new ConformanceGuidance(),
			$this->createMock(ConformanceReportRepository::class),
			$this->createMock(ITimeFactory::class)
		);
		$controller = new ConformanceController(
			'filinq',
			$this->createMock(IRequest::class),
			$conformance,
			$this->createMock(IRootFolder::class),
			$this->createMock(IUserSession::class),
			$this->createMock(IL10N::class),
			new NullLogger()
		);

		return $controller->status()->getData();

	}//end statusRow()

	/**
	 * Installed, absent, and switched off each read as such; the binary's
	 * path on the server is not sent.
	 *
	 * @return void
	 */
	public function testTheRowReadsTheProbe(): void {
		$this->assertSame(['enabled' => true, 'available' => true, 'version' => 'veraPDF 1.30.2'], $this->statusRow());

		$this->config[VeraPdfService::CFG_BINARY_PATH] = '/nonexistent/verapdf';
		$this->assertSame(['enabled' => true, 'available' => false, 'version' => ''], $this->statusRow());

		$this->config[VeraPdfService::CFG_ENABLED] = 'false';
		$this->assertFalse($this->statusRow()['enabled']);

	}//end testTheRowReadsTheProbe()
}//end class
