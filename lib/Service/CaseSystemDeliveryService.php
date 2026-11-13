<?php

/**
 * Case System Delivery Service
 *
 * Picks where a stored generated document goes in the case system and
 * records one delivery per document and destination, for integriq's push
 * synchronisation to send. Filinq never calls a ZGW or StUF endpoint itself.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;

/**
 * Destinations and deliveries (design D2, D5, D6).
 *
 * A template-specific destination wins over a namespace one, and a request
 * may name its own. At most one destination is active per template and per
 * namespace. A document gets one delivery per destination: asking again
 * answers the delivery that exists, so the case system never receives a
 * duplicate from filinq.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
 */
class CaseSystemDeliveryService {

	/**
	 * The register the bridge lands in.
	 */
	public const REGISTER = 'filinq';

	/**
	 * The destination schema.
	 */
	public const DESTINATION_SCHEMA = 'caseSystemDestination';

	/**
	 * The delivery schema.
	 */
	public const DELIVERY_SCHEMA = 'caseSystemDelivery';

	/**
	 * The status a new delivery waits in.
	 */
	public const READY = 'ready_for_writeback';

	/**
	 * The refusal of a second active destination.
	 */
	public const DUPLICATE_ACTIVE = 'An active destination for this template already exists.';

	/**
	 * The destination fields a delivery carries to integriq.
	 */
	private const DESTINATION_FIELDS = ['informatieobjecttype', 'vertrouwelijkheidaanduiding', 'bronorganisatie'];

	/**
	 * The most destinations read at once.
	 */
	private const DESTINATION_LIMIT = 500;

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objects OpenRegister's ObjectService.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
	) {
	}//end __construct()

	/**
	 * Where a document from this template goes, or null when nowhere.
	 *
	 * A request's own destination wins; then an active destination for the
	 * template; then an active one for the template's namespace.
	 *
	 * @param array<string, mixed>      $template The template (`id`, `namespace`).
	 * @param array<string, mixed>|null $override The request's `options.output.caseSystem`, if any.
	 *
	 * @return array<string, mixed>|null The destination.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	public function destinationFor(array $template, ?array $override=null): ?array {
		if ($override !== null && $override !== []) {
			return $override + ['id' => ''];
		}

		$active = array_values(array_filter($this->destinations(), static fn (array $d): bool => ($d['active'] ?? false) === true));

		return (self::firstMatch(destinations: $active, template: $template, byTemplate: true)
			?? self::firstMatch(destinations: $active, template: $template, byTemplate: false));

	}//end destinationFor()

	/**
	 * The first destination for this template (by its id), or for its namespace without a template id.
	 *
	 * @param array<int, array<string, mixed>> $destinations The active destinations.
	 * @param array<string, mixed>             $template     The template (`id`, `namespace`).
	 * @param bool                             $byTemplate   Match on the template id, else on the namespace.
	 *
	 * @return array<string, mixed>|null The destination.
	 */
	private static function firstMatch(array $destinations, array $template, bool $byTemplate): ?array {
		$probe = ['templateId' => '', 'templateNamespace' => (string) ($template['namespace'] ?? '')];
		if ($byTemplate === true) {
			$probe = ['templateId' => (string) ($template['id'] ?? '')];
		}

		if (implode('', $probe) === '') {
			return null;
		}

		foreach ($destinations as $destination) {
			if (self::coverSame(first: $probe, second: $destination) === true) {
				return $destination;
			}
		}

		return null;

	}//end firstMatch()

	/**
	 * Refuse a destination that would be a second active one for its template or namespace.
	 *
	 * @param array<string, mixed> $destination The destination about to be saved.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When another active destination covers the same template or namespace.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	public function assertSingleActive(array $destination): void {
		if (($destination['active'] ?? false) !== true) {
			return;
		}

		$own = (string) ($destination['id'] ?? '');
		foreach ($this->destinations() as $other) {
			if (($other['active'] ?? false) !== true || ($own !== '' && (string) ($other['id'] ?? '') === $own)) {
				continue;
			}

			if (self::coverSame(first: $destination, second: $other) === true) {
				throw new InvalidArgumentException(self::DUPLICATE_ACTIVE);
			}
		}

	}//end assertSingleActive()

	/**
	 * The delivery of a stored document to a destination; the existing one when there is one.
	 *
	 * @param array<string, mixed> $document    The generatedDocument (`id`, `fileId`, `filePath`, `format`).
	 * @param array<string, mixed> $destination The destination from destinationFor().
	 * @param array<string, mixed> $context     `author` (display name), `generatedBy` (user id), `createdAt` (Y-m-d), `mimeType`.
	 *
	 * @return array<string, mixed> The delivery.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
	 */
	public function deliver(array $document, array $destination, array $context): array {
		$documentId    = (string) ($document['id'] ?? '');
		$destinationId = (string) ($destination['id'] ?? '');

		$existing = $this->existingDelivery(documentId: $documentId, destinationId: $destinationId);
		if ($existing !== null) {
			return $existing;
		}

		$delivery = [
			'generatedDocumentId' => $documentId,
			'destinationId' => $destinationId,
			'sourceId' => (string) ($destination['sourceId'] ?? ''),
			'fileId' => (int) ($document['fileId'] ?? 0),
			'metadata' => self::metadata(document: $document, destination: $destination, context: $context),
			'deliveryStatus' => self::READY,
			'generatedBy' => (string) ($context['generatedBy'] ?? ''),
		];
		if ((string) ($destination['zaakUrl'] ?? '') !== '') {
			$delivery['zaakUrl'] = (string) $destination['zaakUrl'];
		}

		$saved = $this->objects->resolve()->saveObject(
			object: $delivery,
			register: self::REGISTER,
			schema: self::DELIVERY_SCHEMA
		);

		return (self::rowOf(entry: $saved) ?? $delivery);

	}//end deliver()

	/**
	 * What filinq fills for integriq to map (design D6).
	 *
	 * @param array<string, mixed> $document    The generatedDocument.
	 * @param array<string, mixed> $destination The destination.
	 * @param array<string, mixed> $context     The author, creation date and MIME type.
	 *
	 * @return array<string, mixed> The metadata.
	 */
	private static function metadata(array $document, array $destination, array $context): array {
		$fileName = basename((string) ($document['filePath'] ?? ''));
		$metadata = [
			'titel' => pathinfo($fileName, PATHINFO_FILENAME),
			'bestandsnaam' => $fileName,
			'formaat' => (string) ($context['mimeType'] ?? ''),
			'taal' => 'dut',
			'creatiedatum' => (string) ($context['createdAt'] ?? ''),
			'auteur' => (string) ($context['author'] ?? ''),
		];
		foreach (self::DESTINATION_FIELDS as $field) {
			$metadata[$field] = (string) ($destination[$field] ?? '');
		}

		return $metadata;

	}//end metadata()

	/**
	 * Whether two destinations cover the same template, or the same namespace without a template.
	 *
	 * @param array<string, mixed> $first  One destination.
	 * @param array<string, mixed> $second The other.
	 *
	 * @return bool
	 */
	private static function coverSame(array $first, array $second): bool {
		$template = (string) ($first['templateId'] ?? '');
		if ($template !== '') {
			return (string) ($second['templateId'] ?? '') === $template;
		}

		$namespace = (string) ($first['templateNamespace'] ?? '');

		return $namespace !== '' && (string) ($second['templateId'] ?? '') === '' && (string) ($second['templateNamespace'] ?? '') === $namespace;

	}//end coverSame()

	/**
	 * Every declared destination.
	 *
	 * @return array<int, array<string, mixed>> The destinations.
	 */
	private function destinations(): array {
		return $this->rows(schema: self::DESTINATION_SCHEMA, filters: [], limit: self::DESTINATION_LIMIT);

	}//end destinations()

	/**
	 * The delivery of this document to this destination, if one exists.
	 *
	 * @param string $documentId    The generatedDocument id.
	 * @param string $destinationId The destination id ('' for a request's own destination).
	 *
	 * @return array<string, mixed>|null The delivery.
	 */
	private function existingDelivery(string $documentId, string $destinationId): ?array {
		if ($documentId === '') {
			return null;
		}

		$rows = $this->rows(schema: self::DELIVERY_SCHEMA, filters: ['generatedDocumentId' => $documentId], limit: self::DESTINATION_LIMIT);
		foreach ($rows as $row) {
			if ((string) ($row['destinationId'] ?? '') === $destinationId) {
				return $row;
			}
		}

		return null;

	}//end existingDelivery()

	/**
	 * Rows of one schema in the register, as arrays.
	 *
	 * @param string               $schema  The schema slug.
	 * @param array<string, mixed> $filters Extra filters.
	 * @param int                  $limit   The most rows.
	 *
	 * @return array<int, array<string, mixed>> The rows.
	 */
	private function rows(string $schema, array $filters, int $limit): array {
		$found = $this->objects->resolve()->findAll(
			config: [
				'filters' => array_merge(['register' => self::REGISTER, 'schema' => $schema], $filters),
				'limit' => $limit,
			],
			_rbac: false
		);

		$rows = [];
		foreach ($found as $entry) {
			$row = self::rowOf(entry: $entry);
			if ($row !== null) {
				$rows[] = $row;
			}
		}

		return $rows;

	}//end rows()

	/**
	 * One stored object as an array, its id under `id`.
	 *
	 * @param mixed $entry An array or an object entity.
	 *
	 * @return array<string, mixed>|null The row.
	 */
	private static function rowOf(mixed $entry): ?array {
		if (is_array($entry) === true) {
			return $entry;
		}

		if (is_object($entry) === false || method_exists($entry, 'jsonSerialize') === false) {
			return null;
		}

		$row = $entry->jsonSerialize();
		if (is_array($row) === false) {
			return null;
		}

		$row['id'] ??= ($row['@self']['id'] ?? null);

		return $row;

	}//end rowOf()
}//end class
