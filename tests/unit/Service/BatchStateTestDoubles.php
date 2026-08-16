<?php

/**
 * Test doubles for the anonymization batch-state store
 *
 * Two fakes that carry real behaviour rather than recorded expectations:
 *
 * - {@see NullCacheFake} reproduces `OC\Memcache\NullCache` — the cache class
 *   Nextcloud actually hands out from `ICacheFactory::createDistributed()` when
 *   `memcache.local` is unset, which is every unconfigured install. It accepts
 *   writes and returns null for every read.
 * - {@see InMemoryObjectServiceFake} reproduces just enough of OpenRegister's
 *   ObjectService to store, retrieve and delete an object across calls, and to
 *   serialise it the way OpenRegister does (data + `@self` + a top-level `id`).
 *
 * ⚠️ THESE ARE SUBCLASSES / IMPLEMENTATIONS, NOT PHPUnit MOCKS, ON PURPOSE.
 * A PHPUnit mock cannot observe named arguments — it reports its own parameter
 * defaults — so a test built on `expects()->with()` can pass against code that
 * passed the wrong values. Storing and reading the values back makes the
 * assertions about real effects.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ICache;

/**
 * A cache that discards everything, exactly like `OC\Memcache\NullCache`.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class NullCacheFake implements ICache {

	/**
	 * How many writes were discarded — asserted on so the fixture is proven to
	 * be reached rather than assumed.
	 *
	 * @var int
	 */
	public int $discardedWrites = 0;

	/**
	 * Always a miss.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed Always null.
	 */
	public function get(string $key): mixed {
		return null;
	}//end get()

	/**
	 * Accept and discard.
	 *
	 * @param string $key Cache key.
	 * @param mixed $value Value.
	 * @param int $ttl TTL in seconds.
	 *
	 * @return mixed True, as NullCache reports success.
	 */
	public function set(string $key, mixed $value, int $ttl = 0): mixed {
		$this->discardedWrites++;
		return true;
	}//end set()

	/**
	 * Nothing is ever present.
	 *
	 * @param string $key Cache key.
	 *
	 * @return bool Always false.
	 */
	public function hasKey(string $key): bool {
		return false;
	}//end hasKey()

	/**
	 * Removing from nothing succeeds.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed Always true.
	 */
	public function remove(string $key): mixed {
		return true;
	}//end remove()

	/**
	 * Clearing nothing succeeds.
	 *
	 * @param string $prefix Key prefix.
	 *
	 * @return mixed Always true.
	 */
	public function clear(string $prefix = ''): mixed {
		return true;
	}//end clear()
}//end class

/**
 * An in-memory cache that actually stores, for the "a distributed cache IS
 * configured" arm of the tests.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class InMemoryCacheFake implements ICache {

	/**
	 * The backing store.
	 *
	 * @var array<string, mixed>
	 */
	private array $entries = [];

	/**
	 * Read a stored value.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed The stored value, or null.
	 */
	public function get(string $key): mixed {
		return ($this->entries[$key] ?? null);
	}//end get()

	/**
	 * Store a value.
	 *
	 * @param string $key Cache key.
	 * @param mixed $value Value.
	 * @param int $ttl TTL in seconds (ignored).
	 *
	 * @return mixed Always true.
	 */
	public function set(string $key, mixed $value, int $ttl = 0): mixed {
		$this->entries[$key] = $value;
		return true;
	}//end set()

	/**
	 * Whether a key is present.
	 *
	 * @param string $key Cache key.
	 *
	 * @return bool True when stored.
	 */
	public function hasKey(string $key): bool {
		return array_key_exists($key, $this->entries);
	}//end hasKey()

	/**
	 * Remove a key.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed Always true.
	 */
	public function remove(string $key): mixed {
		unset($this->entries[$key]);
		return true;
	}//end remove()

	/**
	 * Drop everything.
	 *
	 * @param string $prefix Key prefix (ignored).
	 *
	 * @return mixed Always true.
	 */
	public function clear(string $prefix = ''): mixed {
		$this->entries = [];
		return true;
	}//end clear()
}//end class

/**
 * An OpenRegister object, serialised the way OpenRegister serialises one.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class StoredObjectFake extends ObjectEntity {

	/**
	 * The stored object data.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Construct a stored object.
	 *
	 * @param string $uuid The object UUID.
	 * @param array<string, mixed> $data The object data.
	 */
	public function __construct(string $uuid, array $data) {
		$this->setUuid($uuid);
		$this->data = $data;
	}//end __construct()

	/**
	 * Serialise as OpenRegister does: the data, plus `@self` metadata, plus a
	 * top-level `id` mirroring the UUID.
	 *
	 * @return array<string, mixed> The serialised object.
	 */
	public function jsonSerialize(): array {
		$serialised = $this->data;
		$serialised['@self'] = [
			'id' => $this->getUuid(),
			'name' => $this->getUuid(),
			'files' => [],
		];
		$serialised['id'] = $this->getUuid();

		return $serialised;
	}//end jsonSerialize()
}//end class

/**
 * A minimal in-memory OpenRegister ObjectService.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class InMemoryObjectServiceFake extends ObjectService {

	/**
	 * Stored objects keyed by "register/schema/uuid".
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $stored = [];

	/**
	 * Every save call recorded as [register, schema, uuid, object].
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public array $saveCalls = [];

	/**
	 * Find a stored object.
	 *
	 * @param string $id Object UUID.
	 * @param string $register Register slug.
	 * @param string $schema Schema slug.
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return mixed The stored object.
	 *
	 * @throws DoesNotExistException When the identifier is unknown, mirroring
	 *                               OpenRegister's behaviour on a miss.
	 */
	public function find(
		string $id = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$key = $this->key(register: $register, schema: $schema, uuid: $id);
		if (array_key_exists($key, $this->stored) === false) {
			throw new DoesNotExistException('No object with id ' . $id);
		}

		return new StoredObjectFake(uuid: $id, data: $this->stored[$key]);
	}//end find()

	/**
	 * Store an object under the supplied UUID (upsert).
	 *
	 * @param array $object Object data.
	 * @param string $register Register slug.
	 * @param string $schema Schema slug.
	 * @param string|null $uuid Object UUID.
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return mixed The stored object.
	 */
	public function saveObject(
		array $object = [],
		string $register = '',
		string $schema = '',
		?string $uuid = null,
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		$this->saveCalls[] = [
			'register' => $register,
			'schema' => $schema,
			'uuid' => $uuid,
			'object' => $object,
			'_rbac' => $_rbac,
			'_multitenancy' => $_multitenancy,
		];

		$key = $this->key(register: $register, schema: $schema, uuid: (string)$uuid);
		$this->stored[$key] = $object;

		return new StoredObjectFake(uuid: (string)$uuid, data: $object);
	}//end saveObject()

	/**
	 * Delete a stored object.
	 *
	 * @param string $uuid Object UUID.
	 * @param string $register Register slug.
	 * @param string $schema Schema slug.
	 * @param bool $_rbac RBAC bypass flag.
	 * @param bool $_multitenancy Multitenancy bypass flag.
	 *
	 * @return bool Always true.
	 */
	public function deleteObject(
		string $uuid = '',
		string $register = '',
		string $schema = '',
		bool $_rbac = true,
		bool $_multitenancy = true,
	) {
		unset($this->stored[$this->key(register: $register, schema: $schema, uuid: $uuid)]);
		return true;
	}//end deleteObject()

	/**
	 * Build the storage key for one object.
	 *
	 * @param string $register Register slug.
	 * @param string $schema Schema slug.
	 * @param string $uuid Object UUID.
	 *
	 * @return string The key.
	 */
	private function key(string $register, string $schema, string $uuid): string {
		return $register . '/' . $schema . '/' . $uuid;
	}//end key()
}//end class
