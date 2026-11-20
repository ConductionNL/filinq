<?php

/**
 * Unit tests for the mailArchiveImport declaration
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * Asserts what the register declares about a mail archive import: its fields,
 * its lifecycle, the notice to the user who started it and the demo imports.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MailArchiveImportSchemaTest extends TestCase {

	/**
	 * The parsed register descriptor.
	 *
	 * @param string $file The descriptor file under lib/Settings.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function descriptor(string $file='filinq_register.json'): array {
		$raw = file_get_contents(__DIR__ . '/../../../lib/Settings/' . $file);
		$this->assertIsString($raw);

		$parsed = json_decode($raw, true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end descriptor()

	/**
	 * The import schema.
	 *
	 * @return array<string, mixed> The schema.
	 */
	private function importSchema(): array {
		$schemas = $this->descriptor()['components']['schemas'];
		$this->assertArrayHasKey('mailArchiveImport', $schemas);

		return $schemas['mailArchiveImport'];

	}//end importSchema()

	/**
	 * The register lists the schema and its version moved on, so the import reaches existing installs.
	 *
	 * @return void
	 */
	public function testTheRegisterListsTheImport(): void {
		$this->assertContains('mailArchiveImport', $this->descriptor()['components']['registers']['filinq']['schemas']);
		$this->assertTrue(condition: version_compare(version1: $this->descriptor()['info']['version'], version2: '8.55.0', operator: '>='));
		$this->assertSame('mailArchiveImport', $this->importSchema()['slug']);

	}//end testTheRegisterListsTheImport()

	/**
	 * Only the server writes an import; people read it, an admin deletes it.
	 *
	 * @return void
	 */
	public function testOnlyTheServerWritesAnImport(): void {
		$this->assertSame(['read' => ['authenticated'], 'create' => [], 'update' => [], 'delete' => ['admin']], $this->importSchema()['authorization']);

	}//end testOnlyTheServerWritesAnImport()

	/**
	 * The import carries the design's fields, the three formats, the five counts and six statuses.
	 *
	 * @return void
	 */
	public function testTheImportCarriesTheDesignFields(): void {
		$import = $this->importSchema();

		foreach (['file', 'format', 'folderMap', 'cursor', 'counts', 'failures', 'status', 'requestedBy'] as $field) {
			$this->assertArrayHasKey($field, $import['properties'], $field);
		}

		$this->assertSame(['file', 'status', 'requestedBy'], $import['required']);
		$this->assertSame('integer', $import['properties']['file']['type']);
		$this->assertSame(['mbox', 'zip', 'pst'], $import['properties']['format']['enum']);
		$this->assertSame(['received', 'mapping', 'importing', 'done', 'failed', 'cancelled'], $import['properties']['status']['enum']);
		$this->assertSame(['found', 'filed', 'toInbox', 'skippedDuplicate', 'failed'], array_keys($import['properties']['counts']['properties']));
		$this->assertSame(['archiveFolder', 'messages', 'dossier'], array_keys($import['properties']['folderMap']['items']['properties']));
		$this->assertSame(['messageId', 'archiveFolder', 'name', 'reason'], array_keys($import['properties']['failures']['items']['properties']));

	}//end testTheImportCarriesTheDesignFields()

	/**
	 * An import is received, maps, imports and ends done; it can fail or be cancelled before done.
	 *
	 * @return void
	 */
	public function testTheLifecycleRunsFromReceivedToATerminalState(): void {
		$lifecycle = $this->importSchema()['x-openregister-lifecycle'];

		$this->assertSame('status', $lifecycle['field']);
		$this->assertSame('received', $lifecycle['initial']);
		$this->assertSame($this->importSchema()['properties']['status']['enum'], array_keys($lifecycle['states']));

		$moves = [];
		foreach ($lifecycle['transitions'] as $name => $transition) {
			$moves[$name] = implode(',', (array) $transition['from']) . '>' . $transition['to'];
		}

		$this->assertSame(
			[
				'list' => 'received>mapping',
				'start' => 'mapping>importing',
				'finish' => 'importing>done',
				'fail' => 'received,mapping,importing>failed',
				'cancel' => 'received,mapping,importing>cancelled',
			],
			$moves
		);

		$terminal = array_keys(array_filter($lifecycle['states'], static fn (array $state): bool => $state['terminal'] === true));
		$this->assertSame(['done', 'failed', 'cancelled'], $terminal);

	}//end testTheLifecycleRunsFromReceivedToATerminalState()

	/**
	 * Reaching done or failed tells the user who started the import, in Nextcloud only.
	 *
	 * @return void
	 */
	public function testTheRequesterIsToldWhenTheImportEnds(): void {
		$notices = $this->importSchema()['x-openregister-notifications'];

		$this->assertSame(['finish', 'fail'], array_map(static fn (array $notice): string => $notice['trigger']['transition'], array_values($notices)));
		foreach ($notices as $notice) {
			$this->assertSame('lifecycle', $notice['trigger']['type']);
			$this->assertSame([['kind' => 'field', 'field' => 'requestedBy']], $notice['recipients']);
			$this->assertSame(['nc-notification'], $notice['channels']);
			$this->assertArrayHasKey('nl', $notice['subject']);
			$this->assertArrayHasKey('en', $notice['subject']);
		}

	}//end testTheRequesterIsToldWhenTheImportEnds()

	/**
	 * The demo imports are one done with two failures, one mapping two folders and one importing, in the mock register only.
	 *
	 * @return void
	 */
	public function testTheDemoImportsAreSeeded(): void {
		$imports = array_values(
			array_filter(
				$this->descriptor(file: 'filinq_mock_register.json')['components']['objects'],
				static fn (array $object): bool => ($object['@self']['schema'] ?? '') === 'mailArchiveImport'
			)
		);

		$this->assertSame(['done', 'mapping', 'importing'], array_column($imports, 'status'));
		$this->assertSame(['unsupported-format', 'unparseable'], array_column($imports[0]['failures'], 'reason'));
		$this->assertSame(2, $imports[0]['counts']['failed']);
		$this->assertSame(['Inbox', 'Projecten/Brug'], array_column($imports[1]['folderMap'], 'archiveFolder'));

		$production = array_filter(
			$this->descriptor()['components']['objects'],
			static fn (array $object): bool => ($object['@self']['schema'] ?? '') === 'mailArchiveImport'
		);
		$this->assertSame([], $production, 'demo imports never ship in the production register');
		foreach ($imports as $import) {
			foreach ($this->importSchema()['required'] as $field) {
				$this->assertArrayHasKey($field, $import, $field);
			}
		}

	}//end testTheDemoImportsAreSeeded()
}//end class
