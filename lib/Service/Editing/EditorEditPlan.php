<?php

/**
 * Editor Edit Plan
 *
 * Turns anchored edits into text-to-match replacements an open editor can
 * apply. Suite-independent: it decides WHAT changes, never how a suite is told.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/edit-through-the-open-editor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use RuntimeException;

/**
 * Computes the find-and-replace pairs for an edit delivered through an open editor.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-an-edit-must-be-expressed-as-text-to-match-never-as-an-offset
 */
class EditorEditPlan {

	/**
	 * The replacements for a set of anchored edits, against the blocks they were read from.
	 *
	 * Each replacement names the text to find and what replaces it; nothing
	 * carries a position. The user may keep typing between the read and the
	 * delivery, and a positional edit would then land in the wrong place
	 * silently, where a text match either still finds its target or fails.
	 *
	 * The plan refuses, naming the text, rather than produce a replacement that
	 * could land anywhere but its target:
	 * - an anchor that is not in the read,
	 * - a paragraph with no text to match,
	 * - a paragraph whose text also occurs elsewhere in the document.
	 *
	 * Only replace and delete can be delivered as a text replacement. Inserting
	 * a paragraph or restyling one cannot, and is refused by name.
	 *
	 * @param array<int, array{anchor: string, text: string}>                  $blocks The blocks the edits were computed against.
	 * @param array<int, array{anchor?: string, action?: string, text?: string}> $edits  The anchored edits.
	 *
	 * @return list<array{find: string, replace: string}> The replacements, in edit order.
	 *
	 * @throws RuntimeException When an edit cannot be expressed as an unambiguous replacement.
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/tasks.md#3-deliver-the-edit-through-the-editor
	 */
	public function replacements(array $blocks, array $edits): array {
		if ($edits === []) {
			throw new RuntimeException('No edits were given, so there is nothing to apply.');
		}

		$texts = [];
		foreach ($blocks as $block) {
			$texts[(string)($block['anchor'] ?? '')] = (string)($block['text'] ?? '');
		}

		$replacements = [];
		foreach ($edits as $edit) {
			$anchor = (string)($edit['anchor'] ?? '');
			$action = (string)($edit['action'] ?? 'replace');
			if (in_array($action, ['replace', 'delete'], true) === false) {
				throw new RuntimeException(
					sprintf(
						'A "%s" edit cannot be applied through an open editor, only replacing or deleting text can. '
						. 'Nothing was changed.',
						$action
					)
				);
			}

			if (array_key_exists($anchor, $texts) === false) {
				throw new RuntimeException(
					sprintf('The paragraph "%s" is not in the document as it was read, so nothing was changed. Read it again.', $anchor)
				);
			}

			$find = $texts[$anchor];
			if (trim($find) === '') {
				throw new RuntimeException(
					sprintf('The paragraph "%s" has no text to find in the open editor, so nothing was changed.', $anchor)
				);
			}

			if ($this->occurrences(needle: $find, texts: $texts) !== 1) {
				throw new RuntimeException(
					sprintf(
						'The text "%s" occurs more than once in the document, so the open editor could change the wrong one. '
						. 'Nothing was changed.',
						$find
					)
				);
			}

			$replace = '';
			if ($action === 'replace') {
				$replace = (string)($edit['text'] ?? '');
			}

			$replacements[] = ['find' => $find, 'replace' => $replace];
		}//end foreach

		return $replacements;
	}//end replacements()

	/**
	 * How often a text occurs across all blocks, counting overlaps within a block once each.
	 *
	 * @param string                $needle The text.
	 * @param array<string, string> $texts  The block texts.
	 *
	 * @return int The occurrences.
	 */
	private function occurrences(string $needle, array $texts): int {
		$count = 0;
		foreach ($texts as $text) {
			$count += substr_count($text, $needle);
		}

		return $count;
	}//end occurrences()
}//end class
