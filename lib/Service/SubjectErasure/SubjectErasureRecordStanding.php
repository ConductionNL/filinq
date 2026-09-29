<?php

/**
 * The obligations the OpenRegister record behind a file places on an erasure.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use Throwable;

/**
 * Reads, per file, the obligations that live on the OpenRegister record whose
 * folder holds the file (OpenRegister names an object's folder after its
 * uuid): an active legal hold on it, placed by anybody, and an appraisal to
 * keep it permanently (`blijvend_bewaren`), the retention obligation an
 * erasure may not override.
 *
 * A record that cannot be read refuses: both obligations are reported as
 * standing, never as clear.
 */
class SubjectErasureRecordStanding {

	/**
	 * Appraisals that keep a record permanently (TMLO `archiefnominatie`, MDTO `waardering`).
	 *
	 * @var string[]
	 */
	public const PERMANENT = ['blijvend_bewaren', 'bewaren', 'b'];

	/**
	 * A folder name OpenRegister gives an object folder.
	 *
	 * @var string
	 */
	private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

	/**
	 * Constructor.
	 *
	 * @param LegalHoldRecordFreeze $records Loads a record without the caller's rights.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LegalHoldRecordFreeze $records,
	) {

	}//end __construct()

	/**
	 * The record's hold and retention for one file.
	 *
	 * @param File|null $node The file, null when it no longer exists.
	 *
	 * @return array{legal_hold: string|false, retention: string|false}
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.1
	 */
	public function forFile(?File $node): array {
		$clear = ['legal_hold' => false, 'retention' => false];
		$uuid = $this->recordUuid(node: $node);
		if ($uuid === '') {
			return $clear;
		}

		try {
			$entity = $this->records->load(ref: $uuid);
		} catch (DoesNotExistException) {
			return $clear;
		} catch (Throwable) {
			$unread = 'the record ' . $uuid . ' that holds the file could not be read, so it counts as kept';
			return ['legal_hold' => $unread, 'retention' => $unread];
		}

		if ($entity === null) {
			return $clear;
		}

		$retention = (array) ($entity->getRetention() ?? []);
		$hold = ($retention['legalHold'] ?? []);
		if (is_array($hold) === true && ($hold['active'] ?? false) === true) {
			$clear['legal_hold'] = trim('record ' . $uuid . ' ' . (string) ($hold['reason'] ?? ''));
		}

		$appraisal = strtolower(trim((string) ($retention['archiefnominatie'] ?? ($retention['waardering'] ?? ''))));
		if (in_array($appraisal, self::PERMANENT, true) === true) {
			$clear['retention'] = 'record ' . $uuid . ' is appraised ' . $appraisal;
		}

		return $clear;

	}//end forFile()

	/**
	 * The uuid of the object folder the file sits in, '' when it sits elsewhere.
	 *
	 * @param File|null $node The file.
	 *
	 * @return string The uuid.
	 */
	private function recordUuid(?File $node): string {
		if ($node === null) {
			return '';
		}

		try {
			$name = (string) $node->getParent()->getName();
		} catch (Throwable) {
			return '';
		}

		if (preg_match(self::UUID, $name) !== 1) {
			return '';
		}

		return $name;

	}//end recordUuid()
}//end class
