<?php

/**
 * Unit tests for SigningConcludedEvent.
 *
 * Pins the decidesk delegation seam (signing-trust-rebuild REQ-DDSTR-010): the
 * completion payload built by SigningConcludedEventFactory::create() carries a resolved eIDAS
 * `assuranceLevel` alongside the pre-existing response-shape fields —
 * additive only, so `EIDASSignatureService::composeFilinqSigningRequest()`'s
 * existing expectations keep passing. The native provider only ever produces
 * SES artifacts, so it always resolves `low` — never over-claiming an
 * assurance the artifact cannot actually evidence.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Event
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @version GIT: <git_id>
 *
 * @spec openspec/specs/document-signing/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Event;

use OCA\Filinq\Event\SigningConcludedEventFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SigningConcludedEvent.
 *
 * @spec openspec/specs/document-signing/spec.md
 */
class SigningConcludedEventTest extends TestCase {

	/**
	 * The request-array -> event mapping under test.
	 *
	 * @var SigningConcludedEventFactory
	 */
	private SigningConcludedEventFactory $factory;

	/**
	 * Build the factory. It is a stateless mapper with no collaborators — the
	 * mapping moved here from the event's former static `fromRequest()` named
	 * constructor, so these tests exercise exactly the same code as before.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->factory = new SigningConcludedEventFactory();

	}//end setUp()

	/**
	 * A native-provider, SES-level request resolves `low` assurance —
	 * the ONLY level the native provider can ever actually produce.
	 *
	 * @return void
	 */
	public function testNativeSesResolvesLowAssurance(): void {
		$event = $this->factory->create(
			request: ['id' => 'req-1', 'provider' => 'native', 'signatureLevel' => 'SES'],
			status: 'signed'
		);

		$this->assertSame('low', $event->getAssuranceLevel());

	}//end testNativeSesResolvesLowAssurance()

	/**
	 * A request naming no provider/level (legacy/internal shape) defaults to
	 * the conservative `low`, never over-claiming.
	 *
	 * @return void
	 */
	public function testMissingProviderLevelDefaultsToLow(): void {
		$event = $this->factory->create(request: ['id' => 'req-1'], status: 'signed');

		$this->assertSame('low', $event->getAssuranceLevel());

	}//end testMissingProviderLevelDefaultsToLow()

	/**
	 * A non-native provider at AdES/QES resolves substantial/high — but the
	 * native provider is NEVER granted more than `low` regardless of the
	 * requested level (defence in depth: `supportsLevel()` already rejects
	 * native+non-SES at creation, this is a second, independent floor).
	 *
	 * @return void
	 */
	public function testNonNativeProviderResolvesHigherAssurance(): void {
		$adesEvent = $this->factory->create(
			request: ['id' => 'req-1', 'provider' => 'validsign', 'signatureLevel' => 'AdES'],
			status: 'signed'
		);
		$this->assertSame('substantial', $adesEvent->getAssuranceLevel());

		$qesEvent = $this->factory->create(
			request: ['id' => 'req-1', 'provider' => 'validsign', 'signatureLevel' => 'QES'],
			status: 'signed'
		);
		$this->assertSame('high', $qesEvent->getAssuranceLevel());

		// A native provider is NEVER granted more than `low`, even if a
		// (creation-time-rejected) higher level ever appeared on the row.
		$nativeEvent = $this->factory->create(
			request: ['id' => 'req-1', 'provider' => 'native', 'signatureLevel' => 'QES'],
			status: 'signed'
		);
		$this->assertSame('low', $nativeEvent->getAssuranceLevel());

	}//end testNonNativeProviderResolvesHigherAssurance()

	/**
	 * Additive-only pin: every pre-existing field the mapping populated
	 * before REQ-DDSTR-010 still round-trips unchanged (backward-compatible
	 * response shape for the decidesk consumer).
	 *
	 * @return void
	 */
	public function testPreExistingFieldsStillRoundTrip(): void {
		$event = $this->factory->create(
			request: [
				'id' => 'req-1',
				'signerIds' => ['s1', 's2'],
				'signedAt' => '2026-07-23T12:00:00+00:00',
				'sourceApp' => 'shillinq',
				'subjectRegister' => 'shillinq',
				'subjectSchema' => 'candidate',
				'subjectId' => 'subj-1',
				'externalReference' => 'ext-ref-1',
				'correlationId' => 'corr-1',
				'provider' => 'native',
				'signatureLevel' => 'SES',
			],
			status: 'signed',
			signedDocumentRef: 'file-42:v3'
		);

		$this->assertSame('req-1', $event->getSigningRequestId());
		$this->assertSame('signed', $event->getStatus());
		$this->assertSame('file-42:v3', $event->getSignedDocumentRef());
		$this->assertSame(['s1', 's2'], $event->getSigners());
		$this->assertSame('2026-07-23T12:00:00+00:00', $event->getSignedAt());
		$this->assertSame('shillinq', $event->getSourceApp());
		$this->assertSame('shillinq', $event->getSubjectRegister());
		$this->assertSame('candidate', $event->getSubjectSchema());
		$this->assertSame('subj-1', $event->getSubjectId());
		$this->assertSame('ext-ref-1', $event->getExternalReference());
		$this->assertSame('corr-1', $event->getCorrelationId());
		$this->assertSame('low', $event->getAssuranceLevel());

	}//end testPreExistingFieldsStillRoundTrip()
}//end class
