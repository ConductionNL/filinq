<?php

/**
 * Unit tests for the admin endpoint listing the apps that use the signing rail
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */

declare(strict_types=1);

// phpcs:disable PEAR.Commenting.FunctionComment.Missing -- arrange/act/assert PHPUnit conventions.

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\SigningSourcesSettingsController;
use OCA\Filinq\Service\SigningSourceTally;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Controller\SigningSourcesSettingsController
 */
final class SigningSourcesSettingsControllerTest extends TestCase {

	private function controller(SigningSourceTally $tally, ?LoggerInterface $logger = null): SigningSourcesSettingsController {
		return new SigningSourcesSettingsController(
			request: $this->createMock(IRequest::class),
			tally: $tally,
			logger: ($logger ?? $this->createMock(LoggerInterface::class))
		);
	}//end controller()

	public function testItAnswersTheTally(): void {
		$tally = $this->createMock(SigningSourceTally::class);
		$tally->method('bySourceApp')->willReturn(['sources' => [['app' => 'dossiq', 'count' => 2]], 'total' => 2, 'truncated' => false]);

		$response = $this->controller(tally: $tally)->index();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['sources' => [['app' => 'dossiq', 'count' => 2]], 'total' => 2, 'truncated' => false], $response->getData());
	}//end testItAnswersTheTally()

	public function testAFailedCountIsA500WithoutDetailsAndIsLogged(): void {
		$tally = $this->createMock(SigningSourceTally::class);
		$tally->method('bySourceApp')->willThrowException(new RuntimeException('db down at host x'));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error');

		$response = $this->controller(tally: $tally, logger: $logger)->index();

		$this->assertSame(500, $response->getStatus());
		$this->assertSame(['error' => 'The signing requests could not be counted.'], $response->getData());
	}//end testAFailedCountIsA500WithoutDetailsAndIsLogged()

	public function testOnlyAnAdminSettingHolderMayReadIt(): void {
		$attributes = (new ReflectionMethod(SigningSourcesSettingsController::class, 'index'))->getAttributes(AuthorizedAdminSetting::class);

		$this->assertCount(1, $attributes);
		$this->assertSame([FilinqAdmin::class], $attributes[0]->getArguments());
	}//end testOnlyAnAdminSettingHolderMayReadIt()
}//end class
