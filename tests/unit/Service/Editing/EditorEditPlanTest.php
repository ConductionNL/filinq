<?php

/**
 * Unit tests for EditorEditPlan
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

use OCA\Filinq\Service\Editing\EditorEditPlan;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Anchored edits become text-to-match replacements, or are refused by name.
 *
 * @covers \OCA\Filinq\Service\Editing\EditorEditPlan
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-an-edit-must-be-expressed-as-text-to-match-never-as-an-offset
 */
class EditorEditPlanTest extends TestCase {

	/**
	 * The blocks of a read.
	 *
	 * @return array<int, array{anchor: string, text: string}> The blocks.
	 */
	private static function blocks(): array {
		return [
			['anchor' => 'b1-1', 'text' => 'Besluit op de aanvraag'],
			['anchor' => 'b2-1', 'text' => 'De gemeente Zaanstad kent de subsidie toe.'],
			['anchor' => 'b3-1', 'text' => ''],
			['anchor' => 'b4-1', 'text' => 'Bijlage'],
			['anchor' => 'b5-1', 'text' => 'Zie de Bijlage voor de voorwaarden.'],
		];
	}//end blocks()

	/**
	 * Replace and delete become find/replace pairs in edit order, with no position.
	 *
	 * @return void
	 */
	public function testReplaceAndDeleteBecomeTextReplacements(): void {
		$plan = (new EditorEditPlan())->replacements(
			blocks: self::blocks(),
			edits: [
				['anchor' => 'b2-1', 'action' => 'replace', 'text' => 'De gemeente Zaandam kent de subsidie toe.'],
				['anchor' => 'b1-1', 'action' => 'delete'],
				['anchor' => 'b2-1', 'text' => 'Default action is replace.'],
			]
		);

		self::assertSame(
			[
				['find' => 'De gemeente Zaanstad kent de subsidie toe.', 'replace' => 'De gemeente Zaandam kent de subsidie toe.'],
				['find' => 'Besluit op de aanvraag', 'replace' => ''],
				['find' => 'De gemeente Zaanstad kent de subsidie toe.', 'replace' => 'Default action is replace.'],
			],
			$plan
		);
	}//end testReplaceAndDeleteBecomeTextReplacements()

	/**
	 * Edits that cannot become a clean replacement, and the text the refusal names.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}> The edits and expected message fragments.
	 */
	public static function refusedProvider(): array {
		return [
			'insert' => [['anchor' => 'b1-1', 'action' => 'insertAfter', 'text' => 'x'], 'A "insertAfter" edit cannot be applied through an open editor'],
			'style' => [['anchor' => 'b1-1', 'action' => 'style'], 'A "style" edit cannot be applied through an open editor'],
			'unknown anchor' => [['anchor' => 'b9-1', 'action' => 'replace', 'text' => 'x'], 'The paragraph "b9-1" is not in the document as it was read'],
			'missing anchor' => [['action' => 'delete'], 'The paragraph "" is not in the document as it was read'],
			'empty paragraph' => [['anchor' => 'b3-1', 'action' => 'replace', 'text' => 'x'], 'The paragraph "b3-1" has no text to find'],
			'text also elsewhere' => [['anchor' => 'b4-1', 'action' => 'delete'], 'The text "Bijlage" occurs more than once'],
		];
	}//end refusedProvider()

	/**
	 * An edit that could land anywhere but its target is refused, naming it.
	 *
	 * @param array<string, mixed> $edit    The edit.
	 * @param string               $message The expected message start.
	 *
	 * @dataProvider refusedProvider
	 *
	 * @return void
	 */
	public function testAnEditThatIsNotACleanReplacementIsRefused(array $edit, string $message): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage($message);

		(new EditorEditPlan())->replacements(blocks: self::blocks(), edits: [$edit]);
	}//end testAnEditThatIsNotACleanReplacementIsRefused()

	/**
	 * One refused edit refuses the whole set: nothing is half delivered.
	 *
	 * @return void
	 */
	public function testOneRefusedEditRefusesTheSet(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('is not in the document as it was read');

		(new EditorEditPlan())->replacements(
			blocks: self::blocks(),
			edits: [
				['anchor' => 'b2-1', 'action' => 'replace', 'text' => 'ok'],
				['anchor' => 'gone', 'action' => 'replace', 'text' => 'x'],
			]
		);
	}//end testOneRefusedEditRefusesTheSet()

	/**
	 * No edits is refused rather than delivered as an empty change.
	 *
	 * @return void
	 */
	public function testNoEditsIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No edits were given');

		(new EditorEditPlan())->replacements(blocks: self::blocks(), edits: []);
	}//end testNoEditsIsRefused()

	/**
	 * The code that computes an edit names no office suite: only delivery may vary per suite.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-suite-specific-code-must-be-confined-to-delivery
	 */
	public function testTheManipulationCodeNamesNoSuite(): void {
		$root = dirname(__DIR__, 4);
		$registry = json_decode((string)file_get_contents($root . '/lib/Settings/editor-delivery.json'), true);
		$names = [];
		foreach ($registry['editors'] as $owner => $entry) {
			$names[] = strtolower($owner);
			$names[] = strtolower($entry['suite']);
		}

		self::assertNotEmpty($names);
		foreach (['EditorEditPlan.php', 'PackageCodec.php'] as $file) {
			$code = '';
			foreach (token_get_all((string)file_get_contents($root . '/lib/Service/Editing/' . $file)) as $token) {
				if (is_array($token) === true && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) === true) {
					continue;
				}

				$code .= is_array($token) === true ? $token[1] : $token;
			}

			foreach ($names as $name) {
				self::assertStringNotContainsString($name, strtolower($code), $file . ' names ' . $name);
			}
		}
	}//end testTheManipulationCodeNamesNoSuite()
}//end class
