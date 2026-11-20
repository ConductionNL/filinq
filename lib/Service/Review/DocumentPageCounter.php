<?php

/**
 * How many pages a document has, for a page or range redaction.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use OCA\Filinq\Service\PdfStreamReaderFactory;
use OCP\Files\File;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * A PDF counts its pages, a standalone image is one page, anything else is
 * unknown (0), which refuses page scopes and leaves the whole-document scope.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */
class DocumentPageCounter {

	/**
	 * The standalone image types, the same as OpenRegister's image seam takes.
	 *
	 * @var list<string>
	 */
	public const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/tiff', 'image/webp'];

	/**
	 * The page count of a document.
	 *
	 * @param File $file The document.
	 *
	 * @return int The pages, 0 when not known.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	public function count(File $file): int {
		$mime = (string) $file->getMimeType();
		if (in_array($mime, self::IMAGE_TYPES, true) === true) {
			return 1;
		}

		if ($mime !== 'application/pdf') {
			return 0;
		}

		try {
			return (new Fpdi())->setSourceFile((new PdfStreamReaderFactory())->fromString(content: (string) $file->getContent()));
		} catch (Throwable) {
			return 0;
		}

	}//end count()
}//end class
