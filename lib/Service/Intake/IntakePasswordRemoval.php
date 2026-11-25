<?php

/**
 * Intake password removal
 *
 * Removes the password of an intake document's locked PDF into a copy and
 * points the intake document at the copy, keeping the file that arrived in
 * `lockedOriginal`.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Intake;

use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Kernel\Contracts\IntakePasswordRemovalInterface;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\PdfPasswordRemovalService;
use OCA\Filinq\Service\SessionUserFile;

/**
 * Unlocks the file of one waiting intake document.
 */
class IntakePasswordRemoval implements IntakePasswordRemovalInterface {

	/**
	 * Constructor.
	 *
	 * @param IntakeRepository          $repository The intake documents.
	 * @param IntakeAuthorizationGate   $gate       Whether the caller may change intake documents.
	 * @param SessionUserFile           $files      The caller's own files.
	 * @param PdfPasswordRemovalService $removal    Unlocks a PDF into a copy.
	 */
	public function __construct(
		private readonly IntakeRepository $repository,
		private readonly IntakeAuthorizationGate $gate,
		private readonly SessionUserFile $files,
		private readonly PdfPasswordRemovalService $removal,
	) {
	}//end __construct()

	/**
	 * Remove the password of a waiting intake document's file.
	 *
	 * The caller's right to change intake documents is checked before the
	 * file is read, so a refused caller never makes a copy. On success the
	 * document's `file` names the copy and `lockedOriginal` keeps the file
	 * that arrived; unlocking a copy again keeps the first arrival.
	 *
	 * @param string $uuid     The intake document.
	 * @param string $password The PDF's password; never stored or logged.
	 *
	 * @return array<string, mixed> The removal's answer (`outcome`, and on success the copy and its findings), with `intakeDocument` on success.
	 *
	 * @throws IntakeRefusedException 404 unknown document or unreadable file, 409 not waiting, 403 no right to change it.
	 *
	 * @spec openspec/specs/pdf-password-removal/spec.md#requirement-an-intake-document-keeps-what-arrived-req-ppr-004
	 */
	public function unlock(string $uuid, string $password): array {
		$document = $this->repository->findByUuid(uuid: $uuid);
		if ($document === null) {
			throw new IntakeRefusedException(message: 'This document is not in the intake inbox.', status: 404);
		}

		$status = (string)($document['status'] ?? '');
		if (in_array($status, [IntakeRepository::STATUS_RECEIVED, IntakeRepository::STATUS_DETACHED], true) === false) {
			throw new IntakeRefusedException(message: 'This document has already left the inbox: it is ' . $status . '.', status: 409);
		}

		if ($this->gate->mayWrite(register: IntakeRepository::REGISTER, schema: IntakeRepository::SCHEMA) === false) {
			throw new IntakeRefusedException(message: 'You may not change intake documents.', status: 403);
		}

		$arrived = (int)($document['file'] ?? 0);
		$file    = $this->files->find(fileId: $arrived);
		if ($arrived <= 0 || $file === null) {
			throw new IntakeRefusedException(message: 'File not found', status: 404);
		}

		$result = $this->removal->unlock(file: $file, password: $password);
		if (($result['outcome'] ?? '') !== PdfPasswordRemovalService::UNLOCKED) {
			return $result;
		}

		if ((int)($document['lockedOriginal'] ?? 0) <= 0) {
			$document['lockedOriginal'] = $arrived;
		}

		$document['file'] = (int)$result['fileId'];

		return $result + ['intakeDocument' => $this->repository->save(document: $document, uuid: $uuid)];
	}//end unlock()
}//end class
