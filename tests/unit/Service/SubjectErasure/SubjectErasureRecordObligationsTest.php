<?php

/**
 * The record that holds a file refuses an erasure for its retention and for a hold placed by anybody.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\SubjectErasure
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\SubjectErasure;

use PHPUnit\Framework\TestCase;

/**
 * Before this, only Filinq's own hold cases refused: a document in a dossier
 * appraised to be kept permanently, or held by another party directly on the
 * OpenRegister record, was erased. The real services run over in-memory files
 * whose folder carries the record's uuid, as OpenRegister names object folders.
 */
class SubjectErasureRecordObligationsTest extends TestCase {
	use SubjectErasureDoubles;

	private const PERSON = 'Jan Jansen';

	private const RECORD = '3f2b8c1e-5d4a-4e6b-9c7d-8a1b2c3d4e5f';

	/**
	 * Two documents naming the person; file 12 sits in the record's folder.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->addFile(fileId: 11, name: 'aanvraag.txt', content: 'Aanvrager: Jan Jansen.');
		$this->addFile(fileId: 12, name: 'besluit.txt', content: 'Besluit op de aanvraag van Jan Jansen.');
		$this->files[12]['parent'] = self::RECORD;
		$this->catalogueHas(type: 'PERSON', value: self::PERSON, fileIds: [11, 12]);

	}//end setUp()

	/**
	 * Place, preview and run a request as the privacy officer.
	 *
	 * @return array<string, mixed> The run's answer.
	 */
	private function erase(): array {
		$services = $this->erasure();
		$uuid = (string) $services['service']->create(
			input: ['subject' => self::PERSON, 'ground' => 'AVG artikel 17 lid 1 onder a'],
			userId: 'petra'
		)['uuid'];
		$services['service']->preview(uuid: $uuid, userId: 'petra');

		return $services['run']->run(uuid: $uuid, userId: 'petra');

	}//end erase()

	/**
	 * A record appraised to be kept permanently keeps its document as it is, and says who decides.
	 *
	 * @return void
	 */
	public function testAPermanentlyKeptRecordRefusesForRetention(): void {
		$this->records[self::RECORD] = ['archiefnominatie' => 'blijvend_bewaren'];
		$kept = $this->files[12]['content'];

		$outcome = $this->erase();

		$this->assertSame($kept, $this->files[12]['content']);
		$this->assertStringNotContainsString(self::PERSON, $this->files[11]['content']);
		$refused = $outcome['certificate']['refused'];
		$this->assertCount(1, $refused);
		$this->assertSame('retention', $refused[0]['obligation']);
		$this->assertStringContainsString(self::RECORD, $refused[0]['reason']);
		$this->assertSame('the archivist responsible for the selectielijst term', $refused[0]['decidedBy']);
		$this->assertFalse($outcome['certificate']['complete']);

	}//end testAPermanentlyKeptRecordRefusesForRetention()

	/**
	 * A record that will be destroyed carries no duty to keep the name.
	 *
	 * @return void
	 */
	public function testARecordToBeDestroyedDoesNotRefuse(): void {
		$this->records[self::RECORD] = ['archiefnominatie' => 'vernietigen', 'archiefactiedatum' => '2040-01-01'];

		$outcome = $this->erase();

		$this->assertStringNotContainsString(self::PERSON, $this->files[12]['content']);
		$this->assertSame([], $outcome['certificate']['refused']);

	}//end testARecordToBeDestroyedDoesNotRefuse()

	/**
	 * A hold another party placed on the record wins, even with no Filinq hold case.
	 *
	 * @return void
	 */
	public function testAHoldOnTheRecordByAnotherPartyWins(): void {
		$this->records[self::RECORD] = ['legalHold' => ['active' => true, 'reason' => 'dossiq-bezwaar-88']];
		$kept = $this->files[12]['content'];

		$outcome = $this->erase();

		$this->assertSame($kept, $this->files[12]['content']);
		$this->assertSame('legal_hold', $outcome['certificate']['refused'][0]['obligation']);
		$this->assertStringContainsString('dossiq-bezwaar-88', $outcome['certificate']['refused'][0]['reason']);

	}//end testAHoldOnTheRecordByAnotherPartyWins()

	/**
	 * A record that cannot be read counts as kept and held, never as clear.
	 *
	 * @return void
	 */
	public function testAnUnreadableRecordRefuses(): void {
		$this->recordsDown = true;
		$kept = $this->files[12]['content'];

		$outcome = $this->erase();

		$this->assertSame($kept, $this->files[12]['content']);
		$this->assertStringNotContainsString(self::PERSON, $this->files[11]['content']);
		$this->assertSame(['legal_hold', 'retention'], array_column($outcome['certificate']['refused'], 'obligation'));

	}//end testAnUnreadableRecordRefuses()

	/**
	 * A file outside any object folder has no record to ask, and is erased.
	 *
	 * @return void
	 */
	public function testAFileOutsideAnObjectFolderIsNotRefused(): void {
		$this->recordsDown = true;
		unset($this->files[12]['parent']);

		$outcome = $this->erase();

		$this->assertStringNotContainsString(self::PERSON, $this->files[12]['content']);
		$this->assertSame([], $outcome['certificate']['refused']);

	}//end testAFileOutsideAnObjectFolderIsNotRefused()
}//end class
