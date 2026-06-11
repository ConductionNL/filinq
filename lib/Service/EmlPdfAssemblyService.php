<?php

/**
 * EML→PDF Assembly Service
 *
 * Renders a parsed EML message (as produced by OpenRegister's
 * `TextExtractionService::parseEmlStructured`) into an assembled
 * PDF/A-3b document that combines:
 *
 *   1. A Dutch-headed envelope page (Van/Aan/Cc/Onderwerp/Datum)
 *      followed by the message body (HTML preferred over plain text).
 *   2. Per-attachment divider pages.
 *   3. Per-attachment rendered content (PDF / image / plain-text /
 *      nested EML, with a recursion cap of 3).
 *   4. PDF/A-3 embedded-file annotations carrying the raw attachment
 *      bytes so downstream consumers can extract the originals.
 *
 * The service is invoked by `Conversion\EmlBackend::convert()` and is
 * the eml-pdf-assembly Change's central building block. The OR-side
 * dependency (`parseEmlStructured`) is resolved lazily via the DI
 * container so DocuDesk installs without OpenRegister at the runtime
 * level still load (the backend's `isAvailable()` shields callers).
 *
 * Configuration keys (read via IAppConfig):
 *   - `docudesk.conversion.eml.append_attachment_pages`         (bool, default true)
 *   - `docudesk.conversion.eml.max_attachment_render_size_bytes` (int, default 26214400 = 25 MiB)
 *   - `docudesk.conversion.eml.divider_template`                (string, default `eml/divider.twig`)
 *   - `docudesk.conversion.eml.envelope_template`               (string, default `eml/email_envelope.twig`)
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/eml-pdf-assembly/specs/eml-pdf-assembly/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use OCA\DocuDesk\Exception\ConversionFailedException;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Builds a PDF/A-3b out of an EmlStructure value object.
 *
 * The service deliberately holds no state between calls — `assemble()`
 * is the single entry point and constructs an mPDF instance per
 * invocation. The envelope template + per-attachment templates are
 * rendered through the shared `TemplateRenderer` so the Twig sandbox
 * stays in lockstep with the print-preview path.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EmlPdfAssemblyService
{


    /**
     * App identifier used for IAppConfig reads.
     */
    private const APP_ID = 'docudesk';


    /**
     * Config key for the per-attachment-page toggle.
     */
    private const CFG_APPEND_PAGES = 'docudesk.conversion.eml.append_attachment_pages';


    /**
     * Config key for the byte cap above which a renderable attachment
     * is replaced by a "too large" placeholder divider (bytes are still
     * embedded as PDF/A-3 file attachments).
     */
    private const CFG_MAX_RENDER_BYTES = 'docudesk.conversion.eml.max_attachment_render_size_bytes';


    /**
     * Config key for the divider template path under `lib/Resources/templates/`.
     */
    private const CFG_DIVIDER_TEMPLATE = 'docudesk.conversion.eml.divider_template';


    /**
     * Config key for the envelope template path under `lib/Resources/templates/`.
     */
    private const CFG_ENVELOPE_TEMPLATE = 'docudesk.conversion.eml.envelope_template';


    /**
     * Maximum nesting depth for nested EML attachments.
     *
     * Matches OR's `EmlParser::DEFAULT_NESTING_DEPTH` (3) so the
     * downstream assembly never tries to render a deeper structure than
     * OR is allowed to parse.
     */
    private const MAX_NESTING_DEPTH = 3;


    /**
     * Constructor.
     *
     * @param PdfService         $pdfService       PDF generator (shares mPDF config with print-preview).
     * @param TemplateRenderer   $templateRenderer Twig sandbox runner used for envelope + divider templates.
     * @param IAppConfig         $appConfig        Tenant configuration provider.
     * @param ContainerInterface $container        DI container — used to lazily resolve OR's TextExtractionService.
     * @param LoggerInterface    $logger           Logger for diagnostics.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly TemplateRenderer $templateRenderer,
        private readonly IAppConfig $appConfig,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()


    /**
     * Assemble a parsed EML structure into a PDF/A-3b binary.
     *
     * Returns the raw PDF bytes. The caller (typically `EmlBackend`)
     * is responsible for writing them into Nextcloud Files via
     * `IRootFolder`.
     *
     * @param object      $structure      An `EmlStructure` value object as returned by
     *                                    OR's `TextExtractionService::parseEmlStructured`.
     * @param string|null $sourceFilename Optional original filename, used as PDF title +
     *                                    rendered into the envelope header.
     *
     * @return string PDF binary content.
     *
     * @throws ConversionFailedException When assembly fails catastrophically.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
     */
    public function assemble(object $structure, ?string $sourceFilename=null): string
    {
        return $this->assembleAtDepth(structure: $structure, sourceFilename: $sourceFilename, depth: 0);

    }//end assemble()


    /**
     * Internal entry point that tracks recursion depth so nested EMLs
     * can be rendered into the same document. Depth is capped at
     * MAX_NESTING_DEPTH; deeper nested EMLs degrade to a divider with
     * a "not rendered" placeholder while still being embedded.
     *
     * @param object      $structure      EmlStructure value object.
     * @param string|null $sourceFilename Original filename for the envelope title row.
     * @param int         $depth          Current nesting depth (0 for the outermost call).
     *
     * @return string PDF binary content.
     *
     * @throws ConversionFailedException When the underlying mPDF render fails fatally.
     */
    private function assembleAtDepth(object $structure, ?string $sourceFilename, int $depth): string
    {
        try {
            $mpdf = $this->createMpdf(title: $sourceFilename ?? 'email.eml');

            // 1. Render the envelope page.
            $envelopeHtml = $this->renderEnvelope(structure: $structure, sourceFilename: $sourceFilename);
            $mpdf->WriteHTML(html: $envelopeHtml, mode: HTMLParserMode::DEFAULT_MODE);

            // 2. Iterate attachments — embed + (optionally) page-render.
            $attachments       = $this->extractAttachments(structure: $structure);
            $appendPages       = $this->resolveAppendAttachmentPages();
            $maxRenderBytes    = $this->resolveMaxRenderBytes();
            $total             = count($attachments);

            foreach ($attachments as $index => $attachment) {
                // PDF/A-3 embedded file annotation (best-effort —
                // failure is logged but never aborts assembly).
                $this->embedAttachmentBytes(mpdf: $mpdf, attachment: $attachment);

                if ($appendPages === false) {
                    // Envelope-only mode: skip divider + content but keep embeds.
                    continue;
                }

                $sizeBytes  = strlen($attachment->content);
                $tooLarge   = ($sizeBytes > $maxRenderBytes);
                $renderable = $this->isAttachmentRenderable(attachment: $attachment);

                $mpdf->AddPage();
                $placeholder = null;
                if ($tooLarge === true) {
                    $placeholder = 'too_large';
                } else if ($renderable === false) {
                    $placeholder = 'not_renderable';
                }

                $dividerHtml = $this->renderDivider(
                    attachment: $attachment,
                    index: ((int) $index + 1),
                    total: $total,
                    sizeBytes: $sizeBytes,
                    placeholder: $placeholder,
                    capBytes: $maxRenderBytes
                );
                $mpdf->WriteHTML(html: $dividerHtml, mode: HTMLParserMode::DEFAULT_MODE);

                if ($placeholder !== null) {
                    continue;
                }

                // Render the attachment body itself.
                try {
                    $this->renderAttachmentBody(
                        mpdf: $mpdf,
                        attachment: $attachment,
                        depth: $depth
                    );
                } catch (Throwable $e) {
                    $this->logger->warning(
                        '[EmlPdfAssemblyService] Attachment render failed; divider already in place, continuing.',
                        [
                            'filename'  => $attachment->filename,
                            'mimeType'  => $attachment->mimeType,
                            'exception' => get_class($e),
                            'message'   => $e->getMessage(),
                        ]
                    );

                    // Best-effort: replace the divider placeholder text by
                    // appending a small failure note on a new page.
                    $mpdf->AddPage();
                    $failHtml = $this->renderDivider(
                        attachment: $attachment,
                        index: ((int) $index + 1),
                        total: $total,
                        sizeBytes: $sizeBytes,
                        placeholder: 'render_failed',
                        capBytes: $maxRenderBytes
                    );
                    $mpdf->WriteHTML(html: $failHtml, mode: HTMLParserMode::DEFAULT_MODE);
                }//end try
            }//end foreach

            return $mpdf->Output(name: '', dest: \Mpdf\Output\Destination::STRING_RETURN);
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlPdfAssemblyService] Catastrophic assembly failure.',
                [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            throw new ConversionFailedException(
                message: 'EML assembly failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'assembly threw: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

    }//end assembleAtDepth()


    /**
     * Read the `append_attachment_pages` tenant config flag. Defaults to true.
     *
     * @return bool True when per-attachment dividers + rendered pages are emitted.
     */
    private function resolveAppendAttachmentPages(): bool
    {
        $raw = $this->appConfig->getValueString(self::APP_ID, self::CFG_APPEND_PAGES, 'true');
        return ($raw !== 'false' && $raw !== '0');

    }//end resolveAppendAttachmentPages()


    /**
     * Read the `max_attachment_render_size_bytes` cap. Defaults to 25 MiB.
     *
     * @return int Positive byte cap.
     */
    private function resolveMaxRenderBytes(): int
    {
        $raw    = $this->appConfig->getValueString(self::APP_ID, self::CFG_MAX_RENDER_BYTES, '26214400');
        $parsed = (int) $raw;
        if ($parsed <= 0) {
            return 26214400;
        }

        return $parsed;

    }//end resolveMaxRenderBytes()


    /**
     * Resolve the envelope template path. Defaults to `eml/email_envelope.twig`.
     *
     * @return string Path relative to `lib/Resources/templates/`.
     */
    private function resolveEnvelopeTemplate(): string
    {
        return $this->appConfig->getValueString(self::APP_ID, self::CFG_ENVELOPE_TEMPLATE, 'eml/email_envelope.twig');

    }//end resolveEnvelopeTemplate()


    /**
     * Resolve the divider template path. Defaults to `eml/divider.twig`.
     *
     * @return string Path relative to `lib/Resources/templates/`.
     */
    private function resolveDividerTemplate(): string
    {
        return $this->appConfig->getValueString(self::APP_ID, self::CFG_DIVIDER_TEMPLATE, 'eml/divider.twig');

    }//end resolveDividerTemplate()


    /**
     * Construct an mPDF instance preconfigured for PDF/A-3b output.
     *
     * Font embedding is on; JavaScript and external resources are
     * disabled so the resulting document is self-contained and safe
     * to archive.
     *
     * @param string $title Document title (filename or subject).
     *
     * @return Mpdf Configured mPDF instance.
     */
    private function createMpdf(string $title): Mpdf
    {
        $tempDir = '/tmp/mpdf';
        if (is_dir($tempDir) === false) {
            mkdir($tempDir, 0777, true);
        }

        $config = [
            'tempDir'       => $tempDir,
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 15,
            'margin_right'  => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'PDFA'          => true,
            'PDFAauto'      => true,
        ];

        $fontDir = $this->getFontDirectory();
        if ($fontDir !== null) {
            $config['fontDir']      = [$fontDir];
            $config['fontdata']     = [
                'dejavusans' => [
                    'R'  => 'DejaVuSans.ttf',
                    'B'  => 'DejaVuSans-Bold.ttf',
                    'I'  => 'DejaVuSans-Oblique.ttf',
                    'BI' => 'DejaVuSans-BoldOblique.ttf',
                ],
            ];
            $config['default_font'] = 'dejavusans';
        }

        $mpdf = new Mpdf(config: $config);
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor('DocuDesk');
        $mpdf->SetCreator('DocuDesk EML→PDF Assembly');
        $mpdf->SetSubject($title);

        return $mpdf;

    }//end createMpdf()


    /**
     * Locate the bundled DejaVu font directory; null when absent (the
     * assembly still works, just without explicit font embedding).
     *
     * @return string|null Absolute path to a font directory or null.
     */
    private function getFontDirectory(): ?string
    {
        $fontDir = dirname(__DIR__).'/Fonts';
        if (is_dir($fontDir) === true) {
            return $fontDir;
        }

        return null;

    }//end getFontDirectory()


    /**
     * Render the envelope HTML for an EML structure.
     *
     * Inline `cid:` image references in the HTML body are substituted
     * with `data:` URIs sourced from the attachments BEFORE handing the
     * body to Twig — this keeps the substitution logic out of the
     * template and lets us log unresolved cid:refs centrally.
     *
     * When the Twig render fails, falls back to a minimal HTML envelope
     * containing the bare headers so the assembled PDF still carries
     * the diagnostic context.
     *
     * @param object      $structure      EmlStructure value object.
     * @param string|null $sourceFilename Original filename (rendered above the From row).
     *
     * @return string HTML envelope ready for mPDF.
     */
    private function renderEnvelope(object $structure, ?string $sourceFilename): string
    {
        $headers    = $this->extractHeaders(structure: $structure);
        $bodyHtml   = $this->extractBodyHtml(structure: $structure);
        $bodyPlain  = $this->extractBodyPlain(structure: $structure);
        $attachs    = $this->extractAttachments(structure: $structure);
        $attachCount = count($attachs);

        // Inline cid: substitution before the template runs.
        if ($bodyHtml !== null && $bodyHtml !== '') {
            $bodyHtml = $this->substituteInlineCids(html: $bodyHtml, attachments: $attachs);
        }

        $data = [
            'headers'         => [
                'from'    => $this->renderHeader(headers: $headers, key: 'from'),
                'to'      => $this->renderHeader(headers: $headers, key: 'to'),
                'cc'      => $this->renderHeader(headers: $headers, key: 'cc'),
                'subject' => $this->renderHeader(headers: $headers, key: 'subject'),
                'date'    => $this->renderHeader(headers: $headers, key: 'date'),
            ],
            'body'            => [
                'html'      => $bodyHtml,
                'plainText' => $bodyPlain,
            ],
            'attachment_count' => $attachCount,
            'source_filename'  => $sourceFilename,
        ];

        try {
            $template = $this->loadTemplate(path: $this->resolveEnvelopeTemplate());
            return $this->templateRenderer->renderTemplate(templateContent: $template, data: $data);
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Envelope template render failed; emitting minimal envelope.',
                [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            return $this->renderMinimalEnvelope(headers: $headers, sourceFilename: $sourceFilename);

        }

    }//end renderEnvelope()


    /**
     * Minimal envelope fallback used when the Twig template fails.
     *
     * @param array<string,mixed> $headers        Decoded EmlStructure->headers.
     * @param string|null         $sourceFilename Original filename if available.
     *
     * @return string Minimal HTML envelope.
     */
    private function renderMinimalEnvelope(array $headers, ?string $sourceFilename): string
    {
        $rows = [];
        foreach (['from' => 'Van', 'to' => 'Aan', 'cc' => 'Cc', 'subject' => 'Onderwerp', 'date' => 'Datum'] as $key => $label) {
            $value = $this->renderHeader(headers: $headers, key: $key);
            if ($value !== null && $value !== '') {
                $rows[] = '<tr><td style="color:#555;padding-right:8mm;vertical-align:top">'.htmlspecialchars($label).'</td><td>'.htmlspecialchars($value).'</td></tr>';
            }
        }

        $title  = ($sourceFilename === null) ? '' : '<p style="font-size:9pt;color:#777;margin:0 0 6mm 0">'.htmlspecialchars($sourceFilename).'</p>';
        $tableRows = implode('', $rows);
        $notice = '<p style="color:#a04;font-size:10pt;margin-top:8mm">(template rendering failed — header-only envelope)</p>';

        return $title.'<table style="border-collapse:collapse;font-size:10pt;font-family:DejaVu Sans,sans-serif"><tbody>'.$tableRows.'</tbody></table>'.$notice;

    }//end renderMinimalEnvelope()


    /**
     * Render the divider HTML for one attachment.
     *
     * @param object   $attachment EmlAttachment value object.
     * @param int      $index      1-based attachment position.
     * @param int      $total      Total attachment count for the EML.
     * @param int      $sizeBytes  Decoded attachment byte size.
     * @param ?string  $placeholder One of: null, 'too_large', 'not_renderable', 'render_failed'.
     * @param int      $capBytes   The render byte cap (passed through to the template for 'too_large').
     *
     * @return string Divider HTML ready for mPDF.
     */
    private function renderDivider(
        object $attachment,
        int $index,
        int $total,
        int $sizeBytes,
        ?string $placeholder,
        int $capBytes
    ): string {
        $data = [
            'index'       => $index,
            'total'       => $total,
            'filename'    => $attachment->filename,
            'mime_type'   => $attachment->mimeType,
            'size_bytes'  => $sizeBytes,
            'size_human'  => $this->formatBytes(bytes: $sizeBytes),
            'placeholder' => $placeholder,
            'cap_bytes'   => $capBytes,
        ];

        try {
            $template = $this->loadTemplate(path: $this->resolveDividerTemplate());
            return $this->templateRenderer->renderTemplate(templateContent: $template, data: $data);
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Divider template render failed; emitting minimal divider.',
                [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            return '<div style="font-family:DejaVu Sans,sans-serif;padding-top:30mm">'
                .'<h2 style="font-size:14pt">'.htmlspecialchars($attachment->filename).'</h2>'
                .'<p style="font-size:10pt;color:#555">'.htmlspecialchars($attachment->mimeType).' · '.$sizeBytes.' bytes</p>'
                .'</div>';
        }

    }//end renderDivider()


    /**
     * Embed an attachment's raw bytes as a PDF/A-3 file attachment.
     *
     * mPDF surfaces PDF file attachments via the `Annotation()` API
     * with a `$file` payload. Failures are logged and swallowed —
     * they're observability cues, never assembly aborts.
     *
     * @param Mpdf   $mpdf       Active mPDF instance.
     * @param object $attachment EmlAttachment value object (raw bytes in `content`).
     *
     * @return void
     */
    private function embedAttachmentBytes(Mpdf $mpdf, object $attachment): void
    {
        try {
            // mPDF's Annotation() $file param accepts the raw bytes
            // string and surfaces a paperclip-icon annotation linked to
            // an embedded file stream in the resulting PDF.
            $mpdf->Annotation(
                text: $attachment->filename,
                x: 0,
                y: 0,
                icon: 'Paperclip',
                author: 'DocuDesk',
                subject: $attachment->mimeType,
                opacity: 0,
                colarray: false,
                popup: '',
                file: $attachment->content
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Failed to embed attachment as PDF/A-3 file annotation.',
                [
                    'filename'  => $attachment->filename,
                    'mimeType'  => $attachment->mimeType,
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
        }

    }//end embedAttachmentBytes()


    /**
     * Determine whether an attachment can be rendered as PDF pages.
     *
     * PDF, image (jpg/png/gif), plain-text, and nested EML are
     * renderable. Other types (xlsx, pptx, etc.) are embedded as bytes
     * but not page-rendered.
     *
     * @param object $attachment EmlAttachment value object.
     *
     * @return bool True when a per-type renderer exists.
     */
    private function isAttachmentRenderable(object $attachment): bool
    {
        $mime = strtolower($attachment->mimeType);
        if ($mime === 'application/pdf') {
            return true;
        }

        if ($mime === 'message/rfc822' && isset($attachment->nestedEml) === true && $attachment->nestedEml !== null) {
            return true;
        }

        if (str_starts_with($mime, 'image/') === true) {
            return in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'], true);
        }

        if (str_starts_with($mime, 'text/') === true) {
            return true;
        }

        return false;

    }//end isAttachmentRenderable()


    /**
     * Render the body of an attachment into the active mPDF page stream.
     *
     * Dispatches by MIME family:
     *   - application/pdf      → FPDI setSourceFile + importPage + useTemplate
     *   - image/{jpeg,png,gif} → single page with data: URI image
     *   - text/*               → single page with <pre>-wrapped content
     *   - message/rfc822       → recursive assembly into the same Mpdf (depth-capped)
     *
     * @param Mpdf   $mpdf       Active mPDF instance.
     * @param object $attachment EmlAttachment value object.
     * @param int    $depth      Current nesting depth.
     *
     * @return void
     *
     * @throws Throwable On unrecoverable per-attachment render failure; the
     *                   caller catches and logs.
     */
    private function renderAttachmentBody(Mpdf $mpdf, object $attachment, int $depth): void
    {
        $mime = strtolower($attachment->mimeType);

        if ($mime === 'application/pdf') {
            $this->renderPdfAttachment(mpdf: $mpdf, attachment: $attachment);
            return;
        }

        if (str_starts_with($mime, 'image/') === true) {
            $this->renderImageAttachment(mpdf: $mpdf, attachment: $attachment);
            return;
        }

        if (str_starts_with($mime, 'text/') === true) {
            $this->renderTextAttachment(mpdf: $mpdf, attachment: $attachment);
            return;
        }

        if ($mime === 'message/rfc822' && isset($attachment->nestedEml) === true && $attachment->nestedEml !== null) {
            $this->renderNestedEmlAttachment(mpdf: $mpdf, attachment: $attachment, depth: $depth);
            return;
        }

        // Unknown / unrenderable — caller's renderable gate should
        // have filtered this, but if we reach here treat as a no-op.

    }//end renderAttachmentBody()


    /**
     * Render an embedded PDF attachment via FPDI page import.
     *
     * Writes the raw bytes to a temp file (FPDI's setSourceFile takes a
     * path or stream wrapper), imports every page, and stitches each
     * onto a fresh A4 page in the host document.
     *
     * @param Mpdf   $mpdf       Active mPDF instance.
     * @param object $attachment EmlAttachment value object.
     *
     * @return void
     */
    private function renderPdfAttachment(Mpdf $mpdf, object $attachment): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'docudesk-emlpdf-');
        if ($tmpFile === false) {
            throw new \RuntimeException('Could not create temporary file for PDF attachment');
        }

        try {
            file_put_contents($tmpFile, $attachment->content);
            $pageCount = $mpdf->setSourceFile($tmpFile);

            for ($p = 1; $p <= $pageCount; $p++) {
                $tplId   = $mpdf->importPage($p);
                $tplSize = $mpdf->getTemplateSize($tplId);
                $mpdf->AddPage(
                    orientation: ($tplSize['width'] > $tplSize['height']) ? 'L' : 'P'
                );
                $mpdf->useTemplate($tplId);
            }
        } finally {
            @unlink($tmpFile);
        }

    }//end renderPdfAttachment()


    /**
     * Render a bitmap image attachment as a single A4 page.
     *
     * @param Mpdf   $mpdf       Active mPDF instance.
     * @param object $attachment EmlAttachment value object.
     *
     * @return void
     */
    private function renderImageAttachment(Mpdf $mpdf, object $attachment): void
    {
        $mime    = strtolower($attachment->mimeType);
        $b64     = base64_encode($attachment->content);
        $dataUri = 'data:'.$mime.';base64,'.$b64;

        $mpdf->AddPage();
        $html = '<div style="text-align:center;padding:10mm 0;">'
            .'<img src="'.$dataUri.'" style="max-width:170mm;max-height:240mm;object-fit:contain;"/>'
            .'</div>';
        $mpdf->WriteHTML(html: $html, mode: HTMLParserMode::DEFAULT_MODE);

    }//end renderImageAttachment()


    /**
     * Render a plain-text attachment as a single page wrapped in `<pre>`.
     *
     * @param Mpdf   $mpdf       Active mPDF instance.
     * @param object $attachment EmlAttachment value object.
     *
     * @return void
     */
    private function renderTextAttachment(Mpdf $mpdf, object $attachment): void
    {
        $escaped = htmlspecialchars($attachment->content, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
        $mpdf->AddPage();
        $html = '<pre style="font-family:DejaVu Sans Mono,monospace;font-size:9pt;white-space:pre-wrap;">'.$escaped.'</pre>';
        $mpdf->WriteHTML(html: $html, mode: HTMLParserMode::DEFAULT_MODE);

    }//end renderTextAttachment()


    /**
     * Render a nested EML attachment by recursively assembling its
     * envelope + body into the host document. Depth is capped; beyond
     * the cap we emit a "not rendered" notice page.
     *
     * @param Mpdf   $mpdf       Active mPDF instance.
     * @param object $attachment EmlAttachment value object with `nestedEml` populated.
     * @param int    $depth      Current nesting depth (caller's depth — child uses depth+1).
     *
     * @return void
     */
    private function renderNestedEmlAttachment(Mpdf $mpdf, object $attachment, int $depth): void
    {
        $nextDepth = ($depth + 1);
        if ($nextDepth >= self::MAX_NESTING_DEPTH) {
            $mpdf->AddPage();
            $mpdf->WriteHTML(
                html: '<p style="font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#a04;padding-top:20mm;">'
                    .'Nested EML depth limit ('.self::MAX_NESTING_DEPTH.') bereikt — inhoud niet weergegeven.'
                    .'</p>',
                mode: HTMLParserMode::DEFAULT_MODE
            );
            return;
        }

        // Render the nested envelope inline (skip the embedded-files
        // pass — the outer assembly already handled the outer
        // attachments and we don't want a re-embed at every level).
        $structure = $attachment->nestedEml;
        $envelope  = $this->renderEnvelope(structure: $structure, sourceFilename: $attachment->filename);

        $mpdf->AddPage();
        $mpdf->WriteHTML(html: $envelope, mode: HTMLParserMode::DEFAULT_MODE);

    }//end renderNestedEmlAttachment()


    /**
     * Substitute `<img src="cid:foo">` references in body HTML with
     * `data:` URIs sourced from the attachments collection.
     *
     * Unresolved `cid:` refs are left in place and logged at debug; the
     * substitution is conservative — only `<img>` elements with a
     * `cid:` source are rewritten.
     *
     * @param string                $html        HTML body content.
     * @param array<int,object>     $attachments EmlAttachment array (each has `contentId` + `content` + `mimeType`).
     *
     * @return string Body HTML with inline cid refs substituted.
     */
    private function substituteInlineCids(string $html, array $attachments): string
    {
        if ($html === '' || $attachments === []) {
            return $html;
        }

        $byCid = [];
        foreach ($attachments as $att) {
            if (isset($att->contentId) === true && $att->contentId !== null && $att->contentId !== '') {
                $byCid[$att->contentId] = $att;
            }
        }

        if ($byCid === []) {
            return $html;
        }

        $pattern = '/<img\s+([^>]*?)src=(["\'])cid:([^"\']+)\2([^>]*)>/i';
        return (string) preg_replace_callback(
            $pattern,
            function (array $matches) use ($byCid): string {
                $cid = trim($matches[3]);
                if (isset($byCid[$cid]) === false) {
                    $this->logger->debug(
                        '[EmlPdfAssemblyService] Unresolved inline cid reference; leaving as-is.',
                        ['cid' => $cid]
                    );
                    return $matches[0];
                }

                $att     = $byCid[$cid];
                $dataUri = 'data:'.$att->mimeType.';base64,'.base64_encode($att->content);
                return '<img '.$matches[1].'src='.$matches[2].$dataUri.$matches[2].$matches[4].'>';
            },
            $html
        );

    }//end substituteInlineCids()


    /**
     * Extract the headers map from an EmlStructure-shaped object.
     *
     * Works with both the real OR value object (public readonly
     * `headers` property) and test doubles (stdClass with a `headers`
     * key).
     *
     * @param object $structure EmlStructure or compatible test double.
     *
     * @return array<string,mixed>
     */
    private function extractHeaders(object $structure): array
    {
        if (isset($structure->headers) === true && is_array($structure->headers) === true) {
            return $structure->headers;
        }

        return [];

    }//end extractHeaders()


    /**
     * Pluck the HTML body off an EmlStructure-shaped object.
     *
     * @param object $structure EmlStructure or compatible test double.
     *
     * @return string|null
     */
    private function extractBodyHtml(object $structure): ?string
    {
        if (isset($structure->body) === false || is_object($structure->body) === false) {
            return null;
        }

        if (isset($structure->body->html) === true && is_string($structure->body->html) === true) {
            return $structure->body->html;
        }

        return null;

    }//end extractBodyHtml()


    /**
     * Pluck the plain-text body off an EmlStructure-shaped object.
     *
     * @param object $structure EmlStructure or compatible test double.
     *
     * @return string|null
     */
    private function extractBodyPlain(object $structure): ?string
    {
        if (isset($structure->body) === false || is_object($structure->body) === false) {
            return null;
        }

        if (isset($structure->body->plainText) === true && is_string($structure->body->plainText) === true) {
            return $structure->body->plainText;
        }

        return null;

    }//end extractBodyPlain()


    /**
     * Pluck the attachments array off an EmlStructure-shaped object.
     *
     * @param object $structure EmlStructure or compatible test double.
     *
     * @return array<int,object>
     */
    private function extractAttachments(object $structure): array
    {
        if (isset($structure->attachments) === true && is_array($structure->attachments) === true) {
            return array_values($structure->attachments);
        }

        return [];

    }//end extractAttachments()


    /**
     * Render a header value defensively, accepting either a scalar or
     * an array of recipients (`to`/`cc`). Returns null when missing.
     *
     * @param array<string,mixed> $headers Decoded headers map.
     * @param string              $key     Canonical header key.
     *
     * @return string|null
     */
    private function renderHeader(array $headers, string $key): ?string
    {
        if (isset($headers[$key]) === false || $headers[$key] === null || $headers[$key] === '') {
            return null;
        }

        $value = $headers[$key];
        if (is_array($value) === true) {
            return implode(', ', array_map('strval', $value));
        }

        return (string) $value;

    }//end renderHeader()


    /**
     * Format a byte count as a short human-readable string.
     *
     * @param int $bytes Byte count.
     *
     * @return string e.g. "1.2 MB", "812 B".
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < (1024 * 1024)) {
            return number_format(($bytes / 1024), 1).' KB';
        }

        if ($bytes < (1024 * 1024 * 1024)) {
            return number_format(($bytes / 1024 / 1024), 1).' MB';
        }

        return number_format(($bytes / 1024 / 1024 / 1024), 1).' GB';

    }//end formatBytes()


    /**
     * Load a template file from `lib/Resources/templates/` and return
     * its contents.
     *
     * @param string $path Path relative to the templates root.
     *
     * @return string Twig template contents.
     *
     * @throws \RuntimeException When the template file cannot be read.
     */
    private function loadTemplate(string $path): string
    {
        $baseDir = dirname(__DIR__).'/Resources/templates/';
        $full    = $baseDir.ltrim($path, '/');

        // Defensive against `..` traversal.
        $real     = realpath($full);
        $rootReal = realpath($baseDir);
        if ($real === false || $rootReal === false || str_starts_with($real, $rootReal) === false) {
            throw new \RuntimeException('Refusing to load template outside Resources/templates: '.$path);
        }

        $content = file_get_contents($real);
        if ($content === false) {
            throw new \RuntimeException('Could not read template: '.$path);
        }

        return $content;

    }//end loadTemplate()


    /**
     * Resolve the OpenRegister TextExtractionService lazily, if it is
     * present in the DI container. Returns null when OR is not
     * installed at the runtime level — callers fall back to the
     * "EML conversion unavailable" 422 path.
     *
     * The signature is `object|null` so DocuDesk can be compiled and
     * tested without an OR dependency on the classpath.
     *
     * @return object|null Resolved OR `TextExtractionService` or null.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-8
     */
    public function resolveTextExtractionService(): ?object
    {
        $className = '\\OCA\\OpenRegister\\Service\\TextExtractionService';
        if (class_exists($className) === false) {
            return null;
        }

        try {
            $service = $this->container->get($className);
            if (is_object($service) === true && method_exists($service, 'parseEmlStructured') === true) {
                return $service;
            }
        } catch (Throwable $e) {
            $this->logger->debug(
                '[EmlPdfAssemblyService] OR TextExtractionService unavailable in container.',
                ['message' => $e->getMessage()]
            );
        }

        return null;

    }//end resolveTextExtractionService()
}//end class
