<?php

/**
 * Document Generation Request Rules
 *
 * What a generation request must hold, and how its loosely typed values are
 * read. A request arrives as a flow step's authored configuration or as an
 * array another app built for DocumentGenerationRequestedEvent, so nothing
 * about its types can be assumed; these rules are the one reading of it that
 * DocumentGenerationRequestService, and through it the flow node's
 * validateConfig(), rely on.
 *
 * Pure: no collaborators, no I/O.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use UnexpectedValueException;

/**
 * Validates a generation request and reads its values.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
 */
class DocumentGenerationRequestRules {

	/**
	 * The output formats, and the mime type each produces.
	 *
	 * `html` is the source format: the rendered template before conversion.
	 * These are exactly the formats DocumentRenderPipeline::produceOutput()
	 * produces; a format outside this list would fall through to PDF there.
	 *
	 * @var array<string, string>
	 */
	public const FORMATS = [
		'pdf' => 'application/pdf',
		'odf' => 'application/vnd.oasis.opendocument.text',
		'html' => 'text/html',
	];

	/**
	 * Refuse a request that cannot be carried out.
	 *
	 * @param array<string, mixed> $request The request or the node's configuration.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When the request is unusable.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function validate(array $request): void {
		$this->assertOneTemplate(request: $request);
		$this->assertFormat(request: $request);
		$this->assertDestination(request: $request);

		if (array_key_exists('metadata', $request) === true && is_array($request['metadata']) === false) {
			throw new UnexpectedValueException('"metadata" must be an object.');
		}

		if (array_key_exists('output', $request) === true && $this->text(value: $request['output']) === '') {
			throw new UnexpectedValueException('"output" must name the item field the document is written to.');
		}
	}//end validate()
	/**
	 * Exactly one template source, and a namespace for a slug.
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When no template, or more than one, is named.
	 */
	private function assertOneTemplate(array $request): void {
		$sources = array_filter(
			[
				'templateId' => $this->text(value: ($request['templateId'] ?? null)),
				'templateSlug' => $this->text(value: ($request['templateSlug'] ?? null)),
				'template' => $this->text(value: ($request['template'] ?? null)),
			],
			static fn (string $value): bool => $value !== ''
		);

		if (count($sources) !== 1) {
			throw new UnexpectedValueException(
				'Name exactly one template: "templateId", "templateSlug" or "template" (inline text).'
			);
		}

		if (isset($sources['templateSlug']) === true
			&& $this->text(value: ($request['templateNamespace'] ?? null)) === ''
		) {
			throw new UnexpectedValueException('"templateSlug" needs "templateNamespace", the app the template belongs to.');
		}
	}//end assertOneTemplate()
	/**
	 * A format the render pipeline produces.
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When the format is unknown.
	 */
	private function assertFormat(array $request): void {
		$format = $this->text(value: ($request['format'] ?? 'pdf'));
		if (array_key_exists($format, self::FORMATS) === false) {
			throw new UnexpectedValueException(
				'"format" must be one of: ' . implode(', ', array_keys(self::FORMATS)) . '.'
			);
		}
	}//end assertFormat()
	/**
	 * Somewhere for the document to go: a file, a field, or both.
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When the target field is malformed or nothing receives the document.
	 */
	private function assertDestination(array $request): void {
		$targetField = ($request['targetField'] ?? null);
		if ($targetField !== null && is_string($targetField) === false) {
			throw new UnexpectedValueException('"targetField" must be the name of one field on the object.');
		}

		if ($this->storesFile(request: $request) === false && $this->text(value: $targetField) === '') {
			throw new UnexpectedValueException(
				'With "storeFile" off the document goes nowhere. Set "targetField", or leave "storeFile" on.'
			);
		}
	}//end assertDestination()
	/**
	 * Whether the request stores a file (the default).
	 *
	 * @param array<string, mixed> $request The request.
	 *
	 * @return bool True unless storeFile is explicitly off.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function storesFile(array $request): bool {
		$value = ($request['storeFile'] ?? true);
		if (is_string($value) === true) {
			return in_array(strtolower(trim($value)), ['0', 'false', 'no', 'off', ''], true) === false;
		}

		return (bool)$value;
	}//end storesFile()
	/**
	 * The object reference, when the request carries a complete one.
	 *
	 * @param mixed $value The request's `object`.
	 *
	 * @return array{register: string, schema: string, id: string}|null Null when any part is missing.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function objectRef(mixed $value): ?array {
		if (is_array($value) === false) {
			return null;
		}

		$ref = [
			'register' => $this->text(value: ($value['register'] ?? null)),
			'schema' => $this->text(value: ($value['schema'] ?? null)),
			'id' => $this->text(value: ($value['id'] ?? null)),
		];

		if (in_array('', $ref, true) === true) {
			return null;
		}

		return $ref;
	}//end objectRef()
	/**
	 * The value, or null when it is empty.
	 *
	 * @param string $value The value.
	 *
	 * @return string|null The value, or null.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function orNull(string $value): ?string {
		if ($value === '') {
			return null;
		}

		return $value;
	}//end orNull()
	/**
	 * The value, or the default when it is empty.
	 *
	 * @param string $value   The value.
	 * @param string $default The default.
	 *
	 * @return string The value or the default.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function orDefault(string $value, string $default): string {
		if ($value === '') {
			return $default;
		}

		return $value;
	}//end orDefault()
	/**
	 * A scalar as trimmed text, anything else as ''.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string The text.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-configuration-that-cannot-run-is-refused-when-the-flow-is-saved
	 */
	public function text(mixed $value): string {
		if (is_scalar($value) === false) {
			return '';
		}

		return trim((string)$value);
	}//end text()
}//end class
