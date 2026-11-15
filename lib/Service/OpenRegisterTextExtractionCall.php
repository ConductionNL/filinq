<?php

/**
 * OpenRegister Text Extraction Call
 *
 * Calls OpenRegister's TextExtractionService in the shape the installed
 * OpenRegister declares. From 2.2 `extractFile()` takes a
 * `FileExtractionOptions` and `extractFromProvidedText()` a
 * `ProvidedTextRequest`; earlier releases take the same values as positional
 * arguments.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use ReflectionMethod;
use ReflectionNamedType;

/**
 * Builds the arguments for OpenRegister's extraction methods.
 *
 * @spec openspec/specs/ocr-trigger-surface/spec.md#requirement-automatic-ocr-fallback-in-the-anonymisation-extract-pipeline-req-ddocr-003
 */
final class OpenRegisterTextExtractionCall {

	private const OPTIONS_CLASS = 'OCA\OpenRegister\Files\Service\FileExtractionOptions';

	private const REQUEST_CLASS = 'OCA\OpenRegister\Files\Service\ProvidedTextRequest';

	/**
	 * Extract a file's text and detect its entities.
	 *
	 * @param object $extractor OpenRegister's TextExtractionService.
	 * @param int $fileId The Nextcloud file id.
	 * @param bool $force Whether to ignore up-to-date chunks.
	 * @param array<int, string>|null $entityTypes Entity types to detect; null for all.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.3
	 */
	public function extractFile(object $extractor, int $fileId, bool $force, ?array $entityTypes): void {
		if ($this->takes(extractor: $extractor, method: 'extractFile', position: 1, class: self::OPTIONS_CLASS) === true) {
			$options = self::OPTIONS_CLASS;
			$extractor->extractFile($fileId, new $options(forceReExtract: $force, entityTypes: $entityTypes));
			return;
		}

		$extractor->extractFile($fileId, $force, $entityTypes);

	}//end extractFile()

	/**
	 * Hand OpenRegister text recovered outside it, such as OCR output.
	 *
	 * @param object $extractor OpenRegister's TextExtractionService.
	 * @param int $fileId The Nextcloud file id.
	 * @param string $text The recovered text.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-2.3
	 */
	public function provideText(object $extractor, int $fileId, string $text): void {
		if ($this->takes(extractor: $extractor, method: 'extractFromProvidedText', position: 0, class: self::REQUEST_CLASS) === true) {
			$request = self::REQUEST_CLASS;
			$extractor->extractFromProvidedText(new $request(fileId: $fileId, text: $text));
			return;
		}

		$extractor->extractFromProvidedText($fileId, $text);

	}//end provideText()

	/**
	 * Whether the method's parameter at the position is typed as the class.
	 *
	 * @param object $extractor The service.
	 * @param string $method The method name.
	 * @param int $position The zero-based parameter position.
	 * @param string $class The class name.
	 *
	 * @return bool True when the parameter takes that class.
	 */
	private function takes(object $extractor, string $method, int $position, string $class): bool {
		$parameters = (new ReflectionMethod($extractor, $method))->getParameters();
		$type = ($parameters[$position] ?? null)?->getType();

		return $type instanceof ReflectionNamedType && $type->getName() === $class;

	}//end takes()
}//end class
