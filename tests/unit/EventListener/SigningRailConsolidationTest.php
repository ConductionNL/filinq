<?php

/**
 * Unit tests for the one signing rail: the signed noun and the refusal of a
 * delegated request that does not say what it signs.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\Event\DocumentSigningRequestedEvent;
use OCA\Filinq\Event\SigningProvenance;
use OCA\Filinq\EventListener\DocumentSigningRequestedListener;
use OCA\Filinq\Service\SigningService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the signed noun and the unattributed-request refusal.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SigningRailConsolidationTest extends TestCase {

	/**
	 * Build a request event from a provenance.
	 *
	 * @param SigningProvenance $provenance The provenance.
	 *
	 * @return DocumentSigningRequestedEvent
	 */
	private function makeEvent(SigningProvenance $provenance): DocumentSigningRequestedEvent {
		return new DocumentSigningRequestedEvent(
			provenance: $provenance,
			subjectLabel: 'Subject',
			documentReference: 'file-1',
			signers: [['userId' => 'bob']],
			signatureLevel: 'SES',
			signingMode: 'sequential'
		);

	}//end makeEvent()

	/**
	 * Run an event through the listener and return the create data it sent.
	 *
	 * @param DocumentSigningRequestedEvent $event The event.
	 *
	 * @return array<string, mixed>|null The data handed to createRequest, or null.
	 */
	private function createDataFor(DocumentSigningRequestedEvent $event): ?array {
		$captured       = null;
		$signingService = $this->createMock(SigningService::class);
		$signingService->method('createRequest')->willReturnCallback(
			function (array $data) use (&$captured): array {
				$captured = $data;
				return ['id' => 'req-1'];
			}
		);

		$listener = new DocumentSigningRequestedListener(signingService: $signingService, logger: $this->createMock(LoggerInterface::class));
		$listener->handle($event);

		return $captured;

	}//end createDataFor()

	/**
	 * The noun is the schema slug, or the last segment of a path-shaped one.
	 *
	 * @return void
	 */
	public function testTheNounIsTheLastSegmentOfTheSchema(): void {
		$this->assertSame('beschikking', (new SigningProvenance(subjectSchema: 'beschikking'))->getSubjectNoun());
		$this->assertSame('minutes', (new SigningProvenance(subjectSchema: 'decidiq/minutes/'))->getSubjectNoun());
		$this->assertSame('', (new SigningProvenance())->getSubjectNoun());
		$this->assertSame('', (new SigningProvenance(subjectSchema: '  '))->getSubjectNoun());

	}//end testTheNounIsTheLastSegmentOfTheSchema()

	/**
	 * Two apps' requests carry different nouns onto the signing request.
	 *
	 * @return void
	 */
	public function testABeschikkingAndMinutesReadApartOnTheRecord(): void {
		$dossiq  = $this->createDataFor($this->makeEvent(new SigningProvenance('dossiq', 'zaken', 'beschikking', 'b-1')));
		$decidiq = $this->createDataFor($this->makeEvent(new SigningProvenance('decidiq', 'meetings', 'minutes', 'm-1')));

		$this->assertSame('beschikking', $dossiq['subjectNoun']);
		$this->assertSame('minutes', $decidiq['subjectNoun']);

	}//end testABeschikkingAndMinutesReadApartOnTheRecord()

	/**
	 * Cases of a delegated request missing one subject field.
	 *
	 * @return array<string, array{0: SigningProvenance, 1: string}>
	 */
	public static function unattributedProvider(): array {
		return [
			'no register' => [new SigningProvenance('dossiq', null, 'beschikking', 'b-1'), 'subjectRegister'],
			'no schema'   => [new SigningProvenance('dossiq', 'zaken', '', 'b-1'), 'subjectSchema'],
			'no id'       => [new SigningProvenance('dossiq', 'zaken', 'beschikking', ' '), 'subjectId'],
		];

	}//end unattributedProvider()

	/**
	 * A delegated request missing a subject field is refused, never created.
	 *
	 * @param SigningProvenance $provenance The provenance.
	 * @param string            $missing    The field it lacks.
	 *
	 * @return void
	 */
	#[DataProvider('unattributedProvider')]
	public function testAnUnattributedDelegatedRequestIsRefused(SigningProvenance $provenance, string $missing): void {
		$this->assertSame([$missing], $provenance->missingSubjectFields());

		$signingService = $this->createMock(SigningService::class);
		$signingService->expects($this->never())->method('createRequest');
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$event = $this->makeEvent($provenance);
		(new DocumentSigningRequestedListener(signingService: $signingService, logger: $logger))->handle($event);

		$this->assertFalse($event->isHandled());
		$this->assertSame(
			'Filinq refused the signing request from dossiq: it does not say what it signs (missing: '.$missing.').',
			$event->getRefusal()
		);

	}//end testAnUnattributedDelegatedRequestIsRefused()

	/**
	 * All three missing fields are named, in order.
	 *
	 * @return void
	 */
	public function testEveryMissingFieldIsNamed(): void {
		$this->assertSame(['subjectRegister', 'subjectSchema', 'subjectId'], (new SigningProvenance('dossiq'))->missingSubjectFields());

	}//end testEveryMissingFieldIsNamed()

	/**
	 * A request raised inside filinq names no source app and is not refused.
	 *
	 * @return void
	 */
	public function testAnInternalRequestIsNotAskedForASubject(): void {
		$provenance = new SigningProvenance();
		$this->assertFalse($provenance->isDelegated());
		$this->assertSame([], $provenance->missingSubjectFields());

		$event = $this->makeEvent($provenance);
		$data  = $this->createDataFor($event);

		$this->assertNotNull($data);
		$this->assertTrue($event->isHandled());
		$this->assertNull($event->getRefusal());
		$this->assertSame('', $data['subjectNoun']);

	}//end testAnInternalRequestIsNotAskedForASubject()
}//end class
