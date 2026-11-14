<?php

/**
 * Golden tests for which signers of a request stand as a minor's guardian.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins who counts as a guardian (allowed) and who does not (refused), and which records are read.
 */
class GuardianSelectionGoldenTest extends TestCase {

	/**
	 * Signer records by id.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $records = [];

	/**
	 * The ids the guard read, in order.
	 *
	 * @var array<int, string>
	 */
	private array $reads = [];

	/**
	 * The guardians of `learner-1` among the request's signers.
	 *
	 * @param array<int, mixed> $signerIds The request's signer ids.
	 *
	 * @return array<int, string>
	 */
	private function guardiansOfLearner(array $signerIds): array {
		$this->reads = [];
		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['find'])
			->getMock();
		$objectService->method('find')->willReturnCallback(
			function (string $id = ''): ?array {
				$this->reads[] = $id;
				return ($this->records[$id] ?? null);
			}
		);

		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($objectService);
		$settings->method('resolveSignerRecordBinding')->willReturn(['register' => 'filinq', 'schema' => 'signerRecord']);

		$guard = new GuardianConsentGuard(settingsService: $settings);

		return (new ReflectionMethod($guard, 'guardiansFor'))->invoke($guard, ['signerIds' => $signerIds], 'learner-1');

	}//end guardiansOfLearner()

	/**
	 * Guardians for this signer who have not declined count, in request order.
	 *
	 * @return void
	 */
	public function testStandingGuardiansCount(): void {
		$this->records = [
			'g-1' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-1', 'status' => 'SIGNED'],
			'g-2' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-1'],
			'g-3' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-1', 'status' => 'declined'],
		];

		$this->assertSame(['g-2', 'g-1', 'g-3'], $this->guardiansOfLearner(['g-2', 'g-1', 'g-3']));
		$this->assertSame(['g-2', 'g-1', 'g-3'], $this->reads);
		$this->assertSame([], $this->guardiansOfLearner([7]));
		$this->assertSame(['7'], $this->reads);

	}//end testStandingGuardiansCount()

	/**
	 * Declined guardians, guardians for someone else, other roles, the signer and blank ids do not count.
	 *
	 * @return void
	 */
	public function testOthersDoNotCount(): void {
		$this->records = [
			'declined' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-1', 'status' => 'DECLINED'],
			'elsewhere' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-2'],
			'unbound' => ['role' => 'guardian'],
			'teacher' => ['role' => 'signer', 'guardianForSignerId' => 'learner-1'],
			'learner-1' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-1'],
			'g-1' => ['role' => 'guardian', 'guardianForSignerId' => 'learner-1'],
		];

		$ids = ['', 'learner-1', 'declined', 'elsewhere', 'unbound', 'teacher', 'missing', null, 'g-1'];
		$this->assertSame(['g-1'], $this->guardiansOfLearner($ids));
		$this->assertSame(['declined', 'elsewhere', 'unbound', 'teacher', 'missing', 'g-1'], $this->reads);
		$this->assertSame([], $this->guardiansOfLearner([]));
		$this->assertSame([], $this->reads);

	}//end testOthersDoNotCount()
}//end class
