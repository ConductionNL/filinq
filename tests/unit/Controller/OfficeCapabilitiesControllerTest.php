<?php

/**
 * Unit tests for OfficeCapabilitiesController.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/office-suite-portability
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\OfficeCapabilitiesController;
use OCA\Filinq\Kernel\Contracts\OfficeSuiteAvailabilityServiceInterface;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The endpoint reports availability as booleans and never the probe's reasons.
 */
#[CoversClass(OfficeCapabilitiesController::class)]
class OfficeCapabilitiesControllerTest extends TestCase {

	/**
	 * Only booleans leave the endpoint; reasons and suite names stay server-side.
	 *
	 * @return void
	 */
	public function testShowReturnsBooleansOnly(): void {
		$availability = $this->createMock(originalClassName: OfficeSuiteAvailabilityServiceInterface::class);
		$availability->expects(self::once())->method('resolve')->willReturn(
			[
				'available' => true,
				'suites' => [
					'onlyoffice' => ['available' => false, 'configured' => true, 'reason' => 'probe could not complete: cURL error 7 http://oo.internal', 'suite' => null],
					'collabora' => ['available' => true, 'configured' => true, 'reason' => 'WOPI discovery served', 'suite' => 'Collabora Online'],
					'eurooffice' => ['available' => false, 'configured' => false, 'reason' => 'no DocumentServerInternalUrl configured', 'suite' => null],
				],
			]
		);

		$controller = new OfficeCapabilitiesController(
			appName: 'filinq',
			request: $this->createMock(originalClassName: IRequest::class),
			availability: $availability,
		);

		$response = $controller->show();

		self::assertSame(200, $response->getStatus());
		self::assertSame(
			[
				'available' => true,
				'suites' => [
					'onlyoffice' => ['available' => false, 'configured' => true],
					'collabora' => ['available' => true, 'configured' => true],
					'eurooffice' => ['available' => false, 'configured' => false],
				],
			],
			$response->getData()
		);
		self::assertStringNotContainsString('oo.internal', (string)json_encode($response->getData()));
	}//end testShowReturnsBooleansOnly()

	/**
	 * With no suite usable the capability is absent overall.
	 *
	 * @return void
	 */
	public function testShowReportsAbsentWhenNoSuiteAnswers(): void {
		$availability = $this->createMock(originalClassName: OfficeSuiteAvailabilityServiceInterface::class);
		$availability->method('resolve')->willReturn(['available' => false, 'suites' => []]);

		$controller = new OfficeCapabilitiesController(
			appName: 'filinq',
			request: $this->createMock(originalClassName: IRequest::class),
			availability: $availability,
		);

		self::assertSame(['available' => false, 'suites' => []], $controller->show()->getData());
	}//end testShowReportsAbsentWhenNoSuiteAnswers()
}//end class
