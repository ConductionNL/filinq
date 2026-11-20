<?php

/**
 * Sealed Artifact
 *
 * A composed and signed PDF that is not yet recorded.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel;

/**
 * The sealed bytes and the record fields that go with them.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
 */
class SealedArtifact {

	/**
	 * Constructor.
	 *
	 * @param string $pdf The sealed PDF bytes (source pages + stamp page).
	 * @param array<string, mixed> $record The waarmerk fields, without documentFileId.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $pdf,
		public readonly array $record,
	) {

	}//end __construct()

	/**
	 * A sibling name: `<base>-<suffix>.pdf`.
	 *
	 * @param string $sourceName The source file name.
	 * @param string $suffix The suffix.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public static function siblingName(string $sourceName, string $suffix): string {
		$base = (string) pathinfo($sourceName, PATHINFO_FILENAME);
		if ($base === '') {
			$base = 'document';
		}

		return $base . '-' . $suffix . '.pdf';

	}//end siblingName()
}//end class
