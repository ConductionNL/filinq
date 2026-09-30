<?php

/**
 * Signing Envelope Repository
 *
 * Reads and writes `signingEnvelope` rows in the `filinq` register.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SigningEnvelope;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * Stores and finds signing envelopes.
 *
 * The schema grants read and update to nobody, so through OpenRegister only
 * the initiator (the owner) and admins reach an envelope. A member signer
 * reads it, and the roll-up is written back, through SigningEnvelopeService,
 * which decides who may: that is why the reads and the roll-up write here
 * pass `_rbac: false`. Nothing else calls this class.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SigningEnvelope
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
 */
class SigningEnvelopeRepository {

	/**
	 * Schema slug.
	 */
	public const SCHEMA = 'signingEnvelope';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Save an envelope under a given uuid.
	 *
	 * The uuid is chosen before the member requests are created, so they can
	 * carry it from their first save.
	 *
	 * @param array  $envelope The envelope fields
	 * @param string $uuid     The envelope's uuid
	 *
	 * @return array The stored envelope, with `uuid`
	 *
	 * @throws RuntimeException When OpenRegister refuses the write
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function save(array $envelope, string $uuid): array {
		unset($envelope['uuid'], $envelope['members']);

		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $envelope,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				uuid: $uuid,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the envelope: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '') {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * Find one envelope, whoever asks: the caller decides who may see it.
	 *
	 * @param string $uuid The envelope uuid
	 *
	 * @return array|null The envelope, or null when absent
	 *
	 * @throws RuntimeException When the read fails
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The envelope could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * List envelopes, newest first.
	 *
	 * @param string|null $initiator Only this initiator's envelopes, or null for all
	 *
	 * @return list<array>
	 *
	 * @throws RuntimeException When the read fails
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function list(?string $initiator): array {
		$filters = [];
		if ($initiator !== null) {
			$filters['initiatorUserId'] = $initiator;
		}

		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers a slug with zero rows and no error.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: $filters,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The envelopes could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$envelopes = [];
		foreach ((array) $results as $result) {
			$envelope = $this->normalise(row: $result);
			// The filter is the search's; the owner check is ours.
			if ($initiator === null || ($envelope['initiatorUserId'] ?? '') === $initiator) {
				$envelopes[] = $envelope;
			}
		}

		usort(
			$envelopes,
			static fn (array $a, array $b): int => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''))
		);

		return $envelopes;

	}//end list()

	/**
	 * Flatten an OpenRegister object into its fields plus `uuid`.
	 *
	 * @param mixed $row An ObjectEntity or array
	 *
	 * @return array
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
