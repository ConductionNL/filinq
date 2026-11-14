<?php

/**
 * Editor Delivery Registry
 *
 * Which office editors can take an agent edit while they hold a document. The
 * list is declarative (lib/Settings/editor-delivery.json), so adding an editor
 * is configuration rather than a branch, and this class names none of them.
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

use JsonException;

/**
 * Reads the declared editors and their delivery adapters.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-suite-specific-code-must-be-confined-to-delivery
 */
class EditorDeliveryRegistry {

	/**
	 * The shipped registry file.
	 *
	 * @var string
	 */
	public const DEFAULT_PATH = __DIR__ . '/../../Settings/editor-delivery.json';

	/**
	 * The editors by lock owner, read once.
	 *
	 * @var array<string, array{suite: string, adapter: string}>|null
	 */
	private ?array $editors = null;

	/**
	 * Constructor.
	 *
	 * @param string $path The registry file; the shipped one unless a test names another.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $path=self::DEFAULT_PATH,
	) {
	}//end __construct()

	/**
	 * The declared editor holding a lock under this owner, or null when none is declared.
	 *
	 * @param string $owner The lock owner.
	 *
	 * @return array{suite: string, adapter: string}|null The suite name and its delivery adapter.
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/tasks.md#3-deliver-the-edit-through-the-editor
	 */
	public function forLockOwner(string $owner): ?array {
		$key = strtolower(trim($owner));
		if ($key === '') {
			return null;
		}

		return ($this->editors()[$key] ?? null);
	}//end forLockOwner()

	/**
	 * The declared editors, keyed by lowercased lock owner.
	 *
	 * An unreadable or malformed registry declares nothing: every open editor is
	 * then refused by name, which is the safe direction.
	 *
	 * @return array<string, array{suite: string, adapter: string}> The editors.
	 */
	private function editors(): array {
		if ($this->editors !== null) {
			return $this->editors;
		}

		$this->editors = [];
		if (is_file($this->path) === false || is_readable($this->path) === false) {
			return $this->editors;
		}

		$raw = (string)file_get_contents($this->path);

		try {
			$decoded = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return $this->editors;
		}

		foreach ((array)($decoded['editors'] ?? []) as $owner => $entry) {
			if (is_array($entry) === false) {
				continue;
			}

			$suite = trim((string)($entry['suite'] ?? ''));
			$adapter = trim((string)($entry['adapter'] ?? ''));
			if ($suite === '' || $adapter === '') {
				continue;
			}

			$this->editors[strtolower((string)$owner)] = ['suite' => $suite, 'adapter' => $adapter];
		}

		return $this->editors;
	}//end editors()
}//end class
