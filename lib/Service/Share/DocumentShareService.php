<?php

/**
 * Share a document outside the organisation through a link that runs out.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Share
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/share-document-links/specs/document-share-links/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Share;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Makes a link for one document, records what left, and takes a link back.
 *
 * The link is a Nextcloud public share (D1); the record holds the share id,
 * never its token or URL (D6). Every link runs out (D5), and a labelled
 * document is shared only after the sharer confirms (D8).
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
 */
class DocumentShareService {

	/**
	 * Days a link stays open when the sharer picks nothing else.
	 *
	 * @var int
	 */
	public const DEFAULT_DAYS = 14;

	/**
	 * Constructor.
	 *
	 * @param LinkShareGateway            $links  Nextcloud's link shares.
	 * @param DocumentShareStore          $store  The share records.
	 * @param ReadableFileResolver        $files  The file behind an id, as the user may read it.
	 * @param ConfidentialityLabelService $labels A file's confidentiality label.
	 * @param IUserSession                $session The user who shares.
	 * @param IGroupManager               $groups Whether a user is an admin.
	 * @param ITimeFactory                $clock  Today.
	 * @param LoggerInterface             $logger Logs a label that could not be read.
	 */
	public function __construct(
		private readonly LinkShareGateway $links,
		private readonly DocumentShareStore $store,
		private readonly ReadableFileResolver $files,
		private readonly ConfidentialityLabelService $labels,
		private readonly IUserSession $session,
		private readonly IGroupManager $groups,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Share one document as itself through a read-only link.
	 *
	 * @param int         $fileId         The document's file id.
	 * @param string      $expiresOn      The last day of the link (YYYY-MM-DD); empty means 14 days ahead.
	 * @param string|null $password       The link password, or null for none.
	 * @param string      $note           Who the link is for.
	 * @param bool        $labelConfirmed Whether the sharer confirmed sharing a labelled document.
	 *
	 * @return array{share: array<string, mixed>, url: string, expiresAt: string, expiryShortened: bool} The record, the link
	 *                                                                                                    and the expiry used.
	 *
	 * @throws InvalidArgumentException When the request breaks a rule (400), or a label needs a confirmation (409).
	 * @throws RuntimeException         When there is no user (401), no readable file (404), Nextcloud refuses (403),
	 *                                  or the record cannot be written (500).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	public function shareDocument(int $fileId, string $expiresOn, ?string $password, string $note, bool $labelConfirmed): array {
		$uid = $this->uid();
		$policy = $this->links->linkPolicy();
		[$expires, $shortened] = $this->expiry(expiresOn: $expiresOn, maxDays: $policy['maxDays']);
		if ($policy['passwordRequired'] === true && trim((string) $password) === '') {
			throw new InvalidArgumentException('Nextcloud requires a password on every link', 400);
		}

		$file = $this->files->node(fileId: $fileId);
		if (($file instanceof File) === false) {
			throw new RuntimeException('The document cannot be found', 404);
		}

		$label = $this->labelOf(fileId: $fileId);
		if ($label !== '' && $labelConfirmed === false) {
			throw new InvalidArgumentException('This document is labelled "' . $label . '". Confirm before sharing it by link.', 409);
		}

		$link = $this->links->createLink(fileId: $fileId, uid: $uid, expires: $expires, password: $password, note: trim($note));

		$record = [
			'kind' => 'document',
			'files' => [['fileId' => $fileId, 'name' => $file->getName(), 'version' => (string) $file->getEtag(), 'label' => $label]],
			'shareId' => $link['id'],
			'sharedBy' => $uid,
			'sharedAt' => $this->now()->format(DateTimeInterface::ATOM),
			'expiresAt' => $expires->format('Y-m-d'),
			'note' => trim($note),
			'labelConfirmed' => $label !== '',
			'status' => 'active',
		];

		try {
			$record = $this->store->save(record: $record);
		} catch (Throwable $e) {
			// A link nobody can see in the history must not stay open.
			$this->links->delete(shareId: $link['id']);
			throw new RuntimeException('The share could not be recorded, so the link was taken back again', 500, $e);
		}

		return ['share' => $record, 'url' => $link['url'], 'expiresAt' => $expires->format('Y-m-d'), 'expiryShortened' => $shortened];

	}//end shareDocument()

	/**
	 * Take a link back: delete the share in Nextcloud and mark the record revoked.
	 *
	 * Only the person who made the link, or an admin, may take it back.
	 *
	 * @param string $recordUuid The share record.
	 *
	 * @return array<string, mixed> The revoked record.
	 *
	 * @throws RuntimeException When there is no user (401), the record is unknown (404), the user may not (403), or
	 *                          the link is no longer active (409).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	public function revoke(string $recordUuid): array {
		$uid = $this->uid();
		$record = $this->store->find(uuid: $recordUuid);
		if ($record === null) {
			throw new RuntimeException('The share cannot be found', 404);
		}

		if ((string) ($record['sharedBy'] ?? '') !== $uid && $this->groups->isAdmin($uid) === false) {
			throw new RuntimeException('Only the person who made the link, or an admin, can take it back', 403);
		}

		if (($record['status'] ?? '') !== 'active') {
			throw new RuntimeException('This link is no longer active', 409);
		}

		$this->links->delete(shareId: (string) ($record['shareId'] ?? ''));

		$record['status'] = 'revoked';
		$record['revokedBy'] = $uid;
		$record['revokedAt'] = $this->now()->format(DateTimeInterface::ATOM);

		return $this->store->save(record: $record, uuid: $recordUuid);

	}//end revoke()

	/**
	 * The expiry to use and whether Nextcloud's maximum shortened it.
	 *
	 * @param string   $expiresOn The requested last day, or empty for the default.
	 * @param int|null $maxDays   Nextcloud's enforced maximum, or null.
	 *
	 * @return array{0: DateTimeImmutable, 1: bool} The expiry and whether it was shortened.
	 *
	 * @throws InvalidArgumentException When the date is not a date, or not after today.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function expiry(string $expiresOn, ?int $maxDays): array {
		$today = $this->now()->setTime(0, 0);
		$expiresOn = trim($expiresOn);
		$expires = $today->modify('+' . self::DEFAULT_DAYS . ' days');
		if ($expiresOn !== '') {
			$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresOn, $today->getTimezone());
			if ($parsed === false || $parsed->format('Y-m-d') !== $expiresOn) {
				throw new InvalidArgumentException('A link needs an expiry date (YYYY-MM-DD)', 400);
			}

			$expires = $parsed;
		}

		if ($expires <= $today) {
			throw new InvalidArgumentException('The expiry date must lie after today', 400);
		}

		if ($maxDays !== null && $maxDays > 0) {
			$latest = $today->modify('+' . $maxDays . ' days');
			if ($expires > $latest) {
				return [$latest, true];
			}
		}

		return [$expires, false];

	}//end expiry()

	/**
	 * The file's confidentiality label, or an empty string when it has none.
	 *
	 * @param int $fileId The file.
	 *
	 * @return string The label.
	 *
	 * @throws RuntimeException When the label cannot be read (503).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function labelOf(int $fileId): string {
		try {
			return trim((string) $this->labels->getLabelForFile($fileId)?->getLabel());
		} catch (Throwable $e) {
			// A label that cannot be read might be one that needs a confirmation: refuse rather than guess.
			$this->logger->warning('The confidentiality label could not be read', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			throw new RuntimeException('The confidentiality label of this document could not be read; try again later', 503, $e);
		}

	}//end labelOf()

	/**
	 * The session user.
	 *
	 * @return string The user id.
	 *
	 * @throws RuntimeException Without a user (401).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function uid(): string {
		$user = $this->session->getUser();
		if ($user === null) {
			throw new RuntimeException('Sharing needs a signed-in user', 401);
		}

		return $user->getUID();

	}//end uid()

	/**
	 * Now, in UTC.
	 *
	 * @return DateTimeImmutable Now.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function now(): DateTimeImmutable {
		return DateTimeImmutable::createFromInterface($this->clock->getDateTime())->setTimezone(new DateTimeZone('UTC'));

	}//end now()
}//end class
