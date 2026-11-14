<?php

/**
 * Acting Person Resolver
 *
 * Who is recorded as the person making a document final: the id and name the
 * caller passed, filled in from the session user where they are missing.
 * Built by {@see FinalDocumentService} over its own session.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IUser;
use OCP\IUserSession;
use RuntimeException;

/**
 * Resolves the acting person of a finalisation, preferring what the caller passed.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
final class ActingPersonResolver {

	/**
	 * Constructor.
	 *
	 * @param IUserSession $userSession The session that names the user when the caller does not.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IUserSession $userSession,
	) {

	}//end __construct()

	/**
	 * Resolve the acting person, preferring what the caller passed.
	 *
	 * @param string|null $actorId   The acting user id, or null for the session user.
	 * @param string|null $actorName The acting user's display name, or null to resolve it.
	 *
	 * @return array{id: string, name: string} The acting person.
	 *
	 * @throws RuntimeException When there is no acting person at all.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function resolve(?string $actorId, ?string $actorName): array {
		$user = $this->userSession->getUser();

		$id = $this->actorIdOf(actorId: $actorId, user: $user);
		if ($id === '') {
			throw new RuntimeException(
				message: 'Making a document final needs a named person, and there is no acting user.'
			);
		}

		$name = (string)$actorName;
		if ($name === '') {
			$name = $this->sessionNameFor(user: $user, id: $id);
		}

		return ['id' => $id, 'name' => $name];

	}//end resolve()

	/**
	 * The acting user id: the one given, or else the session user's.
	 *
	 * @param string|null $actorId The acting user id, or null for the session user.
	 * @param IUser|null  $user    The session user, if any.
	 *
	 * @return string The user id, or an empty string when there is none.
	 */
	private function actorIdOf(?string $actorId, ?IUser $user): string {
		$id = (string)$actorId;
		if ($id === '' && $user !== null) {
			return $user->getUID();
		}

		return $id;

	}//end actorIdOf()

	/**
	 * The display name of the session user when that user is the actor, otherwise the id.
	 *
	 * @param IUser|null $user The session user, if any.
	 * @param string     $id   The acting user id.
	 *
	 * @return string The name to record.
	 */
	private function sessionNameFor(?IUser $user, string $id): string {
		if ($user === null || $user->getUID() !== $id) {
			return $id;
		}

		$displayName = $user->getDisplayName();
		if ($displayName === '') {
			return $id;
		}

		return $displayName;

	}//end sessionNameFor()
}//end class
