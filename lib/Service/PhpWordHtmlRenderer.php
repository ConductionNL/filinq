<?php

/**
 * PhpWord HTML Renderer
 *
 * Injectable seam around PhpOffice\PhpWord's document readers and HTML writer.
 * Extracted from {@see EmlPdfAssemblyService} so the assembly pipeline depends
 * on a DocuDesk-owned collaborator it can substitute in tests, rather than on
 * PhpWord's static `IOFactory` facade.
 *
 * Readers and the writer are instantiated directly — `IOFactory::load()` and
 * `IOFactory::createWriter()` are thin static wrappers around exactly these
 * `new` expressions, so the behaviour is identical without the static facade.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use PhpOffice\PhpWord\Reader\MsDoc;
use PhpOffice\PhpWord\Reader\ODText;
use PhpOffice\PhpWord\Reader\RTF;
use PhpOffice\PhpWord\Reader\Word2007;
use PhpOffice\PhpWord\Writer\HTML as HtmlWriter;
use RuntimeException;

/**
 * Renders Word-family bytes to an HTML fragment via PhpWord.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class PhpWordHtmlRenderer
{

    /**
     * PhpWord reader class per lowercased file extension.
     *
     * @var array<string, class-string>
     */
    private const READER_BY_EXT = [
        'doc'  => MsDoc::class,
        'docx' => Word2007::class,
        'odt'  => ODText::class,
        'rtf'  => RTF::class,
    ];

    /**
     * File extension per Word-family MIME type, used when the filename
     * carries no usable extension.
     *
     * @var array<string, string>
     */
    private const EXT_BY_MIME = [
        'application/msword'                                                      => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.oasis.opendocument.text'                                 => 'odt',
        'application/rtf'                                                         => 'rtf',
        'text/rtf'                                                                => 'rtf',
    ];

    /**
     * Render Word-family bytes to an HTML fragment.
     *
     * PhpWord's HTML writer emits `@page` at-rules that mPDF would honour as
     * page geometry; they are stripped so the shared A4 layout wins.
     *
     * @param string $bytes    Redacted Word bytes.
     * @param string $mimeType Lowercased MIME type.
     * @param string $filename Attachment filename (extension hint).
     *
     * @return string Rendered HTML fragment.
     *
     * @throws RuntimeException When no reader matches or the render yields nothing.
     * @throws \Throwable       On read failure (caught by the caller).
     */
    public function renderToHtml(string $bytes, string $mimeType, string $filename): string
    {
        $readerClass = $this->resolveReaderClass(mimeType: $mimeType, filename: $filename);

        $tmp = tempnam(sys_get_temp_dir(), 'eml_word_');
        if ($tmp === false) {
            throw new RuntimeException('Could not create temp file for Word render');
        }

        try {
            file_put_contents($tmp, $bytes);
            $reader  = new $readerClass();
            $phpWord = $reader->load($tmp);
            $writer  = new HtmlWriter($phpWord);

            $html = $writer->getContent();
            $html = (preg_replace('/@page[^{]*\{[^}]*\}/s', '', $html) ?? $html);
            if (is_string($html) === false || $html === '') {
                throw new RuntimeException('PhpWord produced empty HTML');
            }

            return $html;
        } finally {
            if (is_file($tmp) === true) {
                unlink($tmp);
            }
        }//end try

    }//end renderToHtml()

    /**
     * Resolve the PhpWord reader class for an attachment.
     *
     * The filename extension wins when it names a known reader; otherwise the
     * MIME type decides.
     *
     * @param string $mimeType Lowercased MIME type.
     * @param string $filename Attachment filename.
     *
     * @return class-string The PhpWord reader class to instantiate.
     *
     * @throws RuntimeException When neither the extension nor the MIME maps to a reader.
     */
    private function resolveReaderClass(string $mimeType, string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (isset(self::READER_BY_EXT[$ext]) === false) {
            $ext = (self::EXT_BY_MIME[$mimeType] ?? '');
        }

        $readerClass = (self::READER_BY_EXT[$ext] ?? null);
        if ($readerClass === null) {
            throw new RuntimeException('No PhpWord reader for Word MIME '.$mimeType);
        }

        return $readerClass;

    }//end resolveReaderClass()
}//end class
