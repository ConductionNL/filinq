<?php

/**
 * Conversion Backend Interface
 *
 * Contract that every PDF-conversion backend in the cascade implements.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCP\Files\File;

/**
 * Each PDF-conversion backend implements canHandle, isAvailable, convert,
 * and name. The cascade (PdfConversionService) walks an ordered list of
 * backends, calling isAvailable() first, then canHandle(), then convert().
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 */
interface ConversionBackendInterface
{
    /**
     * Short identifier used in diagnostic surfaces.
     *
     * @return string Backend identifier (lowercase, snake_case).
     *
     * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
     */
    public function name(): string;

    /**
     * Whether this backend is usable in the current install.
     *
     * @return bool True when the backend can be invoked.
     *
     * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
     */
    public function isAvailable(): bool;

    /**
     * Whether this backend can process the given MIME type / extension.
     *
     * @param string $mimeType  MIME type.
     * @param string $extension Lowercased file extension WITHOUT the dot.
     *
     * @return bool True when this backend claims the input format.
     *
     * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
     */
    public function canHandle(string $mimeType, string $extension): bool;

    /**
     * Convert the source file to a PDF and return the result file node.
     *
     * @param File $source Source file node.
     *
     * @return File The newly written PDF file node.
     *
     * @throws ConversionFailedException When this backend cannot convert this input.
     *
     * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
     */
    public function convert(File $source): File;
}//end interface
