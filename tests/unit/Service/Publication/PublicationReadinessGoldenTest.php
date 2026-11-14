<?php

/**
 * Golden tests for the publication readiness checks
 *
 * Pins which documents pass and which are held back, the reasons and their
 * order, and the order of the lookups, so the checks can be split into named
 * steps without changing what may be handed off.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use DateTimeImmutable;
use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\Publication\PublicationReadiness;
use OCA\Filinq\Service\Publication\PublicationStore;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\Filinq\Service\RedactionAccessibilityService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Allowed and refused outcomes of PublicationReadiness.
 *
 * @covers \OCA\Filinq\Service\Publication\PublicationReadiness
 */
class PublicationReadinessGoldenTest extends TestCase {

	private const CONFIG = ['register' => 'filinq', 'schema' => 'consent'];

	/**
	 * The consent double.
	 *
	 * @var ConsentService&MockObject
	 */
	private ConsentService&MockObject $consents;

	/**
	 * The consent config double.
	 *
	 * @var ConsentCrudService&MockObject
	 */
	private ConsentCrudService&MockObject $consentConfig;

	/**
	 * The prohibition double.
	 *
	 * @var PolicyMatchService&MockObject
	 */
	private PolicyMatchService&MockObject $policies;

	/**
	 * The review mark double.
	 *
	 * @var RedactionReviewMarkRepository&MockObject
	 */
	private RedactionReviewMarkRepository&MockObject $marks;

	/**
	 * The store double.
	 *
	 * @var PublicationStore&MockObject
	 */
	private PublicationStore&MockObject $store;

	/**
	 * The accessibility double.
	 *
	 * @var RedactionAccessibilityService&MockObject
	 */
	private RedactionAccessibilityService&MockObject $accessibility;

	/**
	 * The calls the doubles saw, in order.
	 *
	 * @var list<string>
	 */
	private array $calls = [];

	/**
	 * Fresh doubles that record their calls.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->calls = [];
		$this->consents = $this->createMock(ConsentService::class);
		$this->consentConfig = $this->createMock(ConsentCrudService::class);
		$this->policies = $this->createMock(PolicyMatchService::class);
		$this->marks = $this->createMock(RedactionReviewMarkRepository::class);
		$this->store = $this->createMock(PublicationStore::class);
		$this->accessibility = $this->createMock(RedactionAccessibilityService::class);

	}//end setUp()

	/**
	 * A document with a reviewed copy, clear consent, no prohibition and a kept structure is ready.
	 *
	 * @return void
	 */
	public function testAFullyClearedDocumentIsReady(): void {
		$consents = [['entityText' => 'Jan', 'scope' => 'document']];
		$this->wire(answers: ['redacted' => 'copy-1', 'mark' => ['by' => 'x'], 'consents' => $consents, 'gate' => ['clear' => true, 'warning' => null]]);
		$this->policies->method('matchProhibition')->willReturnCallback(function (string $entityText, string $entityType): ?array {
			$this->calls[] = 'match:' . $entityText . ':' . $entityType;
			return null;
		});
		$readiness = $this->readiness();

		$record = $readiness->evaluate(['documentFileRef' => 7, 'accessibilityOverrideReason' => 'ok'], new DateTimeImmutable('2026-11-11T10:00:00+00:00'));

		$this->assertSame(
			[
				'documentFileRef' => 7,
				'accessibilityOverrideReason' => 'ok',
				'accessibilityState' => 'preserved',
				'redactedFileRef' => 'copy-1',
				'entitiesReviewed' => true,
				'consentClear' => true,
				'prohibitionsClear' => true,
				'readinessReasons' => [],
				'readinessEvaluatedAt' => '2026-11-11T10:00:00+00:00',
			],
			$record
		);
		$this->assertSame(
			['copy:7', 'mark:7', 'config', 'clear:7', 'consents:7', 'match:Jan:PERSON', 'link:7', 'gate:preserved:ok'],
			$this->calls
		);
		$this->assertTrue($readiness->isReady($record));

	}//end testAFullyClearedDocumentIsReady()

	/**
	 * Every failing check adds its reason, in order: copy, review, accessibility, consent, prohibitions.
	 *
	 * @return void
	 */
	public function testEveryBlockingReasonIsListedInOrder(): void {
		$consents = [
			['entityText' => 'Jan', 'entityType' => 'ORG'],
			['entityText' => 'Piet', 'scope' => 'entity'],
			['entityText' => ''],
			['entityText' => 'Klaas'],
			['entityText' => 'Kees'],
		];
		$clearance = ['clear' => false, 'reasons' => ['Consent c-1 refuses']];
		$gate = ['clear' => false, 'warning' => 'lost'];
		$this->wire(answers: ['redacted' => 'copy-1', 'mark' => null, 'consents' => $consents, 'gate' => $gate, 'clearance' => $clearance]);
		$this->policies->method('matchProhibition')->willReturnCallback(function (string $entityText, string $entityType): ?array {
			$this->calls[] = 'match:' . $entityText . ':' . $entityType;
			$uuids = ['Jan' => 'p-1'];
			return ['uuid' => ($uuids[$entityText] ?? 'p-2')];
		});

		$record = $this->readiness()->evaluate(['documentFileRef' => 'doc'], new DateTimeImmutable('2026-11-11T10:00:00+00:00'));

		$this->assertSame(
			[
				'Nobody has checked the detected entities of document doc',
				'lost',
				'Consent c-1 refuses',
				'Publication prohibition p-1 applies to someone in the document',
				'Publication prohibition p-2 applies to someone in the document',
			],
			$record['readinessReasons']
		);
		$this->assertSame([false, false, false], [$record['entitiesReviewed'], $record['consentClear'], $record['prohibitionsClear']]);
		$matches = array_values(array_filter($this->calls, static fn (string $call): bool => str_starts_with($call, 'match:')));
		$this->assertSame(['match:Jan:ORG', 'match:Klaas:PERSON', 'match:Kees:PERSON'], $matches);
		$this->assertFalse($this->readiness()->isReady($record));

	}//end testEveryBlockingReasonIsListedInOrder()

	/**
	 * Without a redacted copy the accessibility gate is not asked; without a consent register nothing clears.
	 *
	 * @return void
	 */
	public function testNoCopyAndNoConsentRegisterBlock(): void {
		$this->wire(answers: ['redacted' => null, 'mark' => ['by' => 'x'], 'consents' => [], 'gate' => ['clear' => true, 'warning' => null]]);
		$this->consentConfig = $this->createMock(ConsentCrudService::class);
		$this->consentConfig->method('getConsentConfig')->willReturn(null);
		$this->accessibility->expects($this->never())->method('gate');
		$this->store->expects($this->never())->method('findRedactionLink');
		$this->consents->expects($this->never())->method('isDocumentConsentClear');

		$record = $this->readiness()->evaluate(['accessibilityState' => 'stored'], new DateTimeImmutable('2026-11-11T10:00:00+00:00'));

		$this->assertSame(
			[
				'Document  has no redacted copy yet',
				'The consent register is not configured, so consent and prohibitions cannot be checked',
			],
			$record['readinessReasons']
		);
		$verdicts = [$record['entitiesReviewed'], $record['consentClear'], $record['prohibitionsClear']];
		$this->assertSame([false, false, false], $verdicts);
		$this->assertSame(['', 'stored'], [$record['redactedFileRef'], $record['accessibilityState']]);

	}//end testNoCopyAndNoConsentRegisterBlock()

	/**
	 * Without the accessibility service the state is not recorded and does not block.
	 *
	 * @return void
	 */
	public function testWithoutTheAccessibilityServiceTheStateIsNotRecorded(): void {
		$this->wire(answers: ['redacted' => 'copy-1', 'mark' => ['by' => 'x'], 'consents' => [], 'gate' => ['clear' => false, 'warning' => 'lost']]);
		$readiness = new PublicationReadiness($this->consents, $this->consentConfig, $this->policies, $this->marks, $this->store);

		$record = $readiness->evaluate(['documentFileRef' => '9'], new DateTimeImmutable('2026-11-11T10:00:00+00:00'));

		$this->assertArrayNotHasKey('accessibilityState', $record);
		$this->assertSame([], $record['readinessReasons']);
		$this->assertTrue($readiness->isReady($record + ['accessibilityState' => 'degraded']));

	}//end testWithoutTheAccessibilityServiceTheStateIsNotRecorded()

	/**
	 * isReady needs every flag strictly true and an accessibility gate that clears, asked first.
	 *
	 * @return void
	 */
	public function testIsReadyNeedsEveryFlagAndTheGate(): void {
		$ready = ['entitiesReviewed' => true, 'consentClear' => true, 'prohibitionsClear' => true];
		$readiness = $this->readiness();
		$this->assertTrue($readiness->isReady($ready));
		foreach (array_keys($ready) as $flag) {
			$this->assertFalse($readiness->isReady([$flag => 1] + $ready), $flag);
			$without = $ready;
			unset($without[$flag]);
			$this->assertFalse($readiness->isReady($without), $flag);
		}

		$this->setUp();
		$this->accessibility->expects($this->exactly(2))->method('gate')
			->willReturnCallback(static fn (?string $state, string $overrideReason = ''): array => ['clear' => $overrideReason === 'why', 'warning' => null]);
		$readiness = $this->readiness();
		$this->assertFalse($readiness->isReady(['accessibilityState' => 'degraded'] + $ready));
		$this->assertFalse($readiness->isReady(['accessibilityState' => 'degraded', 'accessibilityOverrideReason' => 'why']));

	}//end testIsReadyNeedsEveryFlagAndTheGate()

	/**
	 * Wire the doubles to answer for any document and record their calls.
	 *
	 * @param array<string, mixed> $answers The redacted copy, review mark, consent records,
	 *                                      accessibility gate and, optionally, the consent clearance
	 *
	 * @return void
	 */
	private function wire(array $answers): void {
		$redacted = $answers['redacted'];
		$mark = $answers['mark'];
		$consents = $answers['consents'];
		$gate = $answers['gate'];
		$clearance = ($answers['clearance'] ?? ['clear' => true, 'reasons' => []]);
		$this->store->method('findRedactedCopy')->willReturnCallback(function (string $fileId) use ($redacted): ?string {
			$this->calls[] = 'copy:' . $fileId;
			return $redacted;
		});
		$this->marks->method('findFor')->willReturnCallback(function (string $document) use ($mark): ?array {
			$this->calls[] = 'mark:' . $document;
			return $mark;
		});
		$this->consentConfig->method('getConsentConfig')->willReturnCallback(function (): array {
			$this->calls[] = 'config';
			return self::CONFIG;
		});
		$this->consents->method('isDocumentConsentClear')->willReturnCallback(function (string $documentId) use ($clearance): array {
			$this->calls[] = 'clear:' . $documentId;
			return $clearance;
		});
		$this->consents->method('getConsentsByDocument')->willReturnCallback(function (string $documentId) use ($consents): array {
			$this->calls[] = 'consents:' . $documentId;
			return $consents;
		});
		$this->store->method('findRedactionLink')->willReturnCallback(function (string $fileId): array {
			$this->calls[] = 'link:' . $fileId;
			return ['structurePreservation' => ['state' => 'preserved']];
		});
		$this->accessibility->method('gate')->willReturnCallback(function (?string $state, string $overrideReason = '') use ($gate): array {
			$this->calls[] = 'gate:' . $state . ':' . $overrideReason;
			return $gate;
		});

	}//end wire()

	/**
	 * The readiness checks over the doubles.
	 *
	 * @return PublicationReadiness
	 */
	private function readiness(): PublicationReadiness {
		return new PublicationReadiness($this->consents, $this->consentConfig, $this->policies, $this->marks, $this->store, $this->accessibility);

	}//end readiness()
}//end class
