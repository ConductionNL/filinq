<?php

/**
 * EML PDF Assembly Service
 *
 * Assembles a rich PDF/A-3b from a structured EML (email) file. Consumes
 * OR's parseEmlStructured API to obtain headers, body parts, and
 * attachment metadata, then builds a multi-part PDF containing:
 *   - A header block (Van/Aan/Cc/Onderwerp/Datum)
 *   - The email body (HTML preferred; plain-text fallback; placeholder when absent)
 *   - Every attachment embedded as a PDF/A-3 file attachment
 *   - Renderable attachments additionally rendered as appended pages
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-3
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-4
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-5
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-6
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-7
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-9
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use OCA\DocuDesk\Exception\ConversionFailedException;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

/**
 * Orchestrates multi-pass mPDF assembly for EML → PDF/A-3b.
 *
 * The assembly flow:
 *   1. Call OR TextExtractionService::parseEmlStructured
 *   2. Render envelope HTML via Twig template
 *   3. Resolve cid: inline images in HTML body
 *   4. Configure mPDF for PDF/A-3b and write envelope HTML
 *   5. For each attachment:
 *      a. Embed bytes as PDF/A-3 file attachment
 *      b. If renderable + append_pages enabled + within size cap:
 *         write divider page + rendered attachment pages
 *   6. Return mPDF output as string → write beside source in NC Files
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://conduction.nl
 *
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
 */
class EmlPdfAssemblyService
{


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';


    /**
     * Config key: whether to append rendered attachment pages (default true).
     */
    private const CFG_APPEND_PAGES = 'docudesk.conversion.eml.append_attachment_pages';


    /**
     * Config key: per-attachment render size cap in bytes (default 25 MB).
     */
    private const CFG_MAX_RENDER_BYTES = 'docudesk.conversion.eml.max_attachment_render_size_bytes';


    /**
     * Config key: optional divider template override.
     */
    private const CFG_DIVIDER_TEMPLATE = 'docudesk.conversion.eml.divider_template';


    /**
     * Default maximum attachment render size: 25 MB.
     */
    private const DEFAULT_MAX_RENDER_BYTES = 26214400;


    /**
     * MIME types considered "renderable" as pages (besides EML recursion and Word docs).
     *
     * @var array<string, true>
     */
    private const RENDERABLE_MIMES = [
        'application/pdf'                                                         => true,
        'image/png'                                                               => true,
        'image/jpeg'                                                              => true,
        'image/jpg'                                                               => true,
        'image/gif'                                                               => true,
        'image/webp'                                                              => true,
        'text/plain'                                                              => true,
        'text/csv'                                                                => true,
        'text/markdown'                                                           => true,
        'message/rfc822'                                                          => true,
        'application/msword'                                                      => true,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
        'application/vnd.oasis.opendocument.text'                                 => true,
        'application/rtf'                                                         => true,
        'text/rtf'                                                                => true,
        'text/html'                                                               => true,
    ];


    /**
     * Word-family extensions handled by PhpWord in the cascade.
     *
     * @var array<string, true>
     */
    private const PHPWORD_EXTENSIONS = [
        'doc'  => true,
        'docx' => true,
        'odt'  => true,
        'rtf'  => true,
        'html' => true,
        'htm'  => true,
    ];

    /**
     * Constructor.
     *
     * @param PdfService      $pdfService PdfService for rendering HTML to PDF (PDF/A-3b config).
     * @param IAppConfig      $appConfig  Tenant configuration provider.
     * @param LoggerInterface $logger     Logger for diagnostics.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()

    /**
     * Assemble a PDF/A-3b from an EML source file.
     *
     * Main entry point called by EmlBackend::convert(). Calls OR's
     * parseEmlStructured, builds envelope HTML, creates mPDF in PDF/A-3b
     * mode, embeds attachments, appends rendered pages.
     *
     * @param File        $sourceFile     Source EML file node.
     * @param string|null $sourceFilename Optional filename override for output naming.
     *
     * @return File Newly written PDF file node beside the source.
     *
     * @throws ConversionFailedException When the assembly cannot produce any output.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-9
     */
    public function assemble(File $sourceFile, ?string $sourceFilename=null): File
    {
        $name     = $sourceFilename ?? $sourceFile->getName();
        $mpdf     = null;
        $emlBytes = '';

        try {
            $emlBytes = $sourceFile->getContent();
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'EmlPdfAssemblyService: could not read source EML bytes.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'getContent failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }

        // Attempt structured parse via OR.
        $structure = null;
        $flatText  = '';
        try {
            $structure = $this->parseEmlStructured(emlContent: $emlBytes);
        } catch (Throwable $parseEx) {
            // D8: fall back to flat-text extraction.
            $this->logger->warning(
                '[EmlPdfAssemblyService] parseEmlStructured failed, falling back to flat text.',
                [
                    'source'    => $sourceFile->getPath(),
                    'exception' => get_class($parseEx),
                    'message'   => $parseEx->getMessage(),
                ]
            );
            $flatText = $this->extractFlatText(emlContent: $emlBytes);
        }

        try {
            $mpdf = $this->createMpdf();
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'EmlPdfAssemblyService: could not create mPDF instance.',
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
        }

        if ($structure === null) {
            // Flat-text fallback: single page, no embeds.
            $html = $this->wrapPlainText(text: $flatText);
            $mpdf->WriteHTML(html: $html);
        } else {
            $this->assembleFull(mpdf: $mpdf, structure: $structure);
        }

        $pdfBytes = $mpdf->Output(name: '', dest: \Mpdf\Output\Destination::STRING_RETURN);
        if ($pdfBytes === '') {
            throw new ConversionFailedException(
                message: 'EmlPdfAssemblyService: mPDF produced empty output.',
                attempts: [
                    [
                        'name'      => 'eml',
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'empty PDF output',
                    ],
                ]
            );
        }

        $parent     = $sourceFile->getParent();
        $outputName = $this->stripExtension(name: $name).'.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        return $parent->newFile($outputName, $pdfBytes);

    }//end assemble()

    /**
     * Perform full assembly given a structured EML result.
     *
     * @param Mpdf  $mpdf      mPDF instance (PDF/A-3b mode).
     * @param array $structure EML structure returned by parseEmlStructured.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-5
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-6
     */
    private function assembleFull(Mpdf $mpdf, array $structure): void
    {
        // Render envelope and write it.
        $envelopeHtml = $this->renderEnvelope(structure: $structure);
        $mpdf->WriteHTML(html: $envelopeHtml);

        $appendPages     = $this->isAppendPagesEnabled();
        $maxRenderBytes  = $this->getMaxRenderBytes();
        $dividerTemplate = $this->getDividerTemplate();
        $failedEmbeds    = [];

        $attachments = $structure['attachments'] ?? [];

        foreach ($attachments as $index => $attachment) {
            $attachIndex  = ($index + 1);
            $attFilename  = $attachment['filename'] ?? ('attachment-'.$attachIndex);
            $attMimeType  = $attachment['mimeType'] ?? 'application/octet-stream';
            $attContent   = $attachment['content'] ?? '';
            $attSize      = strlen($attContent);
            $attContentId = $attachment['contentId'] ?? null;
            $nestedEml    = $attachment['nestedEml'] ?? null;

            // Always embed bytes as PDF/A-3 file attachment.
            $this->embedAttachment(
                mpdf: $mpdf,
                filename: $attFilename,
                mimeType: $attMimeType,
                content: $attContent,
                failedEmbeds: $failedEmbeds
            );

            if ($appendPages === false) {
                // Tenant disabled rendering — embed only.
                continue;
            }

            // Render attachment pages when renderable + within size cap.
            $renderable = $this->isRenderable(mimeType: $attMimeType, filename: $attFilename);

            if ($renderable === false) {
                // Non-renderable: add divider with "not rendered" notice.
                $mpdf->AddPage();
                $dividerHtml = $this->renderDivider(
                    index: $attachIndex,
                    filename: $attFilename,
                    mimeType: $attMimeType,
                    size: $attSize,
                    template: $dividerTemplate,
                    notice: 'niet weergegeven; zie ingebed bestand'
                );
                $mpdf->WriteHTML(html: $dividerHtml);
                continue;
            }

            if ($attSize > $maxRenderBytes) {
                // Too large to render — add "too large" divider.
                $mpdf->AddPage();
                $dividerHtml = $this->renderDivider(
                    index: $attachIndex,
                    filename: $attFilename,
                    mimeType: $attMimeType,
                    size: $attSize,
                    template: $dividerTemplate,
                    notice: 'te groot om weer te geven; zie ingebed bestand'
                );
                $mpdf->WriteHTML(html: $dividerHtml);
                continue;
            }

            // Add divider page before attachment pages.
            $mpdf->AddPage();
            $dividerHtml = $this->renderDivider(
                index: $attachIndex,
                filename: $attFilename,
                mimeType: $attMimeType,
                size: $attSize,
                template: $dividerTemplate,
                notice: null
            );
            $mpdf->WriteHTML(html: $dividerHtml);

            // Render the attachment itself.
            try {
                $this->renderAttachmentPages(
                    mpdf: $mpdf,
                    mimeType: $attMimeType,
                    filename: $attFilename,
                    content: $attContent,
                    nestedEml: $nestedEml,
                    depth: 1
                );
            } catch (Throwable $e) {
                // D8: render failure → divider with "could not render" notice, but embed is already done.
                $this->logger->warning(
                    '[EmlPdfAssemblyService] Attachment render failed; keeping embed only.',
                    [
                        'filename'  => $attFilename,
                        'exception' => get_class($e),
                        'message'   => $e->getMessage(),
                    ]
                );
                $mpdf->AddPage();
                $fallbackHtml = $this->renderDivider(
                    index: $attachIndex,
                    filename: $attFilename,
                    mimeType: $attMimeType,
                    size: $attSize,
                    template: $dividerTemplate,
                    notice: 'kon niet worden weergegeven; zie ingebed bestand'
                );
                $mpdf->WriteHTML(html: $fallbackHtml);
            }//end try
        }//end foreach

        // D8: footer notice for failed embeds.
        if (empty($failedEmbeds) === false) {
            $list = implode(', ', $failedEmbeds);
            $this->logger->error(
                '[EmlPdfAssemblyService] Some attachments could not be embedded: '.$list
            );
        }

    }//end assembleFull()

    /**
     * Render attachment pages into mpdf based on type.
     *
     * @param Mpdf       $mpdf      mPDF instance.
     * @param string     $mimeType  Attachment MIME type.
     * @param string     $filename  Attachment filename.
     * @param string     $content   Raw attachment bytes.
     * @param array|null $nestedEml Nested EML structure (for message/rfc822).
     * @param int        $depth     Current nesting depth (1-indexed).
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-7
     */
    private function renderAttachmentPages(
        Mpdf $mpdf,
        string $mimeType,
        string $filename,
        string $content,
        ?array $nestedEml,
        int $depth=1
    ): void {
        if ($mimeType === 'application/pdf') {
            $this->importPdfPages(mpdf: $mpdf, pdfBytes: $content);
            return;
        }

        if (str_starts_with($mimeType, 'image/') === true) {
            $this->renderImagePage(mpdf: $mpdf, mimeType: $mimeType, content: $content);
            return;
        }

        if ($mimeType === 'message/rfc822') {
            if ($nestedEml !== null && $depth < 3) {
                $this->assembleFull(mpdf: $mpdf, structure: $nestedEml);
            } else {
                // Depth limit or no nestedEml (D5).
                $mpdf->AddPage();
                $notice = $this->wrapPlainText(
                    text: '(genest e-mail, niet weergegeven — diepte-limiet)'
                );
                $mpdf->WriteHTML(html: $notice);
            }

            return;
        }

        // Word-family documents: render via PdfService (PhpWord path)
        // then import resulting pages.
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (isset(self::PHPWORD_EXTENSIONS[$ext]) === true
            || $this->isWordMime(mimeType: $mimeType) === true
        ) {
            $this->renderWordDocument(mpdf: $mpdf, content: $content, extension: $ext, filename: $filename);
            return;
        }

        // Plain-text and other text types.
        $html = $this->wrapPlainText(text: $content);
        $mpdf->WriteHTML(html: $html);

    }//end renderAttachmentPages()

    /**
     * Import pages from a PDF byte string into the mpdf document.
     *
     * Uses mPDF's setSourceFile + importPage + useTemplate methods.
     *
     * @param Mpdf   $mpdf     mPDF instance.
     * @param string $pdfBytes PDF binary content.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-7
     */
    private function importPdfPages(Mpdf $mpdf, string $pdfBytes): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'eml_attach_').'_source.pdf';
        file_put_contents($tmpFile, $pdfBytes);

        try {
            $pageCount = $mpdf->setSourceFile(filename: $tmpFile);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $mpdf->importPage(page: $i);
                $mpdf->AddPage();
                $mpdf->useTemplate(tpl: $tplId);
            }
        } finally {
            if (file_exists($tmpFile) === true) {
                unlink($tmpFile);
            }
        }

    }//end importPdfPages()

    /**
     * Render an image attachment as a single page.
     *
     * @param Mpdf   $mpdf     mPDF instance.
     * @param string $mimeType Image MIME type.
     * @param string $content  Raw image bytes.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-7
     */
    private function renderImagePage(Mpdf $mpdf, string $mimeType, string $content): void
    {
        $b64  = base64_encode($content);
        $html = sprintf(
            '<html><body style="margin:0;padding:0;">'
            .'<img src="data:%s;base64,%s" style="max-width:100%%;max-height:100%%;'
            .'display:block;margin:auto;" />'
            .'</body></html>',
            htmlspecialchars($mimeType, ENT_QUOTES, 'UTF-8'),
            $b64
        );
        $mpdf->AddPage();
        $mpdf->WriteHTML(html: $html);

    }//end renderImagePage()

    /**
     * Render a Word-family document as pages by converting to PDF first.
     *
     * Uses PhpWord → HTML → mPDF path for consistency with the cascade.
     * Falls back to "could not render" if PhpWord is unavailable.
     *
     * @param Mpdf   $mpdf      mPDF instance.
     * @param string $content   Raw document bytes.
     * @param string $extension Lowercased file extension.
     * @param string $filename  Original filename for logging.
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-7
     */
    private function renderWordDocument(Mpdf $mpdf, string $content, string $extension, string $filename): void
    {
        if (class_exists('\PhpOffice\PhpWord\IOFactory') === false) {
            $mpdf->AddPage();
            $mpdf->WriteHTML(html: $this->wrapPlainText(text: '(Word-document kan niet worden weergegeven: PhpWord niet beschikbaar)'));
            return;
        }

        $readerMap  = [
            'doc'  => 'MsDoc',
            'docx' => 'Word2007',
            'odt'  => 'ODText',
            'rtf'  => 'RTF',
            'html' => 'HTML',
            'htm'  => 'HTML',
        ];
        $readerName = $readerMap[$extension] ?? null;
        if ($readerName === null) {
            $mpdf->AddPage();
            $mpdf->WriteHTML(html: $this->wrapPlainText(text: '(Word-document kan niet worden weergegeven: onbekend formaat)'));
            return;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'eml_word_').'.'.$extension;
        file_put_contents($tmpFile, $content);

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($tmpFile, $readerName);
            $writer  = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
            $html    = $writer->getContent();
            // Strip @page rules to avoid mPDF page-size degeneration.
            $html     = (string) preg_replace('/@page[^{]*\{[^}]*\}/s', '', (string) $html);
            $pdfBytes = $this->pdfService->generatePdfFromHtml(html: $html, options: ['pdfa' => true]);
            $this->importPdfPages(mpdf: $mpdf, pdfBytes: $pdfBytes);
        } finally {
            if (file_exists($tmpFile) === true) {
                unlink($tmpFile);
            }
        }

    }//end renderWordDocument()

    /**
     * Embed attachment bytes as a PDF/A-3 file attachment annotation.
     *
     * @param Mpdf     $mpdf         mPDF instance.
     * @param string   $filename     Attachment filename.
     * @param string   $mimeType     MIME type.
     * @param string   $content      Raw bytes.
     * @param string[] $failedEmbeds Accumulator for embed failures (passed by ref).
     *
     * @return void
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-6
     */
    private function embedAttachment(
        Mpdf $mpdf,
        string $filename,
        string $mimeType,
        string $content,
        array &$failedEmbeds
    ): void {
        if ($content === '') {
            return;
        }

        try {
            // MPDF's PDF/A-3 file embedding API.
            $mpdf->Annotation(
                txt: $filename,
                x: 0,
                y: 0,
                icon: 'Attachment',
                author: 'DocuDesk',
                subject: $filename,
                opacity: 0,
                popup: '',
                file: $content,
                is_file_annot: true
            );
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlPdfAssemblyService] Could not embed attachment',
                [
                    'filename'  => $filename,
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            $failedEmbeds[] = $filename;
        }//end try

    }//end embedAttachment()

    /**
     * Render the envelope Twig template to HTML.
     *
     * @param array $structure EML structure from parseEmlStructured.
     *
     * @return string HTML for the envelope page.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-3
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-4
     */
    private function renderEnvelope(array $structure): string
    {
        $headers = $structure['headers'] ?? [];
        $body    = $structure['body'] ?? [];

        $from = $headers['from'] ?? '';

        $rawTo = $headers['to'] ?? null;
        if (is_array($rawTo) === true) {
            $to = implode(', ', $rawTo);
        } else {
            $to = (string) ($rawTo ?? '');
        }

        $rawCc = $headers['cc'] ?? null;
        if (is_array($rawCc) === true) {
            $cc = implode(', ', $rawCc);
        } else {
            $cc = (string) ($rawCc ?? '');
        }

        $subject = $headers['subject'] ?? '';
        $date    = $headers['date'] ?? null;
        if ($date !== null) {
            if (is_int($date) === true) {
                $ts = $date;
            } else {
                $ts = strtotime((string) $date);
            }

            if ($ts !== false) {
                $dateStr = date('Y-m-d H:i', $ts);
            } else {
                $dateStr = '(onbekend)';
            }
        } else {
            $dateStr = '(onbekend)';
        }

        $htmlBody  = $body['html'] ?? null;
        $plainBody = $body['plainText'] ?? null;

        if ($htmlBody !== null && $htmlBody !== '') {
            // Resolve cid: references before rendering.
            $resolvedBody = $this->resolveCidReferences(
                html: $htmlBody,
                attachments: $structure['attachments'] ?? []
            );
            $bodySection  = $resolvedBody;
        } else if ($plainBody !== null && $plainBody !== '') {
            $escaped     = htmlspecialchars($plainBody, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
            $bodySection = '<pre style="white-space:pre-wrap;font-family:monospace;">'.$escaped.'</pre>';
        } else {
            $bodySection = '<p><em>(Bericht zonder body — alleen bijlagen)</em></p>';
        }

        try {
            $twig     = $this->createTwig();
            $template = $twig->load('eml/email_envelope.twig');
            return $template->render(
                [
                    'from'    => $from,
                    'to'      => $to,
                    'cc'      => $cc,
                    'subject' => $subject,
                    'date'    => $dateStr,
                    'body'    => $bodySection,
                    'show_cc' => $cc !== '',
                ]
            );
        } catch (Throwable $e) {
            // D8: Twig render failure → minimal envelope.
            $this->logger->error(
                '[EmlPdfAssemblyService] Twig envelope render failed; using minimal fallback.',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            return $this->minimalEnvelope(
                from: $from,
                to: $to,
                subject: $subject,
                date: $dateStr,
                bodySection: $bodySection
            );
        }//end try

    }//end renderEnvelope()

    /**
     * Render a divider page HTML.
     *
     * @param int         $index    1-based attachment index.
     * @param string      $filename Attachment filename.
     * @param string      $mimeType Attachment MIME type.
     * @param int         $size     Attachment size in bytes.
     * @param string      $template Twig template name.
     * @param string|null $notice   Optional notice text (e.g. "te groot om weer te geven").
     *
     * @return string HTML for the divider page.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-3
     */
    private function renderDivider(
        int $index,
        string $filename,
        string $mimeType,
        int $size,
        string $template,
        ?string $notice
    ): string {
        try {
            $twig = $this->createTwig();
            $tmpl = $twig->load($template);
            return $tmpl->render(
                [
                    'index'    => $index,
                    'filename' => $filename,
                    'mimeType' => $mimeType,
                    'size'     => $size,
                    'notice'   => $notice,
                ]
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Divider template render failed; using fallback HTML.',
                ['template' => $template, 'exception' => get_class($e)]
            );
            return $this->minimalDivider(
                index: $index,
                filename: $filename,
                mimeType: $mimeType,
                size: $size,
                notice: $notice
            );
        }//end try

    }//end renderDivider()

    /**
     * Resolve cid: inline image references in HTML body.
     *
     * Substitutes `<img src="cid:ID">` with `data:<mime>;base64,<encoded>`
     * using attachment contentId lookup. Unresolved refs are left as-is.
     *
     * @param string $html        HTML body containing potential cid: refs.
     * @param array  $attachments Attachment list from EML structure.
     *
     * @return string HTML with resolved cid: references.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-4
     */
    public function resolveCidReferences(string $html, array $attachments): string
    {
        // Build lookup by contentId.
        $cidIndex = [];
        foreach ($attachments as $att) {
            $cid = $att['contentId'] ?? null;
            if ($cid !== null && $cid !== '') {
                $cidIndex[$cid] = $att;
            }
        }

        if (empty($cidIndex) === true) {
            return $html;
        }

        // Match src="cid:..." and src='cid:...' variants.
        $pattern = '/<img(\s[^>]*)?\ssrc=(["\'])cid:([^"\']+)\2/i';

        return (string) preg_replace_callback(
            $pattern,
            function (array $m) use ($cidIndex): string {
                $attrs    = $m[1] ?? '';
                $quote    = $m[2];
                $cidValue = $m[3];

                $att = $cidIndex[$cidValue] ?? null;
                if ($att === null) {
                    $this->logger->debug(
                        '[EmlPdfAssemblyService] Unresolved cid reference: '.$cidValue
                    );
                    return $m[0];
                }

                $mime    = $att['mimeType'] ?? 'application/octet-stream';
                $content = $att['content'] ?? '';
                $b64     = base64_encode($content);
                return '<img'.$attrs.' src='.$quote.'data:'.$mime.';base64,'.$b64.$quote;
            },
            $html
        );

    }//end resolveCidReferences()

    /**
     * Call OR's TextExtractionService::parseEmlStructured.
     *
     * Returns a structured array with keys: headers, body, attachments.
     *
     * @param string $emlContent Raw EML bytes.
     *
     * @return array Structured EML data.
     *
     * @throws \RuntimeException When OR's method is unavailable or throws.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-1
     */
    private function parseEmlStructured(string $emlContent): array
    {
        if (class_exists('\OCA\OpenRegister\Service\TextExtractionService') === false
            || method_exists('\OCA\OpenRegister\Service\TextExtractionService', 'parseEmlStructured') === false
        ) {
            throw new \RuntimeException('OR TextExtractionService::parseEmlStructured not available.');
        }

        // Lazy instantiate OR service via \OC::$server if available,
        // otherwise throw (service-not-registered case).
        $orService = $this->resolveOrTextExtractionService();
        return $orService->parseEmlStructured(emlContent: $emlContent);

    }//end parseEmlStructured()

    /**
     * Resolve OR's TextExtractionService via Nextcloud's server container.
     *
     * @return object OR TextExtractionService instance.
     *
     * @throws \RuntimeException When the service cannot be resolved.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-1
     */
    private function resolveOrTextExtractionService(): object
    {
        if (class_exists('\OC') === false || property_exists('\OC', 'server') === false) {
            throw new \RuntimeException('Nextcloud server container not available.');
        }

        try {
            return \OC::$server->get('\OCA\OpenRegister\Service\TextExtractionService');
        } catch (Throwable $e) {
            throw new \RuntimeException(
                'Could not resolve OR TextExtractionService: '.$e->getMessage(),
                0,
                $e
            );
        }

    }//end resolveOrTextExtractionService()

    /**
     * Extract flat text from EML as a fallback when structured parse fails.
     *
     * @param string $emlContent Raw EML bytes.
     *
     * @return string Plain text extracted from the EML (may be empty).
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-9
     */
    private function extractFlatText(string $emlContent): string
    {
        // Try OR's flat extractEml if available, else naive extraction.
        if (class_exists('\OCA\OpenRegister\Service\TextExtractionService') === true
            && method_exists('\OCA\OpenRegister\Service\TextExtractionService', 'extractEml') === true
        ) {
            try {
                $orService = $this->resolveOrTextExtractionService();
                return (string) $orService->extractEml(emlContent: $emlContent);
            } catch (Throwable $e) {
                $this->logger->warning(
                    '[EmlPdfAssemblyService] extractEml also failed.',
                    ['exception' => get_class($e), 'message' => $e->getMessage()]
                );
            }
        }

        // Naive: strip headers and return body lines.
        $lines     = explode("\n", $emlContent);
        $inBody    = false;
        $bodyLines = [];
        foreach ($lines as $line) {
            if ($inBody === true) {
                $bodyLines[] = $line;
            } else if (trim($line) === '') {
                $inBody = true;
            }
        }

        return implode("\n", $bodyLines);

    }//end extractFlatText()

    /**
     * Create an mPDF instance configured for PDF/A-3b.
     *
     * @return Mpdf Configured mPDF instance.
     *
     * @throws MpdfException On mPDF initialisation failure.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-5
     */
    public function createMpdf(): Mpdf
    {
        $tempDir = '/tmp/mpdf';
        if (is_dir($tempDir) === false) {
            mkdir($tempDir, 0777, true);
        }

        $config = [
            'tempDir'  => $tempDir,
            'format'   => 'A4',
            'PDFA'     => true,
            'PDFAauto' => true,
        ];

        // Use bundled DejaVu fonts when available (same as PdfService).
        $fontDir = dirname(__DIR__).'/Fonts';
        if (is_dir($fontDir) === true) {
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
        $mpdf->SetAuthor('DocuDesk');
        $mpdf->SetCreator('DocuDesk EML PDF Assembly');
        return $mpdf;

    }//end createMpdf()

    /**
     * Create a Twig environment loading templates from lib/Resources/templates.
     *
     * @return TwigEnvironment
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-3
     */
    private function createTwig(): TwigEnvironment
    {
        $templateDir = dirname(__DIR__).'/Resources/templates';
        $loader      = new FilesystemLoader($templateDir);
        return new TwigEnvironment($loader, ['autoescape' => false]);

    }//end createTwig()

    /**
     * Whether a given MIME type / filename is renderable as pages.
     *
     * @param string $mimeType Attachment MIME type.
     * @param string $filename Attachment filename.
     *
     * @return bool True when renderable.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-6
     */
    public function isRenderable(string $mimeType, string $filename): bool
    {
        if (isset(self::RENDERABLE_MIMES[$mimeType]) === true) {
            return true;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (isset(self::PHPWORD_EXTENSIONS[$ext]) === true) {
            return true;
        }

        return false;

    }//end isRenderable()

    /**
     * Whether a MIME type is a Word-family document handled by PhpWord.
     *
     * @param string $mimeType MIME type to check.
     *
     * @return bool
     */
    private function isWordMime(string $mimeType): bool
    {
        $wordMimes = [
            'application/msword'                                                      => true,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
            'application/vnd.oasis.opendocument.text'                                 => true,
            'application/rtf'                                                         => true,
            'text/rtf'                                                                => true,
            'text/html'                                                               => true,
            'application/xhtml+xml'                                                   => true,
        ];
        return isset($wordMimes[$mimeType]);

    }//end isWordMime()

    /**
     * Build a minimal envelope HTML as Twig fallback.
     *
     * @param string $from        Sender.
     * @param string $to          Recipient(s).
     * @param string $subject     Subject.
     * @param string $date        Formatted date.
     * @param string $bodySection Pre-rendered body HTML.
     *
     * @return string HTML envelope.
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-9
     */
    private function minimalEnvelope(
        string $from,
        string $to,
        string $subject,
        string $date,
        string $bodySection
    ): string {
        $esc = static function (string $s): string {
            return htmlspecialchars($s, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
        };
        return sprintf(
            '<html><body><table style="border-bottom:1px solid #999;margin-bottom:1em;width:100%%">'
            .'<tr><td><strong>Van:</strong></td><td>%s</td></tr>'
            .'<tr><td><strong>Aan:</strong></td><td>%s</td></tr>'
            .'<tr><td><strong>Onderwerp:</strong></td><td>%s</td></tr>'
            .'<tr><td><strong>Datum:</strong></td><td>%s</td></tr>'
            .'</table>'
            .'<p><em>(template rendering failed)</em></p>'
            .'%s</body></html>',
            $esc($from),
            $esc($to),
            $esc($subject),
            $esc($date),
            $bodySection
        );

    }//end minimalEnvelope()

    /**
     * Build a minimal divider HTML as Twig fallback.
     *
     * @param int         $index    1-based index.
     * @param string      $filename Filename.
     * @param string      $mimeType MIME type.
     * @param int         $size     Bytes.
     * @param string|null $notice   Optional notice.
     *
     * @return string HTML divider.
     */
    private function minimalDivider(
        int $index,
        string $filename,
        string $mimeType,
        int $size,
        ?string $notice
    ): string {
        $esc = static function (string $s): string {
            return htmlspecialchars($s, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
        };
        if ($notice !== null) {
            $noticePart = '<br/><em>'.$esc($notice).'</em>';
        } else {
            $noticePart = '';
        }

        $line = sprintf(
            '<hr/><p><strong>Bijlage %d: %s</strong><br/>%s &mdash; %d bytes%s</p><hr/>',
            $index,
            $esc($filename),
            $esc($mimeType),
            $size,
            $noticePart
        );
        return '<html><body>'.$line.'</body></html>';

    }//end minimalDivider()

    /**
     * Wrap plain text in a minimal HTML envelope with <pre>.
     *
     * @param string $text UTF-8 plain text.
     *
     * @return string HTML document.
     */
    private function wrapPlainText(string $text): string
    {
        $escaped = htmlspecialchars($text, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
        return '<html><body><pre style="white-space:pre-wrap;font-family:monospace;">'
            .$escaped.'</pre></body></html>';

    }//end wrapPlainText()

    /**
     * Strip the extension from a filename.
     *
     * @param string $name Filename with extension.
     *
     * @return string Filename without extension.
     */
    private function stripExtension(string $name): string
    {
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            return $name;
        }

        return substr($name, 0, $dotPos);

    }//end stripExtension()

    /**
     * Read the append_attachment_pages config (default true).
     *
     * @return bool
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
     */
    private function isAppendPagesEnabled(): bool
    {
        $value = $this->appConfig->getValueString(
            app: self::APP_ID,
            key: self::CFG_APPEND_PAGES,
            default: 'true'
        );
        return $value !== 'false';

    }//end isAppendPagesEnabled()

    /**
     * Read the max_attachment_render_size_bytes config (default 25 MB).
     *
     * @return int
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
     */
    private function getMaxRenderBytes(): int
    {
        $value = $this->appConfig->getValueString(
            app: self::APP_ID,
            key: self::CFG_MAX_RENDER_BYTES,
            default: (string) self::DEFAULT_MAX_RENDER_BYTES
        );
        $int   = (int) $value;
        if ($int > 0) {
            return $int;
        }

        return self::DEFAULT_MAX_RENDER_BYTES;

    }//end getMaxRenderBytes()

    /**
     * Read the divider template path config (default eml/divider.twig).
     *
     * @return string
     *
     * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-2
     */
    private function getDividerTemplate(): string
    {
        return $this->appConfig->getValueString(
            app: self::APP_ID,
            key: self::CFG_DIVIDER_TEMPLATE,
            default: 'eml/divider.twig'
        );

    }//end getDividerTemplate()
}//end class
