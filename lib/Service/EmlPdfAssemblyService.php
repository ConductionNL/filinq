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
 * This class orchestrates; the work is split across three collaborators in
 * the same namespace: {@see EmlEnvelopeRenderer} (headers + body),
 * {@see EmlAttachmentRenderer} (dividers + attachment pages) and, beneath
 * them, {@see EmlStructureReader} / {@see EmlTemplateLoader}.
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
     * Default value for the max-render-size config key (25 MB).
     */
    private const DEFAULT_MAX_SIZE = 26214400;

    /**
     * Renders the redacted envelope (headers + body) of a structure.
     *
     * @var EmlEnvelopeRenderer
     */
    private readonly EmlEnvelopeRenderer $envelopeRenderer;

    /**
     * Renders dividers and redacted attachment pages.
     *
     * @var EmlAttachmentRenderer
     */
    private readonly EmlAttachmentRenderer $attachmentRenderer;

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
     * The two renderers are injected; the null defaults keep the historical
     * four-argument signature usable, in which case equivalent renderers are
     * built from the same dependencies.
     *
     * @param PdfService                 $pdfService         Shared mPDF/PDF-A configuration.
     * @param TemplateRenderer           $templateRenderer   Sandboxed Twig renderer.
     * @param IAppConfig                 $appConfig          Tenant configuration provider.
     * @param LoggerInterface            $logger             Logger for diagnostics.
     * @param EmlEnvelopeRenderer|null   $envelopeRenderer   Envelope renderer; built from the above when null.
     * @param EmlAttachmentRenderer|null $attachmentRenderer Attachment renderer; built from the above when null.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        TemplateRenderer $templateRenderer,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
        ?EmlEnvelopeRenderer $envelopeRenderer=null,
        ?EmlAttachmentRenderer $attachmentRenderer=null,
    ) {
        $this->envelopeRenderer = ($envelopeRenderer ?? new EmlEnvelopeRenderer(
            $pdfService,
            $templateRenderer,
            $logger
        ));

        $this->attachmentRenderer = ($attachmentRenderer ?? new EmlAttachmentRenderer(
            $pdfService,
            $templateRenderer,
            $appConfig,
            $logger
        ));

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
        $envelopeHtml = $this->envelopeRenderer->render(result: $result, options: $options);

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
            $this->envelopeRenderer->writeMinimal(mpdf: $mpdf, result: $result, options: $options, addPage: false);
        }

        $index = 0;
        foreach ($this->attachmentRenderer->attachmentsOf(result: $result) as $attachment) {
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
     * The guards below are ordered exactly as the decision table in
     * design.md D6: "no anonymiser" wins over "nested EML", which wins over
     * "no redacted bytes".
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
        $meta = $this->attachmentRenderer->metaOf(attachment: $attachment);

        // Unsupported (no anonymiser) → placeholder, no content.
        if ($meta['unsupported'] === true && $meta['nestedEml'] === null) {
            $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: null, variant: 'unsupported');
            return;
        }

        // Nested EML: recurse on the redacted nested result when present;
        // otherwise (depth cap reached / no nested result) a placeholder.
        if ($meta['mimeType'] === 'message/rfc822') {
            $this->renderNestedEml(
                mpdf: $mpdf,
                meta: $meta,
                index: $index,
                options: $options,
                appendPages: $appendPages,
                maxSize: $maxSize
            );
            return;
        }

        // No redacted bytes available → placeholder (unsupported-shaped).
        if (is_string($meta['redacted']) === false) {
            $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: null, variant: 'unsupported');
            return;
        }

        $this->renderRedactedBytes(
            mpdf: $mpdf,
            meta: $meta,
            index: $index,
            options: $options,
            appendPages: $appendPages,
            maxSize: $maxSize
        );

    }//end renderAttachment()

    /**
     * Render a nested `message/rfc822` attachment: divider plus the recursed
     * redacted structure, or a depth-limit placeholder when OR supplied none.
     *
     * @param \Mpdf\Mpdf           $mpdf        Shared mPDF instance.
     * @param array<string, mixed> $meta        Attachment metadata from `EmlAttachmentRenderer::metaOf()`.
     * @param int                  $index       1-based attachment index.
     * @param array<string,mixed>  $options     PDF options.
     * @param bool                 $appendPages Whether to append renderable pages.
     * @param int                  $maxSize     Max render size in bytes.
     *
     * @return void
     */
    private function renderNestedEml(
        \Mpdf\Mpdf $mpdf,
        array $meta,
        int $index,
        array $options,
        bool $appendPages,
        int $maxSize
    ): void {
        if (is_object($meta['nestedEml']) === false) {
            $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: null, variant: 'depth_limit');
            return;
        }

        $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: null, variant: 'default');
        $this->renderStructure(
            mpdf: $mpdf,
            result: $meta['nestedEml'],
            options: $options,
            appendPages: $appendPages,
            maxSize: $maxSize,
            isRoot: false
        );

    }//end renderNestedEml()

    /**
     * Render an attachment that carries redacted bytes: a placeholder when it
     * is oversize or non-renderable, otherwise a divider plus its pages.
     *
     * @param \Mpdf\Mpdf           $mpdf        Shared mPDF instance.
     * @param array<string, mixed> $meta        Attachment metadata from `EmlAttachmentRenderer::metaOf()`.
     * @param int                  $index       1-based attachment index.
     * @param array<string,mixed>  $options     PDF options.
     * @param bool                 $appendPages Whether to append renderable pages.
     * @param int                  $maxSize     Max render size in bytes.
     *
     * @return void
     */
    private function renderRedactedBytes(
        \Mpdf\Mpdf $mpdf,
        array $meta,
        int $index,
        array $options,
        bool $appendPages,
        int $maxSize
    ): void {
        $redacted = (string) $meta['redacted'];
        $size     = strlen($redacted);

        // Append disabled → render nothing for renderable attachments.
        // Placeholders above still appear because they carry no content.
        if ($appendPages === false) {
            return;
        }

        // Oversize → placeholder.
        if ($size > $maxSize) {
            $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: $size, variant: 'too_large');
            return;
        }

        // Non-renderable MIME → placeholder.
        if ($this->attachmentRenderer->isRenderable(mimeType: $meta['mimeType']) === false) {
            $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: $size, variant: 'non_renderable');
            return;
        }

        // Renderable → divider + rendered pages from the REDACTED bytes.
        $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: $size, variant: 'default');

        try {
            $this->attachmentRenderer->renderBytes(
                mpdf: $mpdf,
                bytes: $redacted,
                mimeType: $meta['mimeType'],
                filename: $meta['filename'],
                options: $options
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlPdfAssemblyService] Attachment render failed; placeholder used',
                [
                    'index'     => $index,
                    'mimeType'  => $meta['mimeType'],
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            $this->writePlaceholder(mpdf: $mpdf, meta: $meta, index: $index, size: $size, variant: 'render_failed');
        }//end try

    }//end renderRedactedBytes()

    /**
     * Write one divider/placeholder page for an attachment.
     *
     * @param \Mpdf\Mpdf           $mpdf    Shared mPDF instance.
     * @param array<string, mixed> $meta    Attachment metadata from `EmlAttachmentRenderer::metaOf()`.
     * @param int                  $index   1-based attachment index.
     * @param int|null             $size    Size in bytes, or null.
     * @param string               $variant Divider variant.
     *
     * @return void
     */
    private function writePlaceholder(\Mpdf\Mpdf $mpdf, array $meta, int $index, ?int $size, string $variant): void
    {
        $this->attachmentRenderer->writeDivider(
            mpdf: $mpdf,
            index: $index,
            filename: $meta['filename'],
            mimeType: $meta['mimeType'],
            size: $size,
            variant: $variant
        );

    }//end writePlaceholder()

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

        $subject = $this->envelopeRenderer->subjectOf(result: $result);
        if ($subject !== '') {
            return $subject;
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
}//end class
