<?php

/**
 * A registration number, once issued, is final.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/post-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\OpenRegister\Event\ObjectUpdatingEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Refuses an update that changes a registration number already issued.
 *
 * 🔴 A POST REGISTER'S WHOLE VALUE IS THAT ITS NUMBERS ARE FIXED. A number is a
 * claim about an official act: a letter went out on this date under this
 * number, and somebody may have quoted it in a reply. Letting it be edited
 * afterwards means a number in a filing cabinet no longer finds the record it
 * names, and nothing anywhere reports that the two came apart.
 *
 * 🔑 THE TASK SAID "IN THE SERVICE EVERY WRITE PATH RESOLVES THROUGH", AND
 * FILINQ HAS NO SUCH SERVICE. Registrations are written through OpenRegister's
 * objects API, so a guard in a filinq service would be bypassed by every
 * ordinary write. What filinq does have is the platform's PRE-WRITE event, and
 * it was checked rather than assumed:
 *
 *   - `MagicMapper` dispatches `ObjectUpdatingEvent` before an update, carrying
 *     both the new object and the old one;
 *   - `stopPropagation()` plus `setErrors()` on that event makes the mapper
 *     throw `HookStoppedException` with the listener's own message.
 *
 * So the refusal lands on every write path that goes through the mapper, which
 * is all of them. The PAST-tense `ObjectUpdatedEvent` filinq already listens to
 * could not have done this: by the time it fires the number is already changed.
 *
 * @template-implements IEventListener<Event>
 */
class DocumentRegistrationWriteGuard implements IEventListener {

	/**
	 * The schema whose numbers are final.
	 */
	public const SCHEMA = 'documentRegistration';

	/**
	 * The property that must not move.
	 */
	public const NUMBER = 'registrationNumber';

	/**
	 * Why an allocation was abandoned.
	 */
	public const WITHDRAWN_REASON = 'withdrawnReason';

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Structured logger.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Refuse an update that moves a number already issued.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 */
	public function handle(Event $event): void {
		if (($event instanceof ObjectUpdatingEvent) === false) {
			return;
		}

		$new = $event->getNewObject();
		$old = $event->getOldObject();

		if ($old === null) {
			// A create, not an update. There is nothing to move yet.
			return;
		}

		if ($this->isRegistration(schema: (string)$new->getSchema()) === false
			&& $this->isRegistration(schema: (string)$old->getSchema()) === false
		) {
			return;
		}

		$was = $this->numberOf(object: $old);
		$now = $this->numberOf(object: $new);

		if ($was === '') {
			// Nothing was issued yet, so the first number may still land. This
			// is the ordinary case: the platform writes the number on create,
			// and an object saved before it had one may legitimately gain one.
			return;
		}

		if ($was === $now) {
			return;
		}

		$this->logger->warning(
			'filinq.post-register.number-change-refused',
			['was' => $was, 'now' => $now, 'uuid' => (string)$new->getUuid()]
		);

		$event->setErrors(
			[
				'message' => sprintf(
					'Registratienummer %s ligt vast en kan niet worden gewijzigd. '
					. 'Trek de registratie in met een reden (%s) en maak een nieuwe aan.',
					$was,
					self::WITHDRAWN_REASON
				),
				'property' => self::NUMBER,
				'was' => $was,
			]
		);
		$event->stopPropagation();
	}//end handle()

	/**
	 * Whether this is a post-register entry.
	 *
	 * 🔑 MATCHED ON THE SLUG, WHICH IS WHAT THE EVENT CARRIES. The schema id is
	 * numeric and differs per instance, so comparing against one would make the
	 * guard silently inert everywhere but the instance it was written on.
	 *
	 * @param string $schema The schema reference on the object.
	 *
	 * @return bool Whether the guard applies.
	 */
	private function isRegistration(string $schema): bool {
		return (strtolower($schema) === strtolower(self::SCHEMA));
	}//end isRegistration()

	/**
	 * The registration number an object carries, or ''.
	 *
	 * @param object $object The object entity.
	 *
	 * @return string The number.
	 */
	private function numberOf(object $object): string {
		if (method_exists($object, 'getObject') === false) {
			return '';
		}

		$data = $object->getObject();
		if (is_array($data) === false) {
			return '';
		}

		return trim((string)($data[self::NUMBER] ?? ''));
	}//end numberOf()
}//end class
