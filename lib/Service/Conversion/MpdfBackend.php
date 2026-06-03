<?php

/**
 * MPDF Conversion Backend
 *
 * Handles HTML and plain-TXT inputs directly via mPDF, reusing
 * `PdfService::generatePdfFromHtml` so PDF/A-3b configuration stays
 * in lockstep with the print-preview rendering path.
 *
 * Last cascade tier before EML / 422; intended for installs without
 * any Office app integration and without phpoffice/phpword coverage
 * (HTML and TXT are PhpWord-readable too, but the direct mPDF path is
 * lighter and preferred when the input doesn't need Word/Office
 * processing).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\PdfService;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Renders HTML directly and wraps plain text in a minimal HTML envelope
 * before rendering. PDF/A-3b output is delegated to the existing
 * PdfService for consistency with print-preview.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class MpdfBackend implements ConversionBackendInterface
{


    /**
     * App config key controlling whether this backend is attempted.
     * Default true; tenants disable for testing or to force fall-through.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.mpdf_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';

    /**
     * Constructor.
     *
     * @param PdfService      $pdfService PDF generator shared with print-preview.
     * @param IAppConfig      $appConfig  Tenant configuration provider.
     * @param LoggerInterface $logger     Logger for diagnostics.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()

    /**
     * Identifier surfaced in the 422 body's `conversionAttempts[].backend`.
     *
     * @return string
     */
    public function name(): string
    {
        return 'mpdf';

    }//end name()

    /**
     * Whether the backend is enabled in tenant config. mPDF is in-process
     * and always available at the runtime level; the flag exists for
     * cascade testing and to let tenants force fall-through.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $value = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
        return $value !== 'false';

    }//end isAvailable()

    /**
     * HTML and plain-text inputs only. Spreadsheet/presentation/Word
     * formats are claimed by other backends earlier in the cascade.
     *
     * @param string $mimeType  Source MIME type.
     * @param string $extension Lowercased extension without dot.
     *
     * @return bool
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
        $htmlMimes = [
            'text/html',
            'application/xhtml+xml',
        ];
        $textMimes = [
            'text/plain',
            'text/markdown',
        ];

        if (in_array($mimeType, $htmlMimes, true) === true
            || $extension === 'html'
            || $extension === 'htm'
            || $extension === 'xhtml'
        ) {
            return true;
        }

        if (in_array($mimeType, $textMimes, true) === true
            || $extension === 'txt'
            || $extension === 'md'
            || $extension === 'markdown'
        ) {
            return true;
        }

        return false;

    }//end canHandle()

    /**
     * Convert the source. For TXT inputs, wrap the body in a minimal
     * `<pre>` envelope so mPDF preserves whitespace; for HTML the body
     * passes through unchanged. Output is written beside the source.
     *
     * @param File $source Source file node.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException When the PDF emission fails.
     */
    public function convert(File $source): File
    {
        $name   = $source->getName();
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            $extension = '';
        } else {
            $extension = strtolower(substr($name, ($dotPos + 1)));
        }

        $rawContent = $source->getContent();
        if (is_string($rawContent) === false) {
            throw new ConversionFailedException(
                message: 'mPDF backend could not read source content.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'getContent returned non-string',
                    ],
                ]
            );
        }

        $isPlainText = in_array(
            $extension,
            ['txt', 'md', 'markdown'],
            true
        );

        if ($isPlainText === true) {
            $html = $this->wrapPlainTextAsHtml(text: $rawContent);
        } else {
            $html = $rawContent;
        }

        try {
            $pdfBinary = $this->pdfService->generatePdfFromHtml(
                html: $html,
                options: [
                    'pdfa'   => true,
                    'format' => 'A4',
                    'title'  => $this->stripExtension(name: $name),
                ]
            );
        } catch (Throwable $e) {
            $this->logger->error(
                '[MpdfBackend] mPDF rendering failed',
                [
                    'source'    => $source->getPath(),
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ]
            );
            throw new ConversionFailedException(
                message: 'mPDF rendering threw: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'mPDF render exception: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        // Write PDF beside the source. If a file with the target name
        // already exists, delete it first — this is a fresh conversion,
        // not an incremental update.
        $parent     = $source->getParent();
        $outputName = $this->stripExtension(name: $name).'.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        return $parent->newFile($outputName, $pdfBinary);

    }//end convert()

    /**
     * Wrap a plain-text body in a minimal HTML envelope so mPDF emits
     * the text with monospace formatting and preserved whitespace.
     * The `<pre>` shape is faithful to the original layout.
     *
     * @param string $text UTF-8 plain text.
     *
     * @return string HTML document.
     */
    private function wrapPlainTextAsHtml(string $text): string
    {
        $escaped  = htmlspecialchars($text, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
        $preStyle = 'font-family:DejaVuSansMono,monospace; font-size:10pt; white-space:pre-wrap;';
        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><pre style="%s">%s</pre></body></html>',
            $preStyle,
            $escaped
        );

    }//end wrapPlainTextAsHtml()

    /**
     * Return $name without its trailing `.ext` suffix, for use as a
     * title and PDF filename base.
     *
     * @param string $name File name with extension.
     *
     * @return string Name without extension.
     */
    private function stripExtension(string $name): string
    {
        $dotPos = strrpos($name, '.');
        if ($dotPos === false) {
            return $name;
        }

        return substr($name, 0, $dotPos);

    }//end stripExtension()
}//end class
