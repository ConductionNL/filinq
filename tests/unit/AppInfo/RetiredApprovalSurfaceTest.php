<?php

/**
 * Retired-approval-surface tests
 *
 * openregister#3302 (flow-approval-consolidation) removes the approval-chain
 * surface with no alias, and its retirement inventory
 * (tests/fixtures/approval-consolidation/retired-approval-surface.json on
 * the OR branch) marks any app still touching it as a broken integration.
 * These tests pin filinq's side of that contract: no retired class or route
 * is referenced anywhere in lib/ or the shipped register JSONs, and the
 * signing wiring boots in a separate PHP process where the retired classes
 * are genuinely absent — no OpenRegister stub loaded at all.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-tasks/tasks.md#4-2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\AppInfo\SigningEventRegistrar;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Tests that the retired approval surface is gone and stays gone.
 *
 * @covers \OCA\Filinq\AppInfo\SigningEventRegistrar
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class RetiredApprovalSurfaceTest extends TestCase {

	/**
	 * The retired FQCN prefixes and routes, per the retirement inventory.
	 *
	 * `OCA\OpenRegister\Db\ApprovalChain` also matches the mapper and
	 * `ApprovalStep` matches its mapper and all four event classes, so the
	 * prefix list covers the full inventory.
	 *
	 * @var array<int, string>
	 */
	private const RETIRED_NEEDLES = [
		'OCA\\OpenRegister\\Db\\ApprovalChain',
		'OCA\\OpenRegister\\Db\\ApprovalStep',
		'OCA\\OpenRegister\\Service\\ApprovalService',
		'OCA\\OpenRegister\\Controller\\ApprovalController',
		'OCA\\OpenRegister\\Event\\ApprovalStep',
		'/api/approval-chains',
		'/api/approval-steps',
	];

	/**
	 * No retired class FQCN and no retired route survives in lib/ or the
	 * shipped register JSONs.
	 *
	 * @return void
	 */
	public function testNoRetiredReferenceSurvivesInLib(): void {
		$libDir = dirname(__DIR__, 3) . '/lib';
		$files = [];
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($libDir));
		foreach ($iterator as $file) {
			if ($file instanceof SplFileInfo === false || $file->isFile() === false) {
				continue;
			}

			if (in_array($file->getExtension(), ['php', 'json'], true) === false) {
				continue;
			}

			$files[] = $file->getPathname();
		}

		$this->assertNotEmpty($files, 'lib/ scan found no files — the scan itself is broken');

		$violations = [];
		foreach ($files as $path) {
			$content = (string) file_get_contents($path);
			// Normalise escaped namespace separators (JSON, string literals)
			// so one needle form finds both spellings.
			$haystack = str_replace('\\\\', '\\', $content);
			foreach (self::RETIRED_NEEDLES as $needle) {
				if (str_contains($haystack, $needle) === true) {
					$violations[] = $path . ' references ' . $needle;
				}
			}
		}

		$this->assertSame(
			[],
			$violations,
			"Retired approval surface still referenced (openregister#3302 removes these):\n"
			. implode("\n", $violations)
		);

	}//end testNoRetiredReferenceSurvivesInLib()

	/**
	 * The registrar's event roster is the task surface, by string literal,
	 * with no retired name.
	 *
	 * @return void
	 */
	public function testTheRegistrarRosterIsTheTaskSurface(): void {
		$this->assertSame(
			[
				'OCA\\OpenRegister\\Event\\TaskTransitionedEvent',
				'OCA\\OpenRegister\\Event\\TaskTerminalEvent',
				'OCA\\OpenRegister\\Event\\TaskSequenceCompletedEvent',
			],
			SigningEventRegistrar::TASK_EVENTS
		);

		foreach (SigningEventRegistrar::TASK_EVENTS as $event) {
			$this->assertStringNotContainsString('Approval', $event);
		}

	}//end testTheRegistrarRosterIsTheTaskSurface()

	/**
	 * The signing wiring boots in a process where the retired classes are
	 * absent and no OpenRegister stub is loaded.
	 *
	 * The experiment must run OUTSIDE this process: the unit bootstrap
	 * loads the OR stubs for every other test, and a stub that exists is
	 * exactly what the proof needs to exclude. The subprocess builds its
	 * own world (composer autoload + Nextcloud stubs only), asserts the
	 * retired classes are unresolvable, force-links every signing-surface
	 * class and runs SigningEventRegistrar::register().
	 *
	 * @return void
	 */
	public function testSigningWiringBootsWithoutOpenRegister(): void {
		$script = dirname(__DIR__, 2) . '/scripts/boot-without-openregister.php';
		$this->assertFileExists($script);

		$output = [];
		$exitCode = 1;
		exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);
		$joined = implode("\n", $output);

		$this->assertSame(0, $exitCode, 'boot proof failed: ' . $joined);
		$this->assertStringContainsString('BOOT-OK', $joined);
		$this->assertStringNotContainsString('BOOT-FAIL', $joined);

	}//end testSigningWiringBootsWithoutOpenRegister()
}//end class
