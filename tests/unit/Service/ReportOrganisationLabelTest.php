<?php

/**
 * Unit tests for ReportOrganisationLabel
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ReportOrganisationLabel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * A report names its organisation from the caller's own organisations, and never guesses.
 *
 * @covers \OCA\Filinq\Service\ReportOrganisationLabel
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-organisation-scoped-dashboards-and-reporting-req-ddmth-005
 */
class ReportOrganisationLabelTest extends TestCase {

	/**
	 * A label over a container whose organisation service lists these organisations.
	 *
	 * @param array<string, string> $organisations UUID => name.
	 *
	 * @return ReportOrganisationLabel
	 */
	private function label(array $organisations): ReportOrganisationLabel {
		$rows = [];
		foreach ($organisations as $uuid => $name) {
			$rows[] = new class ($uuid, $name) {
				/**
				 * Constructor.
				 *
				 * @param string $uuid The UUID.
				 * @param string $name The name.
				 */
				public function __construct(private string $uuid, private string $name) {
				}

				/**
				 * The UUID.
				 *
				 * @return string
				 */
				public function getUuid(): string {
					return $this->uuid;
				}

				/**
				 * The name.
				 *
				 * @return string
				 */
				public function getName(): string {
					return $this->name;
				}
			};
		}

		$service = new class ($rows) {
			/**
			 * Constructor.
			 *
			 * @param array<int, object> $rows The caller's organisations.
			 */
			public function __construct(private array $rows) {
			}

			/**
			 * The caller's organisations.
			 *
			 * @return array<int, object>
			 */
			public function getUserOrganisations(): array {
				return $this->rows;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->atMost(1))->method('get')
			->with('OCA\OpenRegister\Service\OrganisationService')
			->willReturn($service);

		return new ReportOrganisationLabel(container: $container);
	}//end label()

	/**
	 * A stamp of one of the caller's organisations is named.
	 *
	 * @return void
	 */
	public function testACallersOrganisationIsNamed(): void {
		$label = $this->label(['org-a' => 'Gemeente A', 'org-b' => 'Gemeente B']);

		$this->assertSame(['uuid' => 'org-b', 'name' => 'Gemeente B'], $label->describe(uuid: 'org-b'));
	}//end testACallersOrganisationIsNamed()

	/**
	 * An organisation the caller does not belong to keeps its UUID and gets no name.
	 *
	 * @return void
	 */
	public function testAForeignOrganisationIsNotNamed(): void {
		$label = $this->label(['org-a' => 'Gemeente A']);

		$this->assertSame(['uuid' => 'org-x', 'name' => ''], $label->describe(uuid: ' org-x '));
	}//end testAForeignOrganisationIsNotNamed()

	/**
	 * An unstamped object gets an empty label and the organisation service is not asked.
	 *
	 * @return void
	 */
	public function testAnUnstampedObjectHasAnEmptyLabel(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->expects($this->never())->method('get');

		$label = new ReportOrganisationLabel(container: $container);

		$this->assertSame(['uuid' => '', 'name' => ''], $label->describe(uuid: null));
		$this->assertSame(['uuid' => '', 'name' => ''], $label->describe(uuid: '  '));
	}//end testAnUnstampedObjectHasAnEmptyLabel()

	/**
	 * An organisation service that cannot be reached leaves the name empty instead of failing the report.
	 *
	 * @return void
	 */
	public function testAnUnreachableOrganisationServiceLeavesTheNameEmpty(): void {
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new RuntimeException('OpenRegister is not installed'));

		$label = new ReportOrganisationLabel(container: $container);

		$this->assertSame(['uuid' => 'org-a', 'name' => ''], $label->describe(uuid: 'org-a'));
	}//end testAnUnreachableOrganisationServiceLeavesTheNameEmpty()
}//end class
