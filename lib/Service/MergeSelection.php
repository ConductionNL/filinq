<?php

/**
 * Merge Selection
 *
 * The resolved inputs of a merge and what was asked for.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;

/**
 * The resolved inputs of a merge, in order, and the options it was asked with.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
final class MergeSelection {

	/**
	 * @param array<int, array<string, mixed>> $resolved The resolved inputs, each `{fileId, label, file}`.
	 * @param array<string, mixed> $options The cover template, the bookmarks toggle, the target folder and the name.
	 */
	public function __construct(
		public readonly array $resolved,
		public readonly array $options,
	) {
	}//end __construct()

	/**
	 * The first input, whose folder is the default target.
	 *
	 * @return File The first input file.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function first(): File {
		return $this->resolved[0]['file'];
	}//end first()

	/**
	 * Whether every input (and the cover) gets a bookmark; on unless switched off.
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function wantsBookmarks(): bool {
		return (($this->options['bookmarks'] ?? true) === true);
	}//end wantsBookmarks()

	/**
	 * The cover template asked for, or an empty string for no cover.
	 *
	 * @return string The trimmed template reference.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function coverRef(): string {
		return trim((string)($this->options['coverTemplateRef'] ?? ''));
	}//end coverRef()
}//end class
