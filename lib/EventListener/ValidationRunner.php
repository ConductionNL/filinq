<?php

/**
 * Validation Runner
 *
 * Event-listener fallback that computes a document's validation verdict and
 * stores it, until OpenRegister's ADR-031 calculation runtime invokes
 * DocumentValidationService directly. This runner holds only orchestration
 * (resolve the file, call the service, persist the verdict via MetadataService);
 * all validation logic lives in DocumentValidationService and the event
 * listener itself contains no validation logic.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
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

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\MetadataServiceInterface;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs document validation as a calculation fallback for Filinq objects.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
class ValidationRunner {
	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container App container the validation service and root folder are resolved from.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * Run the validation-verdict fallback for an object create/update event.
	 *
	 * Resolves the changed object from the event, then resolves the validation
	 * service and root folder at runtime and hands everything to
	 * {@see validateObject()}. Best-effort: any resolution failure is logged at
	 * debug level and swallowed, so the fallback can never break the event flow.
	 *
	 * @param Event $event The dispatched OpenRegister event.
	 * @param MetadataServiceInterface $metadataService The metadata save path.
	 * @param LoggerInterface $logger Logger.
	 *
	 * @return void
	 *
	 * @psalm-suppress TypeDoesNotContainType OpenRegister is an optional dep; event classes may not be loaded.
	 * @spec           openspec/specs/document-validation-checks/spec.md
	 */
	public function runFallbackForEvent(Event $event, MetadataServiceInterface $metadataService, LoggerInterface $logger): void {
		$object = null;
		if ($event instanceof ObjectCreatedEvent) {
			$object = $event->getObject();
		} elseif ($event instanceof ObjectUpdatedEvent) {
			$object = $event->getNewObject();
		}

		if ($object === null) {
			return;
		}

		try {
			$this->validateObject(
				object: $object,
				context: new ValidationFallbackContext(
					validationService: $this->container->get(DocumentValidationServiceInterface::class),
					metadataService: $metadataService,
					rootFolder: $this->container->get(IRootFolder::class),
					logger: $logger,
					logContext: 'validation fallback'
				)
			);
		} catch (Throwable $e) {
			$logger->debug('Filinq: validation fallback skipped: ' . $e->getMessage());
		}

	}//end runFallbackForEvent()

	/**
	 * Compute + store the validation verdict for an OpenRegister object.
	 *
	 * Resolves the object's `fileId` to a NC file, runs the validation service,
	 * and persists `validationStatus` + `validationFindings` via the existing
	 * metadata save path. Best-effort: failures are logged, never thrown.
	 *
	 * @param mixed                     $object  The OpenRegister object.
	 * @param ValidationFallbackContext $context The services and the log context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	public function validateObject(mixed $object, ValidationFallbackContext $context): void {
		try {
			$verdict = $this->verdictFor(object: $object, context: $context);
			if ($verdict === null) {
				return;
			}

			// Event listeners run without a user session in webcron/background
			// contexts; persist as a trusted system operation so OpenRegister
			// RBAC does not deny the write as 'Anonymous'.
			$context->metadataService->saveEnrichedMetadataAsSystem(
				$object->getUuid(),
				(string)$object->getRegister(),
				(string)$object->getSchema(),
				[
					'validationStatus' => $verdict['validationStatus'],
					'validationFindings' => $verdict['validationFindings'],
				]
			);

			$context->logger->info(
				'Filinq: Validation verdict stored for ' . $context->logContext,
				['objectId' => $object->getUuid(), 'status' => $verdict['validationStatus']]
			);
		} catch (Throwable $e) {
			$context->logger->error(
				'Filinq: Failed to validate object for ' . $context->logContext,
				['exception' => $e->getMessage()]
			);
		}//end try

	}//end validateObject()

	/**
	 * Validate the file an object points at.
	 *
	 * @param mixed                     $object  The OpenRegister object.
	 * @param ValidationFallbackContext $context The services.
	 *
	 * @return array<string, mixed>|null The verdict, or null when the object points at no readable file.
	 */
	private function verdictFor(mixed $object, ValidationFallbackContext $context): ?array {
		$data = $object->getObject();
		$fileId = (int)($data['fileId'] ?? 0);
		if ($fileId <= 0) {
			return null;
		}

		$file = $this->resolveFile(rootFolder: $context->rootFolder, object: $object, fileId: $fileId);
		if ($file === null) {
			return null;
		}

		return $context->validationService->validate(
			file: $file,
			record: $data,
			documentType: ($data['documentType'] ?? null)
		);

	}//end verdictFor()

	/**
	 * Resolve the object's owning user's folder and fetch the file by id.
	 *
	 * Falls back to the object owner's user folder so background dispatch
	 * (no active session) can still resolve the node.
	 *
	 * @param IRootFolder $rootFolder The root folder.
	 * @param mixed $object The OpenRegister object (for owner uid).
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File|null The file or null.
	 */
	private function resolveFile(IRootFolder $rootFolder, mixed $object, int $fileId): ?File {
		$owner = '';
		if (method_exists($object, 'getOwner') === true) {
			$owner = (string)($object->getOwner() ?? '');
		}

		if ($owner === '') {
			return null;
		}

		try {
			$userFolder = $rootFolder->getUserFolder($owner);
			$nodes = $userFolder->getById($fileId);
			if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
				return null;
			}

			return $nodes[0];
		} catch (Throwable $e) {
			return null;
		}

	}//end resolveFile()
}//end class
