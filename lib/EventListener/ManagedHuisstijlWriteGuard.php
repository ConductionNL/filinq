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
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\HuisstijlProfileMapper;
use OCA\Filinq\Service\HuisstijlProfileSync;
use OCA\Filinq\Service\HuisstijlWriteGate;
use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

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
 */
class ManagedHuisstijlWriteGuard implements IEventListener {

	/**
	 * The message of the refusal.
	 */
	public const MESSAGE = 'This house style follows thematiq; change it in Theming';

	/**
	 * Constructor.
	 *
	 * @param HuisstijlWriteGate $gate Open while the profile sync writes.
	 * @param HuisstijlProfileMapper $mapper Tells a managed house style apart.
	 * @param LoggerInterface $logger Structured logger.
	 */
	public function __construct(
		private readonly HuisstijlWriteGate $gate,
		private readonly HuisstijlProfileMapper $mapper,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Refuse an update of a managed house style from anywhere but the sync.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/specs/letter-correspondence-generation/spec.md#requirement-a-managed-house-style-is-changed-only-in-thematiq-req-hsp-003
	 */
	public function handle(Event $event): void {
		if (($event instanceof ObjectUpdatingEvent) === false || $this->gate->isOpen() === true) {
			return;
		}

		$old = $event->getOldObject();
		$new = $event->getNewObject();
		if ($old === null || $new === null) {
			return;
		}

		if ($this->isHuisstijl(schema: (string) $old->getSchema()) === false
			&& $this->isHuisstijl(schema: (string) $new->getSchema()) === false
		) {
			return;
		}

		if ($this->mapper->isManaged(huisstijl: $this->dataOf(object: $old)) === false
			&& $this->mapper->isManaged(huisstijl: $this->dataOf(object: $new)) === false
		) {
			return;
		}

		$this->logger->warning('filinq.huisstijl.managed-update-refused', ['uuid' => (string) $new->getUuid()]);
		$event->setErrors(['message' => self::MESSAGE, 'property' => 'managedBy']);
		$event->stopPropagation();

	}//end handle()

	/**
	 * Whether a schema reference is the house style, matched on the slug the event carries.
	 *
	 * @param string $schema The schema reference.
	 *
	 * @return bool Whether it is the house style.
	 */
	private function isHuisstijl(string $schema): bool {
		return strtolower($schema) === strtolower(HuisstijlProfileSync::SCHEMA);

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
