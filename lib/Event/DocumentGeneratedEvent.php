<?php

/**
 * Filinq DocumentGeneratedEvent
 *
 * Emitted after every document generated through Filinq's published
 * generation path: the `filinq.generate-document` flow node and the
 * DocumentGenerationRequestedEvent command. It tells the rest of the fleet
 * that a document now exists for an object, so an app that keeps its own
 * record of documents (dossiq files an informatieobject in the case dossier)
 * can do that from the event instead of rendering a second copy itself.
 *
 * `metadata` is passed through untouched from the request. Filinq does not
 * read it; it exists so the requesting app can carry what IT needs to file
 * the document (a document type, a classification, addressees) from the
 * moment it asked to the moment it hears back.
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * Announces a document Filinq generated.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
 */
class DocumentGeneratedEvent extends Event {

	/**
	 * Construct the event.
	 *
	 * @param array<string, mixed> $document      The generated document: fileId, path, name,
	 *                                            mime, size, format, template, object,
	 *                                            targetField, metadata, warnings.
	 * @param string               $requestingApp The app that asked for it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function __construct(
		private readonly array $document,
		private readonly string $requestingApp,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * The whole generated-document record.
	 *
	 * @return array<string, mixed> The document.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getDocument(): array {
		return $this->document;
	}//end getDocument()

	/**
	 * The object the document belongs to.
	 *
	 * @return array{register: string, schema: string, id: string}|null Null when the request named none.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getObject(): ?array {
		$object = ($this->document['object'] ?? null);
		if (is_array($object) === false) {
			return null;
		}

		return [
			'register' => (string)($object['register'] ?? ''),
			'schema' => (string)($object['schema'] ?? ''),
			'id' => (string)($object['id'] ?? ''),
		];
	}//end getObject()

	/**
	 * The Nextcloud file id of the stored document.
	 *
	 * @return int|null Null when no file was stored (a field-only generation).
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getFileId(): ?int {
		$fileId = ($this->document['fileId'] ?? null);
		if ($fileId === null) {
			return null;
		}

		return (int)$fileId;
	}//end getFileId()

	/**
	 * The path of the stored document.
	 *
	 * @return string|null Null when no file was stored.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getFilePath(): ?string {
		$path = ($this->document['path'] ?? null);
		if ($path === null) {
			return null;
		}

		return (string)$path;
	}//end getFilePath()

	/**
	 * The template the document was rendered from.
	 *
	 * @return array<string, mixed> id, slug, name and source (id, slug or inline).
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getTemplate(): array {
		return (array)($this->document['template'] ?? []);
	}//end getTemplate()

	/**
	 * The requester's metadata, passed through untouched.
	 *
	 * @return array<string, mixed> The metadata.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getMetadata(): array {
		return (array)($this->document['metadata'] ?? []);
	}//end getMetadata()

	/**
	 * The app that asked for the document.
	 *
	 * @return string The requesting app id.
	 *
	 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-every-generation-on-this-path-announces-itself
	 */
	public function getRequestingApp(): string {
		return $this->requestingApp;
	}//end getRequestingApp()
}//end class
