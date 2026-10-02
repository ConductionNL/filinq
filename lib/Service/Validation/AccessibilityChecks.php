<?php

/**
 * The accessibility checks of document validation: whether a PDF carries
 * the things a screen reader needs (tags, a language, a title) and says it
 * targets PDF/UA.
 *
 * These are presence heuristics over the PDF bytes, in the style of the
 * encryption and text-layer checks: no parser, no new dependency. They
 * find what is missing; they do not certify PDF/UA (that takes a
 * Matterhorn-grade validator). A PDF that keeps its catalog or metadata in
 * compressed object streams can hide what these checks look for, so they
 * can report a missing tag that is there, never the other way round.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Validation;

use OCA\Filinq\Service\DocumentValidationService;

/**
 * Accessibility findings for one PDF.
 */
class AccessibilityChecks {

	public const CATEGORY = 'accessibility';

	/**
	 * The accessibility findings for a file's bytes under a profile.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string               $mime    The file's MIME type.
	 * @param mixed                $content The file's bytes.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
	 */
	public function findings(array $profile, string $mime, mixed $content): array {
		if ($mime !== 'application/pdf' || is_string($content) === false || $content === '') {
			return [];
		}

		$tagged = $this->isTagged(bytes: $content);
		$checks = [
			DocumentValidationService::CHECK_PDF_NOT_TAGGED => [
				$tagged === false,
				'The PDF has no tags, so a screen reader cannot follow its structure.',
			],
			DocumentValidationService::CHECK_PDF_LANGUAGE_MISSING => [
				$this->hasLanguage(bytes: $content) === false,
				'The PDF does not say which language it is in.',
			],
			DocumentValidationService::CHECK_PDF_TITLE_MISSING => [
				$this->hasTitle(bytes: $content) === false,
				'The PDF has no title.',
			],
			// Only meaningful on a tagged PDF: an untagged one already fails above.
			DocumentValidationService::CHECK_PDFUA_IDENTIFIER_MISSING => [
				$tagged === true && $this->hasPdfUaIdentifier(bytes: $content) === false,
				'The PDF does not say it follows PDF/UA.',
			],
		];

		$findings = [];
		foreach ($checks as $checkId => [$fires, $message]) {
			$severity = (string) ($profile['severities'][$checkId] ?? DocumentValidationService::SEVERITY_WARNING);
			if ($fires === false || $severity === DocumentValidationService::SEVERITY_OFF) {
				continue;
			}

			$findings[] = [
				'checkId' => $checkId,
				'category' => self::CATEGORY,
				'severity' => $severity,
				'message' => $message,
				'params' => [],
			];
		}

		return $findings;

	}//end findings()

	/**
	 * Whether the PDF is tagged: a structure tree, and marked content.
	 *
	 * @param string $bytes The PDF bytes.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
	 */
	public function isTagged(string $bytes): bool {
		return str_contains($bytes, '/StructTreeRoot') === true
			&& preg_match('#/MarkInfo\s*<<[^>]*/Marked\s+true#', $bytes) === 1;

	}//end isTagged()

	/**
	 * Whether the catalog names a non-empty document language.
	 *
	 * @param string $bytes The PDF bytes.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
	 */
	public function hasLanguage(string $bytes): bool {
		return preg_match('#/Lang\s*(\([^)\s]+\)|<[0-9A-Fa-f]{2,}>)#', $bytes) === 1;

	}//end hasLanguage()

	/**
	 * Whether XMP dc:title or the Info dictionary carries a non-empty title.
	 *
	 * @param string $bytes The PDF bytes.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
	 */
	public function hasTitle(string $bytes): bool {
		if (preg_match('#<dc:title>.*?<rdf:li[^>]*>\s*[^<\s][^<]*</rdf:li>#s', $bytes) === 1) {
			return true;
		}

		// Info /Title as a literal string with some content, or a hex string
		// longer than a byte-order mark (FEFF) alone.
		return preg_match('#/Title\s*(\((?!\))|<(?!(FEFF|feff)?>)[0-9A-Fa-f]{2,}>)#', $bytes) === 1;

	}//end hasTitle()

	/**
	 * Whether the XMP metadata carries a pdfuaid:part identifier.
	 *
	 * @param string $bytes The PDF bytes.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-2.1
	 */
	public function hasPdfUaIdentifier(string $bytes): bool {
		return preg_match('#pdfuaid:part\s*(=\s*["\']\d|>\s*\d)#', $bytes) === 1;

	}//end hasPdfUaIdentifier()
}//end class
