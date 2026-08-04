<?php

/**
 * PhpWord I/O Seam
 *
 * Instance-level wrapper around the reader/writer construction that
 * `PhpOffice\PhpWord\IOFactory` otherwise exposes only through static
 * methods. Concrete reader and writer classes are instantiated directly —
 * exactly what IOFactory does internally — so the conversion backend can
 * depend on an injectable collaborator instead of a static call.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use InvalidArgumentException;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Reader\HTML as HtmlReader;
use PhpOffice\PhpWord\Reader\MsDoc;
use PhpOffice\PhpWord\Reader\ODText;
use PhpOffice\PhpWord\Reader\ReaderInterface;
use PhpOffice\PhpWord\Reader\RTF;
use PhpOffice\PhpWord\Reader\Word2007;
use PhpOffice\PhpWord\Writer\HTML as HtmlWriter;

/**
 * Injectable seam over PhpWord's reader/writer construction.
 *
 * The reader names accepted here are the same short names PhpWord's own
 * IOFactory uses (`Word2007`, `MsDoc`, `ODText`, `RTF`, `HTML`), so the
 * backend's extension→reader map and its diagnostic messages are unchanged.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
class PhpWordIo
{
    /**
     * Read a document from disk with the named PhpWord reader.
     *
     * @param string $path       Absolute path to the source document.
     * @param string $readerName PhpWord reader short name (e.g. `Word2007`).
     *
     * @return PhpWord The parsed document model.
     *
     * @throws InvalidArgumentException When $readerName is not a known reader.
     *
     * @spec openspec/specs/pdf-conversion/spec.md
     */
    public function load(string $path, string $readerName): PhpWord
    {
        return $this->createReader(readerName: $readerName)->load($path);

    }//end load()

    /**
     * Render a parsed document to the HTML intermediate representation.
     *
     * @param PhpWord $document Parsed document model.
     *
     * @return string HTML produced by PhpWord's HTML writer.
     *
     * @spec openspec/specs/pdf-conversion/spec.md
     */
    public function toHtml(PhpWord $document): string
    {
        return (new HtmlWriter($document))->getContent();

    }//end toHtml()

    /**
     * Instantiate the concrete reader for a PhpWord reader short name.
     *
     * @param string $readerName PhpWord reader short name.
     *
     * @return ReaderInterface The matching reader instance.
     *
     * @throws InvalidArgumentException When $readerName is not a known reader.
     */
    private function createReader(string $readerName): ReaderInterface
    {
        $reader = match ($readerName) {
            'Word2007' => new Word2007(),
            'MsDoc' => new MsDoc(),
            'ODText' => new ODText(),
            'RTF' => new RTF(),
            'HTML' => new HtmlReader(),
            default => null,
        };

        if ($reader === null) {
            throw new InvalidArgumentException('"'.$readerName.'" is not a valid PhpWord reader.');
        }

        return $reader;

    }//end createReader()
}//end class
