<?php

/**
 * PDF/A-3 Conversion Service
 *
 * Converts an existing PDF (or freshly rendered HTML) into a genuinely
 * PDF/A-3b compliant file: XMP metadata identifying the conformance
 * level, an embedded ICC output intent, embedded fonts, and — the
 * feature that actually distinguishes PDF/A-3 from PDF/A-1/A-2 —
 * embedded file attachments (e.g. the MDTO/archival metadata sidecar,
 * or the original source document alongside its converted form).
 *
 * Converter: mPDF (already vendored, ^8.3.1) plus its bundled FPDI
 * import support (`setSourceFile`/`importPage`/`useTemplate`, native
 * to `Mpdf\Mpdf` via `Mpdf\FpdiTrait`). No new composer dependency and
 * no external binary — see design.md "Converter decision" for the
 * full shadow-risk assessment against the fleet's vendored-library
 * incident history (a vendored sabre/xml once shadowed Nextcloud core
 * CalDAV instance-wide).
 *
 * Honesty note (see design.md "Validation scope"): for the
 * convertExistingPdf() path, imported page content streams are copied
 * from the source PDF as opaque XObjects — this service wraps them in
 * a compliant PDF/A-3 container (XMP + ICC + attachments) but cannot
 * retroactively embed fonts that were missing in the source itself.
 * Full veraPDF-grade content validation is out of scope; this service
 * only asserts the container-level markers it is responsible for.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use OCA\DocuDesk\Exception\Pdfa3ConversionException;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Converts an existing PDF or rendered HTML into a PDF/A-3b compliant
 * document with embedded attachments and MDTO/archival metadata.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class Pdfa3ConversionService
{

    /**
     * App identifier used for IAppConfig reads.
     */
    private const APP_ID = 'docudesk';

    /**
     * App config key: master enable/disable switch for this service.
     * Default true; tenants disable to force a graceful "unavailable"
     * response (e.g. during a phased rollout, or when an install wants
     * to guarantee the endpoint is a hard no-op).
     */
    private const CFG_ENABLED = 'docudesk.pdfa3.enabled';

    /**
     * App config key: maximum source PDF size, in bytes.
     */
    private const CFG_MAX_INPUT_BYTES = 'docudesk.pdfa3.max_input_bytes';

    /**
     * App config key: wall-clock time budget for one conversion, in seconds.
     */
    private const CFG_MAX_SECONDS = 'docudesk.pdfa3.max_seconds';

    /**
     * Default cap: 50 MiB source PDF.
     */
    private const DEFAULT_MAX_INPUT_BYTES = 52428800;

    /**
     * Default cap: 60 seconds per conversion.
     */
    private const DEFAULT_MAX_SECONDS = 60;

    /**
     * PDF/A conformance level this service always targets. Part "3"
     * (embedded-file support), conformance "B" (Basic — matches the
     * font/colour rigor already used by PdfService's PDF/A-3b path).
     */
    private const PDFA_VERSION = '3-B';

    /**
     * Serialises MDTO/archival metadata into the XMP packet and the
     * embedded-attachment set.
     *
     * @var Pdfa3MetadataAssembler
     */
    private readonly Pdfa3MetadataAssembler $metadataAssembler;

    /**
     * Builds FPDI stream readers over in-memory PDF byte strings.
     *
     * @var PdfStreamReaderFactory
     */
    private readonly PdfStreamReaderFactory $streamReaderFactory;

    /**
     * Constructor.
     *
     * @param PdfService                  $pdfService          Shared font-directory resolution (keeps
     *                                                         the embedded DejaVu Sans set in lockstep
     *                                                         with print-preview / renderPdfA).
     * @param IAppConfig                  $appConfig           Tenant configuration provider.
     * @param LoggerInterface             $logger              Logger for diagnostics.
     * @param Pdfa3MetadataAssembler|null $metadataAssembler   MDTO metadata assembler; autowired in
     *                                                         production, defaulted here so existing
     *                                                         call sites stay source-compatible.
     * @param PdfStreamReaderFactory|null $streamReaderFactory FPDI stream-reader seam; autowired in
     *                                                         production, defaulted here so existing
     *                                                         call sites stay source-compatible.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
        ?Pdfa3MetadataAssembler $metadataAssembler=null,
        ?PdfStreamReaderFactory $streamReaderFactory=null,
    ) {
        $this->metadataAssembler   = ($metadataAssembler ?? new Pdfa3MetadataAssembler($appConfig));
        $this->streamReaderFactory = ($streamReaderFactory ?? new PdfStreamReaderFactory());

    }//end __construct()

    /**
     * Convert an existing PDF file to PDF/A-3b.
     *
     * Imports every page of the source PDF as-is (page content is
     * copied, not re-rendered) into a fresh PDF/A-3 container carrying
     * the supplied metadata and attachments.
     *
     * @param File                           $source      The existing PDF to convert.
     * @param array<string,mixed>            $metadata    MDTO/archival metadata (title,
     *                                                    author, subject, keywords,
     *                                                    plus any archival fields —
     *                                                    identifier, caseReference,
     *                                                    archiefvormer,
     *                                                    aggregatieniveau, etc.).
     * @param array<int,array<string,mixed>> $attachments Files to embed. Each entry:
     *                                                    {name, mime, content, description?,
     *                                                    AFRelationship?}.
     * @param array<string,mixed>            $options     {format?, orientation?}.
     *
     * @return array{content:string,checksumSha256:string,pages:int,conformance:string}
     *
     * @throws Pdfa3ConversionException On any guardrail violation or conversion failure.
     *
     * @spec openspec/specs/pdfa3-conversion/spec.md
     */
    public function convertExistingPdf(File $source, array $metadata=[], array $attachments=[], array $options=[]): array
    {
        $this->assertAvailable();

        $size = $source->getSize();
        if ($size > $this->resolveMaxInputBytes()) {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_SOURCE_TOO_LARGE,
                message: sprintf(
                    'Source PDF (%d bytes) exceeds the configured PDF/A-3 conversion cap (%d bytes).',
                    $size,
                    $this->resolveMaxInputBytes()
                ),
                adminHint: sprintf(
                    'Increase %s in app config, or split the source document before converting.',
                    self::CFG_MAX_INPUT_BYTES
                ),
                code: 413
            );
        }

        $raw = $source->getContent();
        if (is_string($raw) === false || $raw === '') {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_SOURCE_UNREADABLE,
                message: 'Source file could not be read as PDF content.',
                adminHint: 'Confirm the source node is a readable PDF file, not a folder or broken storage reference.',
                code: 422
            );
        }

        $deadline = $this->deadline();

        return $this->buildPdfa3(
            pageBuilder: function (Mpdf $mpdf) use ($raw, $deadline): void {
                $this->importAllPages(mpdf: $mpdf, raw: $raw, deadline: $deadline);
            },
            metadata: $metadata,
            attachments: $attachments,
            options: $options,
            defaultTitle: $this->stripExtension(name: $source->getName()),
            deadline: $deadline
        );

    }//end convertExistingPdf()

    /**
     * Convert freshly rendered HTML directly to PDF/A-3b.
     *
     * Unlike convertExistingPdf(), mPDF renders every element itself,
     * so font embedding and colour-space compliance are fully
     * guaranteed by mPDF's own PDF/A auto-correction — this is the
     * strongest-guarantee path and the one docudesk's own generation
     * flow (PdfController::renderPdfA) uses.
     *
     * @param string                         $html        Rendered HTML document body.
     * @param array<string,mixed>            $metadata    MDTO/archival metadata; see convertExistingPdf().
     * @param array<int,array<string,mixed>> $attachments Files to embed; see convertExistingPdf().
     * @param array<string,mixed>            $options     {format?, orientation?, margin?}.
     *
     * @return array{content:string,checksumSha256:string,pages:int,conformance:string}
     *
     * @throws Pdfa3ConversionException On any guardrail violation or conversion failure.
     *
     * @spec openspec/specs/pdfa3-conversion/spec.md
     */
    public function convertHtml(string $html, array $metadata=[], array $attachments=[], array $options=[]): array
    {
        $this->assertAvailable();

        $deadline = $this->deadline();

        return $this->buildPdfa3(
            pageBuilder: function (Mpdf $mpdf) use ($html): void {
                $mpdf->WriteHTML(html: $html);
            },
            metadata: $metadata,
            attachments: $attachments,
            options: $options,
            defaultTitle: (string) ($metadata['title'] ?? ''),
            deadline: $deadline
        );

    }//end convertHtml()

    /**
     * Shared mPDF assembly: configure PDF/A-3, apply metadata and
     * attachments, run the caller-supplied page builder, then validate
     * and return the output.
     *
     * @param callable                       $pageBuilder  function(Mpdf $mpdf): void — writes page
     *                                                     content.
     * @param array<string,mixed>            $metadata     MDTO/archival metadata.
     * @param array<int,array<string,mixed>> $attachments  Files to embed.
     * @param array<string,mixed>            $options      {format?, orientation?, margin?}.
     * @param string                         $defaultTitle Title used when $metadata['title'] is absent.
     * @param float                          $deadline     Absolute microtime(true) deadline for this conversion.
     *
     * @return array{content:string,checksumSha256:string,pages:int,conformance:string}
     *
     * @throws Pdfa3ConversionException On render failure or output-validation failure.
     */
    private function buildPdfa3(
        callable $pageBuilder,
        array $metadata,
        array $attachments,
        array $options,
        string $defaultTitle,
        float $deadline
    ): array {
        $associatedFiles = $this->metadataAssembler->buildAssociatedFiles(
            attachments: $attachments,
            metadata: $metadata
        );
        $mpdf            = $this->instantiateMpdf(options: $options);

        $this->metadataAssembler->applyMetadata(
            mpdf: $mpdf,
            metadata: $metadata,
            defaultTitle: $defaultTitle
        );

        if (empty($associatedFiles) === false) {
            $mpdf->SetAssociatedFiles(files: $associatedFiles);
        }

        $rendered = $this->renderPages(mpdf: $mpdf, pageBuilder: $pageBuilder, deadline: $deadline);

        $this->validateOutput(pdfBytes: $rendered['content']);

        return [
            'content'        => $rendered['content'],
            'checksumSha256' => hash(algo: 'sha256', data: $rendered['content']),
            'pages'          => max($rendered['pages'], 1),
            'conformance'    => self::PDFA_VERSION,
        ];

    }//end buildPdfa3()

    /**
     * Build mPDF's config array and instantiate it, pre-configured for
     * PDF/A-3 (embedded fonts, ICC output intent, XMP identification —
     * all driven by the PDFA/PDFAauto/PDFAversion keys).
     *
     * @param array<string,mixed> $options {format?, orientation?, margin?}.
     *
     * @return Mpdf
     *
     * @throws Pdfa3ConversionException REASON_CONVERTER_UNAVAILABLE.
     */
    private function instantiateMpdf(array $options): Mpdf
    {
        $tempDir = sys_get_temp_dir().'/docudesk-pdfa3';
        $this->ensureTempDirectory(tempDir: $tempDir);

        $margins = $options['margin'] ?? [];
        $config  = [
            'tempDir'       => $tempDir,
            'format'        => $options['format'] ?? 'A4',
            'orientation'   => $options['orientation'] ?? 'P',
            'margin_top'    => $margins['top'] ?? 15,
            'margin_right'  => $margins['right'] ?? 15,
            'margin_bottom' => $margins['bottom'] ?? 15,
            'margin_left'   => $margins['left'] ?? 15,
            'PDFA'          => true,
            'PDFAauto'      => true,
            'PDFAversion'   => self::PDFA_VERSION,
        ];

        $fontDir = $this->pdfService->getFontDirectory();
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

        try {
            return new Mpdf(config: $config);
        } catch (Throwable $e) {
            $this->logger->error(
                '[Pdfa3ConversionService] mPDF instantiation failed',
                ['exception' => $e]
            );
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_CONVERTER_UNAVAILABLE,
                message: 'PDF/A-3 converter could not be initialised: '.$e->getMessage(),
                adminHint: 'Verify the mpdf/mpdf vendor install is intact (composer install) and the temp directory is writable.',
                code: 503,
                previous: $e
            );
        }

    }//end instantiateMpdf()

    /**
     * Run the page builder against the deadline and capture the
     * rendered output. Isolated from buildPdfa3() so each concern
     * (config, metadata, rendering) stays independently readable.
     *
     * @param Mpdf     $mpdf        Configured, metadata/attachment-primed document.
     * @param callable $pageBuilder function(Mpdf $mpdf): void — writes page content.
     * @param float    $deadline    Absolute microtime(true) deadline.
     *
     * @return array{content:string,pages:int}
     *
     * @throws Pdfa3ConversionException REASON_TIME_LIMIT_EXCEEDED, REASON_RENDER_FAILED,
     *                                  or REASON_SOURCE_UNREADABLE.
     */
    private function renderPages(Mpdf $mpdf, callable $pageBuilder, float $deadline): array
    {
        try {
            if ($this->now() > $deadline) {
                throw $this->timeLimitExceeded();
            }

            $pageBuilder($mpdf);

            if ($this->now() > $deadline) {
                throw $this->timeLimitExceeded();
            }

            return [
                'content' => $mpdf->Output(name: '', dest: Destination::STRING_RETURN),
                'pages'   => (int) $mpdf->page,
            ];
        } catch (Pdfa3ConversionException $e) {
            throw $e;
        } catch (MpdfException $e) {
            $this->logger->error(
                '[Pdfa3ConversionService] mPDF render failed',
                ['exception' => $e]
            );
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_RENDER_FAILED,
                message: 'PDF/A-3 rendering failed: '.$e->getMessage(),
                adminHint: 'Check the source content for unsupported constructs (fonts, embedded media) that mPDF could not process.',
                code: 500,
                previous: $e
            );
        } catch (Throwable $e) {
            $this->logger->error(
                '[Pdfa3ConversionService] Unexpected failure during PDF/A-3 assembly',
                ['exception' => $e]
            );
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_SOURCE_UNREADABLE,
                message: 'PDF/A-3 conversion failed while importing the source: '.$e->getMessage(),
                adminHint: 'Confirm the source is a valid, non-encrypted PDF.',
                code: 422,
                previous: $e
            );
        }//end try

    }//end renderPages()

    /**
     * Import every page of a raw PDF byte string into the given mPDF
     * document, checking the time budget between pages so a
     * pathologically large source cannot hang the request
     * indefinitely (best-effort — PHP has no preemptive interrupt for
     * a single synchronous mPDF call; the deadline is enforced at
     * page granularity).
     *
     * @param Mpdf   $mpdf     Target document (already PDF/A-3 configured).
     * @param string $raw      Raw PDF bytes of the source document.
     * @param float  $deadline Absolute microtime(true) deadline.
     *
     * @return void
     *
     * @throws Pdfa3ConversionException REASON_SOURCE_UNREADABLE or REASON_TIME_LIMIT_EXCEEDED.
     */
    private function importAllPages(Mpdf $mpdf, string $raw, float $deadline): void
    {
        try {
            $pageCount = $mpdf->setSourceFile(file: $this->streamReaderFactory->fromString($raw));
        } catch (Throwable $e) {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_SOURCE_UNREADABLE,
                message: 'Source could not be parsed as PDF: '.$e->getMessage(),
                adminHint: 'The file may be encrypted, corrupted, or not actually a PDF despite its MIME type.',
                code: 422,
                previous: $e
            );
        }

        if ($pageCount < 1) {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_SOURCE_UNREADABLE,
                message: 'Source PDF reports zero pages.',
                adminHint: 'Re-export the source document and retry.',
                code: 422
            );
        }

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            if ($this->now() > $deadline) {
                throw $this->timeLimitExceeded();
            }

            $mpdf->AddPage();
            $templateId = $mpdf->importPage(pageNumber: $pageNumber);
            $mpdf->useTemplate(tpl: $templateId, adjustPageSize: true);
        }

    }//end importAllPages()

    /**
     * Build a Pdfa3ConversionException for the time-budget guardrail.
     *
     * @return Pdfa3ConversionException
     */
    private function timeLimitExceeded(): Pdfa3ConversionException
    {
        return new Pdfa3ConversionException(
            reason: Pdfa3ConversionException::REASON_TIME_LIMIT_EXCEEDED,
            message: sprintf(
                'PDF/A-3 conversion exceeded the %d second time budget.',
                $this->resolveMaxSeconds()
            ),
            adminHint: sprintf(
                'Increase %s in app config, or convert the document in smaller batches.',
                self::CFG_MAX_SECONDS
            ),
            code: 504
        );

    }//end timeLimitExceeded()

    /**
     * No-silent-passthrough guardrail: assert the assembled bytes carry
     * the minimum markers a genuine PDF/A-3 file must have. Full
     * veraPDF-grade structural/content validation is out of scope (see
     * design.md); this is a defence-in-depth check against a future
     * regression that accidentally drops the PDFA flag while still
     * returning 200.
     *
     * @param string $pdfBytes Assembled PDF bytes.
     *
     * @return void
     *
     * @throws Pdfa3ConversionException REASON_OUTPUT_VALIDATION_FAILED.
     */
    private function validateOutput(string $pdfBytes): void
    {
        if (str_starts_with($pdfBytes, '%PDF-') === false) {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_OUTPUT_VALIDATION_FAILED,
                message: 'Assembled output is missing the %PDF header — refusing to return a non-PDF as PDF/A-3.',
                adminHint: 'This indicates a converter defect; report it rather than retrying.',
                code: 500
            );
        }

        $hasPart        = (strpos($pdfBytes, 'pdfaid:part') !== false);
        $hasConformance = (strpos($pdfBytes, 'pdfaid:conformance') !== false);
        if ($hasPart === false || $hasConformance === false) {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_OUTPUT_VALIDATION_FAILED,
                message: 'Assembled output is missing the PDF/A XMP identification markers '
                    .'(pdfaid:part / pdfaid:conformance) — refusing to claim PDF/A-3 compliance.',
                adminHint: 'This indicates a converter defect (PDFA flag dropped); report it rather than retrying.',
                code: 500
            );
        }

    }//end validateOutput()

    /**
     * Whether the converter is available: the tenant has not disabled
     * it, and the mPDF classes this service depends on are actually
     * autoloadable.
     *
     * @return void
     *
     * @throws Pdfa3ConversionException REASON_CONVERTER_UNAVAILABLE.
     */
    private function assertAvailable(): void
    {
        $enabled = $this->appConfig->getValueString(self::APP_ID, self::CFG_ENABLED, 'true');
        if ($enabled === 'false' || $enabled === '0') {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_CONVERTER_UNAVAILABLE,
                message: 'PDF/A-3 conversion is disabled on this instance.',
                adminHint: sprintf('Enable it by setting %s to "true" in app config.', self::CFG_ENABLED),
                code: 503
            );
        }

        if (class_exists(Mpdf::class) === false) {
            throw new Pdfa3ConversionException(
                reason: Pdfa3ConversionException::REASON_CONVERTER_UNAVAILABLE,
                message: 'PDF/A-3 converter (mPDF) is not installed.',
                adminHint: 'Run composer install in the docudesk app directory to restore the mpdf/mpdf vendor package.',
                code: 503
            );
        }

    }//end assertAvailable()

    /**
     * Ensure the mPDF temp directory exists and is writable.
     *
     * @param string $tempDir The temp directory path.
     *
     * @return void
     */
    private function ensureTempDirectory(string $tempDir): void
    {
        if (file_exists(filename: $tempDir) === false) {
            mkdir(directory: $tempDir, permissions: 0777, recursive: true);
        }

        chmod(filename: $tempDir, permissions: 0777);

    }//end ensureTempDirectory()

    /**
     * Absolute deadline for the current conversion.
     *
     * @return float microtime(true) deadline.
     */
    private function deadline(): float
    {
        return ($this->now() + $this->resolveMaxSeconds());

    }//end deadline()

    /**
     * Current wall-clock time. Isolated behind a method (rather than a
     * bare microtime(true) call at each use site) so tests can
     * construct a partial mock that overrides this to deterministically
     * simulate the time-budget guardrail without real sleeping.
     *
     * @return float
     */
    protected function now(): float
    {
        return microtime(true);

    }//end now()

    /**
     * Read the max-input-bytes tenant config. Defaults to 50 MiB.
     *
     * @return int Positive byte cap.
     */
    private function resolveMaxInputBytes(): int
    {
        $raw    = $this->appConfig->getValueString(self::APP_ID, self::CFG_MAX_INPUT_BYTES, (string) self::DEFAULT_MAX_INPUT_BYTES);
        $parsed = (int) $raw;
        if ($parsed <= 0) {
            return self::DEFAULT_MAX_INPUT_BYTES;
        }

        return $parsed;

    }//end resolveMaxInputBytes()

    /**
     * Read the max-seconds tenant config. Defaults to 60 seconds.
     *
     * @return int Positive second cap.
     */
    private function resolveMaxSeconds(): int
    {
        $raw    = $this->appConfig->getValueString(self::APP_ID, self::CFG_MAX_SECONDS, (string) self::DEFAULT_MAX_SECONDS);
        $parsed = (int) $raw;
        if ($parsed <= 0) {
            return self::DEFAULT_MAX_SECONDS;
        }

        return $parsed;

    }//end resolveMaxSeconds()

    /**
     * Return $name without its trailing `.ext` suffix, for use as a
     * default document title.
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
