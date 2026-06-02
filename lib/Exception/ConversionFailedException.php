<?php

/**
 * Conversion Failed Exception
 *
 * Raised when PdfConversionService has exhausted every backend in the
 * cascade without successfully converting the source file. Carries the
 * per-backend attempt log so callers can surface diagnostic information
 * in HTTP 422 responses without inspecting server logs.
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;

/**
 * Exception thrown when PDF conversion fails across all available backends.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 */
class ConversionFailedException extends RuntimeException
{

    /**
     * Per-backend attempt records.
     *
     * Each entry has shape: {backend: string, available: bool, supports: bool, reason: string}
     *
     * @var array<int, array<string, mixed>>
     */
    private array $attempts;

    /**
     * Constructor
     *
     * @param string                           $message  Error description
     * @param array<int, array<string, mixed>> $attempts Per-backend attempt records
     * @param int                              $code     Exception code (default 422)
     * @param \Throwable|null                  $previous Previous exception
     */
    public function __construct(
        string $message='PDF conversion failed: no backend could handle the file.',
        array $attempts=[],
        int $code=422,
        ?\Throwable $previous=null
    ) {
        parent::__construct(message: $message, code: $code, previous: $previous);
        $this->attempts = $attempts;

    }//end __construct()

    /**
     * Return per-backend attempt records for diagnostic 422 bodies.
     *
     * Each record shape: {backend: string, available: bool, supports: bool, reason: string}
     *
     * @return array<int, array<string, mixed>>
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function getAttempts(): array
    {
        return $this->attempts;

    }//end getAttempts()
}//end class
