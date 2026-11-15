<?php

/**
 * Golden payloads of a new consent record and of a resubmission merged into a stored one.
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

use DateTime;
use OCA\Filinq\Service\ConsentNotesHelper;
use OCA\Filinq\Service\ConsentPolicyReferentValidator;
use OCA\Filinq\Service\ConsentRecordWriter;
use OCA\Filinq\Service\ConsentScopeValidator;
use OCA\Filinq\Service\ObjectionDeadlineChecker;
use OCA\Filinq\Service\Support\ConsentRecordRules;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Pins every field, and the order of the fields, of both payloads, including what a resubmission may never overwrite.
 */
class ConsentRecordPayloadGoldenTest extends TestCase {

	/**
	 * Call one of the writer's private payload builders.
	 *
	 * @param string            $method    The builder.
	 * @param array<int, mixed> $arguments Its arguments, in order.
	 *
	 * @return array<string, mixed>
	 */
	private function build(string $method, array $arguments): array {
		$deadline = $this->createMock(ObjectionDeadlineChecker::class);
		$deadline->method('calculateDeadline')->willReturn(new DateTime('2026-10-20T00:00:00+00:00'));

		$writer = new ConsentRecordWriter(
			logger: $this->createMock(LoggerInterface::class),
			openRegister: new OpenRegisterAccess(apps: $this->createMock(IAppManager::class), container: $this->createMock(ContainerInterface::class)),
			rules: new ConsentRecordRules(
				deadlineChecker: $deadline,
				notesHelper: new ConsentNotesHelper(),
				scopeValidator: $this->createMock(ConsentScopeValidator::class),
				referentValidator: $this->createMock(ConsentPolicyReferentValidator::class)
			)
		);

		return (new ReflectionMethod($writer, $method))->invokeArgs($writer, $arguments);

	}//end build()

	/**
	 * A resubmission context.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 *
	 * @return array<string, mixed>
	 */
	private function context(array $overrides = []): array {
		return array_merge(
			[
				'documentId' => 'doc-1',
				'entityType' => 'PERSON',
				'entityText' => 'Jan Jansen',
				'entityKey' => null,
				'publicationBases' => [],
				'contactEmail' => null,
				'contactAddress' => null,
				'policyMatchUuid' => null,
				'policyMatchKind' => null,
				'standingConsentUuid' => null,
			],
			$overrides
		);

	}//end context()

	/**
	 * A bare new record is pending with a deadline; every optional field appears only when given, in a fixed order.
	 *
	 * @return void
	 */
	public function testANewRecord(): void {
		$bare = $this->build('buildNewConsentPayload', [$this->context()]);
		$this->assertSame(
			[
				'documentId' => 'doc-1',
				'entityType' => 'PERSON',
				'entityText' => 'Jan Jansen',
				'scope' => 'document',
				'notificationStatus' => 'pending',
				'consentStatus' => 'pending',
				'publicationDecision' => 'pending',
				'objectionDeadline' => (new DateTime('2026-10-20T00:00:00+00:00'))->format('c'),
			],
			$bare
		);

		$full = $this->build(
			'buildNewConsentPayload',
			[
				$this->context(
					[
						'entityKey' => 'key-1',
						'publicationBases' => ['Art. 5.1 Woo', 'Art. 5.2 Woo'],
						'contactEmail' => 'jan@example.org',
						'contactAddress' => 'Straat 1',
						'standingConsentUuid' => 'sc-1',
						'policyMatchKind' => 'standing_consent',
					]
				),
			]
		);
		$this->assertSame(
			['documentId', 'entityType', 'entityText', 'scope', 'notificationStatus', 'consentStatus', 'publicationDecision', 'policyMatch', 'matchKind', 'entityKey', 'legalBasis', 'notes', 'contactEmail', 'contactAddress'],
			array_keys($full)
		);
		$this->assertSame('Art. 5.1 Woo', $full['legalBasis']);
		$this->assertStringContainsString('Art. 5.2 Woo', $full['notes']);
		$this->assertSame(['consent_given', 'publish_with_consent', 'skipped', 'sc-1', 'standing_consent'], [$full['consentStatus'], $full['publicationDecision'], $full['notificationStatus'], $full['policyMatch'], $full['matchKind']]);

		$oneBasis = $this->build('buildNewConsentPayload', [$this->context(['entityKey' => '', 'publicationBases' => ['Art. 5.1 Woo']])]);
		$this->assertSame('Art. 5.1 Woo', $oneBasis['legalBasis']);
		$this->assertArrayNotHasKey('notes', $oneBasis);
		$this->assertArrayNotHasKey('entityKey', $oneBasis);

	}//end testANewRecord()

	/**
	 * A resubmission refreshes the operator fields and never overwrites the workflow fields or an existing match.
	 *
	 * @return void
	 */
	public function testAResubmission(): void {
		$existing = [
			'uuid' => 'c-1',
			'entityType' => 'ORG',
			'legalBasis' => 'Oud',
			'notes' => 'Operator note',
			'contactEmail' => 'oud@example.org',
			'consentStatus' => 'objected',
			'notificationSentAt' => '2026-09-01',
			'objectionDeadline' => null,
		];

		$plain = $this->build('mergeResubmission', [$existing, $this->context()]);
		$this->assertSame(
			['uuid' => 'c-1', 'entityType' => 'PERSON', 'legalBasis' => 'Oud', 'notes' => 'Operator note', 'contactEmail' => 'oud@example.org', 'consentStatus' => 'objected', 'notificationSentAt' => '2026-09-01', 'objectionDeadline' => null],
			$plain
		);

		$rich = $this->build(
			'mergeResubmission',
			[
				$existing,
				$this->context(['publicationBases' => ['Nieuw', 'Extra'], 'contactEmail' => 'nieuw@example.org', 'contactAddress' => 'Laan 2', 'policyMatchUuid' => 'p-1', 'policyMatchKind' => 'prohibition']),
			]
		);
		$this->assertSame(['uuid', 'entityType', 'legalBasis', 'notes', 'contactEmail', 'consentStatus', 'notificationSentAt', 'objectionDeadline', 'contactAddress', 'policyMatch', 'matchKind'], array_keys($rich));
		$this->assertSame(['Nieuw', 'nieuw@example.org', 'Laan 2', 'p-1', 'prohibition', 'objected'], [$rich['legalBasis'], $rich['contactEmail'], $rich['contactAddress'], $rich['policyMatch'], $rich['matchKind'], $rich['consentStatus']]);
		$this->assertStringContainsString('Operator note', $rich['notes']);
		$this->assertStringContainsString('Extra', $rich['notes']);

		$kept = $this->build('mergeResubmission', [['policyMatch' => 'p-old', 'matchKind' => 'standing_consent'], $this->context(['policyMatchUuid' => 'p-new', 'policyMatchKind' => 'prohibition'])]);
		$this->assertSame(['p-old', 'standing_consent'], [$kept['policyMatch'], $kept['matchKind']]);

	}//end testAResubmission()
}//end class
