<?php

/**
 * Unit tests for the external document lifecycle declaration
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
 * @spec openspec/changes/zgw-document-bridge/tasks.md#1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

/**
 * What the register declares about a synced external document: both bridge
 * schemas in the filinq register, and the processing-status lifecycle in the
 * dialect OpenRegister reads, with exactly the REQ-DDZGW-004 transitions.
 *
 * @coversNothing
 */
class ExternalDocumentLifecycleTest extends TestCase {

	/**
	 * The parsed register.
	 *
	 * @return array<string, mixed> The descriptor.
	 */
	private function register(): array {
		$parsed = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$this->assertIsArray($parsed);

		return $parsed;

	}//end register()

	/**
	 * Both bridge schemas live in the filinq register, the app's one register.
	 *
	 * @return void
	 */
	public function testTheBridgeSchemasAreInTheFilinqRegister(): void {
		$register = $this->register();

		foreach (['bridgeSource', 'externalDocument'] as $slug) {
			$this->assertContains($slug, $register['components']['registers']['filinq']['schemas']);
			$this->assertArrayHasKey($slug, $register['components']['schemas']);
		}

		$this->assertTrue(version_compare($register['info']['version'], '8.56.0', '>='));

	}//end testTheBridgeSchemasAreInTheFilinqRegister()

	/**
	 * The lifecycle uses `initial: staged` (OpenRegister ignores `initialState`,
	 * and refuses an annotation without `initial`) and exactly the seven moves.
	 *
	 * @return void
	 */
	public function testTheLifecycleIsDeclaredInTheDialectOpenRegisterReads(): void {
		$schema    = $this->register()['components']['schemas']['externalDocument'];
		$lifecycle = $schema['x-openregister-lifecycle'];

		$this->assertSame('processingStatus', $lifecycle['field']);
		$this->assertSame('staged', $lifecycle['initial']);
		$this->assertArrayNotHasKey('initialState', $lifecycle);

		$pairs = [];
		foreach ($lifecycle['transitions'] as $transition) {
			$pairs[] = $transition['from'] . '->' . $transition['to'];
		}

		sort($pairs);
		$this->assertSame(
			[
				'in_processing->processed',
				'in_processing->staged',
				'processed->ready_for_writeback',
				'ready_for_writeback->writeback_failed',
				'ready_for_writeback->written_back',
				'staged->in_processing',
				'writeback_failed->ready_for_writeback',
			],
			$pairs
		);
		$this->assertSame(array_keys($lifecycle['states']), $schema['properties']['processingStatus']['enum']);

	}//end testTheLifecycleIsDeclaredInTheDialectOpenRegisterReads()
}//end class
