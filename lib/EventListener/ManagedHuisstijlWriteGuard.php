<?php

/**
 * A house style managed by thematiq is changed only in thematiq.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\Contracts\HuisstijlWriteGateInterface;
use OCA\Filinq\Kernel\Contracts\ManagedHuisstijlInterface;
use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Refuses an update of a managed house style that does not come from the profile sync.
 *
 * House styles are written through OpenRegister's objects API, so the refusal
 * sits on the platform's pre-write event, like the registration-number guard:
 * a stopped `ObjectUpdatingEvent` makes the mapper throw with this message on
 * every write path. An update that would mark a hand-made house style as
 * managed is refused too: only the sync sets `managedBy`.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
 */
class ManagedHuisstijlWriteGuard implements IEventListener {

	/**
	 * The message of the refusal.
	 */
	public const MESSAGE = 'This house style follows thematiq; change it in Theming';

	/**
	 * Constructor.
	 *
	 * @param HuisstijlWriteGateInterface $gate      Open while the profile sync writes.
	 * @param ManagedHuisstijlInterface   $managed   Tells a managed house style apart.
	 * @param LoggerInterface             $logger    Structured logger.
	 * @param ContainerInterface          $container Resolves OpenRegister's SchemaMapper lazily.
	 */
	public function __construct(
		private readonly HuisstijlWriteGateInterface $gate,
		private readonly ManagedHuisstijlInterface $managed,
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
	) {
	}//end __construct()

	/**
	 * Refuse an update of a managed house style from anywhere but the sync.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
	 */
	public function handle(Event $event): void {
		if (($event instanceof ObjectUpdatingEvent) === false || $this->gate->isOpen() === true) {
			return;
		}

		$objects = $this->objectsOf(event: $event);

		// Cheap test first: only an object marked managed costs a schema lookup.
		if ($this->anyManaged(objects: $objects) === false || $this->anyHuisstijl(objects: $objects) === false) {
			return;
		}

		$this->logger->warning('filinq.huisstijl.managed-update-refused', ['uuid' => (string) $objects[0]->getUuid()]);
		$event->setErrors(['message' => self::MESSAGE, 'property' => 'managedBy']);
		$event->stopPropagation();

	}//end handle()

	/**
	 * The objects an update carries: the new one first, then the stored one.
	 *
	 * @param ObjectUpdatingEvent $event The update.
	 *
	 * @return list<\OCA\OpenRegister\Db\ObjectEntity> The objects, or [] when the update has no stored object.
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
	 */
	private function objectsOf(ObjectUpdatingEvent $event): array {
		$new = $event->getNewObject();
		$old = $event->getOldObject();
		if ($new === null || $old === null) {
			return [];
		}

		return [$new, $old];

	}//end objectsOf()

	/**
	 * Whether any of the objects is marked as managed.
	 *
	 * @param list<\OCA\OpenRegister\Db\ObjectEntity> $objects The objects.
	 *
	 * @return bool Whether one is managed.
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
	 */
	private function anyManaged(array $objects): bool {
		foreach ($objects as $object) {
			if ($this->managed->isManaged(huisstijl: $this->dataOf(object: $object)) === true) {
				return true;
			}
		}

		return false;

	}//end anyManaged()

	/**
	 * Whether any of the objects is of the house style schema.
	 *
	 * @param list<\OCA\OpenRegister\Db\ObjectEntity> $objects The objects.
	 *
	 * @return bool Whether one is a house style.
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
	 */
	private function anyHuisstijl(array $objects): bool {
		foreach ($objects as $object) {
			if ($this->isHuisstijl(schema: trim((string) $object->getSchema())) === true) {
				return true;
			}
		}

		return false;

	}//end anyHuisstijl()

	/**
	 * Whether a schema reference (OpenRegister stores the numeric id; a slug is
	 * accepted too) is the house style schema. A schema that does not exist is
	 * not the house style; a lookup that fails otherwise counts as the house
	 * style, so the write of a managed object is refused rather than let through
	 * unchecked.
	 *
	 * @param string $schema The schema reference.
	 *
	 * @return bool Whether it is the house style.
	 *
	 * @spec openspec/changes/archive/2026-11-25-templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
	 */
	private function isHuisstijl(string $schema): bool {
		if (strtolower($schema) === ManagedHuisstijlInterface::SCHEMA) {
			return true;
		}

		if ($schema === '') {
			return false;
		}

		try {
			$found = $this->container->get('OCA\OpenRegister\Db\SchemaMapper')->find($schema);
			return strtolower((string) $found->getSlug()) === ManagedHuisstijlInterface::SCHEMA;
		} catch (DoesNotExistException) {
			return false;
		} catch (Throwable $e) {
			$this->logger->error('filinq.huisstijl.schema-lookup-failed', ['schema' => $schema, 'exception' => $e]);
			return true;
		}

	}//end isHuisstijl()

	/**
	 * The data an object carries.
	 *
	 * @param object $object The object entity.
	 *
	 * @return array<string, mixed> The data, or [].
	 */
	private function dataOf(object $object): array {
		if (method_exists($object, 'getObject') === false) {
			return [];
		}

		$data = $object->getObject();
		if (is_array($data) === false) {
			return [];
		}

		return $data;

	}//end dataOf()
}//end class
