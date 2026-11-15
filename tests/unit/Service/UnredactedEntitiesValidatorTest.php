<?php

/**
 * Unit tests for UnredactedEntitiesValidator
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\UnredactedEntitiesValidator;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

/**
 * Every message the unredactedEntities[] check can give, in the order they win.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class UnredactedEntitiesValidatorTest extends TestCase {

	/**
	 * Validate the entries with a translator that marks and fills in its text.
	 *
	 * @param array<int, mixed> $entries The entries
	 *
	 * @return string|null The error message, or null when every entry is valid.
	 */
	private function errorFor(array $entries): ?string {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []): string => '[t] ' . vsprintf($text, $parameters)
		);

		$result = (new UnredactedEntitiesValidator($l10n))->validate($entries);
		if ($result === null) {
			return null;
		}

		$this->assertSame(400, $result['status']);
		$this->assertSame(['error'], array_keys($result['body']));
		return $result['body']['error'];

	}//end errorFor()

	/**
	 * A complete entry, to break one field of.
	 *
	 * @return array<string, mixed>
	 */
	private function valid(): array {
		return ['entityId' => 3, 'entityText' => 'Jan', 'entityType' => 'PERSON', 'publicationBases' => ['5.1.2e']];

	}//end valid()

	/**
	 * Valid entries, with and without the optional contact fields, pass.
	 *
	 * @return void
	 */
	public function testValidEntriesPass(): void {
		$this->assertNull($this->errorFor([]));
		$withContact = $this->valid() + ['contactEmail' => 'a@b.nl', 'contactAddress' => 'Straat 1', 'extra' => 1];
		$this->assertNull($this->errorFor([$this->valid(), $withContact]));
		$this->assertNull($this->errorFor([$this->valid() + ['contactEmail' => null]]));

	}//end testValidEntriesPass()

	/**
	 * The entry shape and the identity fields are checked first.
	 *
	 * @return void
	 */
	public function testShapeAndIdentityFields(): void {
		$this->assertSame('[t] Each unredactedEntities entry must be an object (index 1)', $this->errorFor([$this->valid(), 'Jan']));

		$idMessage = '[t] unredactedEntities[0].entityId is required and must be an integer';
		$this->assertSame($idMessage, $this->errorFor([['entityText' => '']]));
		$this->assertSame($idMessage, $this->errorFor([['entityId' => '3'] + $this->valid()]));
		$this->assertSame($idMessage, $this->errorFor([['entityId' => null] + $this->valid()]));

		$textMessage = '[t] unredactedEntities[0].entityText is required and must be a string';
		$this->assertSame($textMessage, $this->errorFor([['entityText' => ''] + $this->valid()]));
		$this->assertSame($textMessage, $this->errorFor([['entityText' => 5, 'entityType' => ''] + $this->valid()]));
		$this->assertSame($textMessage, $this->errorFor([['entityText' => '0'] + $this->valid()]));

		$typeMessage = '[t] unredactedEntities[0].entityType is required and must be a string';
		$this->assertSame($typeMessage, $this->errorFor([['entityType' => null, 'publicationBases' => 'x'] + $this->valid()]));
		$this->assertSame($typeMessage, $this->errorFor([['entityType' => ['PERSON']] + $this->valid()]));

	}//end testShapeAndIdentityFields()

	/**
	 * The publication bases must be a non-empty list of strings.
	 *
	 * @return void
	 */
	public function testPublicationBases(): void {
		$entry = $this->valid();
		unset($entry['publicationBases']);
		$this->assertSame('[t] unredactedEntities[0].publicationBases is required and must be an array', $this->errorFor([$entry]));
		$this->assertSame(
			'[t] unredactedEntities[0].publicationBases is required and must be an array',
			$this->errorFor([['publicationBases' => '5.1.2e', 'contactEmail' => 1] + $this->valid()])
		);
		$this->assertSame(
			'[t] unredactedEntities[0].publicationBases must contain at least one basis',
			$this->errorFor([['publicationBases' => []] + $this->valid()])
		);
		$this->assertSame(
			'[t] Each entry in unredactedEntities[0].publicationBases must be a string',
			$this->errorFor([['publicationBases' => ['5.1.2e', 7], 'contactEmail' => 1] + $this->valid()])
		);

	}//end testPublicationBases()

	/**
	 * The optional contact fields must be strings when given; the first wrong entry wins.
	 *
	 * @return void
	 */
	public function testContactFieldsAndOrder(): void {
		$this->assertSame(
			'[t] unredactedEntities[0].contactEmail must be a string',
			$this->errorFor([['contactEmail' => 1, 'contactAddress' => 2] + $this->valid()])
		);
		$this->assertSame(
			'[t] unredactedEntities[0].contactAddress must be a string',
			$this->errorFor([['contactAddress' => ['x']] + $this->valid()])
		);
		$this->assertSame(
			'[t] unredactedEntities[k].entityId is required and must be an integer',
			$this->errorFor(['k' => [], 'l' => 'x'])
		);

	}//end testContactFieldsAndOrder()
}//end class
