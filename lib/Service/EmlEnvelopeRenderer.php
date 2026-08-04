<?php

/**
 * EML Envelope Renderer
 *
 * Renders the message envelope — redacted headers plus the redacted body,
 * with `cid:` inline images resolved against OpenRegister's redacted
 * inline-image map — for one `AnonymisedEmlStructure`. Extracted from
 * {@see EmlPdfAssemblyService}, which now only orchestrates.
 *
 * Like the assembler, this renderer performs NO redaction: it consumes OR's
 * already-redacted components and never embeds original bytes.
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

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Renders the redacted envelope (headers + body) of one EML structure.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlEnvelopeRenderer
{

    /**
     * Sandboxed loader for the bundled envelope template.
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
     * Constructor.
     *
     * The two leaf helpers are dependency-free; they default-construct so the
     * renderer stays usable without explicit wiring, and remain injectable for
     * tests and for Nextcloud's autowiring.
     *
     * @param PdfService              $pdfService       Shared mPDF/PDF-A configuration.
     * @param TemplateRenderer        $templateRenderer Sandboxed Twig renderer.
     * @param LoggerInterface         $logger           Logger for diagnostics.
     * @param EmlTemplateLoader|null  $templateLoader   Bundled-template loader.
     * @param EmlStructureReader|null $structureReader  OR value-object reader.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly TemplateRenderer $templateRenderer,
        private readonly LoggerInterface $logger,
        ?EmlTemplateLoader $templateLoader=null,
        ?EmlStructureReader $structureReader=null,
    ) {
        $this->templateLoader  = ($templateLoader ?? new EmlTemplateLoader());
        $this->structureReader = ($structureReader ?? new EmlStructureReader());

    }//end __construct()

    /**
     * Render the envelope (headers + body) HTML for one structure. Resolves
     * inline `cid:` images against OR's redacted inline-image map.
     *
     * @param object              $result  AnonymisedEmlStructure.
     * @param array<string,mixed> $options PDF options (for print CSS).
     *
     * @return string Envelope HTML (print-CSS prefixed).
     */
    public function render(object $result, array $options): string
    {
        $data = $this->buildEnvelopeData(result: $result);

        try {
            $html = $this->templateRenderer->renderTemplate(
                templateContent: $this->templateLoader->load(name: 'eml/email_envelope.twig'),
                data: $data
            );
        } catch (Throwable $e) {
            $this->logger->warning(
                '[EmlEnvelopeRenderer] Envelope template render failed; using minimal envelope',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            $data['templateFailed'] = true;
            $data['bodyMode']       = 'failed';
            try {
                $html = $this->templateRenderer->renderTemplate(
                    templateContent: $this->templateLoader->load(name: 'eml/email_envelope.twig'),
                    data: $data
                );
            } catch (Throwable $inner) {
                $html = $this->fallbackEnvelopeHtml(data: $data);
            }
        }//end try

        return $this->pdfService->applyPrintCss(html: $html, options: $options);

    }//end render()

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
    public function writeMinimal(\Mpdf\Mpdf $mpdf, object $result, array $options, bool $addPage): void
    {
        $headers = $this->structureReader->headers(result: $result);
        $html    = $this->fallbackEnvelopeHtml(data: ['headers' => $headers, 'templateFailed' => true]);

        try {
            if ($addPage === true) {
                $mpdf->AddPage();
            }

            $mpdf->WriteHTML(html: $this->pdfService->applyPrintCss(html: $html, options: $options));
        } catch (Throwable $e) {
            $this->logger->error(
                '[EmlEnvelopeRenderer] Minimal envelope WriteHTML failed',
                ['message' => $e->getMessage()]
            );
        }

    }//end writeMinimal()

    /**
     * Return the redacted subject of a structure, or '' when it has none.
     *
     * @param object $result AnonymisedEmlStructure.
     *
     * @return string Redacted subject.
     */
    public function subjectOf(object $result): string
    {
        $headers = $this->structureReader->headers(result: $result);

        return ($headers['subject'] ?? '');

    }//end subjectOf()

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
        $headers      = $this->structureReader->headers(result: $result);
        $body         = $this->structureReader->prop(obj: $result, name: 'body', default: null);
        $inlineImages = $this->structureReader->inlineImages(result: $result);

        $plain = null;
        $html  = null;
        if (is_object($body) === true) {
            $plain = $this->structureReader->prop(obj: $body, name: 'plain', default: null);
            $html  = $this->structureReader->prop(obj: $body, name: 'html', default: null);
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
            function (array $matches) use ($inlineImages): string {
                $contentId  = trim($matches[2]);
                $candidates = [$contentId, '<'.$contentId.'>', trim($contentId, '<>')];
                foreach ($candidates as $key) {
                    if (isset($inlineImages[$key]) === true) {
                        $bytes = $inlineImages[$key];
                        $mime  = $this->sniffImageMime(bytes: $bytes);
                        return 'src='.$matches[1].'data:'.$mime.';base64,'.base64_encode($bytes).$matches[1];
                    }
                }

                $this->logger->debug(
                    '[EmlEnvelopeRenderer] Unresolved inline cid reference',
                    ['contentId' => $contentId]
                );
                return $matches[0];
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
}//end class
