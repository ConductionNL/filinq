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

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Kernel\Contracts\CaseSystemDeliveryServiceInterface;
use OCP\IUserManager;
use RuntimeException;
use Throwable;

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
class CaseSystemDeliveryService implements CaseSystemDeliveryServiceInterface {

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
	 * The status a delivery the case system refused waits in.
	 */
	public const FAILED = 'writeback_failed';

	/**
	 * The answer for a delivery that does not exist.
	 */
	public const NOT_FOUND = 'Delivery not found.';

	/**
	 * The refusal of a retry on a delivery that did not fail.
	 */
	public const NOT_FAILED = 'Only a failed delivery can be retried.';

	/**
	 * The refusal of a case-system request that files nothing (design D3).
	 */
	public const MUST_STORE = 'A document sent to the case system must be stored first. Use output mode files or both.';

	/**
	 * The warning when a destination applied but the store failed (design D3).
	 */
	public const NOT_STORED = 'Not sent to the case system: the document was not stored.';

	/**
	 * The warning when the generatedDocument entry could not be written, so there is nothing to deliver.
	 */
	public const NO_RECORD = 'Not sent to the case system: the generated document could not be recorded.';

	/**
	 * The prefix of the warning when the delivery itself could not be written (design D4).
	 */
	public const NOT_RECORDED = 'Not sent to the case system: ';

	/**
	 * The generated document schema the delivery is linked from.
	 */
	public const DOCUMENT_SCHEMA = 'generatedDocument';

	/**
	 * The mime type of each output format.
	 */
	private const MIME_TYPES = [
		'pdf' => 'application/pdf',
		'odf' => 'application/vnd.oasis.opendocument.text',
		'html' => 'text/html',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	];

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
	 * @param IUserManager|null             $users   Names the generating user as the delivery's author.
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objects,
		private readonly ?IUserManager $users=null,
	) {
	}//end __construct()

	/**
	 * Refuse a request that names a case-system destination but files nothing (design D3).
	 *
	 * @param array<string, mixed> $options The generation options (`output.caseSystem`).
	 * @param string               $mode    The resolved output mode.
	 *
	 * @return void
	 *
	 * @throws Exception Code 400 when `output.caseSystem` is set with mode `return`.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
	 */
	public static function assertStoredMode(array $options, string $mode): void {
		$override = ($options['output']['caseSystem'] ?? null);
		if ($mode === 'return' && is_array($override) === true && $override !== []) {
			throw new Exception(message: self::MUST_STORE, code: 400);
		}

	}//end assertStoredMode()

	/**
	 * Record the delivery of one generation and link it from its generatedDocument (design D3, D4).
	 *
	 * Never throws: a failure to record becomes a warning, so the generation
	 * itself still succeeds.
	 *
	 * @param array<string, mixed> $template   The template (`id`, `namespace`).
	 * @param array<string, mixed> $document   The generatedDocument entry just written ([] when it failed).
	 * @param array<string, mixed> $generation `stored` (`fileId`, `path`), `options` and `format` of the generation.
	 *
	 * @return array{document: array<string, mixed>, delivery: array<string, mixed>|null, warnings: array<int, string>}
	 *         The entry (with `caseSystemDeliveryId` when linked), the delivery and any warnings.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
	 */
	public function recordGeneration(array $template, array $document, array $generation): array {
		$outcome = ['document' => $document, 'delivery' => null, 'warnings' => []];
		$options = (array) ($generation['options'] ?? []);

		$override = ($options['output']['caseSystem'] ?? null);
		if (is_array($override) === false) {
			$override = null;
		}

		try {
			$destination = $this->destinationFor(template: $template, override: $override);
		} catch (Throwable $e) {
			$outcome['warnings'][] = self::NOT_RECORDED . $e->getMessage();
			return $outcome;
		}

		if ($destination === null) {
			return $outcome;
		}

		$stored = (array) ($generation['stored'] ?? []);
		if (($stored['fileId'] ?? null) === null) {
			$outcome['warnings'][] = self::NOT_STORED;
			return $outcome;
		}

		$document = self::rowOf(entry: $document) ?? [];
		if ((string) ($document['id'] ?? '') === '') {
			$outcome['warnings'][] = self::NO_RECORD;
			return $outcome;
		}

		try {
			$delivery = $this->deliver(
				document: $document + ['fileId' => $stored['fileId'], 'filePath' => ($stored['path'] ?? '')],
				destination: $destination,
				context: $this->contextFor(options: $options, format: (string) ($generation['format'] ?? ''))
			);
			$outcome['delivery'] = $delivery;
			$outcome['document'] = $this->link(document: $document, deliveryId: (string) ($delivery['id'] ?? ''));
		} catch (Throwable $e) {
			$outcome['warnings'][] = self::NOT_RECORDED . $e->getMessage();
		}

		return $outcome;

	}//end recordGeneration()

	/**
	 * The fields an admin may set on a destination.
	 */
	private const DESTINATION_INPUT = [
		'templateNamespace',
		'templateId',
		'sourceId',
		'informatieobjecttype',
		'vertrouwelijkheidaanduiding',
		'bronorganisatie',
	];

	/**
	 * The statuses the bridge panel lists: waiting and failed.
	 */
	private const OPEN_STATUSES = [self::READY, self::FAILED];

	/**
	 * Every declared destination, for the bridge panel.
	 *
	 * @return array<int, array<string, mixed>> The destinations.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	public function listDestinations(): array {
		return $this->destinations();

	}//end listDestinations()

	/**
	 * The waiting and failed deliveries, for the bridge panel.
	 *
	 * @return array<int, array<string, mixed>> The deliveries, waiting ones first.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	public function openDeliveries(): array {
		$open = [];
		foreach (self::OPEN_STATUSES as $status) {
			$rows = $this->rows(schema: self::DELIVERY_SCHEMA, filters: ['deliveryStatus' => $status], limit: self::DESTINATION_LIMIT);
			$open = array_merge($open, $rows);
		}

		return $open;

	}//end openDeliveries()

	/**
	 * Create or update a destination; a second active one for the same template or namespace is refused.
	 *
	 * @param array<string, mixed> $input The destination fields, with `id` to update an existing one.
	 *
	 * @return array<string, mixed> The destination as stored.
	 *
	 * @throws InvalidArgumentException When it names neither a template nor a namespace, has no source,
	 *                                  or would be a second active destination.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
	 */
	public function saveDestination(array $input): array {
		$destination = [];
		foreach (self::DESTINATION_INPUT as $field) {
			$destination[$field] = trim((string) ($input[$field] ?? ''));
		}

		$destination['active'] = ($input['active'] ?? false) === true;
		if ($destination['templateId'] === '' && $destination['templateNamespace'] === '') {
			throw new InvalidArgumentException('A destination names a template or a template namespace.');
		}

		if ($destination['sourceId'] === '') {
			throw new InvalidArgumentException('A destination names a bridge source.');
		}

		$id = trim((string) ($input['id'] ?? ''));
		$this->assertSingleActive(destination: $destination + ['id' => $id]);
		$uuid = null;
		if ($id !== '') {
			$uuid = $id;
		}

		$saved = $this->objects->resolve()->saveObject(
			object: $destination,
			register: self::REGISTER,
			schema: self::DESTINATION_SCHEMA,
			uuid: $uuid
		);

		return (self::rowOf(entry: $saved) ?? ($destination + ['id' => $id]));

	}//end saveDestination()

	/**
	 * Put a failed delivery back in the queue, on the same object (REQ-GSC-003, design D5).
	 *
	 * @param string $id The delivery id.
	 *
	 * @return array<string, mixed> The delivery, waiting again.
	 *
	 * @throws RuntimeException 404 when there is no such delivery, 409 when it is not in `writeback_failed`.
	 *
	 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
	 */
	public function retry(string $id): array {
		$row = null;
		if ($id !== '') {
			$row = self::rowOf(
				entry: $this->objects->resolve()->find(id: $id, register: self::REGISTER, schema: self::DELIVERY_SCHEMA, _rbac: false)
			);
		}

		if ($row === null) {
			throw new RuntimeException(self::NOT_FOUND, 404);
		}

		if (($row['deliveryStatus'] ?? '') !== self::FAILED) {
			throw new RuntimeException(self::NOT_FAILED, 409);
		}

		$fields = $row;
		unset($fields['@self'], $fields['id'], $fields['errorMessage']);
		$fields['deliveryStatus'] = self::READY;

		$saved = $this->objects->resolve()->saveObject(
			object: $fields,
			register: self::REGISTER,
			schema: self::DELIVERY_SCHEMA,
			uuid: (string) ($row['id'] ?? $id)
		);

		return (self::rowOf(entry: $saved) ?? ($fields + ['id' => $id]));

	}//end retry()

	/**
	 * Who generated the document, when, and in which mime type (design D6).
	 *
	 * @param array<string, mixed> $options The generation options (`userId`).
	 * @param string               $format  The output format.
	 *
	 * @return array<string, mixed> The context deliver() takes.
	 */
	private function contextFor(array $options, string $format): array {
		$userId = (string) ($options['userId'] ?? '');
		$author = $userId;
		$user   = null;
		if ($userId !== '' && $this->users !== null) {
			$user = $this->users->get($userId);
		}

		if ($user !== null) {
			$author = $user->getDisplayName();
		}

		return [
			'author' => $author,
			'generatedBy' => $userId,
			'createdAt' => date('Y-m-d'),
			'mimeType' => (self::MIME_TYPES[$format] ?? 'application/octet-stream'),
		];

	}//end contextFor()

	/**
	 * Put the delivery's id on its generatedDocument; unchanged when it is already there.
	 *
	 * @param array<string, mixed> $document   The generatedDocument entry, its id under `id`.
	 * @param string               $deliveryId The delivery id.
	 *
	 * @return array<string, mixed> The entry as stored.
	 */
	private function link(array $document, string $deliveryId): array {
		if ($deliveryId === '' || (string) ($document['caseSystemDeliveryId'] ?? '') === $deliveryId) {
			return $document;
		}

		$documentId = (string) $document['id'];
		$fields     = $document;
		unset($fields['@self'], $fields['id']);
		$fields['caseSystemDeliveryId'] = $deliveryId;

		$saved = $this->objects->resolve()->saveObject(
			object: $fields,
			register: self::REGISTER,
			schema: self::DOCUMENT_SCHEMA,
			uuid: $documentId
		);

		return (self::rowOf(entry: $saved) ?? ($fields + ['id' => $documentId]));

	}//end link()

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
		$row = $entry;
		if (is_object($entry) === true && method_exists($entry, 'jsonSerialize') === true) {
			$row = $entry->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$row['id'] ??= ($row['@self']['id'] ?? null);

		return $row;

	}//end rowOf()
}//end class
