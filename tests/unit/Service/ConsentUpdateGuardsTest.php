<?php

/**
 * Consent update: the guards and the status events, through the public method.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use OCA\Filinq\Service\ConsentUpdateHandler;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Every refusal updateConsentStatus makes, and what it saves when it does not refuse.
 *
 * The guard methods also have direct tests; these run them through the
 * public method, so removing a guard call fails here.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentUpdateGuardsTest extends TestCase {

	/**
	 * The records the handler saved.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * A prohibition-matched record: both transition fields are locked.
	 *
	 * @var array<string, mixed>
	 */
	private const PROHIBITION = [
		'consentStatus'       => 'pending',
		'notificationStatus'  => 'pending',
		'publicationDecision' => 'anonymize',
		'policyMatch'         => 'rule-1',
		'matchKind'           => 'prohibition',
	];

	/**
	 * A standing-consent-matched record: the decision may be overridden, the status not.
	 *
	 * @var array<string, mixed>
	 */
	private const STANDING_CONSENT = [
		'consentStatus'       => 'pending',
		'notificationStatus'  => 'pending',
		'publicationDecision' => 'publish',
		'policyMatch'         => 'rule-2',
		'matchKind'           => 'standing_consent',
	];

	/**
	 * A handler over one stored record.
	 *
	 * @param array<string, mixed> $world The stored record and what fails.
	 *
	 * @return ConsentUpdateHandler The handler.
	 */
	private function handler(array $world): ConsentUpdateHandler {
		$world = array_merge(['stored' => [], 'installed' => ['openregister'], 'findFails' => false, 'saveThrows' => null], $world);

		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('find')->willReturnCallback(
			static function () use ($world): ObjectEntity {
				if ($world['findFails'] === true) {
					throw new RuntimeException(message: 'register offline');
				}

				$entity = new ObjectEntity();
				$entity->setObject($world['stored']);
				return $entity;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object) use ($world): ObjectEntity {
				if ($world['saveThrows'] !== null) {
					throw $world['saveThrows'];
				}

				$this->saved[] = $object;
				$entity = new ObjectEntity();
				$entity->setObject($object);
				return $entity;
			}
		);

		$appManager = $this->createMock(originalClassName: IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn($world['installed']);
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($objects);

		return new ConsentUpdateHandler(
			logger: $this->createMock(originalClassName: LoggerInterface::class),
			container: $container,
			appManager: $appManager
		);
	}//end handler()

	/**
	 * Update with these data as `ingrid`.
	 *
	 * @param array<string, mixed> $world The stored record and what fails.
	 * @param array<string, mixed> $data  The update.
	 *
	 * @return array<string, mixed> The saved record.
	 */
	private function update(array $world, array $data): array {
		return $this->handler(world: $world)->updateConsentStatus(
			consentId: 'c-1',
			register: 'filinq',
			schema: 'publicationConsent',
			data: $data,
			actor: 'ingrid'
		);
	}//end update()

	/**
	 * Update and return what was thrown.
	 *
	 * @param array<string, mixed> $world The stored record and what fails.
	 * @param array<string, mixed> $data  The update.
	 *
	 * @return Throwable What the update threw.
	 */
	private function refusal(array $world, array $data): Throwable {
		try {
			$this->update(world: $world, data: $data);
		} catch (Throwable $e) {
			return $e;
		}

		$this->fail(message: 'Expected the update to be refused');
	}//end refusal()

	/**
	 * A prohibition match refuses a status move the event rules would allow.
	 *
	 * @return void
	 */
	public function testProhibitionRefusesAStatusMove(): void {
		$refusal = $this->refusal(
			world: ['stored' => self::PROHIBITION],
			data: ['consentStatus' => 'consent_given', 'evidence' => 'Signed form']
		);

		$this->assertSame(expected: InvalidArgumentException::class, actual: get_class($refusal));
		$this->assertSame(
			expected: 'consentStatus "consent_given" rejected on policy-pre-empted record (policyMatch=rule-1, current=pending).',
			actual: $refusal->getMessage()
		);
		$this->assertSame(expected: [], actual: $this->saved);
	}//end testProhibitionRefusesAStatusMove()

	/**
	 * A prohibition match refuses a decision change.
	 *
	 * @return void
	 */
	public function testProhibitionRefusesADecisionChange(): void {
		$refusal = $this->refusal(world: ['stored' => self::PROHIBITION], data: ['publicationDecision' => 'publish']);

		$this->assertSame(
			expected: 'publicationDecision "publish" rejected on policy-pre-empted record (policyMatch=rule-1, current=anonymize).',
			actual: $refusal->getMessage()
		);
		$this->assertSame(expected: [], actual: $this->saved);
	}//end testProhibitionRefusesADecisionChange()

	/**
	 * A prohibition match keeps other fields writable, and re-sending the locked values is no change.
	 *
	 * @return void
	 */
	public function testProhibitionKeepsOtherFieldsWritable(): void {
		$saved = $this->update(
			world: ['stored' => self::PROHIBITION],
			data: ['notificationStatus' => 'sent', 'consentStatus' => 'pending', 'publicationDecision' => 'anonymize']
		);

		$this->assertSame(expected: 'sent', actual: $saved['notificationStatus']);
		$this->assertSame(expected: 'pending', actual: $saved['consentStatus']);
		$this->assertSame(expected: 'anonymize', actual: $saved['publicationDecision']);
		$this->assertSame(expected: 'rule-1', actual: $saved['policyMatch']);
	}//end testProhibitionKeepsOtherFieldsWritable()

	/**
	 * A standing-consent match lets the operator override the decision.
	 *
	 * @return void
	 */
	public function testStandingConsentAllowsADecisionOverride(): void {
		$saved = $this->update(world: ['stored' => self::STANDING_CONSENT], data: ['publicationDecision' => 'anonymize']);

		$this->assertSame(expected: 'anonymize', actual: $saved['publicationDecision']);
		$this->assertSame(expected: 'pending', actual: $saved['consentStatus']);
		$this->assertArrayNotHasKey(key: 'statusEvents', array: $saved);
	}//end testStandingConsentAllowsADecisionOverride()

	/**
	 * A standing-consent match still refuses a status move, also when the decision changes with it.
	 *
	 * @return void
	 */
	public function testStandingConsentRefusesAStatusMove(): void {
		$refusal = $this->refusal(
			world: ['stored' => self::STANDING_CONSENT],
			data: ['consentStatus' => 'objection_received', 'publicationDecision' => 'anonymize', 'evidence' => 'Letter']
		);

		$this->assertSame(
			expected: 'consentStatus "objection_received" rejected on policy-pre-empted record (policyMatch=rule-2, current=pending).',
			actual: $refusal->getMessage()
		);
		$this->assertSame(expected: [], actual: $this->saved);
	}//end testStandingConsentRefusesAStatusMove()

	/**
	 * An unmatched record records the move as an event by the actor, with the evidence, at the time of the update.
	 *
	 * @return void
	 */
	public function testAStatusMoveIsRecordedByTheActor(): void {
		$before = new DateTimeImmutable('-1 second');
		$saved = $this->update(
			world: ['stored' => ['consentStatus' => 'pending', 'statusEvents' => [['field' => 'old']]]],
			data: ['consentStatus' => 'consent_given', 'evidence' => '  Signed form received by post  ']
		);
		$after = new DateTimeImmutable('+1 second');

		$this->assertSame(expected: 'consent_given', actual: $saved['consentStatus']);
		$this->assertCount(expectedCount: 2, haystack: $saved['statusEvents']);
		$event = $saved['statusEvents'][1];
		$at = new DateTimeImmutable($event['at']);
		unset($event['at']);
		$this->assertSame(
			expected: [
				'field'    => 'consentStatus',
				'from'     => 'pending',
				'to'       => 'consent_given',
				'evidence' => 'Signed form received by post',
				'by'       => 'ingrid',
			],
			actual: $event
		);
		$this->assertGreaterThanOrEqual(expected: $before, actual: $at);
		$this->assertLessThanOrEqual(expected: $after, actual: $at);
	}//end testAStatusMoveIsRecordedByTheActor()

	/**
	 * A move the rules do not declare is refused with 400.
	 *
	 * @return void
	 */
	public function testAnUndeclaredMoveIsRefused(): void {
		$refusal = $this->refusal(world: ['stored' => ['consentStatus' => 'consent_given']], data: ['consentStatus' => 'pending']);

		$this->assertSame(expected: InvalidArgumentException::class, actual: get_class($refusal));
		$this->assertSame(expected: 400, actual: $refusal->getCode());
		$this->assertSame(expected: 'consentStatus cannot move from "consent_given" to "pending".', actual: $refusal->getMessage());
		$this->assertSame(expected: [], actual: $this->saved);
	}//end testAnUndeclaredMoveIsRefused()

	/**
	 * Consent without evidence is refused with 400; whitespace is no evidence.
	 *
	 * @return void
	 */
	public function testConsentWithoutEvidenceIsRefused(): void {
		$refusal = $this->refusal(world: ['stored' => ['consentStatus' => 'pending']], data: ['consentStatus' => 'consent_given', 'evidence' => '   ']);

		$this->assertSame(expected: 400, actual: $refusal->getCode());
		$this->assertSame(expected: 'Recording consent_given needs evidence.', actual: $refusal->getMessage());
		$this->assertSame(expected: [], actual: $this->saved);
	}//end testConsentWithoutEvidenceIsRefused()

	/**
	 * A fabricated matchKind is refused before the lock, on a matched and an unmatched record.
	 *
	 * @return void
	 */
	public function testAFabricatedMatchKindIsRefused(): void {
		foreach ([self::PROHIBITION, ['consentStatus' => 'pending']] as $stored) {
			$refusal = $this->refusal(world: ['stored' => $stored], data: ['matchKind' => 'standing_consent', 'publicationDecision' => 'publish']);

			$this->assertSame(expected: InvalidArgumentException::class, actual: get_class($refusal));
			$this->assertSame(expected: 'matchKind is server-controlled and cannot be modified by the caller.', actual: $refusal->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->saved);
	}//end testAFabricatedMatchKindIsRefused()

	/**
	 * Without OpenRegister the update fails wrapped.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegister(): void {
		$refusal = $this->refusal(world: ['installed' => ['files']], data: ['notificationStatus' => 'sent']);

		$this->assertSame(expected: Exception::class, actual: get_class($refusal));
		$this->assertSame(expected: 'Failed to update consent status: OpenRegister service is not available.', actual: $refusal->getMessage());
	}//end testWithoutOpenRegister()

	/**
	 * A failing read fails wrapped and saves nothing.
	 *
	 * @return void
	 */
	public function testAFailingRead(): void {
		$refusal = $this->refusal(world: ['findFails' => true], data: ['notificationStatus' => 'sent']);

		$this->assertSame(expected: Exception::class, actual: get_class($refusal));
		$this->assertSame(expected: 'Failed to update consent status: register offline', actual: $refusal->getMessage());
		$this->assertSame(expected: [], actual: $this->saved);
	}//end testAFailingRead()

	/**
	 * An InvalidArgumentException from the save passes through unwrapped.
	 *
	 * @return void
	 */
	public function testASaveRefusalPassesThrough(): void {
		$thrown = new InvalidArgumentException(message: 'schema says no', code: 422);
		$refusal = $this->refusal(
			world: ['stored' => ['notificationStatus' => 'pending'], 'saveThrows' => $thrown],
			data: ['notificationStatus' => 'sent']
		);

		$this->assertSame(expected: $thrown, actual: $refusal);
	}//end testASaveRefusalPassesThrough()
}//end class
