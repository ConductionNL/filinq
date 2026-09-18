<?php

/**
 * Intake Authorization Gate
 *
 * Answers one question: may the person at the keyboard write this schema? The
 * intake inbox asks it twice, about two different schemas, because assigning a
 * document touches both the intake register and the record the document is
 * assigned to.
 *
 * The answer comes from the schema's own `authorization.update` declaration in
 * OpenRegister, not from a list kept here, so a schema whose rights change in
 * the register changes what the inbox allows with no code change. The gate
 * fails CLOSED: an unreachable register answers "no", because that is exactly
 * when an unnoticed assignment is most likely.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Decides whether the current user may write a register schema.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeAuthorizationGate {

	/**
	 * The audiences that mean "anybody who is logged in".
	 *
	 * @var array<int, string>
	 */
	private const OPEN_AUDIENCES = ['authenticated', 'public', 'user', 'users'];

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param IUserSession $userSession The current session.
	 * @param IGroupManager $groupManager Group membership.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * May the current user write objects of this schema?
	 *
	 * @param string $register The register slug.
	 * @param string $schema The schema slug.
	 *
	 * @return bool True when the write is allowed.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function mayWrite(string $register, string $schema): bool {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return false;
		}

		$userId = $user->getUID();

		try {
			$objectService = $this->objectResolver->resolve();
			$objectService->setRegister($register);
			$objectService->setSchema($schema);
			$entity = $objectService->getCurrentSchemaEntity();
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeAuthorizationGate] could not read the schema rights, refusing the write',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'register' => $register,
					'schema' => $schema,
					'error' => $e->getMessage(),
				]
			);

			return false;
		}//end try

		if ($entity === null) {
			return false;
		}

		$declared = $entity->jsonSerialize();
		$groups = [];
		if (isset($declared['authorization']['update']) === true && is_array($declared['authorization']['update']) === true) {
			$groups = $declared['authorization']['update'];
		}

		return $this->allows(groups: $groups, userId: $userId);

	}//end mayWrite()

	/**
	 * Read one `authorization.update` declaration against one user.
	 *
	 * An EMPTY declaration is OpenRegister's "no restriction", not "nobody":
	 * most schemas in the fleet carry none, and reading it as a refusal would
	 * make the inbox refuse every ordinary target while looking like a
	 * permission bug in the consuming app rather than in this line.
	 *
	 * @param array<int, string> $groups The declared groups.
	 * @param string $userId The user id.
	 *
	 * @return bool True when the declaration lets this user write.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function allows(array $groups, string $userId): bool {
		if ($groups === []) {
			return true;
		}

		foreach ($groups as $group) {
			$name = strtolower(trim((string)$group));
			if ($name === '') {
				continue;
			}

			if (in_array(needle: $name, haystack: self::OPEN_AUDIENCES, strict: true) === true) {
				return true;
			}

			if ($this->groupManager->isInGroup($userId, (string)$group) === true) {
				return true;
			}
		}

		return $this->groupManager->isAdmin($userId);

	}//end allows()
}//end class
