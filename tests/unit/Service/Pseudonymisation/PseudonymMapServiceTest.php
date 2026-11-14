<?php

/**
 * Unit tests for the encrypted pseudonym map store and its lifecycle
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;
use OCA\Filinq\EventListener\PseudonymMapLinkDeletedListener;
use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * The key is encrypted, never rendered, one per link, and dies with the link.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymMapServiceTest extends TestCase {
	use PseudonymDoubles;

	/**
	 * What is written to OpenRegister is ciphertext: no original value in it.
	 *
	 * @return void
	 */
	public function testTheMappingIsStoredEncrypted(): void {
		$container = $this->container();
		$stored = $this->mapService(container: $container)->store(linkId: 'link-1', sourceFileId: 812200, pairs: $this->twoPeople(), scope: 'document', userId: 'alice');

		$row = $this->rows['pseudonymMap'][$stored['uuid']];
		$this->assertStringStartsWith('enc:', $row['mappings']);
		$this->assertStringNotContainsString('Jan Jansen', $row['mappings']);
		$this->assertStringNotContainsString('PERSOON', $row['mappings']);
		$this->assertSame(2, $row['entryCount']);
		$this->assertSame('nextcloud-icrypto-v1', $row['algorithm']);
		$this->assertContains('save:pseudonymMap', $this->bypasses);

		$this->assertSame($this->twoPeople(), $this->mapService(container: $container)->readPairs(linkId: 'link-1')['pairs']);

	}//end testTheMappingIsStoredEncrypted()

	/**
	 * The metadata read, which is the only read outside the restore path, never
	 * carries the payload, even when the register would return it.
	 *
	 * @return void
	 */
	public function testMappingsNeverRendered(): void {
		$container = $this->container();
		$this->mapService(container: $container)->store(linkId: 'link-1', sourceFileId: 812200, pairs: $this->twoPeople(), scope: 'dossier', userId: 'alice');

		$described = $this->mapService(container: $container)->describe(linkId: 'link-1');

		$this->assertIsArray($described);
		$this->assertArrayNotHasKey('mappings', $described);
		$this->assertSame(2, $described['entryCount']);
		$this->assertSame('dossier', $described['scope']);

	}//end testMappingsNeverRendered()

	/**
	 * Re-anonymising overwrites the same map, it does not add a second one.
	 *
	 * @return void
	 */
	public function testReanonymisingOverwritesTheSameMap(): void {
		$container = $this->container();
		$first = $this->mapService(container: $container)->store(linkId: 'link-1', sourceFileId: 812200, pairs: $this->twoPeople(), scope: 'document', userId: 'alice');
		$second = $this->mapService(container: $container)->store(linkId: 'link-1', sourceFileId: 812200, pairs: [$this->twoPeople()[0]], scope: 'document', userId: 'bob');

		$this->assertSame($first['uuid'], $second['uuid']);
		$this->assertCount(1, $this->rows['pseudonymMap']);
		$this->assertSame(1, $this->rows['pseudonymMap'][$first['uuid']]['entryCount']);

	}//end testReanonymisingOverwritesTheSameMap()

	/**
	 * Deleting the link deletes the key; deleting something else leaves it.
	 *
	 * @return void
	 */
	public function testMapDeletedWithLink(): void {
		$container = $this->container();
		$this->mapService(container: $container)->store(linkId: 'link-1', sourceFileId: 812200, pairs: $this->twoPeople(), scope: 'document', userId: 'alice');
		$listener = new PseudonymMapLinkDeletedListener($this->mapService(container: $container), new NullLogger());

		$other = new ObjectEntity();
		$other->setUuid('link-1');
		$other->setObject(['title' => 'not a link']);
		$listener->handle(new ObjectDeletedEvent($other));
		$this->assertCount(1, $this->rows['pseudonymMap']);

		$link = new ObjectEntity();
		$link->setUuid('link-1');
		$link->setObject(['sourceFileId' => 812200, 'anonymizedFileId' => 812201, 'mappingRef' => 'x']);
		$listener->handle(new ObjectDeletedEvent($link));

		$this->assertSame([], $this->rows['pseudonymMap']);
		$this->assertContains('delete:pseudonymMap', $this->bypasses);

	}//end testMapDeletedWithLink()

	/**
	 * An irreversible run kept no key: reading one is refused, not answered empty.
	 *
	 * @return void
	 */
	public function testAnIrreversibleRunHasNoKeyToRead(): void {
		$this->expectException(PseudonymRestoreRefusedException::class);
		$this->expectExceptionMessage('irreversible');

		$this->mapService()->readPairs(linkId: 'link-without-key');

	}//end testAnIrreversibleRunHasNoKeyToRead()

	/**
	 * A payload the server secret did not encrypt (the demo rows) is refused as unreadable.
	 *
	 * @return void
	 */
	public function testAKeyThatDoesNotDecryptIsRefused(): void {
		$this->rows['pseudonymMap']['pseudonymmap-1'] = [
			'anonymizationLink' => 'demo-anonymizationlink-1',
			'mappings' => 'demo: no key was kept, this map cannot be decrypted',
		];

		try {
			$this->mapService()->readPairs(linkId: 'demo-anonymizationlink-1');
			$this->fail('A payload that does not decrypt must not read as pairs.');
		} catch (PseudonymRestoreRefusedException $refusal) {
			$this->assertSame(PseudonymRestoreRefusedException::REASON_MAP_UNREADABLE, $refusal->getReason());
		}

	}//end testAKeyThatDoesNotDecryptIsRefused()
}//end class
