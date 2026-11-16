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
use OCA\Filinq\Service\ObjectResultExtractor;
use OCA\Filinq\Service\PolicyRetroactiveService;
use OCA\Filinq\Service\SystemContextSeam;
use OCA\OpenRegister\Contract\ObjectEntityInterface;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use OCA\Filinq\Tests\Support\LegacyObjectArgs;

/**
 * The retroactive sweep: which in-flight records are loaded, which the rules
 * match, what the force-resolved record looks like, and how failures degrade.
 *
 * @covers \OCA\Filinq\Service\PolicyRetroactiveService
 * @uses   \OCA\Filinq\Service\ObjectResultExtractor
 * @uses   \OCA\Filinq\Service\PolicyRuleNormaliser
 * @uses   \OCA\Filinq\Service\SystemContextSeam
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
	 * The seam's own logger, apart from the service's.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $seamLogger;

	/**
	 * Operations the object service ran as the system.
	 *
	 * @var int
	 */
	private int $systemRuns = 0;

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
		$this->seamLogger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->systemRuns = 0;
		$this->objectService->method('runAsSystem')->willReturnCallback(
			function (callable $operation): mixed {
				$this->systemRuns++;
				return $operation();
			}
		);
		$this->objectService->method('find')->willReturn($this->stampedProhibition(organisation: 'org-a'));

	}//end setUp()

	/**
	 * The stored prohibition as OpenRegister returns it, stamped with an organisation.
	 *
	 * @param string|null $organisation The owning organisation.
	 *
	 * @return ObjectEntityInterface&MockObject
	 */
	private function stampedProhibition(?string $organisation): ObjectEntityInterface&MockObject {
		$entity = $this->createMock(originalClassName: ObjectEntityInterface::class);
		$entity->method('getOrganisation')->willReturn($organisation);

		return $entity;

	}//end stampedProhibition()

	/**
	 * A consent row carrying the organisation OpenRegister stamped on it.
	 *
	 * @param array<string, mixed> $row          The row.
	 * @param string               $organisation The row's organisation.
	 *
	 * @return array<string, mixed>
	 */
	private static function inOrganisation(array $row, string $organisation = 'org-a'): array {
		$row['@self'] = (($row['@self'] ?? []) + ['organisation' => $organisation]);

		return $row;

	}//end inOrganisation()

	/**
	 * Build the service under test over the real result extractor.
	 *
	 * @return PolicyRetroactiveService
	 */
	private function service(): PolicyRetroactiveService {
		return new PolicyRetroactiveService(
			$this->logger,
			$this->matcher,
			$this->objectService,
			new ObjectResultExtractor(),
			new SystemContextSeam(logger: $this->seamLogger)
		);

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
			'@self' => ['id' => 'rec-a', 'organisation' => 'org-a'],
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
			self::inOrganisation(row: ['id' => 'rec-b', 'scope' => 'entity', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan']),
			self::inOrganisation(
				row: [
					'id' => 'rec-c',
					'scope' => 'document',
					'consentStatus' => 'anonymized',
					'entityType' => 'PERSON',
					'entityText' => 'Jan',
				]
			),
			self::inOrganisation(
				row: [
					'id' => 'rec-d',
					'consentStatus' => 'consent_given',
					'policyMatch' => 'other',
					'entityType' => 'PERSON',
					'entityText' => 'Jan',
				]
			),
			self::inOrganisation(
				row: [
					'id' => 'rec-e',
					'consentStatus' => 'no_response',
					'policyMatch' => '',
					'entityType' => 'PERSON',
					'entityText' => 'Piet',
				]
			),
			self::inOrganisation(row: ['id' => 'rec-f', 'consentStatus' => 'pending', 'entityType' => 'ORGANIZATION', 'entityText' => 'Jan']),
			self::inOrganisation(row: ['id' => 'rec-g', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => '']),
			self::inOrganisation(row: ['consentStatus' => 'objection_received', 'entityType' => 'PERSON', 'entityText' => 'Jan']),
			self::inOrganisation(row: ['uuid' => 'rec-i', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan']),
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
				]
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
					$rest = array_slice(LegacyObjectArgs::positional(method: 'saveObject', args: [$object, ...$rest]), 1);
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
			[['@self' => ['uuid' => 'rec-x', 'organisation' => 'org-a'], 'consentStatus' => 'pending', 'entityType' => 'ORG', 'entityText' => 'Acme']]
		);
		$this->matcher->method('entityMatchesAnyRule')->willReturn(true);

		$this->objectService->expects($this->once())
			->method('saveObject')
			->with(...LegacyObjectArgs::with(method: 'saveObject', expected: [
					'object' => $this->callback(static fn (array $o): bool => $o['policyMatch'] === 'prohib-2' && isset($o['@self']) === false),
					'extend' => $this->anything(),
					'register' => 'filinq',
					'schema' => 'publicationConsent',
					'uuid' => 'rec-x',
					'_rbac' => true,
					'_multitenancy' => true,
				]));

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
				self::inOrganisation(row: ['id' => 'r1', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan']),
				self::inOrganisation(row: ['id' => 'r2', 'consentStatus' => 'pending', 'entityType' => 'EMAIL', 'entityText' => 'jan@x.nl']),
			]
		);
		$this->matcher->expects($this->exactly(2))->method('entityMatchesAnyRule')->willReturn(true);
		$this->objectService->expects($this->exactly(2))->method('saveObject');

		$this->assertSame(2, $this->service()->applyProhibitionMutation($prohibition));

	}//end testMissingEntityTypeSweepsAllTypes()

	/**
	 * The pass reads and writes the consents only inside one logged system run.
	 *
	 * The prohibition admins who may save a prohibition hold no grant on
	 * document consents, so the load and every force-resolve must happen as the
	 * system, through the seam, with its literal reason logged.
	 *
	 * @return void
	 */
	public function testSweepRunsAsTheSystemThroughTheLoggedSeam(): void {
		$inside = false;
		$this->objectService = $this->createMock(originalClassName: ObjectServiceInterface::class);
		$this->objectService->expects($this->once())->method('runAsSystem')->willReturnCallback(
			function (callable $operation) use (&$inside): mixed {
				$inside = true;
				$result = $operation();
				$inside = false;
				return $result;
			}
		);
		$calls = [];
		$prohibition = $this->stampedProhibition(organisation: 'org-a');
		$this->objectService->method('find')->willReturnCallback(
			function () use (&$inside, &$calls, $prohibition): ObjectEntityInterface {
				$calls[] = ['find', $inside];
				return $prohibition;
			}
		);
		$this->objectService->method('findAll')->willReturnCallback(
			function () use (&$inside, &$calls): array {
				$calls[] = ['findAll', $inside];
				return [self::inOrganisation(row: ['id' => 'r1', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan'])];
			}
		);
		$this->objectService->method('saveObject')->willReturnCallback(
			function () use (&$inside, &$calls): ObjectEntityInterface {
				$calls[] = ['saveObject', $inside];
				return $this->createMock(originalClassName: ObjectEntityInterface::class);
			}
		);
		$this->matcher->method('entityMatchesAnyRule')->willReturn(true);
		$this->seamLogger->expects($this->once())
			->method('info')
			->with(
				'Filinq ran an operation as the system, across organisations',
				$this->callback(
					callback: fn (array $context): bool => $context['reason']
						=== 'a saved prohibition force-resolves the in-flight document consents it matches in its own organisation'
						&& $context['caller'] === PolicyRetroactiveService::class . '::applyProhibitionMutation'
				)
			);

		$this->assertSame(expected: 1, actual: $this->service()->applyProhibitionMutation(prohibition: $this->prohibition()));
		$this->assertSame(expected: [['find', false], ['findAll', true], ['saveObject', true]], actual: $calls);

	}//end testSweepRunsAsTheSystemThroughTheLoggedSeam()

	/**
	 * The system run lifts organisation filtering, so the sweep itself keeps to
	 * the prohibition's organisation: consents of another organisation, or of
	 * none, are never read as matches or written.
	 *
	 * @return void
	 */
	public function testSweepLeavesOtherOrganisationsConsentsAlone(): void {
		$this->objectService->method('findAll')->willReturn(
			[
				self::inOrganisation(row: ['id' => 'own', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan']),
				self::inOrganisation(
					row: [
						'id' => 'other',
						'consentStatus' => 'pending',
						'entityType' => 'PERSON',
						'entityText' => 'Jan',
					],
					organisation: 'org-b'
				),
				['id' => 'unstamped', 'consentStatus' => 'pending', 'entityType' => 'PERSON', 'entityText' => 'Jan'],
			]
		);
		$this->matcher->expects($this->once())->method('entityMatchesAnyRule')->willReturn(true);
		$written = [];
		$this->objectService->expects($this->once())
			->method('saveObject')
			->willReturnCallback(
				function (array $object, ...$rest) use (&$written): ObjectEntityInterface {
					$written[] = $object['id'];
					return $this->createMock(originalClassName: ObjectEntityInterface::class);
				}
			);

		$this->assertSame(expected: 1, actual: $this->service()->applyProhibitionMutation(prohibition: $this->prohibition()));
		$this->assertSame(expected: ['own'], actual: $written);

	}//end testSweepLeavesOtherOrganisationsConsentsAlone()

	/**
	 * A prohibition the saving user cannot read, one without an organisation,
	 * or a failing lookup sweeps nothing and never runs as the system.
	 *
	 * @return void
	 */
	public function testProhibitionWithoutAnOrganisationSweepsNothing(): void {
		$lookups = [
			null,
			$this->stampedProhibition(organisation: null),
			$this->stampedProhibition(organisation: ''),
			new Exception('lookup down'),
		];
		foreach ($lookups as $found) {
			$this->objectService = $this->createMock(originalClassName: ObjectServiceInterface::class);
			if ($found instanceof Exception) {
				$this->objectService->method('find')->willThrowException($found);
			} else {
				$this->objectService->method('find')->willReturn($found);
			}

			$this->objectService->expects($this->never())->method('runAsSystem');
			$this->objectService->expects($this->never())->method('findAll');
			$this->objectService->expects($this->never())->method('saveObject');

			$this->assertSame(expected: 0, actual: $this->service()->applyProhibitionMutation(prohibition: $this->prohibition()));
		}

	}//end testProhibitionWithoutAnOrganisationSweepsNothing()

	/**
	 * An ineligible prohibition never reaches the seam.
	 *
	 * @return void
	 */
	public function testIneligibleProhibitionDoesNotRunAsTheSystem(): void {
		$this->seamLogger->expects($this->never())->method('info');
		$this->objectService->expects($this->never())->method('findAll');

		$this->assertSame(expected: 0, actual: $this->service()->applyProhibitionMutation(prohibition: $this->prohibition(overrides: ['active' => false])));
		$this->assertSame(expected: 0, actual: $this->systemRuns);

	}//end testIneligibleProhibitionDoesNotRunAsTheSystem()

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
