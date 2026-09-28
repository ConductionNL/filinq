<?php

/**
 * Template Image Resolver
 *
 * Resolves a Nextcloud file id into an embeddable image for the sandboxed
 * `nc_image()` template function. The file is read through the generating
 * user's own folder, so a template can never place an image its author
 * could not open themselves.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

use finfo;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\Lock\LockedException;

/**
 * Turns a file id into a data URI, as the signed-in user, raster only.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-006
 */
class TemplateImageResolver {

	/**
	 * App-config key for the largest image a template may embed.
	 *
	 * @var string
	 */
	public const CONFIG_MAX_BYTES = 'templates.max_image_bytes';

	/**
	 * Default size cap: 5 MB.
	 *
	 * @var int
	 */
	public const DEFAULT_MAX_BYTES = 5242880;

	/**
	 * Raster types a template may embed. SVG is left out on purpose: a
	 * user-supplied SVG can carry script and external references.
	 *
	 * @var string[]
	 */
	private const RASTER_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

	/**
	 * Constructor.
	 *
	 * @param IRootFolder  $rootFolder  Resolves the signed-in user's folder.
	 * @param IUserSession $userSession The generating user.
	 * @param IAppConfig   $appConfig   Reads the size cap.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IAppConfig $appConfig,
	) {
	}//end __construct()

	/**
	 * Resolve a file id to a data URI.
	 *
	 * 🔴 EVERY "CANNOT READ" ANSWERS THE SAME WORD. A file that does not exist,
	 * one outside the user's folder and one the user may not read all come back
	 * as "not found or no access", so the marker in a generated document never tells its
	 * reader whether somebody else's file exists.
	 *
	 * @param mixed $fileId The file id the template passed.
	 *
	 * @return array{src: string|null, reason: string|null, parameters: array} The data URI, or the
	 *                                                                        reason there is none (English
	 *                                                                        source text with `%s` slots).
	 *
	 * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-006
	 */
	public function resolve(mixed $fileId): array {
		$file = $this->findFile(fileId: $fileId);
		if ($file === null) {
			return $this->refuse(reason: 'not found or no access');
		}

		$maxBytes = $this->appConfig->getValueInt('filinq', self::CONFIG_MAX_BYTES, self::DEFAULT_MAX_BYTES);
		if ($file->getSize() > $maxBytes) {
			return $this->refuse(reason: 'larger than %s bytes', parameters: [$maxBytes]);
		}

		try {
			$bytes = $file->getContent();
		} catch (NotPermittedException|NotFoundException|LockedException) {
			return $this->refuse(reason: 'not found or no access');
		}

		$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
		if (is_string($mime) === false || in_array($mime, self::RASTER_MIMES, true) === false) {
			return $this->refuse(reason: 'not a raster image');
		}

		return ['src' => 'data:' . $mime . ';base64,' . base64_encode($bytes), 'reason' => null, 'parameters' => []];

	}//end resolve()

	/**
	 * Find the file in the signed-in user's own folder.
	 *
	 * @param mixed $fileId The file id the template passed.
	 *
	 * @return File|null The file, or null when this user cannot reach it.
	 */
	private function findFile(mixed $fileId): ?File {
		$isId = is_int($fileId) === true || (is_string($fileId) === true && ctype_digit($fileId) === true);
		if ($isId === false) {
			return null;
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}

		try {
			$node = $this->rootFolder->getUserFolder($user->getUID())->getFirstNodeById((int)$fileId);
		} catch (NotPermittedException|NotFoundException) {
			return null;
		}

		if ($node instanceof File === false || $node->isReadable() === false) {
			return null;
		}

		return $node;

	}//end findFile()

	/**
	 * Build a refusal.
	 *
	 * @param string $reason     Why no image is embedded, with `%s` slots.
	 * @param array  $parameters Values for the slots.
	 *
	 * @return array{src: null, reason: string, parameters: array} The refusal.
	 */
	private function refuse(string $reason, array $parameters = []): array {
		return ['src' => null, 'reason' => $reason, 'parameters' => $parameters];

	}//end refuse()
}//end class
