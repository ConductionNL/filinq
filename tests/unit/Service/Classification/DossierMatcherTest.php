<?php

/**
 * Tests for DossierMatcher
 *
 * Files a document under a dossier only when exactly one dossier matches by
 * correspondent name or by a case reference in the text.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Classification;

use OCA\Filinq\Service\Classification\DossierMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the dossier matching rules.
 *
 * @covers \OCA\Filinq\Service\Classification\DossierMatcher
 */
class DossierMatcherTest extends TestCase {

	/**
	 * The subject.
	 *
	 * @var DossierMatcher
	 */
	private DossierMatcher $matcher;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->matcher = new DossierMatcher();

	}//end setUp()

	/**
	 * A dossier named after the correspondent, alone or followed by more words,
	 * matches case- and punctuation-insensitively.
	 *
	 * @return void
	 */
	public function testDossierNamedAfterTheCorrespondentMatches(): void {
		$dossiers = [
			['uuid' => 'u-1', 'name' => 'Heijmans: renovatie stadhuis'],
			['uuid' => 'u-2', 'name' => 'Ballast Nedam'],
		];

		$this->assertSame('u-1', $this->matcher->match($dossiers, 'HEIJMANS', ''));
		$this->assertSame('u-2', $this->matcher->match($dossiers, 'ballast nedam', ''));

	}//end testDossierNamedAfterTheCorrespondentMatches()

	/**
	 * The correspondent must match whole words: a longer word that starts with it
	 * is not a match.
	 *
	 * @return void
	 */
	public function testCorrespondentPrefixMustEndAtAWordBoundary(): void {
		$dossiers = [['uuid' => 'u-1', 'name' => 'Heijmansstraat herinrichting']];

		$this->assertNull($this->matcher->match($dossiers, 'Heijmans', ''));

	}//end testCorrespondentPrefixMustEndAtAWordBoundary()

	/**
	 * A case reference in the document text selects the dossier carrying it.
	 *
	 * @return void
	 */
	public function testCaseReferenceInTheTextMatches(): void {
		$dossiers = [
			['uuid' => 'u-1', 'name' => 'Bouwvergunning ZK-2024-123'],
			['uuid' => 'u-2', 'name' => 'Bouwvergunning ZK-2024-1234'],
		];

		$this->assertSame('u-1', $this->matcher->match($dossiers, null, 'Betreft uw aanvraag ZK-2024-123 van maart.'));

	}//end testCaseReferenceInTheTextMatches()

	/**
	 * More than one matching dossier is ambiguous, and none is no match: both null.
	 *
	 * @return void
	 */
	public function testAmbiguousOrAbsentMatchYieldsNull(): void {
		$dossiers = [
			['uuid' => 'u-1', 'name' => 'Heijmans renovatie'],
			['uuid' => 'u-2', 'name' => 'Heijmans nieuwbouw'],
		];

		$this->assertNull($this->matcher->match($dossiers, 'Heijmans', ''));
		$this->assertNull($this->matcher->match($dossiers, 'Acme', 'geen kenmerk'));
		$this->assertNull($this->matcher->match($dossiers, null, ''));

	}//end testAmbiguousOrAbsentMatchYieldsNull()

	/**
	 * Dossiers without a name or uuid are never matched and do not make a
	 * single real match ambiguous.
	 *
	 * @return void
	 */
	public function testDossiersWithoutNameOrUuidAreSkipped(): void {
		$dossiers = [
			['uuid' => '', 'name' => 'Heijmans oud'],
			['uuid' => 'u-3', 'name' => '  '],
			['name' => 'Heijmans zonder uuid'],
			['uuid' => 'u-4', 'name' => 'Heijmans renovatie'],
		];

		$this->assertSame('u-4', $this->matcher->match($dossiers, 'Heijmans', ''));

	}//end testDossiersWithoutNameOrUuidAreSkipped()
}//end class
