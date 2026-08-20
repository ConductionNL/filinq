<?php

/**
 * Agent Artefact Marker
 *
 * Applies the ADR-088 "Agent authored" Nextcloud system tag to a file an agent
 * produced or changed, in the same code path that writes the file.
 *
 * Files is where a user actually looks, and a system tag is visible and
 * filterable there, so "did an agent write this?" is answerable without opening
 * an audit page. The mark is a HINT, not a guarantee -- a user can remove a
 * system tag. Hermiq's invocation record is the authoritative account; the tag
 * is what makes that record discoverable from the file. Nothing here claims
 * tamper-resistance.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-editing-tools/tasks.md#task-2-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use RuntimeException;
use Throwable;

/**
 * Marks agent-produced files with the fleet-wide ADR-088 system tag.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-marked-as-agent-authored-at-write-time
 */
class AgentArtefactMarker {

	/**
	 * The ADR-088 tag name.
	 *
	 * Deliberately NOT translated. The tag is one row in one database and every
	 * app in the fleet has to agree on it -- Hermiq marks calendar events and
	 * contacts with the same string. A per-language name would fragment the tag
	 * per user locale and make "show me everything an agent touched" return a
	 * different set for every user.
	 *
	 * @var string
	 */
	public const TAG_NAME = 'Agent authored';

	/**
	 * Nextcloud's object type for files in the system-tag mapper.
	 *
	 * @var string
	 */
	private const OBJECT_TYPE = 'files';

	/**
	 * Constructor.
	 *
	 * @param ISystemTagManager $tagManager The system tag manager.
	 * @param ISystemTagObjectMapper $tagMapper The system tag object mapper.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ISystemTagManager $tagManager,
		private readonly ISystemTagObjectMapper $tagMapper,
	) {

	}//end __construct()

	/**
	 * Mark a file as agent-authored.
	 *
	 * Throws rather than returning false. A caller that gets a boolean back
	 * tends to log it and carry on, and "the file was written but not marked"
	 * is the single outcome nothing downstream will ever re-examine.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return bool True when this call added the tag, false when it was already present.
	 *
	 * @throws RuntimeException When the tag cannot be resolved or assigned.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-marked-as-agent-authored-at-write-time
	 */
	public function mark(int $fileId): bool {
		$tag = $this->resolveTag();

		try {
			$alreadyTagged = $this->tagMapper->haveTag(
				[(string)$fileId],
				self::OBJECT_TYPE,
				$tag,
				true
			);

			if ($alreadyTagged === true) {
				return false;
			}

			$this->tagMapper->assignTags((string)$fileId, self::OBJECT_TYPE, [$tag]);

			return true;
		} catch (Throwable $e) {
			throw new RuntimeException(
				sprintf('Could not mark file %d as agent-authored: %s', $fileId, $e->getMessage()),
				0,
				$e
			);
		}

	}//end mark()

	/**
	 * Remove the mark this service added.
	 *
	 * Used only to undo a mark applied moments earlier by a write that then
	 * failed, so a file the agent did not in the end change is not left
	 * claiming it did.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-marked-as-agent-authored-at-write-time
	 */
	public function unmark(int $fileId): void {
		try {
			$this->tagMapper->unassignTags((string)$fileId, self::OBJECT_TYPE, [$this->resolveTag()]);
		} catch (Throwable) {
			// Best effort: the write already failed and the caller is reporting
			// that failure. A stuck tag is a smaller problem than masking it.
		}

	}//end unmark()

	/**
	 * Resolve the tag id, creating the tag the first time it is needed.
	 *
	 * User-visible and user-assignable: the point is that a person can see it in
	 * Files and filter on it.
	 *
	 * @return string The system tag id.
	 *
	 * @throws RuntimeException When the tag can be neither found nor created.
	 */
	private function resolveTag(): string {
		try {
			return $this->tagManager->getTag(self::TAG_NAME, true, true)->getId();
		} catch (TagNotFoundException) {
			// First use on this instance.
		} catch (Throwable $e) {
			throw new RuntimeException('Could not read the agent-authored tag: ' . $e->getMessage(), 0, $e);
		}

		try {
			return $this->tagManager->createTag(self::TAG_NAME, true, true)->getId();
		} catch (Throwable $e) {
			// Another request may have created it in between; a lost race is not
			// a failure, so re-read before giving up.
			try {
				return $this->tagManager->getTag(self::TAG_NAME, true, true)->getId();
			} catch (Throwable) {
				throw new RuntimeException('Could not create the agent-authored tag: ' . $e->getMessage(), 0, $e);
			}
		}

	}//end resolveTag()
}//end class
