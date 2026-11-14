<?php

/**
 * Unit tests for EditorDeliveryRegistry
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
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

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\EditorDeliveryRegistry;
use PHPUnit\Framework\TestCase;

/**
 * The declared editors are read from configuration, and a broken file declares none.
 *
 * @covers \OCA\Filinq\Service\Editing\EditorDeliveryRegistry
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-suite-specific-code-must-be-confined-to-delivery
 */
class EditorDeliveryRegistryTest extends TestCase {

	/**
	 * Temporary files to remove.
	 *
	 * @var list<string>
	 */
	private array $files = [];

	/**
	 * Remove the temporary files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->files as $file) {
			@unlink($file);
		}

		parent::tearDown();
	}//end tearDown()

	/**
	 * A registry file with this content.
	 *
	 * @param string $content The content.
	 *
	 * @return string The path.
	 */
	private function file(string $content): string {
		$path = (string)tempnam(sys_get_temp_dir(), 'editors');
		file_put_contents($path, $content);
		$this->files[] = $path;

		return $path;
	}//end file()

	/**
	 * Every editor the shipped file declares names a suite and one of the two delivery adapters.
	 *
	 * @return void
	 */
	public function testTheShippedRegistryDeclaresAnAdapterPerEditor(): void {
		$declared = json_decode((string)file_get_contents(EditorDeliveryRegistry::DEFAULT_PATH), true)['editors'];
		$registry = new EditorDeliveryRegistry();

		self::assertNotEmpty($declared);
		foreach ($declared as $owner => $entry) {
			$found = $registry->forLockOwner(owner: strtoupper($owner));
			self::assertSame(['suite' => $entry['suite'], 'adapter' => $entry['adapter']], $found);
			self::assertContains($found['adapter'], ['connector', 'postMessage']);
		}
	}//end testTheShippedRegistryDeclaresAnAdapterPerEditor()

	/**
	 * Lookup is by trimmed, case-insensitive owner; incomplete entries and blank owners are not declared.
	 *
	 * @return void
	 */
	public function testLookupAndIncompleteEntries(): void {
		$registry = new EditorDeliveryRegistry(
			path: $this->file(
				(string)json_encode(
					[
						'editors' => [
							'MyEditor' => ['suite' => ' My Editor ', 'adapter' => 'connector'],
							'nosuite' => ['adapter' => 'connector'],
							'noadapter' => ['suite' => 'X', 'adapter' => ' '],
							'scalar' => 'connector',
						],
					]
				)
			)
		);

		self::assertSame(['suite' => 'My Editor', 'adapter' => 'connector'], $registry->forLockOwner(owner: ' myeditor '));
		self::assertNull($registry->forLockOwner(owner: 'nosuite'));
		self::assertNull($registry->forLockOwner(owner: 'noadapter'));
		self::assertNull($registry->forLockOwner(owner: 'scalar'));
		self::assertNull($registry->forLockOwner(owner: 'unknown'));
		self::assertNull($registry->forLockOwner(owner: '  '));
	}//end testLookupAndIncompleteEntries()

	/**
	 * A missing or malformed file declares nothing, so every editor is refused by name.
	 *
	 * @return void
	 */
	public function testAnUnreadableRegistryDeclaresNothing(): void {
		self::assertNull((new EditorDeliveryRegistry(path: '/nonexistent/editor-delivery.json'))->forLockOwner(owner: 'anything'));
		self::assertNull((new EditorDeliveryRegistry(path: $this->file('{not json')))->forLockOwner(owner: 'anything'));
		self::assertNull((new EditorDeliveryRegistry(path: $this->file('{"editors": 5}')))->forLockOwner(owner: 'anything'));
	}//end testAnUnreadableRegistryDeclaresNothing()
}//end class
