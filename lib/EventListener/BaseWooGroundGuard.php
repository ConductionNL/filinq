<?php

/**
 * Filinq BaseWooGroundGuard
 *
 * Refuses a new Woo refusal ground on the `base` schema: the Woo grounds come
 * from dossiq's one list (or its read-only snapshot), so `base` keeps only a
 * tenant's own bases.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\Grounds\WooGroundsLegacyMap;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stops the creation of a `base` object that names a Woo ground.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */
class BaseWooGroundGuard implements IEventListener {

	/**
	 * The schema that no longer holds Woo grounds.
	 */
	public const SCHEMA = 'base';

	/**
	 * Why the write is refused.
	 */
	public const MESSAGE = 'Woo refusal grounds come from dossiq; maintain them there. A basis of your own needs a slug that is not a Woo ground.';

	/**
	 * Constructor.
	 *
	 * @param WooGroundsLegacyMap $legacy The old Woo slugs.
	 * @param LoggerInterface     $logger Records a refused write.
	 */
	public function __construct(
		private readonly WooGroundsLegacyMap $legacy,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Refuse a new `base` object whose slug is a Woo ground.
	 *
	 * @param Event $event The pre-create event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	public function handle(Event $event): void {
		$object = $this->createdObject(event: $event);
		if ($object === null || strtolower((string)$object->getSchema()) !== self::SCHEMA) {
			return;
		}

		$slug = self::slugOf(object: $object);
		if ($this->isWooGround(slug: $slug) === false) {
			return;
		}

		$this->logger->warning('filinq.grounds.woo-base-refused', ['slug' => $slug]);
		if (method_exists($event, 'setErrors') === true) {
			$event->setErrors(['message' => self::MESSAGE, 'property' => 'slug', 'slug' => $slug]);
		}

		$event->stopPropagation();
	}//end handle()

	/**
	 * The object being created, or null for any other event.
	 *
	 * @param Event $event The event.
	 *
	 * @return object|null
	 */
	private function createdObject(Event $event): ?object {
		if (is_a($event, 'OCA\\OpenRegister\\Event\\ObjectCreatingEvent') === false || method_exists($event, 'getObject') === false) {
			return null;
		}

		$object = $event->getObject();
		if (is_object($object) === false || method_exists($object, 'getSchema') === false) {
			return null;
		}

		return $object;
	}//end createdObject()

	/**
	 * The slug the new object asks for: its payload's `slug`, else the entity's.
	 *
	 * @param object $object The object entity.
	 *
	 * @return string
	 */
	private static function slugOf(object $object): string {
		$payload = [];
		if (method_exists($object, 'getObject') === true && is_array($object->getObject()) === true) {
			$payload = $object->getObject();
		}

		$slug = ($payload['slug'] ?? $payload['@self']['slug'] ?? null);
		if (is_string($slug) === false && method_exists($object, 'getSlug') === true) {
			$slug = $object->getSlug();
		}

		return trim((string)$slug);
	}//end slugOf()

	/**
	 * Whether a slug names a Woo ground: a Woo article code or an old Woo slug.
	 *
	 * @param string $slug The slug.
	 *
	 * @return bool
	 */
	private function isWooGround(string $slug): bool {
		if (preg_match('/^5\.\d/', $slug) === 1) {
			return true;
		}

		try {
			return $this->legacy->isLegacySlug(slug: $slug);
		} catch (Throwable) {
			return false;
		}
	}//end isWooGround()
}//end class
