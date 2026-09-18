<?php

/**
 * Records that a document left the building, and who took it.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;

/**
 * One download, recorded, with the notification enqueued rather than sent.
 *
 * 🔴 A PUBLIC LINK NAMES THE LINK, NOT A PERSON. Whoever opened it is
 * unauthenticated, so the only identity available is a guess from an IP or a
 * session, and writing a guessed name into an audit record is worse than writing
 * none: a record that says "J. de Vries downloaded this" is read as fact by
 * whoever reads it next. The link is the thing that was actually used, and it is
 * the thing that can be revoked.
 *
 * 🔴 THE NOTIFICATION IS ENQUEUED, NEVER DELIVERED HERE. This runs on the
 * download path, where the person is waiting for bytes. A mail server that is
 * slow makes the download slow; a mail server that is down makes the download
 * fail, and the document then did not leave the building because a notification
 * could not be sent. Enqueuing separates "it happened" from "somebody was told".
 *
 * 🔑 THE COLLAPSE LIVES HERE BECAUSE THE PLATFORM DIALECT CANNOT DO IT.
 * `x-openregister-notifications` supports `trigger.dedupeFields`, and it is
 * honoured ONLY by `ScheduledNotificationJob` and `TaskScheduledNotificationJob`
 * — both scheduled paths. There is no collapsing for an event-driven
 * notification and no time-window concept in the dialect at all. Declaring a
 * window there would be a key nobody reads.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */
class DocumentDownloadRecorder {

	/**
	 * How long repeats of one download collapse into one notification.
	 *
	 * Somebody opening a document three times while reading it is one event to
	 * anybody being told about it, and three notifications is how a useful
	 * signal becomes noise that gets muted.
	 */
	public const COLLAPSE_SECONDS = 900;

	/**
	 * What is written when the downloader cannot be named.
	 */
	public const ANONYMOUS = 'public-link';

	/**
	 * One download, as a record.
	 *
	 * @param int                 $fileId   The file.
	 * @param string              $version  The version taken, or '' for the current one.
	 * @param string              $route    The route it was taken through.
	 * @param string|null         $userId   The caller, or null on a public link.
	 * @param string|null         $linkId   The link used, when there is one.
	 * @param DateTimeImmutable   $moment   When.
	 *
	 * @return array<string, mixed> The record.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function record(
		int $fileId,
		string $version,
		string $route,
		?string $userId,
		?string $linkId,
		DateTimeImmutable $moment,
	): array {
		$identity = trim((string)$userId);

		if ($identity === '') {
			// Named by the link, or by nothing at all. Never by a guess.
			$link     = trim((string)$linkId);
			$identity = self::ANONYMOUS;
			if ($link !== '') {
				$identity = 'link:' . $link;
			}
		}

		return [
			'fileId' => $fileId,
			'version' => $version,
			'route' => $route,
			'identity' => $identity,
			'downloadedAt' => $moment->format('c'),
		];
	}//end record()

	/**
	 * Whether this download should raise a notification, given the last one.
	 *
	 * 🔑 THE COLLAPSE IS PER FILE AND PER IDENTITY, NOT PER FILE ALONE. Two
	 * different people downloading the same document within the window are two
	 * facts, and collapsing them would hide the second person entirely — which
	 * on a confidential document is the one you most want to know about.
	 *
	 * @param array<string, mixed>      $record The download just recorded.
	 * @param array<string, mixed>|null $last   The last notified download of that file, or null.
	 *
	 * @return bool Whether to enqueue a notification.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
	 */
	public function shouldNotify(array $record, ?array $last): bool {
		if ($last === null) {
			return true;
		}

		if (($last['fileId'] ?? null) !== ($record['fileId'] ?? null)) {
			return true;
		}

		if (($last['identity'] ?? null) !== ($record['identity'] ?? null)) {
			return true;
		}

		$then = strtotime((string)($last['downloadedAt'] ?? ''));
		$now = strtotime((string)($record['downloadedAt'] ?? ''));

		if ($then === false || $now === false) {
			// 🔴 AN UNREADABLE TIMESTAMP NOTIFIES. The alternative is silently
			// swallowing a download because a date could not be parsed, and a
			// missing notification about a document leaving is the failure this
			// whole requirement exists to prevent. Noise is the cheaper error.
			return true;
		}

		return (($now - $then) >= self::COLLAPSE_SECONDS);
	}//end shouldNotify()
}//end class
