<?php

/**
 * Writes a download of a document filinq owns into that document's trail.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Audit
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/filinq-notifications/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Audit;

use DateTimeImmutable;
use OCA\Filinq\Service\DocumentDownloadRecorder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The one call a download route makes once it has the bytes in hand.
 *
 * The record itself (file, version, moment, route, identity) is built by
 * {@see DocumentDownloadRecorder}; this writes it as a `downloaded` entry of the
 * document's trail through {@see DocumentActionAudit}. A failed write never
 * fails the download: the document has already left, so the failure is logged
 * and the answer is false.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/filinq-notifications/spec.md
 */
class DocumentDownloadLog {

	/**
	 * Taken through filinq's own web interface.
	 */
	public const ROUTE_UI = 'ui';

	/**
	 * Taken through Nextcloud's own direct download (the Files app, a direct link).
	 */
	public const ROUTE_FILES = 'files';

	/**
	 * Constructor.
	 *
	 * @param DocumentDownloadRecorder $recorder Builds the record.
	 * @param DocumentActionAudit      $audit    Writes it into the document's trail.
	 * @param IUserSession             $session  Names the caller; none on a public link.
	 * @param LoggerInterface          $logger   Logs a record that could not be written.
	 *
	 * @psalm-suppress PossiblyUnusedMethod Instantiated via Nextcloud dependency injection.
	 */
	public function __construct(
		private readonly DocumentDownloadRecorder $recorder,
		private readonly DocumentActionAudit $audit,
		private readonly IUserSession $session,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Record one download of a file.
	 *
	 * @param int         $fileId  The Nextcloud file id taken.
	 * @param string      $version The version taken, or '' for the current one.
	 * @param string      $route   The route it was taken through, e.g. {@see self::ROUTE_UI}.
	 * @param string|null $linkId  The link used, when the caller has no session.
	 *
	 * @return bool True when the download is in the trail.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/filinq-notifications/spec.md#requirement-a-download-is-recorded-with-its-route-and-its-identity-req-dio-06
	 */
	public function downloaded(int $fileId, string $version, string $route, ?string $linkId = null): bool {
		if ($fileId <= 0) {
			return false;
		}

		try {
			$record = $this->recorder->record(
				fileId: $fileId,
				version: $version,
				route: $route,
				userId: $this->session->getUser()?->getUID(),
				linkId: $linkId,
				moment: new DateTimeImmutable()
			);

			return $this->audit->record(fileId: $fileId, action: 'downloaded', context: $record);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[DocumentDownloadLog] a download happened but could not be recorded',
				context: ['fileId' => $fileId, 'route' => $route, 'error' => $e->getMessage()]
			);
			return false;
		}

	}//end downloaded()
}//end class
