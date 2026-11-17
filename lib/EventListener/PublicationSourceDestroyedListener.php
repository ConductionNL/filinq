<?php

/**
 * Takes a publication offline when its source is destroyed
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\PublicationPipelineServiceInterface;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Withdraws the publications of an object OpenRegister destroyed.
 *
 * Only a destruction counts: OpenRegister runs the delete of a destruction
 * list inside its retention-destruction frame, and an ordinary delete or a
 * move to the trash leaves the publication alone. A held object is withheld
 * from the destruction, so it is never deleted here and its publication stays.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-a-publication-goes-offline-when-its-source-document-is-destroyed-req-scr-009
 *
 * @template-implements IEventListener<Event>
 */
class PublicationSourceDestroyedListener implements IEventListener {

	/**
	 * OpenRegister's retention-destruction frame, named by string: it is an optional peer.
	 */
	public const DESTRUCTION_FRAME = 'OCA\OpenRegister\Service\Object\RetentionDestructionInterface';

	/**
	 * The properties of a destroyed object that hold the file it describes.
	 */
	private const FILE_FIELDS = ['document', 'fileId', 'documentFileRef'];

	/**
	 * Constructor.
	 *
	 * @param PublicationPipelineServiceInterface $pipeline  Withdraws the publications
	 * @param ContainerInterface                  $container Holds OpenRegister's destruction frame
	 * @param LoggerInterface                     $logger    Logger
	 */
	public function __construct(
		private readonly PublicationPipelineServiceInterface $pipeline,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Withdraw the publications of the deleted object, when it was destroyed.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-a-publication-goes-offline-when-its-source-document-is-destroyed-req-scr-009
	 */
	public function handle(Event $event): void {
		if (($event instanceof ObjectDeletedEvent) === false) {
			return;
		}

		$object = $event->getObject();
		if ($object === null || $this->isDestruction() === false) {
			return;
		}

		$uuid = (string) $object->getUuid();
		$refs = array_merge([$uuid], $this->fileRefs(data: (array) $object->getObject()));

		try {
			$this->pipeline->withdrawDestroyedSource(sourceRefs: $refs, detail: '(object ' . $uuid . ')');
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[PublicationSourceDestroyedListener] a publication of a destroyed source was NOT withdrawn',
				context: ['objectId' => $uuid, 'error' => $e->getMessage()]
			);
		}

	}//end handle()

	/**
	 * The file references the destroyed object carried, in FILE_FIELDS order.
	 *
	 * @param array<string, mixed> $data The object's data
	 *
	 * @return list<string> The references of the scalar file fields.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/woo-publicatie-pipeline/spec.md#requirement-a-publication-goes-offline-when-its-source-document-is-destroyed-req-scr-009
	 */
	private function fileRefs(array $data): array {
		$refs = [];
		foreach (self::FILE_FIELDS as $field) {
			if (is_scalar($data[$field] ?? null) === true) {
				$refs[] = (string) $data[$field];
			}
		}

		return $refs;

	}//end fileRefs()

	/**
	 * Whether OpenRegister is running a destruction right now.
	 *
	 * @return bool False when the frame is not there or cannot say.
	 */
	private function isDestruction(): bool {
		try {
			if ($this->container->has(self::DESTRUCTION_FRAME) === false) {
				return false;
			}

			$frame = $this->container->get(self::DESTRUCTION_FRAME);
		} catch (Throwable) {
			return false;
		}

		return is_object($frame) === true && method_exists($frame, 'isRunning') === true && $frame->isRunning() === true;

	}//end isDestruction()
}//end class
