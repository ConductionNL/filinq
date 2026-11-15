<?php

/**
 * Unit tests for DetectionRunIdentity
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Redaction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\Redaction\DetectionRunIdentity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * A detection run is named by its file, the file's version and what was
 * found, never by the order the detector reported it in.
 */
#[CoversClass(DetectionRunIdentity::class)]
class DetectionRunIdentityTest extends TestCase {

	/**
	 * The subject.
	 *
	 * @var DetectionRunIdentity
	 */
	private DetectionRunIdentity $identity;

	/**
	 * Build the subject.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->identity = new DetectionRunIdentity();
	}//end setUp()

	/**
	 * A file without an id has no run identity.
	 *
	 * @return void
	 */
	public function testAFileWithoutAnIdHasNoIdentity(): void {
		$this->assertSame('', $this->identity->of(fileId: 0, entities: [['type' => 'PERSON', 'text' => 'Jan']]));
		$this->assertSame('', $this->identity->of(fileId: -3, entities: []));
	}//end testAFileWithoutAnIdHasNoIdentity()

	/**
	 * The identity is the first 40 hex characters of the SHA-256 of file, etag and sorted fingerprints.
	 *
	 * @return void
	 */
	public function testTheIdentityIsTheHashOfItsMaterial(): void {
		$entities = [
			['entityType' => 'PERSON', 'text' => 'Jan', 'start' => 4, 'end' => 7],
			['type' => 'EMAIL', 'value' => 'a@b.nl', 'offset' => 10, 'length' => 6],
		];
		$expected = substr(hash('sha256', '42|v1|EMAIL:a@b.nl:10:6,PERSON:Jan:4:7'), 0, 40);

		$this->assertSame($expected, $this->identity->of(fileId: 42, entities: $entities, etag: ' v1 '));
	}//end testTheIdentityIsTheHashOfItsMaterial()

	/**
	 * The detector's order does not change the identity; non-array entries are ignored.
	 *
	 * @return void
	 */
	public function testOrderAndNoiseDoNotChangeTheIdentity(): void {
		$a = ['type' => 'PERSON', 'text' => 'Jan', 'start' => 1, 'end' => 4];
		$b = ['type' => 'IBAN', 'text' => 'NL01', 'start' => 9, 'end' => 13];

		$this->assertSame(
			$this->identity->of(fileId: 5, entities: [$a, $b]),
			$this->identity->of(fileId: 5, entities: [$b, 'noise', null, $a])
		);
	}//end testOrderAndNoiseDoNotChangeTheIdentity()

	/**
	 * A different file version or a different finding is a different run.
	 *
	 * @return void
	 */
	public function testAnotherVersionOrFindingIsAnotherRun(): void {
		$entities = [['type' => 'PERSON', 'text' => 'Jan', 'start' => 1, 'end' => 4]];
		$base = $this->identity->of(fileId: 5, entities: $entities, etag: 'e1');

		$this->assertNotSame($base, $this->identity->of(fileId: 5, entities: $entities, etag: 'e2'));
		$this->assertNotSame($base, $this->identity->of(fileId: 6, entities: $entities, etag: 'e1'));
		$this->assertNotSame($base, $this->identity->of(fileId: 5, entities: [['type' => 'PERSON', 'text' => 'Jan', 'start' => 2, 'end' => 5]], etag: 'e1'));
		$this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $base);
	}//end testAnotherVersionOrFindingIsAnotherRun()
}//end class
