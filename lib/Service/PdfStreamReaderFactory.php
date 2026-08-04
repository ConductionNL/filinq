<?php

/**
 * FPDI Stream-Reader Factory
 *
 * Instance-level seam over FPDI's stream-reader construction. FPDI exposes
 * in-memory parsing only through the static `StreamReader::createByString()`
 * factory; this class performs the same three steps against the reader's
 * public constructor (temp stream, write, rewind) so callers can depend on
 * an injectable collaborator instead of a static call.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use InvalidArgumentException;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Builds FPDI stream readers over in-memory PDF byte strings.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class PdfStreamReaderFactory
{

    /**
     * Bytes FPDI keeps in memory before spilling the temp stream to disk.
     * Matches FPDI's own default for createByString().
     */
    private const DEFAULT_MAX_MEMORY = 2097152;

    /**
     * Build a stream reader over a raw PDF byte string.
     *
     * @param string $content Raw PDF bytes.
     *
     * @return StreamReader Reader positioned at the start of $content.
     *
     * @throws InvalidArgumentException When the temp stream cannot be opened.
     *
     * @spec openspec/specs/pdfa3-conversion/spec.md
     */
    public function fromString(string $content): StreamReader
    {
        $handle = fopen('php://temp/maxmemory:'.self::DEFAULT_MAX_MEMORY, 'r+b');
        if ($handle === false) {
            throw new InvalidArgumentException('No stream given.');
        }

        fwrite($handle, $content);
        rewind($handle);

        return new StreamReader($handle, true);

    }//end fromString()
}//end class
