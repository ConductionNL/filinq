<?php

/**
 * The readpst command line
 *
 * Converts an Outlook PST into one `.eml` per message with libpst's
 * `readpst`, the only maintained PST reader; PHP has none (design D1).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

/**
 * Probes and runs `readpst` on the server. No message leaves the server.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-the-container-format-is-detected-and-split-on-the-server-req-mai-002
 */
class ReadpstCommand {

	/**
	 * Whether `readpst` is installed and runs.
	 *
	 * @return bool True when the binary answers its version flag.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function isAvailable(): bool {
		if (function_exists('exec') === false) {
			return false;
		}

		$output = [];
		$returnCode = 1;
		exec('readpst -V 2>&1', $output, $returnCode);

		return $returnCode === 0;

	}//end isAvailable()

	/**
	 * Convert a PST into `.eml` files below a directory, one folder per PST folder.
	 *
	 * @param string $pstPath   The local PST file.
	 * @param string $outputDir An existing, empty directory.
	 *
	 * @return bool True when readpst finished without an error.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function convert(string $pstPath, string $outputDir): bool {
		$output = [];
		$returnCode = 1;
		exec(
			sprintf('readpst -e -q -o %s %s 2>&1', escapeshellarg($outputDir), escapeshellarg($pstPath)),
			$output,
			$returnCode
		);

		return $returnCode === 0;

	}//end convert()
}//end class
