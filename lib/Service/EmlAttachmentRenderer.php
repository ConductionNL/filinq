<?php

/**
 * EML Attachment Renderer
 *
 * Renders one redacted EML attachment into the shared mPDF document: the
 * divider/placeholder page, and — when the attachment is renderable — the
 * redacted bytes themselves (PDF via FPDI import, images inline as data URIs,
 * text in a `<pre>` block, Word-family via PhpWord's HTML writer).
 *
 * Extracted from {@see EmlPdfAssemblyService}, which now only orchestrates and
 * owns the nested-EML recursion. NO original bytes are ever embedded and NO
 * bytes are attached to the PDF as PDF/A-3 file attachments — only OR's
 * already-redacted bytes are rendered, as pages.
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

use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Renders redacted EML attachments as pages of the shared mPDF document.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlAttachmentRenderer
{

    /**
     * App identifier used for IAppConfig reads.
     */
    private const APP_ID = 'docudesk';

    /**
     * Config key: optional override for the divider/placeholder template.
     * Default `eml/divider.twig`.
     */
    private const KEY_DIVIDER_TEMPLATE = 'docudesk.conversion.eml.divider_template';

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
     * Renderable Word-family MIME types — routed through PhpWord's HTML
     * writer (the same engine `PhpWordBackend` uses).
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
     * Sandboxed loader for the bundled divider template.
     *
     * @var EmlTemplateLoader
     */
    private readonly EmlTemplateLoader $templateLoader;

    /**
     * Reader for OR's redacted EML value objects.
     *
     * @var EmlStructureReader
     */
    private readonly EmlStructureReader $structureReader;

    /**
     * Injectable seam around PhpWord's readers and HTML writer.
     *
     * @var PhpWordHtmlRenderer
     */
    private readonly PhpWordHtmlRenderer $wordRenderer;

    /**
     * Constructor.
     *
     * The three leaf helpers are dependency-free; they default-construct so
     * the renderer stays usable without explicit wiring, and remain injectable
     * for tests and for Nextcloud's autowiring.
     *
     * @param PdfService               $pdfService       Shared mPDF/PDF-A configuration.
     * @param TemplateRenderer         $templateRenderer Sandboxed Twig renderer.
     * @param IAppConfig               $appConfig        Tenant configuration provider.
     * @param LoggerInterface          $logger           Logger for diagnostics.
     * @param EmlTemplateLoader|null   $templateLoader   Bundled-template loader.
     * @param EmlStructureReader|null  $structureReader  OR value-object reader.
     * @param PhpWordHtmlRenderer|null $wordRenderer     Word-family HTML renderer.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly TemplateRenderer $templateRenderer,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
        ?EmlTemplateLoader $templateLoader=null,
        ?EmlStructureReader $structureReader=null,
        ?PhpWordHtmlRenderer $wordRenderer=null,
    ) {
        $this->templateLoader  = ($templateLoader ?? new EmlTemplateLoader());
        $this->structureReader = ($structureReader ?? new EmlStructureReader());
        $this->wordRenderer    = ($wordRenderer ?? new PhpWordHtmlRenderer());

    }//end __construct()

    /**
     * List the attachment value objects of a redacted structure.
     *
     * @param object $result AnonymisedEmlStructure.
     *
     * @return array<int, object> Attachment value objects.
     */
    public function attachmentsOf(object $result): array
    {
        return $this->structureReader->attachments(result: $result);

    }//end attachmentsOf()

    /**
     * Read the fields the assembler decides on from one attachment.
     *
     * @param object $attachment AnonymisedEmlAttachment.
     *
     * @return array{filename: string, mimeType: string, unsupported: bool, redacted: mixed, nestedEml: mixed} Attachment metadata.
     */
    public function metaOf(object $attachment): array
    {
        return [
            'filename'    => (string) $this->structureReader->prop(
                obj: $attachment,
                name: 'filename',
                default: '(onbekend)'
            ),
            'mimeType'    => strtolower(
                (string) $this->structureReader->prop(
                    obj: $attachment,
                    name: 'mimeType',
                    default: 'application/octet-stream'
                )
            ),
            'unsupported' => (bool) $this->structureReader->prop(
                obj: $attachment,
                name: 'unsupported',
                default: false
            ),
            'redacted'    => $this->structureReader->prop(obj: $attachment, name: 'redactedContent', default: null),
            'nestedEml'   => $this->structureReader->prop(obj: $attachment, name: 'nestedEml', default: null),
        ];

    }//end metaOf()

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
    public function writeDivider(
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
                templateContent: $this->templateLoader->load(name: $this->dividerTemplate()),
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
                '[EmlAttachmentRenderer] Divider WriteHTML failed',
                ['index' => $index, 'message' => $e->getMessage()]
            );
        }

    }//end writeDivider()

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
    public function renderBytes(
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
            $this->writeHtmlPage(mpdf: $mpdf, html: $html, options: $options);
            return;
        }

        if ($this->isTextLike(mimeType: $mimeType) === true) {
            $escaped  = htmlspecialchars($bytes, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
            $preStyle = 'font-family:DejaVuSansMono,monospace; font-size:9pt; white-space:pre-wrap;';
            $this->writeHtmlPage(mpdf: $mpdf, html: '<pre style="'.$preStyle.'">'.$escaped.'</pre>', options: $options);
            return;
        }

        if (in_array($mimeType, self::WORD_MIMES, true) === true) {
            $html = $this->wordRenderer->renderToHtml(bytes: $bytes, mimeType: $mimeType, filename: $filename);
            $this->writeHtmlPage(mpdf: $mpdf, html: $html, options: $options);
            return;
        }

        // Should not be reached — isRenderable gates this.
        throw new RuntimeException('No renderer for MIME '.$mimeType);

    }//end renderBytes()

    /**
     * Whether the MIME type is renderable as appended pages.
     *
     * @param string $mimeType Lowercased MIME.
     *
     * @return bool
     */
    public function isRenderable(string $mimeType): bool
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
     * Add a page and write a print-CSS-wrapped HTML fragment onto it.
     *
     * @param \Mpdf\Mpdf          $mpdf    Shared mPDF instance.
     * @param string              $html    HTML fragment.
     * @param array<string,mixed> $options PDF options (for print CSS).
     *
     * @return void
     */
    private function writeHtmlPage(\Mpdf\Mpdf $mpdf, string $html, array $options): void
    {
        $mpdf->AddPage();
        $mpdf->WriteHTML(html: $this->pdfService->applyPrintCss(html: $html, options: $options));

    }//end writeHtmlPage()

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
            throw new RuntimeException('Could not create temp file for PDF import');
        }

        try {
            file_put_contents($tmp, $bytes);
            $pageCount = $mpdf->setSourceFile($tmp);
            for ($p = 1; $p <= $pageCount; $p++) {
                $tplId = $mpdf->importPage($p);
                $mpdf->AddPage($this->orientationOf(size: $mpdf->getTemplateSize($tplId)));
                $mpdf->useTemplate($tplId);
            }
        } finally {
            if (is_file($tmp) === true) {
                unlink($tmp);
            }
        }//end try

    }//end importPdfPages()

    /**
     * Derive the mPDF page orientation from an FPDI template size.
     *
     * @param mixed $size Template size as returned by `getTemplateSize()`.
     *
     * @return string 'L' for landscape, 'P' otherwise.
     */
    private function orientationOf(mixed $size): string
    {
        if (is_array($size) === true
            && isset($size['width'], $size['height']) === true
            && $size['width'] > $size['height']
        ) {
            return 'L';
        }

        return 'P';

    }//end orientationOf()

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
     * Resolve the divider template name (config-driven, sandboxed to the
     * template root by the template loader).
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
