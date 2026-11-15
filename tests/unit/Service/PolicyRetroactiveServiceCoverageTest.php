<?php

/**
 * Coverage tests for PolicyRetroactiveService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\PolicyRetroactiveService;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The retroactive sweep: which in-flight records are loaded, which the rules
 * match, what the force-resolved record looks like, and how failures degrade.
 *
 * @covers \OCA\Filinq\Service\PolicyRetroactiveService
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 * @uses   \OCA\Filinq\Service\PolicyRuleNormaliser
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PolicyRetroactiveServiceCoverageTest extends TestCase {

	/**
	 * Logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Policy matcher.
	 *
	 * @var PolicyMatchService&MockObject
	 */
	private PolicyMatchService&MockObject $matcher;

	/**
	 * OpenRegister object service.
	 *
	 * @var ObjectServiceInterface&MockObject
	 */
	private ObjectServiceInterface&MockObject $objectService;

	/**
	 * Build the mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->matcher = $this->createMock(PolicyMatchService::class);
		$this->objectService = $this->createMock(ObjectServiceInterface::class);

	}//end setUp()

	/**
	 * Build the service under test over the real result extractor.
	 *
	 * @return PolicyRetroactiveService
	 */
	private function service(): PolicyRetroactiveService {
		return new PolicyRetroactiveService($this->logger, $this->matcher, $this->objectService);

	}//end service()

	/**
	 * A prohibition record that is active and inside its validity window.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 *
	 * @return array<string, mixed>
	 */
	private function prohibition(array $overrides = []): array {
		return array_merge(
			[
				'@self' => ['id' => 'prohib-1'],
				'active' => true,
				'validFrom' => '-1 year',
				'validUntil' => '+1 year',
				'entityType' => 'PERSON',
				'matchRules' => [['type' => 'exact', 'value' => 'Jan']],
			],
			$overrides
		);

	}//end prohibition()

	/**
	 * The full sweep: only in-flight document-scope records of the entity type
	 * without a policy match are tested; matching ones are force-resolved with
	 * the audit fields preserved; failures are not counted.
	 *
	 * @return void
	 */
	public function testSweepForceResolvesOnlyMatchingInFlightRecords(): void {
		$recordA = [
			'@self' => ['id' => 'rec-a'],
			'scope' => 'document',
			'consentStatus' => 'pending',
			'entityType' => 'PERSON',
			'entityText' => 'Jan',
			'bsn' => '123456782',
			'kvk' => 12345678,
			'resolvedIdentifiers' => ['kvk' => '87654321', 'bsn' => 99],
			'notificationSentAt' => '2026-01-01T00:00:00+00:00',
			'objectionDeadline' => '2026-02-01T00:00:00+00:00',
		];
		$rows = [
			$recordA,
			['id' => 'rec-b', 'scope' => 'entity', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
			['id' => 'rec-c', 'scope' => 'document', 'consentStatus' => 'anonymized', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
			['id' => 'rec-d', 'consentStatus' => 'consent_given', 'policyMatch' => 'other', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
			['id' => 'rec-e', 'consentStatus' => 'no_response', 'policyMatch' => '', 'entityType' => 'PERSON', 'entityText' => 'Piet'],
			['id' => 'rec-f', 'consentStatus' => 'pending', 'entityType' => 'ORGANIZATION', 'entityText' => 'Jan'],
			['id' => 'rec-g', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => ''],
			['consentStatus' => 'objection_received', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
			['uuid' => 'rec-i', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
		];

		$this->objectService->expects($this->once())
			->method('findAll')
			->with(
				[
					'filters' => [
						'register' => 'filinq',
						'schema' => 'publicationConsent',
						'scope' => 'document',
					],
				],
				false
			)
			->willReturn(['results' => $rows]);

		$matcherCalls = [];
		$this->matcher->method('entityMatchesAnyRule')->willReturnCallback(
			function (array $matchRules, string $entityText, array $resolvedIdentifiers) use (&$matcherCalls): bool {
				$matcherCalls[] = [$entityText, $resolvedIdentifiers];
				return $entityText === 'Jan';
			}
		);
		$this->matcher->expects($this->once())->method('invalidateCache');

		$entity = $this->createMock(ObjectEntityInterface::class);
		$saved = [];
		$this->objectService->expects($this->exactly(2))
			->method('saveObject')
			->willReturnCallback(
				function (array $object, ...$rest) use (&$saved, $entity): ObjectEntityInterface {
					$saved[] = $object;
					if (($object['uuid'] ?? null) === 'rec-i') {
						throw new Exception('write refused');
					}

					return $entity;
				}
			);

		$this->logger->expects($this->once())
			->method('error')
			->with(
				'PolicyRetroactiveService: failed to force-resolve record',
				['recordUuid' => 'rec-i', 'prohibitionUuid' => 'prohib-1', 'error' => 'write refused']
			);
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'PolicyRetroactiveService: prohibition retroactively force-resolved records',
				['prohibitionUuid' => 'prohib-1', 'resolved' => 1]
			);

		$this->assertSame(1, $this->service()->applyProhibitionMutation($this->prohibition()));

		$this->assertSame(
			[
				['Jan', ['bsn' => '123456782', 'kvk' => '87654321']],
				['Piet', []],
				['Jan', []],
				['Jan', []],
			],
			$matcherCalls
		);

		$expectedA = $recordA;
		unset($expectedA['@self']);
		$expectedA['consentStatus'] = 'anonymized';
		$expectedA['notificationStatus'] = 'skipped';
		$expectedA['publicationDecision'] = 'anonymize';
		$expectedA['objectionDeadline'] = null;
		$expectedA['policyMatch'] = 'prohib-1';
		$this->assertSame($expectedA, $saved[0]);
		$this->assertSame('2026-01-01T00:00:00+00:00', $saved[0]['notificationSentAt']);

	}//end testSweepForceResolvesOnlyMatchingInFlightRecords()

	/**
	 * The resolved record is saved under its uuid with RBAC and multitenancy off.
	 *
	 * @return void
	 */
	public function testForceResolveSavesUnderTheRecordUuid(): void {
		$this->objectService->method('findAll')->willReturn(
			[['@self' => ['uuid' => 'rec-x'], 'consentStatus' => 'pending', 'entityType' => 'ORG', 'entityText' => 'Acme']]
		);
		$this->matcher->method('entityMatchesAnyRule')->willReturn(true);

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(
				$this->callback(static fn (array $o): bool => $o['policyMatch'] === 'prohib-2' && isset($o['@self']) === false),
				$this->anything(),
				'filinq',
				'publicationConsent',
				'rec-x',
				false,
				false
			);

		$resolved = $this->service()->applyProhibitionMutation(
			$this->prohibition(['@self' => [], 'uuid' => 'prohib-2', 'entityType' => 'OTHER'])
		);

		$this->assertSame(1, $resolved);

	}//end testForceResolveSavesUnderTheRecordUuid()

	/**
	 * A prohibition without an entityType sweeps every entity type (OTHER),
	 * and the uuid can come from `@self.uuid`.
	 *
	 * @return void
	 */
	public function testMissingEntityTypeSweepsAllTypes(): void {
		$prohibition = $this->prohibition(['@self' => ['uuid' => 'prohib-3']]);
		unset($prohibition['entityType'], $prohibition['validFrom'], $prohibition['validUntil']);

		$this->objectService->method('findAll')->willReturn(
			[
				['id' => 'r1', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
				['id' => 'r2', 'consentStatus' => 'pending', 'entityType' => 'EMAIL', 'entityText' => 'jan@x.nl'],
			]
		);
		$this->matcher->expects($this->exactly(2))->method('entityMatchesAnyRule')->willReturn(true);
		$this->objectService->expects($this->exactly(2))->method('saveObject');

		$this->assertSame(2, $this->service()->applyProhibitionMutation($prohibition));

	}//end testMissingEntityTypeSweepsAllTypes()

	/**
	 * When nothing is resolved, no info line is logged but the cache is still
	 * invalidated.
	 *
	 * @return void
	 */
	public function testNoMatchesLogsNothingButInvalidatesCache(): void {
		$this->objectService->method('findAll')->willReturn([]);
		$this->matcher->expects($this->never())->method('entityMatchesAnyRule');
		$this->matcher->expects($this->once())->method('invalidateCache');
		$this->logger->expects($this->never())->method('info');

		$this->assertSame(0, $this->service()->applyProhibitionMutation($this->prohibition()));

	}//end testNoMatchesLogsNothingButInvalidatesCache()

	/**
	 * A failing load is logged as a warning and resolves nothing.
	 *
	 * @return void
	 */
	public function testLoadFailureIsLoggedAndResolvesNothing(): void {
		$this->objectService->method('findAll')->willThrowException(new Exception('search down'));
		$this->objectService->expects($this->never())->method('saveObject');
		$this->logger->expects($this->once())
			->method('warning')
			->with('PolicyRetroactiveService: failed to load in-flight records', ['error' => 'search down']);
		$this->matcher->expects($this->once())->method('invalidateCache');

		$this->assertSame(0, $this->service()->applyProhibitionMutation($this->prohibition()));

	}//end testLoadFailureIsLoggedAndResolvesNothing()

	/**
	 * An expired prohibition does not sweep.
	 *
	 * @return void
	 */
	public function testExpiredProhibitionResolvesNothing(): void {
		$this->objectService->expects($this->never())->method('findAll');
		$this->matcher->expects($this->never())->method('invalidateCache');

		$this->assertSame(
			0,
			$this->service()->applyProhibitionMutation($this->prohibition(['validUntil' => '-1 day']))
		);

	}//end testExpiredProhibitionResolvesNothing()

	/**
	 * A not-yet-valid or non-true `active` prohibition does not sweep.
	 *
	 * @return void
	 */
	public function testIneligibleProhibitionsResolveNothing(): void {
		$this->objectService->expects($this->never())->method('findAll');

		$service = $this->service();
		$this->assertSame(0, $service->applyProhibitionMutation($this->prohibition(['validFrom' => '+1 day'])));
		$this->assertSame(0, $service->applyProhibitionMutation($this->prohibition(['active' => 'yes'])));

	}//end testIneligibleProhibitionsResolveNothing()

	/**
	 * An unparseable date is ignored rather than blocking the sweep.
	 *
	 * @return void
	 */
	public function testUnparseableDatesDoNotBlockTheSweep(): void {
		$this->objectService->expects($this->once())->method('findAll')->willReturn([]);

		$this->assertSame(
			0,
			$this->service()->applyProhibitionMutation(
				$this->prohibition(['validFrom' => 'not a date', 'validUntil' => 'also not'])
			)
		);

	}//end testUnparseableDatesDoNotBlockTheSweep()

	/**
	 * Without match rules (empty or not a list) nothing is swept.
	 *
	 * @return void
	 */
	public function testMissingMatchRulesResolveNothing(): void {
		$this->objectService->expects($this->never())->method('findAll');
		$this->matcher->expects($this->never())->method('invalidateCache');

		$service = $this->service();
		$this->assertSame(0, $service->applyProhibitionMutation($this->prohibition(['matchRules' => []])));
		$this->assertSame(0, $service->applyProhibitionMutation($this->prohibition(['matchRules' => 'Jan'])));

	}//end testMissingMatchRulesResolveNothing()

	/**
	 * A prohibition with no uuid anywhere is skipped with a warning.
	 *
	 * @return void
	 */
	public function testProhibitionWithoutUuidIsSkipped(): void {
		$this->objectService->expects($this->never())->method('findAll');
		$this->logger->expects($this->once())
			->method('warning')
			->with('PolicyRetroactiveService: prohibition has no UUID; skipping retroactive sweep');

		$this->assertSame(0, $this->service()->applyProhibitionMutation($this->prohibition(['@self' => []])));

	}//end testProhibitionWithoutUuidIsSkipped()

	/**
	 * Standing-consent mutations and rule removals only invalidate the cache.
	 *
	 * @return void
	 */
	public function testStandingConsentAndRemovalOnlyInvalidateCache(): void {
		$this->matcher->expects($this->exactly(2))->method('invalidateCache');
		$this->objectService->expects($this->never())->method('findAll');
		$this->objectService->expects($this->never())->method('saveObject');

		$service = $this->service();
		$service->applyStandingConsentMutation();
		$service->applyRuleRemoval();

	}//end testStandingConsentAndRemovalOnlyInvalidateCache()
}//end class
