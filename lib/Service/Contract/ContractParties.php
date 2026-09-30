<?php

/**
 * Contract parties
 *
 * A party linked to a Nextcloud contact takes its name and address from that
 * contact: the contact is the source of truth. A party without a contact, or
 * whose contact is gone, keeps the name stored on the contract.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use OCP\Contacts\IManager;

/**
 * Resolves contract parties against the address books the caller can read.
 */
class ContractParties {

	/**
	 * The prefix of a contact reference.
	 */
	private const CONTACT_PREFIX = 'urn:nc:contact:';

	/**
	 * Constructor.
	 *
	 * @param IManager $contacts The contacts manager, searching as the caller.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IManager $contacts,
	) {

	}//end __construct()

	/**
	 * The contract's parties as the detail shows them.
	 *
	 * @param array<string, mixed> $contract The contract.
	 *
	 * @return list<array{role: string, displayName: string, contactRef: string, linked: bool, email: string}> The parties.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
	 */
	public function resolve(array $contract): array {
		$resolved = [];
		foreach ((array) ($contract['parties'] ?? []) as $party) {
			if (is_array($party) === false) {
				continue;
			}

			$contactRef = (string) ($party['contactRef'] ?? '');
			$row = [
				'role'        => (string) ($party['role'] ?? ''),
				'displayName' => (string) ($party['displayName'] ?? ''),
				'contactRef'  => $contactRef,
				'linked'      => false,
				'email'       => '',
			];
			$contact = $this->contact(contactRef: $contactRef);
			if ($contact !== null) {
				$row['displayName'] = (string) ($contact['FN'] ?? $row['displayName']);
				$row['linked'] = true;
				$row['email'] = $this->firstEmail(email: $contact['EMAIL'] ?? '');
			}

			$resolved[] = $row;
		}//end foreach

		return $resolved;

	}//end resolve()

	/**
	 * The contact a reference points at, or null.
	 *
	 * @param string $contactRef The reference.
	 *
	 * @return array<string, mixed>|null The contact.
	 */
	private function contact(string $contactRef): ?array {
		if (str_starts_with($contactRef, self::CONTACT_PREFIX) === false || $this->contacts->isEnabled() === false) {
			return null;
		}

		$uid = substr($contactRef, strlen(self::CONTACT_PREFIX));
		if ($uid === '') {
			return null;
		}

		foreach ($this->contacts->search($uid, ['UID'], ['strict_search' => true, 'limit' => 5]) as $found) {
			if (is_array($found) === true && (string) ($found['UID'] ?? '') === $uid) {
				return $found;
			}
		}

		return null;

	}//end contact()

	/**
	 * The first address of a contact's EMAIL, which is a string or a list.
	 *
	 * @param mixed $email The EMAIL property.
	 *
	 * @return string The address, or ''.
	 */
	private function firstEmail(mixed $email): string {
		if (is_array($email) === true) {
			$email = reset($email);
		}

		if (is_array($email) === true) {
			$email = ($email['value'] ?? '');
		}

		return (string) $email;

	}//end firstEmail()
}//end class
