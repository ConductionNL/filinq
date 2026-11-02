<?php

/**
 * OpenRegister's sanitizer classes, for the unit tests.
 *
 * SanitizationReport, SanitizationResult and SanitizationException are
 * copied verbatim from OpenRegister development (lib/Service/File and
 * lib/Exception); OfficeDocumentSanitizer keeps only its public methods, so a
 * double cannot grow a method OpenRegister does not have.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Exception {
use Throwable;

/**
 * Exception thrown by the Office document sanitiser.
 *
 * The `reason` field is a stable, structured code:
 *
 *   - `REASON_UNSUPPORTED_MIME` → no strategy supports the file MIME type.
 *   - `REASON_ENCRYPTED`        → the ZIP container is password-protected.
 *   - `REASON_CORRUPT_ZIP`      → the ZIP container could not be opened.
 *   - `REASON_INTERNAL`         → unexpected surgery failure.
 *
 * Per ADR-005 the message MUST NOT contain a filename or document content —
 * only the reason code and structural detail (part path, element name).
 *
 * @category Exception
 * @package  OCA\OpenRegister\Exception
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://OpenRegister.app
 *
 */
class SanitizationException extends \Exception {

	/**
	 * No registered strategy supports the input MIME type.
	 *
	 * @var string
	 */
	public const REASON_UNSUPPORTED_MIME = 'unsupported-mime';

	/**
	 * The ZIP container is encrypted / password-protected.
	 *
	 * @var string
	 */
	public const REASON_ENCRYPTED = 'encrypted';

	/**
	 * The ZIP container is corrupt or not a valid Office package.
	 *
	 * @var string
	 */
	public const REASON_CORRUPT_ZIP = 'corrupt-zip';

	/**
	 * Unexpected internal sanitiser failure.
	 *
	 * @var string
	 */
	public const REASON_INTERNAL = 'internal';

	/**
	 * The structured reason code (one of the REASON_* constants).
	 *
	 * MUST NOT carry filename or content (ADR-005).
	 *
	 * @var string
	 */
	public readonly string $reason;

	/**
	 * Constructor.
	 *
	 * @param string $reason One of the REASON_* constants.
	 * @param string $message PII-free human-readable detail.
	 * @param Throwable|null $previous Previous exception.
	 *
	 */
	public function __construct(
		string $reason,
		string $message = '',
		?Throwable $previous = null,
	) {
		$this->reason = $reason;

		$finalMessage = $message;
		if ($finalMessage === '') {
			$finalMessage = sprintf('Office document sanitisation failed: %s', $reason);
		}

		parent::__construct(message: $finalMessage, code: 0, previous: $previous);
	}//end __construct()

	/**
	 * Get the structured reason code.
	 *
	 * @return string One of the REASON_* constants.
	 *
	 */
	public function getReason(): string {
		return $this->reason;
	}//end getReason()
}//end class
}

namespace OCA\OpenRegister\Service\File {
use JsonSerializable;

/**
 * Immutable value object capturing the per-category sanitisation counts.
 *
 * @category Service
 * @package  OCA\OpenRegister\Service\File
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://OpenRegister.app
 *
 */
final class SanitizationReport implements JsonSerializable {
	/**
	 * Constructor.
	 *
	 * @param int $commentsRemoved Distinct comments/annotations removed.
	 * @param int $trackedChangesAccepted Insert ranges accepted (unwrapped).
	 * @param int $trackedChangesDropped Delete ranges dropped.
	 * @param int $revisionAttributesStripped Revision (rsid) attributes removed.
	 * @param int $hyperlinksFlattened Hyperlinks flattened to plain text.
	 * @param int $metadataFieldsScrubbed Metadata fields replaced with sentinel.
	 * @param int $customXmlPartsDropped Custom XML parts removed.
	 * @param int $fieldCodesStripped Person-identity field codes removed.
	 * @param string $sentinelApplied The sentinel string used for scrubbing.
	 *
	 * @SuppressWarnings(PHPMD.LongVariable) Property names are the stable audit-report JSON keys (design D9).
	 *
	 */
	public function __construct(
		public readonly int $commentsRemoved = 0,
		public readonly int $trackedChangesAccepted = 0,
		public readonly int $trackedChangesDropped = 0,
		public readonly int $revisionAttributesStripped = 0,
		public readonly int $hyperlinksFlattened = 0,
		public readonly int $metadataFieldsScrubbed = 0,
		public readonly int $customXmlPartsDropped = 0,
		public readonly int $fieldCodesStripped = 0,
		public readonly string $sentinelApplied = '',
	) {
	}//end __construct()

	/**
	 * Serialise to a stable, ordered associative array.
	 *
	 * @return array<string, int|string>
	 *
	 */
	public function jsonSerialize(): array {
		return [
			'commentsRemoved' => $this->commentsRemoved,
			'trackedChangesAccepted' => $this->trackedChangesAccepted,
			'trackedChangesDropped' => $this->trackedChangesDropped,
			'revisionAttributesStripped' => $this->revisionAttributesStripped,
			'hyperlinksFlattened' => $this->hyperlinksFlattened,
			'metadataFieldsScrubbed' => $this->metadataFieldsScrubbed,
			'customXmlPartsDropped' => $this->customXmlPartsDropped,
			'fieldCodesStripped' => $this->fieldCodesStripped,
			'sentinelApplied' => $this->sentinelApplied,
		];
	}//end jsonSerialize()
}//end class

/**
 * Immutable result of an Office document sanitisation pass.
 *
 * @category Service
 * @package  OCA\OpenRegister\Service\File
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://OpenRegister.app
 *
 */
final class SanitizationResult {
	/**
	 * Constructor.
	 *
	 * @param string $path Absolute path to the sanitised temp file.
	 * @param SanitizationReport $report Per-category sanitisation counts.
	 *
	 */
	public function __construct(
		public readonly string $path,
		public readonly SanitizationReport $report,
	) {
	}//end __construct()
}//end class

	/**
	 * OpenRegister's office sanitizer: its public surface only.
	 */
	class OfficeDocumentSanitizer {

		/**
		 * Whether a MIME type has a strategy.
		 *
		 * @param string $mimeType The MIME type.
		 *
		 * @return bool
		 */
		public function isSanitizable(string $mimeType): bool {
			return false;
		}

		/**
		 * Sanitise a file into a temporary file.
		 *
		 * @param int $fileId The file.
		 *
		 * @return SanitizationResult
		 */
		public function sanitize(int $fileId): SanitizationResult {
			throw new \OCA\OpenRegister\Exception\SanitizationException(reason: 'internal');
		}
	}
}
