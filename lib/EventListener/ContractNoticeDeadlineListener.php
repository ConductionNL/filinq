<?php

/**
 * Contract notice deadline listener
 *
 * Fills in a contract's notice deadline (end date minus notice period) when
 * it is saved without one, however it is saved: through the app, through
 * OpenRegister's object API, or by an import. A deadline somebody entered is
 * never changed.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\Contract\ContractDates;
use OCA\Filinq\Service\Contract\ContractRepository;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Defaults `noticeDeadline` on a contract's create and update.
 *
 * Listens to OpenRegister's pre-write events by string, because OpenRegister
 * is an optional peer. MagicMapper merges `setModifiedData()` over the object
 * before it writes.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-contract-is-a-first-class-openregister-object-req-ddclm-001
 */
class ContractNoticeDeadlineListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves OpenRegister's SchemaMapper lazily.
	 * @param ContractDates      $dates     The notice-deadline rule.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly ContractDates $dates = new ContractDates(),
	) {

	}//end __construct()

	/**
	 * Fill in the notice deadline of a contract that is being written.
	 *
	 * @param Event $event ObjectCreatingEvent or ObjectUpdatingEvent.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function handle(Event $event): void {
		$object = null;
		if (method_exists($event, 'getNewObject') === true) {
			$object = $event->getNewObject();
		} else if (method_exists($event, 'getObject') === true) {
			$object = $event->getObject();
		}

		if (is_object($object) === false || method_exists($event, 'setModifiedData') === false || method_exists($event, 'getModifiedData') === false) {
			return;
		}

		$data = (array) $object->getObject();
		$filled = $this->dates->withNoticeDeadline(contract: $data);
		// Cheap test first: only an object that gains a deadline costs a schema lookup.
		if (isset($filled['noticeDeadline']) === false || ($data['noticeDeadline'] ?? null) === $filled['noticeDeadline']) {
			return;
		}

		if ($this->isContract(schema: (string) $object->getSchema()) === false) {
			return;
		}

		$event->setModifiedData(array_merge($event->getModifiedData(), ['noticeDeadline' => $filled['noticeDeadline']]));

	}//end handle()

	/**
	 * Whether a schema reference (id or slug) is the contract schema.
	 *
	 * @param string $schema The object's schema reference.
	 *
	 * @return bool True for the contract schema.
	 */
	private function isContract(string $schema): bool {
		if ($schema === ContractRepository::SCHEMA) {
			return true;
		}

		if ($schema === '') {
			return false;
		}

		try {
			$found = $this->container->get('OCA\OpenRegister\Db\SchemaMapper')->find($schema);
			return $found->getSlug() === ContractRepository::SCHEMA;
		} catch (Throwable) {
			return false;
		}

	}//end isContract()
}//end class
