<?php

/**
 * EML PDF Assembly Service
 *
 * Assembles a single PDF/A-3b document from OpenRegister's *redacted*
 * anonymise-EML result (an {@see \OCA\OpenRegister\Service\File\AnonymisedEmlStructure}).
 *
 * Architecture: OpenRegister redacts every component (headers, body,
 * attachment bytes, inline images); this service ONLY assembles the
 * already-redacted result into a PDF. It performs NO redaction itself,
 * embeds NO original bytes, and embeds NO redacted bytes as PDF/A-3 file
 * attachments — renderable redacted attachments are rendered as appended
 * pages via the existing conversion cascade, and un-anonymisable /
 * non-renderable / oversize attachments become placeholder pages only.
 *
 * See openspec/changes/eml-pdf-assembly/design.md (D1–D11).
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

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Builds a PDF/A-3b from a redacted EML structure.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlPdfAssemblyService
{


    /**
     * App identifier used for IAppConfig reads.
     */
    private const APP_ID = 'docudesk';


    /**
     * Config key: when false, only the redacted envelope renders; renderable
     * attachments are not appended as pages. Default true.
     */
    private const KEY_APPEND_PAGES = 'docudesk.conversion.eml.append_attachment_pages';


    /**
     * Config key: redacted attachments larger than this (bytes) get a
     * placeholder page. Default 26214400 (25 MB).
     */
    private const KEY_MAX_SIZE = 'docudesk.conversion.eml.max_attachment_render_size_bytes';


    /**
     * Config key: optional override for the divider/placeholder template.
     * Default `eml/divider.twig`.
     */
    private const KEY_DIVIDER_TEMPLATE = 'docudesk.conversion.eml.divider_template';


    /**
     * Default value for the max-render-size config key (25 MB).
     */
    private const DEFAULT_MAX_SIZE = 26214400;


    /**
     * Renderable image MIME types — rendered inline as a single `<img>` page.
     *
     * @var array<int, string>
     */
    private const IMAGE_MIMES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];


    /**
     * Renderable Word-family MIME types — routed through PhpWordBackend's
     * engine via the cascade (rendered to HTML then to PDF pages).
     *
     * @var array<int, string>
     */
    private const WORD_MIMES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/rtf',
        'text/rtf',
    ];


    /**
     * Constructor.
     *
     * Renderable redacted attachment bytes are rendered within the shared mPDF
     * instance: PDF via FPDI import, images inline as data URIs, text in a
     * `<pre>` block, Word-family via PhpWord's HTML writer (the same engine
     * `PhpWordBackend` uses), and nested EML by recursion. The cascade
     * backends are NOT injected here — they operate on `OCP\Files\File` nodes,
     * not the raw redacted bytes this service holds, so reusing the PhpWord
     * engine directly is the clean path (see DEFERRED_QUESTIONS).
     *
     * @param PdfService       $pdfService       Shared mPDF/PDF-A configuration.
     * @param TemplateRenderer $templateRenderer Sandboxed Twig renderer.
     * @param IAppConfig       $appConfig        Tenant configuration provider.
     * @param LoggerInterface  $logger           Logger for diagnostics.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly TemplateRenderer $templateRenderer,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()


    /**
     * Assemble a PDF/A-3b from a redacted EML structure and return its bytes.
     *
     * The caller writes the returned bytes to Nextcloud Files (mirroring the
     * cascade's `_anonymized` naming). Returning bytes — rather than a File —
     * keeps this service free of filesystem coupling and lets nested EML
     * attachments be rendered inline within the same mPDF document.
     *
     * @param object      $result         OR's AnonymisedEmlStructure (redacted).
     * @param string|null $sourceFilename Original .eml filename, for the PDF title.
     *
     * @return string PDF/A-3b binary content.
     *
     * @throws ConversionFailedException When no output can be produced.
     */
    public function assemble(object $result, ?string $sourceFilename=null): string
    {
        $options = [
            'pdfa'   => true,
            'format' => 'A4',
            'title'  => $this->deriveTitle(result: $result, sourceFilename: $sourceFilename),
        ];

        try {
            $mpdf = $this->pdfService->createMpdfInstance(options: $options);
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlPdfAssemblyService] Failed to create mPDF instance',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            throw new ConversionFailedException(
                message: 'EML assembly could not initialise the PDF engine: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'mPDF init failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        $appendPages = $this->shouldAppendPages();
        $maxSize     = $this->maxAttachmentSize();

        $this->renderStructure(
            mpdf: $mpdf,
            result: $result,
            options: $options,
            appendPages: $appendPages,
            maxSize: $maxSize,
            isRoot: true
        );

        try {
            $bytes = $mpdf->Output(name: '', dest: \Mpdf\Output\Destination::STRING_RETURN);
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlPdfAssemblyService] mPDF Output failed',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            throw new ConversionFailedException(
                message: 'EML assembly failed to emit the PDF: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'mPDF Output failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        if (is_string($bytes) === false || $bytes === '') {
            throw new ConversionFailedException(
                message: 'EML assembly produced empty PDF output.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'mPDF Output returned empty',
                    ],
                ]
            );
        }

        return $bytes;

    }//end assemble()


    /**
     * Render one EML structure (envelope + attachments) into the shared mPDF
     * instance. Recurses for nested EML attachments.
     *
     * @param \Mpdf\Mpdf          $mpdf        Shared mPDF instance.
     * @param object              $result      AnonymisedEmlStructure to render.
     * @param array<string,mixed> $options     PDF options (for print CSS).
     * @param bool                $appendPages Whether renderable attachments are appended.
     * @param int                 $maxSize     Max attachment render size in bytes.
     * @param bool                $isRoot      True for the outermost message (no leading page break).
     *
     * @return void
     */
    private function renderStructure(
        \Mpdf\Mpdf $mpdf,
        object $result,
        array $options,
        bool $appendPages,
        int $maxSize,
        bool $isRoot
    ): void {
        $envelopeHtml = $this->renderEnvelopeHtml(result: $result, options: $options);

        try {
            if ($isRoot === false) {
                $mpdf->AddPage();
            }

            $mpdf->WriteHTML(html: $envelopeHtml);
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Envelope WriteHTML failed; rendering minimal envelope',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            $this->writeMinimalEnvelope(mpdf: $mpdf, result: $result, options: $options, addPage: false);
        }

        $attachments = $this->extractAttachments(result: $result);
        $index       = 0;
        foreach ($attachments as $attachment) {
            $index++;
            $this->renderAttachment(
                mpdf: $mpdf,
                attachment: $attachment,
                index: $index,
                options: $options,
                appendPages: $appendPages,
                maxSize: $maxSize
            );
        }

    }//end renderStructure()


    /**
     * Render a single redacted attachment: either appended rendered pages
     * (renderable + within cap + append enabled) or a placeholder page.
     *
     * @param \Mpdf\Mpdf          $mpdf        Shared mPDF instance.
     * @param object              $attachment  AnonymisedEmlAttachment.
     * @param int                 $index       1-based attachment index.
     * @param array<string,mixed> $options     PDF options.
     * @param bool                $appendPages Whether to append renderable pages.
     * @param int                 $maxSize     Max render size in bytes.
     *
     * @return void
     */
    private function renderAttachment(
        \Mpdf\Mpdf $mpdf,
        object $attachment,
        int $index,
        array $options,
        bool $appendPages,
        int $maxSize
    ): void {
        $filename    = $this->prop(obj: $attachment, name: 'filename', default: '(onbekend)');
        $mimeType    = strtolower($this->prop(obj: $attachment, name: 'mimeType', default: 'application/octet-stream'));
        $unsupported = (bool) $this->prop(obj: $attachment, name: 'unsupported', default: false);
        $redacted    = $this->prop(obj: $attachment, name: 'redactedContent', default: null);
        $nestedEml   = $this->prop(obj: $attachment, name: 'nestedEml', default: null);

        // Unsupported (no anonymiser) → placeholder, no content.
        if ($unsupported === true && $nestedEml === null) {
            $this->writeDivider(
                mpdf: $mpdf,
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: null,
                variant: 'unsupported'
            );
            return;
        }

        // Nested EML: recurse on the redacted nested result when present;
        // otherwise (depth cap reached / no nested result) a placeholder.
        if ($mimeType === 'message/rfc822') {
            if (is_object($nestedEml) === true) {
                $this->writeDivider(
                    mpdf: $mpdf,
                    index: $index,
                    filename: $filename,
                    mimeType: $mimeType,
                    size: null,
                    variant: 'default'
                );
                $this->renderStructure(
                    mpdf: $mpdf,
                    result: $nestedEml,
                    options: $options,
                    appendPages: $appendPages,
                    maxSize: $maxSize,
                    isRoot: false
                );
                return;
            }

            $this->writeDivider(
                mpdf: $mpdf,
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: null,
                variant: 'depth_limit'
            );
            return;
        }//end if

        // No redacted bytes available → placeholder (unsupported-shaped).
        if (is_string($redacted) === false) {
            $this->writeDivider(
                mpdf: $mpdf,
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: null,
                variant: 'unsupported'
            );
            return;
        }

        $size = strlen($redacted);

        // Append disabled → render nothing for renderable attachments.
        // Placeholders above still appear because they carry no content.
        if ($appendPages === false) {
            return;
        }

        // Oversize → placeholder.
        if ($size > $maxSize) {
            $this->writeDivider(
                mpdf: $mpdf,
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: $size,
                variant: 'too_large'
            );
            return;
        }

        // Non-renderable MIME → placeholder.
        if ($this->isRenderable(mimeType: $mimeType) === false) {
            $this->writeDivider(
                mpdf: $mpdf,
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: $size,
                variant: 'non_renderable'
            );
            return;
        }

        // Renderable → divider + rendered pages from the REDACTED bytes.
        $this->writeDivider(
            mpdf: $mpdf,
            index: $index,
            filename: $filename,
            mimeType: $mimeType,
            size: $size,
            variant: 'default'
        );

        try {
            $this->renderRenderableBytes(
                mpdf: $mpdf,
                bytes: $redacted,
                mimeType: $mimeType,
                filename: $filename,
                options: $options
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Attachment render failed; placeholder used',
                [
                    'index'     => $index,
                    'mimeType'  => $mimeType,
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            $this->writeDivider(
                mpdf: $mpdf,
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: $size,
                variant: 'render_failed'
            );
        }//end try

    }//end renderAttachment()


    /**
     * Render renderable redacted bytes onto a new page (or pages) of the
     * shared mPDF instance. No bytes are embedded as file attachments.
     *
     * @param \Mpdf\Mpdf          $mpdf     Shared mPDF instance.
     * @param string              $bytes    Decoded redacted bytes.
     * @param string              $mimeType Lowercased MIME type.
     * @param string              $filename Attachment filename (for text rendering).
     * @param array<string,mixed> $options  PDF options.
     *
     * @return void
     *
     * @throws Throwable On unrecoverable render failure (caught by caller).
     */
    private function renderRenderableBytes(
        \Mpdf\Mpdf $mpdf,
        string $bytes,
        string $mimeType,
        string $filename,
        array $options
    ): void {
        if ($mimeType === 'application/pdf') {
            $this->importPdfPages(mpdf: $mpdf, bytes: $bytes);
            return;
        }

        if (in_array($mimeType, self::IMAGE_MIMES, true) === true) {
            $dataUri  = 'data:'.$mimeType.';base64,'.base64_encode($bytes);
            $imgStyle = 'max-width:100%; max-height:100%;';
            $html     = '<div style="text-align:center;"><img src="'.$dataUri.'" style="'.$imgStyle.'"></div>';
            $mpdf->AddPage();
            $mpdf->WriteHTML(html: $this->pdfService->applyPrintCss(html: $html, options: $options));
            return;
        }

        if ($this->isTextLike(mimeType: $mimeType) === true) {
            $escaped  = htmlspecialchars($bytes, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
            $preStyle = 'font-family:DejaVuSansMono,monospace; font-size:9pt; white-space:pre-wrap;';
            $html     = '<pre style="'.$preStyle.'">'.$escaped.'</pre>';
            $mpdf->AddPage();
            $mpdf->WriteHTML(html: $this->pdfService->applyPrintCss(html: $html, options: $options));
            return;
        }

        if (in_array($mimeType, self::WORD_MIMES, true) === true) {
            $html = $this->renderWordBytesToHtml(bytes: $bytes, mimeType: $mimeType, filename: $filename);
            $mpdf->AddPage();
            $mpdf->WriteHTML(html: $this->pdfService->applyPrintCss(html: $html, options: $options));
            return;
        }

        // Should not be reached — isRenderable gates this.
        throw new \RuntimeException('No renderer for MIME '.$mimeType);

    }//end renderRenderableBytes()


    /**
     * Import every page of a redacted PDF into the shared mPDF instance via
     * FPDI. Each imported page is placed full-size on its own new page.
     *
     * @param \Mpdf\Mpdf $mpdf  Shared mPDF instance.
     * @param string     $bytes Redacted PDF bytes.
     *
     * @return void
     *
     * @throws Throwable When FPDI cannot parse the PDF (caught by caller).
     */
    private function importPdfPages(\Mpdf\Mpdf $mpdf, string $bytes): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'eml_pdf_');
        if ($tmp === false) {
            throw new \RuntimeException('Could not create temp file for PDF import');
        }

        try {
            file_put_contents($tmp, $bytes);
            $pageCount = $mpdf->setSourceFile($tmp);
            for ($p = 1; $p <= $pageCount; $p++) {
                $tplId       = $mpdf->importPage($p);
                $size        = $mpdf->getTemplateSize($tplId);
                $orientation = 'P';
                if (is_array($size) === true
                    && isset($size['width'], $size['height']) === true
                    && $size['width'] > $size['height']
                ) {
                    $orientation = 'L';
                }

                $mpdf->AddPage($orientation);
                $mpdf->useTemplate($tplId);
            }
        } finally {
            @unlink($tmp);
        }

    }//end importPdfPages()


    /**
     * Render Word-family redacted bytes to HTML via PhpWord (reusing the
     * cascade's PhpWordBackend engine). Falls back to a notice on failure.
     *
     * @param string $bytes    Redacted Word bytes.
     * @param string $mimeType Lowercased MIME type.
     * @param string $filename Attachment filename (extension hint).
     *
     * @return string Rendered HTML fragment.
     *
     * @throws Throwable On read failure (caught by caller).
     */
    private function renderWordBytesToHtml(string $bytes, string $mimeType, string $filename): string
    {
        $extByMime   = [
            'application/msword'                                                      => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.oasis.opendocument.text'                                 => 'odt',
            'application/rtf'                                                         => 'rtf',
            'text/rtf'                                                                => 'rtf',
        ];
        $readerByExt = [
            'doc'  => 'MsDoc',
            'docx' => 'Word2007',
            'odt'  => 'ODText',
            'rtf'  => 'RTF',
        ];

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (isset($readerByExt[$ext]) === false) {
            $ext = $extByMime[$mimeType] ?? '';
        }

        $reader = $readerByExt[$ext] ?? null;
        if ($reader === null) {
            throw new \RuntimeException('No PhpWord reader for Word MIME '.$mimeType);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'eml_word_');
        if ($tmp === false) {
            throw new \RuntimeException('Could not create temp file for Word render');
        }

        try {
            file_put_contents($tmp, $bytes);
            $phpWord    = \PhpOffice\PhpWord\IOFactory::load($tmp, $reader);
            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
            if (($htmlWriter instanceof \PhpOffice\PhpWord\Writer\HTML) === false) {
                throw new \RuntimeException('PhpWord did not return an HTML writer');
            }

            $html = $htmlWriter->getContent();
            $html = preg_replace('/@page[^{]*\{[^}]*\}/s', '', $html) ?? $html;
            if (is_string($html) === false || $html === '') {
                throw new \RuntimeException('PhpWord produced empty HTML');
            }

            return $html;
        } finally {
            @unlink($tmp);
        }

    }//end renderWordBytesToHtml()


    /**
     * Render the envelope (headers + body) HTML for one structure. Resolves
     * inline `cid:` images against OR's redacted inline-image map.
     *
     * @param object              $result  AnonymisedEmlStructure.
     * @param array<string,mixed> $options PDF options (for print CSS).
     *
     * @return string Envelope HTML (print-CSS prefixed).
     */
    private function renderEnvelopeHtml(object $result, array $options): string
    {
        $data = $this->buildEnvelopeData(result: $result);

        try {
            $html = $this->templateRenderer->renderTemplate(
                templateContent: $this->loadTemplate(name: 'eml/email_envelope.twig'),
                data: $data
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Envelope template render failed; using minimal envelope',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            $data['templateFailed'] = true;
            $data['bodyMode']       = 'failed';
            try {
                $html = $this->templateRenderer->renderTemplate(
                    templateContent: $this->loadTemplate(name: 'eml/email_envelope.twig'),
                    data: $data
                );
            } catch (Throwable $inner) {
                $html = $this->fallbackEnvelopeHtml(data: $data);
            }
        }//end try

        return $this->pdfService->applyPrintCss(html: $html, options: $options);

    }//end renderEnvelopeHtml()


    /**
     * Build the Twig data context for the envelope template from a redacted
     * structure, choosing the body mode and resolving inline images.
     *
     * @param object $result AnonymisedEmlStructure.
     *
     * @return array<string,mixed> Twig context.
     */
    private function buildEnvelopeData(object $result): array
    {
        $headers      = $this->extractHeaders(result: $result);
        $body         = $this->prop(obj: $result, name: 'body', default: null);
        $inlineImages = $this->extractInlineImages(result: $result);

        $plain = null;
        $html  = null;
        if (is_object($body) === true) {
            $plain = $this->prop(obj: $body, name: 'plain', default: null);
            $html  = $this->prop(obj: $body, name: 'html', default: null);
        }

        $bodyMode = 'empty';
        $bodyHtml = '';
        if (is_string($html) === true && trim($html) !== '') {
            $bodyMode = 'html';
            $bodyHtml = $this->resolveInlineImages(html: $html, inlineImages: $inlineImages);
        } else if (is_string($plain) === true && trim($plain) !== '') {
            $bodyMode = 'plain';
        }

        return [
            'headers'        => $headers,
            'bodyMode'       => $bodyMode,
            'bodyHtml'       => $bodyHtml,
            'bodyPlain'      => ($plain ?? ''),
            'templateFailed' => false,
        ];

    }//end buildEnvelopeData()


    /**
     * Normalise OR's redacted headers map into the flat shape the template
     * consumes (string From/Subject/Date/Reply-To, comma-joined To/Cc).
     *
     * @param object $result AnonymisedEmlStructure.
     *
     * @return array<string,string> Flattened header strings.
     */
    private function extractHeaders(object $result): array
    {
        $raw = $this->prop(obj: $result, name: 'headers', default: []);
        if (is_array($raw) === false) {
            $raw = [];
        }

        $joinList = static function (mixed $value): string {
            if (is_array($value) === false) {
                if (is_string($value) === true) {
                    return trim($value);
                }

                return '';
            }

            $parts = [];
            foreach ($value as $entry) {
                if (is_string($entry) === false) {
                    continue;
                }

                $trimmed = trim($entry);
                if ($trimmed !== '') {
                    $parts[] = $trimmed;
                }
            }

            return implode(', ', $parts);
        };

        $asString = static function (mixed $value): string {
            if (is_string($value) === true) {
                return $value;
            }

            return '';
        };

        return [
            'from'    => $asString($raw['from'] ?? null),
            'replyTo' => $asString($raw['replyTo'] ?? null),
            'to'      => $joinList($raw['to'] ?? ''),
            'cc'      => $joinList($raw['cc'] ?? ''),
            'subject' => $asString($raw['subject'] ?? null),
            'date'    => $this->formatDate(date: $raw['date'] ?? ''),
        ];

    }//end extractHeaders()


    /**
     * Format OR's redacted date string to `YYYY-MM-DD HH:MM`. Passes through
     * unparseable values unchanged (they may be redacted to a placeholder).
     *
     * @param mixed $date Raw date value.
     *
     * @return string Formatted date, or the original/empty string.
     */
    private function formatDate(mixed $date): string
    {
        if (is_string($date) === false || trim($date) === '') {
            return '';
        }

        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }

        return date('Y-m-d H:i', $ts);

    }//end formatDate()


    /**
     * Resolve `<img src="cid:...">` references against OR's redacted
     * inline-image map, substituting data URIs. Unresolved refs are left in
     * place and debug-logged. Only OR's redacted bytes are substituted.
     *
     * @param string                $html         Redacted HTML body.
     * @param array<string, string> $inlineImages Map contentId => redacted bytes.
     *
     * @return string HTML with resolvable cid: refs replaced.
     */
    private function resolveInlineImages(string $html, array $inlineImages): string
    {
        return preg_replace_callback(
            '/src\s*=\s*(["\'])cid:([^"\']+)\1/i',
            function (array $m) use ($inlineImages): string {
                $contentId  = trim($m[2]);
                $candidates = [$contentId, '<'.$contentId.'>', trim($contentId, '<>')];
                foreach ($candidates as $key) {
                    if (isset($inlineImages[$key]) === true) {
                        $bytes = $inlineImages[$key];
                        $mime  = $this->sniffImageMime(bytes: $bytes);
                        return 'src='.$m[1].'data:'.$mime.';base64,'.base64_encode($bytes).$m[1];
                    }
                }

                $this->logger->debug(
                    '[EmlPdfAssemblyService] Unresolved inline cid reference',
                    ['contentId' => $contentId]
                );
                return $m[0];
            },
            $html
        ) ?? $html;

    }//end resolveInlineImages()


    /**
     * Best-effort image MIME sniff from leading magic bytes; defaults to PNG.
     *
     * @param string $bytes Image bytes.
     *
     * @return string MIME type.
     */
    private function sniffImageMime(string $bytes): string
    {
        if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }

        if (strncmp($bytes, "GIF8", 4) === 0) {
            return 'image/gif';
        }

        if (strncmp($bytes, "RIFF", 4) === 0 && strpos(substr($bytes, 0, 16), 'WEBP') !== false) {
            return 'image/webp';
        }

        return 'image/png';

    }//end sniffImageMime()


    /**
     * Render a divider/placeholder page for one attachment.
     *
     * @param \Mpdf\Mpdf $mpdf     Shared mPDF instance.
     * @param int        $index    1-based attachment index.
     * @param string     $filename Attachment filename.
     * @param string     $mimeType MIME type.
     * @param int|null   $size     Size in bytes, or null.
     * @param string     $variant  One of default|non_renderable|too_large|unsupported|depth_limit|render_failed.
     *
     * @return void
     */
    private function writeDivider(
        \Mpdf\Mpdf $mpdf,
        int $index,
        string $filename,
        string $mimeType,
        ?int $size,
        string $variant
    ): void {
        $data = [
            'index'    => $index,
            'filename' => $filename,
            'mimeType' => $mimeType,
            'size'     => $size,
            'variant'  => $variant,
        ];

        try {
            $html = $this->templateRenderer->renderTemplate(
                templateContent: $this->loadTemplate(name: $this->dividerTemplate()),
                data: $data
            );
        } catch (Throwable $e) {
            $html = '<p>Bijlage '.$index.': '.htmlspecialchars($filename, ENT_QUOTES, 'UTF-8').'</p>';
        }

        try {
            $mpdf->AddPage();
            $mpdf->WriteHTML(html: $html);
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Divider WriteHTML failed',
                ['index' => $index, 'message' => $e->getMessage()]
            );
        }

    }//end writeDivider()


    /**
     * Write a minimal envelope (redacted headers + failure notice) when the
     * main envelope render fails — never emits un-redacted content.
     *
     * @param \Mpdf\Mpdf          $mpdf    Shared mPDF instance.
     * @param object              $result  AnonymisedEmlStructure.
     * @param array<string,mixed> $options PDF options.
     * @param bool                $addPage Whether to add a page first.
     *
     * @return void
     */
    private function writeMinimalEnvelope(\Mpdf\Mpdf $mpdf, object $result, array $options, bool $addPage): void
    {
        $headers = $this->extractHeaders(result: $result);
        $html    = $this->fallbackEnvelopeHtml(data: ['headers' => $headers, 'templateFailed' => true]);

        try {
            if ($addPage === true) {
                $mpdf->AddPage();
            }

            $mpdf->WriteHTML(html: $this->pdfService->applyPrintCss(html: $html, options: $options));
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlPdfAssemblyService] Minimal envelope WriteHTML failed',
                ['message' => $e->getMessage()]
            );
        }

    }//end writeMinimalEnvelope()


    /**
     * Build a plain HTML fallback envelope from redacted headers — used only
     * when Twig rendering is unavailable. Renders no body content.
     *
     * @param array<string,mixed> $data Context with `headers`.
     *
     * @return string HTML.
     */
    private function fallbackEnvelopeHtml(array $data): string
    {
        $headers = $data['headers'] ?? [];
        $esc     = static fn ($v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $lines   = [
            'Van: '.$esc($headers['from'] ?? ''),
            'Aan: '.$esc($headers['to'] ?? ''),
            'Onderwerp: '.$esc($headers['subject'] ?? ''),
            'Datum: '.$esc($headers['date'] ?? ''),
        ];

        $open   = '<div style="font-family:DejaVu Sans,sans-serif;font-size:10pt;"><p>';
        $notice = '</p><p style="color:#b00;font-style:italic;">(template rendering failed)</p></div>';

        return $open.implode('<br>', $lines).$notice;

    }//end fallbackEnvelopeHtml()


    /**
     * Load a bundled template file's content.
     *
     * @param string $name Template path relative to lib/Resources/templates.
     *
     * @return string Template content.
     *
     * @throws \RuntimeException When the template file is missing.
     */
    private function loadTemplate(string $name): string
    {
        $path = dirname(__DIR__).'/Resources/templates/'.$name;
        $real = realpath($path);
        $base = realpath(dirname(__DIR__).'/Resources/templates');
        if ($real === false || $base === false || strncmp($real, $base, strlen($base)) !== 0) {
            throw new \RuntimeException('Template not found or outside template root: '.$name);
        }

        $content = file_get_contents($real);
        if ($content === false) {
            throw new \RuntimeException('Could not read template: '.$name);
        }

        return $content;

    }//end loadTemplate()


    /**
     * Whether the MIME type is renderable as appended pages.
     *
     * @param string $mimeType Lowercased MIME.
     *
     * @return bool
     */
    private function isRenderable(string $mimeType): bool
    {
        if ($mimeType === 'application/pdf') {
            return true;
        }

        if (in_array($mimeType, self::IMAGE_MIMES, true) === true) {
            return true;
        }

        if (in_array($mimeType, self::WORD_MIMES, true) === true) {
            return true;
        }

        return $this->isTextLike(mimeType: $mimeType);

    }//end isRenderable()


    /**
     * Whether the MIME type is plain-text-like (rendered in a `<pre>` block).
     *
     * @param string $mimeType Lowercased MIME.
     *
     * @return bool
     */
    private function isTextLike(string $mimeType): bool
    {
        if (strncmp($mimeType, 'text/', 5) === 0 && $mimeType !== 'text/rtf' && $mimeType !== 'text/calendar') {
            return true;
        }

        return false;

    }//end isTextLike()


    /**
     * Extract the attachments array from a structure (typed property or map).
     *
     * @param object $result AnonymisedEmlStructure.
     *
     * @return array<int, object> Attachment value objects.
     */
    private function extractAttachments(object $result): array
    {
        $attachments = $this->prop(obj: $result, name: 'attachments', default: []);
        if (is_array($attachments) === false) {
            return [];
        }

        return array_values(array_filter($attachments, 'is_object'));

    }//end extractAttachments()


    /**
     * Extract the inline-image map (contentId => redacted bytes).
     *
     * @param object $result AnonymisedEmlStructure.
     *
     * @return array<string, string> Inline-image map.
     */
    private function extractInlineImages(object $result): array
    {
        $map = $this->prop(obj: $result, name: 'inlineImages', default: []);
        if (is_array($map) === false) {
            return [];
        }

        $clean = [];
        foreach ($map as $key => $value) {
            if (is_string($value) === true) {
                $clean[(string) $key] = $value;
            }
        }

        return $clean;

    }//end extractInlineImages()


    /**
     * Read a public property from an OR value object with a default.
     *
     * Properties are accessed directly (the OR value objects expose readonly
     * public properties); arrays are tolerated for test fixtures.
     *
     * @param object|array<string,mixed> $obj     Source object or array.
     * @param string                     $name    Property/key name.
     * @param mixed                      $default Default when absent.
     *
     * @return mixed The value or default.
     */
    private function prop(object|array $obj, string $name, mixed $default): mixed
    {
        if (is_array($obj) === true) {
            return $obj[$name] ?? $default;
        }

        if (isset($obj->$name) === true) {
            return $obj->$name;
        }

        // Distinguish a present-but-null property from an absent one.
        if (property_exists($obj, $name) === true) {
            return $obj->$name;
        }

        return $default;

    }//end prop()


    /**
     * Derive the PDF title from the source filename or the redacted subject.
     *
     * @param object      $result         AnonymisedEmlStructure.
     * @param string|null $sourceFilename Original .eml filename.
     *
     * @return string PDF title.
     */
    private function deriveTitle(object $result, ?string $sourceFilename): string
    {
        if ($sourceFilename !== null && trim($sourceFilename) !== '') {
            $dot = strrpos($sourceFilename, '.');
            if ($dot === false) {
                return $sourceFilename;
            }

            return substr($sourceFilename, 0, $dot);
        }

        $headers = $this->extractHeaders(result: $result);
        if ($headers['subject'] !== '') {
            return $headers['subject'];
        }

        return 'E-mail';

    }//end deriveTitle()


    /**
     * Whether renderable attachments should be appended (config-driven).
     *
     * @return bool
     */
    private function shouldAppendPages(): bool
    {
        $value = $this->appConfig->getValueString(self::APP_ID, self::KEY_APPEND_PAGES, 'true');
        return $value !== 'false';

    }//end shouldAppendPages()


    /**
     * Resolve the max-attachment-render-size config (positive integer).
     *
     * @return int Size in bytes.
     */
    private function maxAttachmentSize(): int
    {
        $value = (int) $this->appConfig->getValueString(
            self::APP_ID,
            self::KEY_MAX_SIZE,
            (string) self::DEFAULT_MAX_SIZE
        );
        if ($value <= 0) {
            return self::DEFAULT_MAX_SIZE;
        }

        return $value;

    }//end maxAttachmentSize()


    /**
     * Resolve the divider template name (config-driven, sandboxed to the
     * template root by loadTemplate).
     *
     * @return string Template path relative to the template root.
     */
    private function dividerTemplate(): string
    {
        $value = $this->appConfig->getValueString(self::APP_ID, self::KEY_DIVIDER_TEMPLATE, 'eml/divider.twig');
        if (trim($value) === '') {
            return 'eml/divider.twig';
        }

        return $value;

    }//end dividerTemplate()


}//end class
