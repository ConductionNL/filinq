<?php

/**
 * Contract of SealedFileWriter
 *
 * The public surface of the SealedFileWriter service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCA\Filinq\Kernel\SealedArtifact;
use OCP\Files\Folder;
use Throwable;

/**
 * Contract of SealedFileWriter.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SealedFileWriterInterface {
	/**
	 * Write the sealed PDF into a folder and record its seal.
	 *
	 * @param Folder $folder Where the sealed file goes.
	 * @param string $name The wanted file name (made unique in the folder).
	 * @param SealedArtifact $artifact The prepared artifact.
	 *
	 * @return array{waarmerk: array<string, mixed>, fileId: int, fileName: string}
	 *
	 * @throws Throwable When the record cannot be stored (the file is removed first).
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function write(Folder $folder, string $name, SealedArtifact $artifact): array;
}
