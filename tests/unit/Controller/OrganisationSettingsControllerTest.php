<?php

/**
 * Unit tests for OrganisationSettingsController
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

use InvalidArgumentException;
use OCA\Filinq\Controller\OrganisationSettingsController;
use OCA\Filinq\Exception\OrganisationSettingsDeniedException;
use OCA\Filinq\Service\OrganisationSettingsService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The controller passes only the schema's fields and maps each refusal to its status.
 *
 * @covers \OCA\Filinq\Controller\OrganisationSettingsController
 * @uses   \OCA\Filinq\Exception\OrganisationSettingsDeniedException
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */
class OrganisationSettingsControllerTest extends TestCase {

	/**
	 * The service.
	 *
	 * @var OrganisationSettingsService&MockObject
	 */
	private OrganisationSettingsService&MockObject $service;

	/**
	 * Request parameters.
	 *
	 * @var array<string, mixed>
	 */
	private array $params = [];

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = $this->createMock(OrganisationSettingsService::class);
	}//end setUp()

	/**
	 * The controller under test.
	 *
	 * @return OrganisationSettingsController
	 */
	private function controller(): OrganisationSettingsController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(fn (string $key, mixed $default = null): mixed => ($this->params[$key] ?? $default));

		return new OrganisationSettingsController(
			request: $request,
			settings: $this->service,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end controller()

	/**
	 * The read returns the service's view.
	 *
	 * @return void
	 */
	public function testIndexReturnsTheView(): void {
		$this->service->method('view')->willReturn(['organisation' => null, 'canEdit' => false]);

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['organisation' => null, 'canEdit' => false], $response->getData());
	}//end testIndexReturnsTheView()

	/**
	 * A failing read is a 409 without the underlying message.
	 *
	 * @return void
	 */
	public function testAFailingReadIsAConflict(): void {
		$this->service->method('view')->willThrowException(new RuntimeException('internal detail'));

		$response = $this->controller()->index();

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertStringNotContainsString('internal detail', json_encode($response->getData()));
	}//end testAFailingReadIsAConflict()

	/**
	 * Only the schema's fields reach the service.
	 *
	 * @return void
	 */
	public function testUpdatePassesOnlyTheSchemaFields(): void {
		$this->params = ['consentPeriodDays' => '42', 'organisation' => 'other', 'owner' => 'bob', 'notes' => 'n'];
		$this->service->expects($this->once())->method('save')->with([
			'name' => null,
			'consentPeriodDays' => '42',
			'anonymizationProfile' => null,
			'defaultHuisstijl' => null,
			'notes' => 'n',
		])->willReturn(['canEdit' => true]);

		$response = $this->controller()->update();

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['canEdit' => true], $response->getData());
	}//end testUpdatePassesOnlyTheSchemaFields()

	/**
	 * A refused write is 403.
	 *
	 * @return void
	 */
	public function testARefusedWriteIsForbidden(): void {
		$this->service->method('save')->willThrowException(new OrganisationSettingsDeniedException('Only an admin of this organisation may change its settings.'));

		$response = $this->controller()->update();

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertSame(['error' => 'Only an admin of this organisation may change its settings.'], $response->getData());
	}//end testARefusedWriteIsForbidden()

	/**
	 * An out-of-range value is 400 with its rule.
	 *
	 * @return void
	 */
	public function testAnInvalidValueIsABadRequest(): void {
		$this->service->method('save')->willThrowException(new InvalidArgumentException('The objection period is between 1 and 365 days.'));

		$response = $this->controller()->update();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['error' => 'The objection period is between 1 and 365 days.'], $response->getData());
	}//end testAnInvalidValueIsABadRequest()

	/**
	 * Any other failure is 409 without the underlying message.
	 *
	 * @return void
	 */
	public function testAnAmbiguousStoreIsAConflict(): void {
		$this->service->method('save')->willThrowException(new RuntimeException('More than one organisation settings object is visible'));

		$response = $this->controller()->update();

		$this->assertSame(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertSame(['error' => 'The organisation settings could not be saved.'], $response->getData());
	}//end testAnAmbiguousStoreIsAConflict()
}//end class
