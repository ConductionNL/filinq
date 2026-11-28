<?php

/**
 * Unit tests for MdtoRestrictionProposer: the beperkingGebruik proposal matrix
 * over consent, prohibition and anonymisation state.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-filinq-context-prefills-the-supplement-as-confirmable-proposals-req-ddtmm-003
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\MdtoRestrictionProposer;
use OCA\Filinq\Service\MdtoRestrictionSources;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MdtoRestrictionProposer::class)]
#[CoversClass(MdtoRestrictionSources::class)]
class MdtoRestrictionProposerTest extends TestCase {

	/**
	 * Consent states and whether each proposes an AVG restriction.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function consentStates(): array {
		return [
			'objection' => ['objection_received', true],
			'still pending' => ['pending', true],
			'no response' => ['no_response', true],
			'consent given' => ['consent_given', false],
			'already anonymised' => ['anonymized', false],
		];

	}//end consentStates()

	/**
	 * An unresolved or objected consent proposes an AVG restriction; a resolved one does not.
	 *
	 * @param string $state    The consent status.
	 * @param bool   $proposes Whether a restriction is proposed.
	 *
	 * @return void
	 */
	#[DataProvider('consentStates')]
	public function testConsentStateDecidesTheAvgProposal(string $state, bool $proposes): void {
		$entries = (new MdtoRestrictionProposer())->propose(
			new MdtoRestrictionSources(consents: [['consentStatus' => $state, 'legalBasis' => 'AVG art. 6 lid 1 sub e']]),
			'2026-11-14'
		);

		$expected = [];
		if ($proposes === true) {
			$expected = [['type' => 'avg-persoonsgegevens', 'grondslag' => 'AVG art. 6 lid 1 sub e', 'proposed' => true, 'source' => 'consent']];
		}

		$this->assertSame($expected, $entries);

	}//end testConsentStateDecidesTheAvgProposal()

	public function testAConsentWithoutALegalBasisFallsBackToAvg(): void {
		$entries = (new MdtoRestrictionProposer())->propose(new MdtoRestrictionSources(consents: [['consentStatus' => 'objection_received', 'legalBasis' => '  ']]), '2026-11-14');

		$this->assertSame('AVG', $entries[0]['grondslag']);

	}//end testAConsentWithoutALegalBasisFallsBackToAvg()

	/**
	 * Prohibitions and whether each is active on 2026-11-14.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: bool}>
	 */
	public static function prohibitions(): array {
		return [
			'active, open-ended' => [['active' => true], true],
			'active, inside its window' => [['active' => true, 'validFrom' => '2026-01-01', 'validUntil' => '2026-12-31T23:59:59Z'], true],
			'active, ends today' => [['active' => true, 'validUntil' => '2026-11-14'], true],
			'active, not started' => [['active' => true, 'validFrom' => '2026-11-15'], false],
			'active, expired' => [['active' => true, 'validUntil' => '2026-11-13'], false],
			'switched off' => [['active' => false], false],
			'active as a string' => [['active' => 'true'], false],
		];

	}//end prohibitions()

	/**
	 * Only a prohibition active on the reference date proposes a Woo exception, with its legal authority.
	 *
	 * @param array<string, mixed> $prohibition The prohibition.
	 * @param bool                 $proposes    Whether a restriction is proposed.
	 *
	 * @return void
	 */
	#[DataProvider('prohibitions')]
	public function testOnlyAnActiveProhibitionProposesAWooException(array $prohibition, bool $proposes): void {
		$prohibition['legalAuthority'] = 'Rechtbank Den Haag KG ZA 24/123';
		$entries = (new MdtoRestrictionProposer())->propose(new MdtoRestrictionSources(prohibitions: [$prohibition]), '2026-11-14');

		$expected = [];
		if ($proposes === true) {
			$expected = [['type' => 'woo-uitzondering', 'grondslag' => 'Rechtbank Den Haag KG ZA 24/123', 'proposed' => true, 'source' => 'prohibition']];
		}

		$this->assertSame($expected, $entries);

	}//end testOnlyAnActiveProhibitionProposesAWooException()

	public function testAProhibitionWithoutAuthorityFallsBackToTheWooArticle(): void {
		$entries = (new MdtoRestrictionProposer())->propose(new MdtoRestrictionSources(prohibitions: [['active' => true]]), '2026-11-14');

		$this->assertSame('Woo art. 5.1', $entries[0]['grondslag']);

	}//end testAProhibitionWithoutAuthorityFallsBackToTheWooArticle()

	public function testAnAnonymisedRenditionProposesANoteNamingIt(): void {
		$entries = (new MdtoRestrictionProposer())->propose(
			new MdtoRestrictionSources(renditions: [['anonymizedFileName' => 'besluit-geanonimiseerd.pdf'], ['anonymizedFileName' => '']]),
			'2026-11-14'
		);

		$this->assertSame(
			[
				[
					'type' => 'avg-persoonsgegevens',
					'grondslag' => 'AVG',
					'proposed' => true,
					'source' => 'anonymisation',
					'omschrijving' => 'De openbare versie is de geanonimiseerde afleiding besluit-geanonimiseerd.pdf.',
				],
			],
			$entries
		);

	}//end testAnAnonymisedRenditionProposesANoteNamingIt()

	public function testIdenticalProposalsAreListedOnceInSourceOrder(): void {
		$objection = ['consentStatus' => 'objection_received', 'legalBasis' => 'AVG'];
		$entries   = (new MdtoRestrictionProposer())->propose(
			new MdtoRestrictionSources(consents: [$objection, $objection], prohibitions: [['active' => true]]),
			'2026-11-14'
		);

		$this->assertSame(['consent', 'prohibition'], array_column($entries, 'source'));

	}//end testIdenticalProposalsAreListedOnceInSourceOrder()
}//end class
