<?php

/**
 * A stand-in for exec() inside OCA\Filinq\Service, for tests that pin command lines.
 *
 * Only ever required from a test that runs in its own process: once defined,
 * every exec() call in this namespace reaches it for the rest of the process.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Record the command and answer the canned output and return code.
 *
 * @param string $command The command line.
 * @param mixed $output Receives the canned output lines.
 * @param mixed $returnCode Receives the canned return code.
 *
 * @return string The last output line, as exec() returns it.
 */
function exec(string $command, &$output = null, &$returnCode = null): string {
	$GLOBALS['tesseractExecStub']['commands'][] = $command;
	$output = ($GLOBALS['tesseractExecStub']['output'] ?? []);
	$returnCode = ($GLOBALS['tesseractExecStub']['returnCode'] ?? 0);
	return (string)(end($output) ?: '');
}
