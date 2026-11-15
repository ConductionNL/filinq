<?php

/**
 * Tests for CorrespondentRanker
 *
 * Picks the correspondent from detected entities: frequency, letterhead
 * position and organisation marks, ties to the earlier mention.
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

use OCA\Filinq\Service\Classification\CorrespondentRanker;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the correspondent ranking rules.
 *
 * @covers \OCA\Filinq\Service\Classification\CorrespondentRanker
 */
class CorrespondentRankerTest extends TestCase {

	/**
	 * The subject.
	 *
	 * @var CorrespondentRanker
	 */
	private CorrespondentRanker $ranker;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ranker = new CorrespondentRanker();

	}//end setUp()

	/**
	 * Build one detected-entity row.
	 *
	 * @param string $type The entity type.
	 * @param string $value The entity value.
	 * @param int $position The start offset.
	 *
	 * @return array<string, mixed>
	 */
	private function row(string $type, string $value, int $position): array {
		return ['entity_type' => $type, 'entity_value' => $value, 'position_start' => $position];

	}//end row()

	/**
	 * Only named persons and organisations are candidates; nothing else ranks.
	 *
	 * @return void
	 */
	public function testNoPersonOrOrganisationYieldsNull(): void {
		$this->assertNull($this->ranker->rank([]));
		$this->assertNull(
			$this->ranker->rank(
				[
					$this->row('EMAIL', 'info@example.nl', 10),
					$this->row('PERSON', '   ', 20),
					['entity_value' => 'No type'],
				]
			)
		);

	}//end testNoPersonOrOrganisationYieldsNull()

	/**
	 * A mention in the letterhead outweighs a name mentioned twice further down.
	 *
	 * @return void
	 */
	public function testLetterheadMentionOutweighsRepetitionLowerDown(): void {
		$result = $this->ranker->rank(
			[
				$this->row('PERSON', 'Piet Jansen', 2000),
				$this->row('PERSON', 'Piet Jansen', 2500),
				$this->row('person', 'Anna de Vries', 100),
			]
		);

		$this->assertSame(['name' => 'Anna de Vries', 'entityType' => 'PERSON', 'source' => 'ner'], $result);

	}//end testLetterheadMentionOutweighsRepetitionLowerDown()

	/**
	 * Mentions of one name are counted case-insensitively, and the frequency wins
	 * when position does not decide.
	 *
	 * @return void
	 */
	public function testRepeatedMentionsAreCountedCaseInsensitively(): void {
		$result = $this->ranker->rank(
			[
				$this->row('PERSON', 'Anna de Vries', 900),
				$this->row('PERSON', 'Piet Jansen', 1000),
				$this->row('PERSON', 'PIET JANSEN', 1100),
			]
		);

		$this->assertSame('Piet Jansen', $result['name']);

	}//end testRepeatedMentionsAreCountedCaseInsensitively()

	/**
	 * A person-typed name with a legal-form mark at either end ranks like an
	 * organisation.
	 *
	 * @return void
	 */
	public function testOrganisationMarkOnAPersonNameEarnsTheOrganisationBonus(): void {
		$trailing = $this->ranker->rank(
			[
				$this->row('PERSON', 'Piet Jansen', 900),
				$this->row('PERSON', 'Piet Jansen', 950),
				$this->row('PERSON', 'Heijmans B.V.', 1000),
			]
		);
		$leading = $this->ranker->rank(
			[
				$this->row('PERSON', 'Piet Jansen', 900),
				$this->row('PERSON', 'Piet Jansen', 950),
				$this->row('PERSON', 'Gemeente Tilburg', 1000),
			]
		);

		$this->assertSame(['name' => 'Heijmans B.V.', 'entityType' => 'PERSON', 'source' => 'ner'], $trailing);
		$this->assertSame('Gemeente Tilburg', $leading['name']);

	}//end testOrganisationMarkOnAPersonNameEarnsTheOrganisationBonus()

	/**
	 * An organisation-typed entity beats a person mentioned once at the same depth.
	 *
	 * @return void
	 */
	public function testOrganisationTypeEarnsTheBonus(): void {
		$result = $this->ranker->rank(
			[
				$this->row('PERSON', 'Piet Jansen', 900),
				$this->row('ORGANIZATION', 'Acme', 1000),
			]
		);

		$this->assertSame(['name' => 'Acme', 'entityType' => 'ORGANIZATION', 'source' => 'ner'], $result);

	}//end testOrganisationTypeEarnsTheBonus()

	/**
	 * Equal scores go to the earlier mention, whatever the row order.
	 *
	 * @return void
	 */
	public function testTieGoesToTheEarlierMention(): void {
		$result = $this->ranker->rank(
			[
				$this->row('PERSON', 'Late Name', 1500),
				$this->row('PERSON', 'Early Name', 1200),
			]
		);

		$this->assertSame('Early Name', $result['name']);

	}//end testTieGoesToTheEarlierMention()
}//end class
