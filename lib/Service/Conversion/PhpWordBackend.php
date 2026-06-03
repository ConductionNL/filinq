<?php

/**
 * PhpWord Conversion Backend
 *
 * Reads Word-family inputs via PhpOffice\PhpWord, renders them to HTML
 * with PhpWord's HTML writer, then hands that HTML to PdfService for
 * mPDF rendering. Covers DOC (MsDoc, limited fidelity), DOCX (Word2007),
 * ODT (ODText), RTF, and HTML — all the Word-family formats DocuDesk
 * needs to redact in the no-Office-app tier. Spreadsheet and
 * presentation formats are deliberately out of scope (see design D7).
 *
 * Routing PhpWord → HTML → PdfService (rather than PhpWord's own
 * PdfWriter) unifies the mPDF setup with PdfService's PDF/A-3b config
 * and reuses PdfService's writable temp-dir handling — PhpWord's
 * PdfWriter hardcodes mPDF's tempDir under the vendor directory, which
 * isn't writable in our container layout.
 *
 * Reached when the OfficeAppBackend declined the input (no Office app
 * installed, or convert failed). Lower fidelity than a real Office
 * engine — installs that care should configure an Office app and the
 * cascade will route there first.
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
use OCP\ITempManager;
use PhpOffice\PhpWord\IOFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Converts DOC/DOCX/ODT/RTF/HTML to PDF via PhpWord-HTML + PdfService.
 *
 * PhpWord's HTML writer produces the intermediate representation;
 * PdfService renders that HTML to mPDF-emitted PDF/A-3b with its own
 * writable temp-dir handling. Inputs that PhpWord can't read raise
 * ConversionFailedException for the cascade to fall through.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class PhpWordBackend implements ConversionBackendInterface
{


    /**
     * App config key controlling whether this backend is attempted.
     * Default true; tenants disable for testing or forced fall-through.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.phpword_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';


    /**
     * PhpWord reader name to use per extension. PhpWord's IOFactory
     * normally auto-detects, but explicit dispatch sidesteps the auto-
     * detect heuristic on edge cases (e.g. RTF files with no leading
     * magic bytes).
     *
     * @var array<string, string>
     */
    private const READER_BY_EXT = [
        'doc'  => 'MsDoc',
        'docx' => 'Word2007',
        'odt'  => 'ODText',
        'rtf'  => 'RTF',
        'html' => 'HTML',
        'htm'  => 'HTML',
    ];


    /**
     * Constructor.
     *
     * @param IAppConfig      $appConfig   Tenant configuration provider.
     * @param ITempManager    $tempManager Provides Nextcloud-managed temp paths.
     * @param PdfService      $pdfService  Renders the HTML produced by PhpWord to PDF/A-3b.
     * @param LoggerInterface $logger      Logger for diagnostics.
     */
    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly ITempManager $tempManager,
        private readonly PdfService $pdfService,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()


    /**
     * Backend identifier surfaced in the 422 body's `conversionAttempts[].name`.
     *
     * @return string Identifier surfaced in 422 attempt records.
     */
    public function name(): string
    {
        return 'phpword';

    }//end name()


    /**
     * Available iff the tenant flag is set AND the PhpWord library is
     * actually present at runtime (autoload should always make it so
     * once composer require lands, but defensively check).
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $value = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
        if ($value === 'false') {
            return false;
        }

        return class_exists(IOFactory::class);

    }//end isAvailable()


    /**
     * Declare whether PhpWord can read the source format.
     *
     * @param string $mimeType  Source MIME.
     * @param string $extension Source extension (lowercased, no dot).
     *
     * @return bool True for Word-family formats PhpWord can read.
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
        if (isset(self::READER_BY_EXT[$extension]) === true) {
            return true;
        }

        $mimeMap = [
            'application/msword'                                                      => true,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
            'application/vnd.oasis.opendocument.text'                                 => true,
            'application/rtf'                                                         => true,
            'text/rtf'                                                                => true,
            'text/html'                                                               => true,
            'application/xhtml+xml'                                                   => true,
        ];

        return isset($mimeMap[$mimeType]);

    }//end canHandle()


    /**
     * Convert via PhpWord-HTML + PdfService. PhpWord reads the source
     * into its document model; the HTML writer's getContent() yields
     * an HTML string that PdfService renders to PDF/A-3b through mPDF.
     *
     * @param File $source Source file node.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException On read/render failure.
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

        $readerName = self::READER_BY_EXT[$extension] ?? null;
        if ($readerName === null) {
            throw new ConversionFailedException(
                message: 'PhpWord backend reached convert() for unsupported extension '.$extension,
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => false,
                        'reason'    => 'No PhpWord reader mapped for .'.$extension,
                    ],
                ]
            );
        }

        // Materialise the source bytes to a temp file: PhpWord readers
        // operate on file paths, not streams.
        $sourceTmp = $this->tempManager->getTemporaryFile('.'.$extension);
        $bytes     = $source->getContent();
        if (is_string($bytes) === false) {
            throw new ConversionFailedException(
                message: 'PhpWord backend could not read source content.',
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

        file_put_contents($sourceTmp, $bytes);

        try {
            $phpWord = IOFactory::load($sourceTmp, $readerName);
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'PhpWord could not read source ('.$readerName.'): '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'IOFactory::load failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }

        try {
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            $html       = $htmlWriter->getContent();
            $html       = $this->stripAtPageRules(html: $html);
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'PhpWord HTML writer failed: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'createWriter(HTML)/getContent failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }

        if (is_string($html) === false || $html === '') {
            throw new ConversionFailedException(
                message: 'PhpWord HTML writer produced empty output.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'HTML writer getContent returned empty',
                    ],
                ]
            );
        }

        try {
            $pdfBytes = $this->pdfService->generatePdfFromHtml(html: $html);
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'PdfService failed to render PhpWord-HTML: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'PdfService::generatePdfFromHtml failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }

        if ($pdfBytes === '') {
            throw new ConversionFailedException(
                message: 'PdfService returned empty PDF bytes.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'generatePdfFromHtml returned empty string',
                    ],
                ]
            );
        }

        $parent     = $source->getParent();
        $outputName = $this->stripExtension(name: $name).'.pdf';
        if ($parent->nodeExists($outputName) === true) {
            $parent->get($outputName)->delete();
        }

        return $parent->newFile($outputName, $pdfBytes);

    }//end convert()


    /**
     * Strip CSS `@page` rules emitted by PhpWord's HTML writer.
     *
     * PhpWord emits a per-section `@page pageN { size: A4 portrait;
     * margin-*: ...; }` rule. mPDF's CSS parser mishandles that
     * construct and degenerates into one character per page (the
     * same root cause noted in PdfService::buildPrintCss). Page
     * geometry is already set by mPDF's config (format / orientation
     * / margin_* in PdfService::buildMpdfConfig), so the rule is
     * redundant — dropping it is safe.
     *
     * @param string $html HTML output from PhpWord's HTML writer.
     *
     * @return string Same HTML with all `@page` rules removed.
     */
    private function stripAtPageRules(string $html): string
    {
        return preg_replace('/@page[^{]*\{[^}]*\}/s', '', $html) ?? $html;

    }//end stripAtPageRules()


    /**
     * Return $name without its trailing `.ext`.
     *
     * @param string $name File name with extension.
     *
     * @return string
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
