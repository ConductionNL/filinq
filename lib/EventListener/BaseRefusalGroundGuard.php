<?php

/**
 * Filinq BaseRefusalGroundGuard
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

use OCA\Filinq\Kernel\Contracts\RefusalGroundsLegacyMapInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stops the creation of a `base` object that names a Woo ground.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
 */
class BaseRefusalGroundGuard implements IEventListener {

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
	 * @param RefusalGroundsLegacyMapInterface $legacy    The old Woo slugs.
	 * @param LoggerInterface         $logger    Records a refused write.
	 * @param ContainerInterface      $container Resolves OpenRegister's SchemaMapper lazily.
	 */
	public function __construct(
		private readonly RefusalGroundsLegacyMapInterface $legacy,
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
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
		if ($object === null) {
			return;
		}

		// Cheap test first: only a Woo-shaped slug costs a schema lookup.
		$slug = self::slugOf(object: $object);
		if ($this->isWooGround(slug: $slug) === false) {
			return;
		}

		if ($this->isBaseSchema(schema: trim((string)$object->getSchema())) === false) {
			return;
		}

		$this->logger->warning('filinq.grounds.woo-base-refused', ['slug' => $slug]);
		if (method_exists($event, 'setErrors') === true) {
			$event->setErrors(['message' => self::MESSAGE, 'property' => 'slug', 'slug' => $slug]);
		}

		$event->stopPropagation();
	}//end handle()

	/**
	 * Whether a schema reference (OpenRegister stores the numeric id; a slug is
	 * accepted too) is the `base` schema. A schema that does not exist is not
	 * `base`; a lookup that fails otherwise counts as `base`, so the write is
	 * refused rather than let through unchecked.
	 *
	 * @param string $schema The object's schema reference.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function isBaseSchema(string $schema): bool {
		if (strtolower($schema) === self::SCHEMA) {
			return true;
		}

		if ($schema === '') {
			return false;
		}

		try {
			$found = $this->container->get('OCA\OpenRegister\Db\SchemaMapper')->find($schema);
			return strtolower((string)$found->getSlug()) === self::SCHEMA;
		} catch (DoesNotExistException) {
			return false;
		} catch (Throwable $e) {
			$this->logger->error('filinq.grounds.schema-lookup-failed', ['schema' => $schema, 'exception' => $e]);
			return true;
		}
	}//end isBaseSchema()

	/**
	 * The object being created, or null for any other event.
	 *
	 * @param Event $event The event.
	 *
	 * @return object|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function createdObject(Event $event): ?object {
		if (self::isCreatingEvent(event: $event) === false || method_exists($event, 'getObject') === false) {
			return null;
		}

		return self::withSchema(object: $event->getObject());
	}//end createdObject()

	/**
	 * The object when it is an entity with a schema, else null.
	 *
	 * @param mixed $object The event's object.
	 *
	 * @return object|null
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private static function withSchema(mixed $object): ?object {
		if (is_object($object) === false || method_exists($object, 'getSchema') === false) {
			return null;
		}

		return $object;
	}//end withSchema()

	/**
	 * Whether the event is OpenRegister's object-creating event.
	 *
	 * @param Event $event The event.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private static function isCreatingEvent(Event $event): bool {
		return is_a($event, 'OCA\\OpenRegister\\Event\\ObjectCreatingEvent') === true;
	}//end isCreatingEvent()

	/**
	 * The slug the new object asks for: its payload's `slug`, else the entity's.
	 *
	 * @param object $object The object entity.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
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
	 * When the old slugs cannot be read the slug counts as one, so the schema
	 * check still decides and a `base` write is refused rather than let through.
	 *
	 * @param string $slug The slug.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-existing-references-are-re-pointed-to-dossiqs-codes-without-guessing-req-grd-003
	 */
	private function isWooGround(string $slug): bool {
		if (preg_match('/^5\.\d/', $slug) === 1) {
			return true;
		}

		try {
			return $this->legacy->isLegacySlug(slug: $slug);
		} catch (Throwable $e) {
			$this->logger->error('filinq.grounds.legacy-map-unreadable', ['exception' => $e]);
			return true;
		}
	}//end isWooGround()
}//end class
