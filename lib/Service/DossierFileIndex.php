<?php

/**
 * Dossier file index
 *
 * The dossier each file belongs to, built one dossier at a time.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Maps file ids to the first dossier that claims them.
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */
final class DossierFileIndex {

	/**
	 * The dossier by file id.
	 *
	 * @var array<int, array{id: string, name: string}>
	 */
	private array $byFile = [];

	/**
	 * Claim a dossier's member files that no earlier dossier claimed.
	 *
	 * A member is a file id, or an array carrying it under `id`. Members
	 * without a positive id, and dossiers without an id, are skipped.
	 *
	 * @param array{id: string, name: string} $dossier The dossier.
	 * @param array<int|string, mixed>        $members Its member files.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function add(array $dossier, array $members): void {
		if ($dossier['id'] === '') {
			return;
		}

		foreach ($members as $member) {
			$fileId = (int)$member;
			if (is_array($member) === true) {
				$fileId = (int)($member['id'] ?? 0);
			}

			if ($fileId > 0 && isset($this->byFile[$fileId]) === false) {
				$this->byFile[$fileId] = $dossier;
			}
		}
	}//end add()

	/**
	 * The dossier by file id.
	 *
	 * @return array<int, array{id: string, name: string}> The map.
	 *
	 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function byFile(): array {
		return $this->byFile;
	}//end byFile()
}//end class
