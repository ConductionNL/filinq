<?php

/**
 * Document File Inspector
 *
 * The file-level probes behind the document validation checks: defensive
 * name/mime reads, the extension-versus-mime contradiction test, and the two
 * parser-free PDF heuristics (encryption, text-layer presence).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Validation;

use OCP\Files\File;
use OCP\IAppConfig;
use Throwable;

/**
 * Runs the file-level probes used by the validation checks.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Validation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
class DocumentFileInspector {

	/**
	 * App config key for the minimum chars-per-page threshold.
	 *
	 * @var string
	 */
	private const CONFIG_TEXT_LAYER_MIN = 'validation.text_layer_min_chars_per_page';

	/**
	 * Default minimum extracted characters per page.
	 *
	 * @var integer
	 */
	private const DEFAULT_TEXT_LAYER_MIN = 32;

	/**
	 * Extension → expected mime prefixes used by the mismatch check.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const EXTENSION_MIME_MAP = [
		'pdf' => ['application/pdf'],
		'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
		'doc' => ['application/msword'],
		'odt' => ['application/vnd.oasis.opendocument.text'],
		'txt' => ['text/plain'],
		'md' => ['text/markdown', 'text/plain'],
		'html' => ['text/html'],
		'htm' => ['text/html'],
	];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig App configuration.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Read a file's mime type defensively.
	 *
	 * @param File $file The file.
	 *
	 * @return string The mime type or ''.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function safeMimeType(File $file): string {
		try {
			return (string)$file->getMimeType();
		} catch (Throwable $e) {
			return '';
		}

	}//end safeMimeType()

	/**
	 * Read a file's name defensively.
	 *
	 * @param File $file The file.
	 *
	 * @return string The name or ''.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function safeName(File $file): string {
		try {
			return (string)$file->getName();
		} catch (Throwable $e) {
			return '';
		}

	}//end safeName()

	/**
	 * Whether the file extension contradicts the detected mime type.
	 *
	 * @param string $name The file name.
	 * @param string $mime The detected mime type.
	 *
	 * @return bool True when a known extension maps to a different mime.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function extensionMismatches(string $name, string $mime): bool {
		if ($mime === '') {
			return false;
		}

		$dot = strrpos($name, '.');
		if ($dot === false) {
			return false;
		}

		$ext = strtolower(substr($name, ($dot + 1)));
		if (isset(self::EXTENSION_MIME_MAP[$ext]) === false) {
			return false;
		}

		return in_array($mime, self::EXTENSION_MIME_MAP[$ext], true) === false;
	}//end extensionMismatches()

	/**
	 * Heuristic: whether a PDF byte stream is encrypted.
	 *
	 * Looks for an `/Encrypt` entry in the trailer, which a non-encrypted PDF
	 * does not carry. Cheap and parser-free.
	 *
	 * @param string $content The PDF bytes.
	 *
	 * @return bool True when encrypted.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function isPdfEncrypted(string $content): bool {
		if (str_starts_with($content, '%PDF') === false) {
			return false;
		}

		return str_contains($content, '/Encrypt');
	}//end isPdfEncrypted()

	/**
	 * Heuristic: whether a PDF lacks a usable text layer.
	 *
	 * Counts PDF page objects (`/Type /Page`) and the text-show operators
	 * (`Tj`/`TJ`); when the average extractable signal per page falls below the
	 * configured threshold, the text layer is considered missing (scan-only).
	 *
	 * @param string $content The PDF bytes.
	 *
	 * @return bool True when the text layer is missing.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function textLayerMissing(string $content): bool {
		if (str_starts_with($content, '%PDF') === false) {
			return false;
		}

		$pages = preg_match_all('/\/Type\s*\/Page[^s]/', $content);
		if ($pages === false || $pages === 0) {
			$pages = 1;
		}

		// Count text-show operators as a proxy for extractable characters.
		$showOperators = preg_match_all('/\b(Tj|TJ)\b/', $content);
		if ($showOperators === false) {
			$showOperators = 0;
		}

		// Approximate extractable signal per page; ~1 operator ≈ a text run.
		$perPage = ($showOperators * 8) / $pages;

		return $perPage < $this->getTextLayerMin();
	}//end textLayerMissing()

	/**
	 * Read the configured minimum chars-per-page threshold.
	 *
	 * @return int The threshold.
	 */
	private function getTextLayerMin(): int {
		$value = $this->appConfig->getValueInt('filinq', self::CONFIG_TEXT_LAYER_MIN, self::DEFAULT_TEXT_LAYER_MIN);
		if ($value <= 0) {
			return self::DEFAULT_TEXT_LAYER_MIN;
		}

		return $value;
	}//end getTextLayerMin()
}//end class
