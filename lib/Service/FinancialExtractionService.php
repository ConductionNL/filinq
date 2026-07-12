<?php

/**
 * Financial Extraction Service
 *
 * Orchestrates the "scan-en-herken" pipeline: obtains text via OcrService
 * (reusing embedded PDF text where possible), runs the deterministic
 * heuristic extractors, reconciles totals, optionally refines low-confidence
 * fields through the local Nextcloud Assistant provider (absent-safe), and
 * persists the result on a `financialExtraction` OpenRegister object.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\DocuDesk\Event\FinancialExtractionCompletedEvent;
use OCA\DocuDesk\Service\Extraction\AmountExtractor;
use OCA\DocuDesk\Service\Extraction\DateExtractor;
use OCA\DocuDesk\Service\Extraction\IbanExtractor;
use OCA\DocuDesk\Service\Extraction\KvkExtractor;
use OCA\DocuDesk\Service\Extraction\TotalsReconciler;
use OCA\DocuDesk\Service\Extraction\VatIdExtractor;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\Task as TextProcessingTask;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Orchestrates financial-document field extraction.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
 */
class FinancialExtractionService
{

    /**
     * Valid `docType` values accepted by the extraction endpoint.
     *
     * @var array<int, string>
     */
    private const VALID_DOC_TYPES = ['receipt', 'supplier-invoice'];

    /**
     * Field keys that always exist in the shaped result (REQ-FIN-03), and
     * their "empty" default value.
     *
     * @var array<string, mixed>
     */
    private const FIELD_DEFAULTS = [
        'supplierName'  => null,
        'supplierIban'  => null,
        'supplierKvk'   => null,
        'supplierVatId' => null,
        'invoiceNumber' => null,
        'issueDate'     => null,
        'dueDate'       => null,
        'currency'      => null,
        'totalExcl'     => null,
        'totalVat'      => null,
        'totalIncl'     => null,
        'vatBreakdown'  => [],
        'lines'         => [],
    ];

    /**
     * Labels used to locate the invoice/issue date.
     *
     * @var array<int, string>
     */
    private const ISSUE_DATE_LABELS = ['factuurdatum', 'invoice date', 'datum'];

    /**
     * Labels used to locate the due date.
     *
     * @var array<int, string>
     */
    private const DUE_DATE_LABELS = ['vervaldatum', 'betaaldatum', 'due date', 'uiterste betaaldatum'];

    /**
     * Labels used to locate the amount excluding VAT.
     *
     * @var array<int, string>
     */
    private const TOTAL_EXCL_LABELS = ['subtotaal', 'totaal excl. btw', 'totaal exclusief btw', 'exclusief btw'];

    /**
     * Labels used to locate the VAT amount.
     *
     * @var array<int, string>
     */
    private const TOTAL_VAT_LABELS = ['btw', 'vat', 'omzetbelasting'];

    /**
     * Labels used to locate the amount including VAT.
     *
     * @var array<int, string>
     */
    private const TOTAL_INCL_LABELS = ['totaal', 'totaalbedrag', 'te betalen', 'total'];

    /**
     * Labels used to locate the invoice number.
     *
     * @var array<int, string>
     */
    private const INVOICE_NUMBER_LABELS = ['factuurnummer', 'factuur nr', 'invoice number', 'invoice no'];

    /**
     * Confidence assigned to a heuristic supplier-name match.
     *
     * @var float
     */
    private const SUPPLIER_NAME_CONFIDENCE = 0.55;

    /**
     * Confidence assigned to a heuristic invoice-number match.
     *
     * @var float
     */
    private const INVOICE_NUMBER_CONFIDENCE = 0.7;

    /**
     * Confidence assigned to a currency-marker match.
     *
     * @var float
     */
    private const CURRENCY_CONFIDENCE = 0.8;

    /**
     * Confidence boost applied to totalExcl/totalVat/totalIncl when they reconcile.
     *
     * @var float
     */
    private const RECONCILIATION_BOOST = 0.2;

    /**
     * Confidence assigned to a field filled by the optional AI enhancement step.
     *
     * @var float
     */
    private const AI_FILL_CONFIDENCE = 0.55;

    /**
     * Fields at or below this confidence (or null) are eligible for AI
     * enhancement, unless checksum-locked.
     *
     * @var float
     */
    private const LOW_CONFIDENCE_THRESHOLD = 0.6;

    /**
     * Constructor.
     *
     * @param SettingsService    $settingsService  Resolves OpenRegister's ObjectService.
     * @param IAppConfig         $config           App configuration (register/schema ids).
     * @param IUserSession       $userSession      User session, for file resolution.
     * @param IRootFolder        $rootFolder       Root folder, for file resolution.
     * @param OcrService         $ocrService       OCR service (text acquisition seam).
     * @param IEventDispatcher   $eventDispatcher  Dispatches the completion event.
     * @param ContainerInterface $container        DI container, for the optional AI provider.
     * @param LoggerInterface    $logger           Logger.
     * @param IbanExtractor      $ibanExtractor    Pure IBAN extractor.
     * @param KvkExtractor       $kvkExtractor     Pure KvK extractor.
     * @param VatIdExtractor     $vatIdExtractor   Pure BTW-nummer extractor.
     * @param DateExtractor      $dateExtractor    Pure date extractor.
     * @param AmountExtractor    $amountExtractor  Pure amount extractor.
     * @param TotalsReconciler   $totalsReconciler Pure totals reconciler.
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly IAppConfig $config,
        private readonly IUserSession $userSession,
        private readonly IRootFolder $rootFolder,
        private readonly OcrService $ocrService,
        private readonly IEventDispatcher $eventDispatcher,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
        private readonly IbanExtractor $ibanExtractor,
        private readonly KvkExtractor $kvkExtractor,
        private readonly VatIdExtractor $vatIdExtractor,
        private readonly DateExtractor $dateExtractor,
        private readonly AmountExtractor $amountExtractor,
        private readonly TotalsReconciler $totalsReconciler,
    ) {

    }//end __construct()

    /**
     * Run the full extraction pipeline for a request and persist the result.
     *
     * @param array<string, mixed> $data        Request body: `fileId|documentUri`, `docType`, `callbackEvent`.
     * @param string               $requestedBy Nextcloud user id that initiated the extraction.
     *
     * @return array<string, mixed> The persisted `financialExtraction` object.
     *
     * @throws RuntimeException (code 400) On missing file reference or invalid docType.
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function extractFinancial(array $data, string $requestedBy): array
    {
        $request = $this->resolveRequestParams(data: $data);

        $textResult = $this->resolveText(fileId: $request['resolvedFileId']);

        $pipeline = $this->runExtraction(text: $textResult['text'], docType: $request['docType']);
        $pipeline = $this->applyAiEnhancement(text: $textResult['text'], result: $pipeline, requestedBy: $requestedBy);

        $payload = [
            'documentUri'       => $request['effectiveDocumentUri'],
            'docType'           => $request['docType'],
            'requestedBy'       => $requestedBy,
            'sourceApp'         => $request['sourceApp'],
            'fields'            => $pipeline['fields'],
            'fieldConfidence'   => $pipeline['fieldConfidence'],
            'overallConfidence' => $pipeline['overallConfidence'],
            'corrections'       => [],
        ];

        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'financialExtraction_register', '');
        $schema        = $this->config->getValueString('docudesk', 'financialExtraction_schema', '');
        $saved         = $objectService->saveObject(object: $payload, register: $register, schema: $schema);
        $savedArray    = $this->toArray(object: $saved);

        if ($request['callbackEvent'] === true) {
            $this->dispatchCompletionEvent(
                documentUri: $request['effectiveDocumentUri'],
                requestedBy: $requestedBy,
                sourceApp: $request['sourceApp'],
                docType: $request['docType'],
                pipeline: $pipeline
            );
        }

        return $savedArray;

    }//end extractFinancial()

    /**
     * Validate and normalise the extraction request body: docType, the
     * fileId/documentUri file reference (with a best-effort fallback that
     * derives a fileId from a trailing numeric documentUri segment), and the
     * provenance flags.
     *
     * @param array<string, mixed> $data Request body.
     *
     * @return array{docType: string, sourceApp: string, callbackEvent: bool, resolvedFileId: int|null, effectiveDocumentUri: string}
     *
     * @throws RuntimeException (code 400) On missing file reference or invalid docType.
     */
    private function resolveRequestParams(array $data): array
    {
        $docType = (string) ($data['docType'] ?? '');
        if (in_array($docType, self::VALID_DOC_TYPES, true) === false) {
            throw new RuntimeException('docType must be "receipt" or "supplier-invoice"', 400);
        }

        [$fileId, $documentUri] = $this->extractFileReference(data: $data);
        if ($fileId === null && $documentUri === null) {
            throw new RuntimeException('Either fileId or documentUri is required', 400);
        }

        $resolvedFileId       = $this->resolveFileIdFallback(fileId: $fileId, documentUri: $documentUri);
        $effectiveDocumentUri = $documentUri ?? ('nc://file/'.$fileId);

        return [
            'docType'              => $docType,
            'sourceApp'            => (string) ($data['sourceApp'] ?? ''),
            'callbackEvent'        => (bool) ($data['callbackEvent'] ?? false),
            'resolvedFileId'       => $resolvedFileId,
            'effectiveDocumentUri' => $effectiveDocumentUri,
        ];

    }//end resolveRequestParams()

    /**
     * Read the `fileId`/`documentUri` file reference off the request body.
     *
     * @param array<string, mixed> $data Request body.
     *
     * @return array{0: int|null, 1: string|null} `[fileId, documentUri]`.
     */
    private function extractFileReference(array $data): array
    {
        $fileId = null;
        if (empty($data['fileId']) === false) {
            $fileId = (int) $data['fileId'];
        }

        $documentUri = null;
        if (empty($data['documentUri']) === false) {
            $documentUri = (string) $data['documentUri'];
        }

        return [$fileId, $documentUri];

    }//end extractFileReference()

    /**
     * Best-effort fallback: when only a documentUri was supplied, derive a
     * fileId from its trailing numeric path segment.
     *
     * @param int|null    $fileId      The explicit fileId, if any.
     * @param string|null $documentUri The explicit documentUri, if any.
     *
     * @return int|null The resolved fileId, or null when unresolvable.
     */
    private function resolveFileIdFallback(?int $fileId, ?string $documentUri): ?int
    {
        if ($fileId !== null || $documentUri === null) {
            return $fileId;
        }

        if (preg_match('/(\d+)\s*$/', $documentUri, $trailingId) !== 1) {
            return null;
        }

        return (int) $trailingId[1];

    }//end resolveFileIdFallback()

    /**
     * Store human-corrected field values against an existing extraction.
     *
     * Additive/non-destructive: the original `fields` object is never
     * mutated, corrections are appended to `corrections[]` (REQ-FIN-07).
     *
     * @param string               $id              The `financialExtraction` object id.
     * @param array<string, mixed> $correctedFields Map of field name to corrected value.
     * @param string               $correctedBy     Nextcloud user id submitting the corrections.
     *
     * @return array<string, mixed> The updated `financialExtraction` object.
     *
     * @throws RuntimeException (code 404) When no extraction exists for the given id.
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function addCorrection(string $id, array $correctedFields, string $correctedBy): array
    {
        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'financialExtraction_register', '');
        $schema        = $this->config->getValueString('docudesk', 'financialExtraction_schema', '');

        $object = $objectService->find(id: $id, register: $register, schema: $schema);
        if ($object === null) {
            throw new RuntimeException('Financial extraction not found: '.$id, 404);
        }

        $data           = $this->toArray(object: $object);
        $originalFields = (array) ($data['fields'] ?? []);
        $corrections    = (array) ($data['corrections'] ?? []);
        $now            = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

        foreach ($correctedFields as $fieldName => $correctedValue) {
            $corrections[] = [
                'field'       => (string) $fieldName,
                'original'    => ($originalFields[$fieldName] ?? null),
                'corrected'   => $correctedValue,
                'correctedBy' => $correctedBy,
                'correctedAt' => $now,
            ];
        }

        // Additive only — the original extraction result (`fields`,
        // `fieldConfidence`, `overallConfidence`) is left untouched.
        $data['corrections'] = $corrections;

        $saved = $objectService->saveObject(object: $data, register: $register, schema: $schema);

        return $this->toArray(object: $saved);

    }//end addCorrection()

    /**
     * Run the deterministic heuristic pipeline against extracted text.
     *
     * Pure given its input (no I/O): shapes the full REQ-FIN-03 field set
     * (missing fields null, never omitted), assigns per-field confidence,
     * and reconciles totals.
     *
     * @param string $text    The extracted document text.
     * @param string $docType `receipt` or `supplier-invoice`; part of the
     *                        REQ-FIN-01 pipeline signature and reserved for
     *                        a future docType-specific heuristic tuning pass
     *                        — the current heuristics apply uniformly.
     *
     * @return array{fields: array<string, mixed>, fieldConfidence: array<string, float>, overallConfidence: float, reconciled: bool}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $docType reserved for future per-type heuristic tuning
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function runExtraction(string $text, string $docType): array
    {
        $fields     = self::FIELD_DEFAULTS;
        $confidence = [];

        $this->applyExtraction(fields: $fields, confidence: $confidence, field: 'supplierIban', extracted: $this->ibanExtractor->extract($text));
        $this->applyExtraction(fields: $fields, confidence: $confidence, field: 'supplierKvk', extracted: $this->kvkExtractor->extract($text));
        $this->applyExtraction(fields: $fields, confidence: $confidence, field: 'supplierVatId', extracted: $this->vatIdExtractor->extract($text));
        $this->applyExtraction(fields: $fields, confidence: $confidence, field: 'invoiceNumber', extracted: $this->extractInvoiceNumber(text: $text));
        $this->applyExtraction(fields: $fields, confidence: $confidence, field: 'supplierName', extracted: $this->extractSupplierName(text: $text));
        $this->applyExtraction(fields: $fields, confidence: $confidence, field: 'currency', extracted: $this->extractCurrency(text: $text));

        $this->applyExtraction(
            fields: $fields,
            confidence: $confidence,
            field: 'issueDate',
            extracted: $this->dateExtractor->extractLabelled($text, self::ISSUE_DATE_LABELS)
        );
        $this->applyExtraction(
            fields: $fields,
            confidence: $confidence,
            field: 'dueDate',
            extracted: $this->dateExtractor->extractLabelled($text, self::DUE_DATE_LABELS)
        );

        $this->applyExtraction(
            fields: $fields,
            confidence: $confidence,
            field: 'totalExcl',
            extracted: $this->amountExtractor->extractLabelled($text, self::TOTAL_EXCL_LABELS)
        );
        $this->applyExtraction(
            fields: $fields,
            confidence: $confidence,
            field: 'totalVat',
            extracted: $this->amountExtractor->extractLabelled($text, self::TOTAL_VAT_LABELS)
        );
        $this->applyExtraction(
            fields: $fields,
            confidence: $confidence,
            field: 'totalIncl',
            extracted: $this->amountExtractor->extractLabelled($text, self::TOTAL_INCL_LABELS)
        );

        $fields['vatBreakdown'] = $this->extractVatBreakdown(text: $text);

        $reconciled = $this->totalsReconciler->reconciles($fields['totalExcl'], $fields['totalVat'], $fields['totalIncl']);
        if ($reconciled === true) {
            foreach (['totalExcl', 'totalVat', 'totalIncl'] as $amountField) {
                if (isset($confidence[$amountField]) === true) {
                    $confidence[$amountField] = min(1.0, $confidence[$amountField] + self::RECONCILIATION_BOOST);
                }
            }
        }

        return [
            'fields'            => $fields,
            'fieldConfidence'   => $confidence,
            'overallConfidence' => $this->aggregateConfidence(confidence: $confidence),
            'reconciled'        => $reconciled,
        ];

    }//end runExtraction()

    /**
     * Apply an optional AI-backend enhancement pass to fill null/low-confidence
     * fields (REQ-FIN-06). Absent-safe: returns the input unchanged when no
     * provider is available or the AI call fails for any reason.
     *
     * @param string               $text        The extracted document text.
     * @param array<string, mixed> $result      The heuristic-only pipeline result
     *                                          (`{fields, fieldConfidence,
     *                                          overallConfidence, reconciled}`,
     *                                          see {@see runExtraction()}).
     * @param string               $requestedBy Nextcloud user id (task quota attribution).
     *
     * @return array<string, mixed> The (possibly AI-enhanced) pipeline result, same shape as the input.
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    public function applyAiEnhancement(string $text, array $result, string $requestedBy): array
    {
        $fillable = $this->fillableFields(result: $result);
        if ($fillable === [] || trim($text) === '') {
            return $result;
        }

        $manager = $this->resolveAiManager();
        if ($manager === null) {
            return $result;
        }

        try {
            $raw     = $this->runAiTask(manager: $manager, text: $text, fillable: $fillable, requestedBy: $requestedBy);
            $decoded = json_decode($this->stripCodeFences(text: $raw), associative: true);
            if (is_array($decoded) === false) {
                return $result;
            }

            $result = $this->mergeAiFields(result: $result, fillable: $fillable, decoded: $decoded);
        } catch (Throwable $e) {
            $this->logger->warning(
                'DocuDesk: AI extraction enhancement failed, returning heuristic-only result: '.$e->getMessage()
            );
        }//end try

        return $result;

    }//end applyAiEnhancement()

    /**
     * Merge the AI's decoded field values onto the pipeline result: only
     * fillable fields with a non-empty decoded value are written, each at
     * the fixed AI-fill confidence, and the aggregate is recomputed.
     *
     * @param array<string, mixed> $result   Pipeline result to merge onto.
     * @param array<int, string>   $fillable Field names the AI was allowed to fill.
     * @param array<string, mixed> $decoded  Decoded AI JSON response.
     *
     * @return array<string, mixed> The merged pipeline result.
     */
    private function mergeAiFields(array $result, array $fillable, array $decoded): array
    {
        foreach ($fillable as $field) {
            if (array_key_exists($field, $decoded) === false) {
                continue;
            }

            $value = $decoded[$field];
            if ($value === null || $value === '') {
                continue;
            }

            $result['fields'][$field]          = $value;
            $result['fieldConfidence'][$field] = self::AI_FILL_CONFIDENCE;
        }

        $result['overallConfidence'] = $this->aggregateConfidence(confidence: $result['fieldConfidence']);

        return $result;

    }//end mergeAiFields()

    /**
     * Apply one extractor's `{value, confidence}` result onto the shaped
     * field set, only when a value was found.
     *
     * @param array<string, mixed>                   $fields     Field set (by reference).
     * @param array<string, float>                   $confidence Confidence map (by reference).
     * @param string                                 $field      Field key to write.
     * @param array{value: mixed, confidence: float} $extracted  Extractor result.
     *
     * @return void
     */
    private function applyExtraction(array &$fields, array &$confidence, string $field, array $extracted): void
    {
        if ($extracted['value'] === null) {
            return;
        }

        $fields[$field]     = $extracted['value'];
        $confidence[$field] = $extracted['confidence'];

    }//end applyExtraction()

    /**
     * Heuristic supplier-name match: a capitalised phrase ending in a Dutch
     * legal-entity suffix (B.V. / N.V.).
     *
     * @param string $text The text to search.
     *
     * @return array{value: string|null, confidence: float}
     */
    private function extractSupplierName(string $text): array
    {
        $matched = preg_match(
            '/\b([A-Z][A-Za-z0-9&.,\'\- ]{1,58}?\s(?:B\.?V\.?|N\.?V\.?))/',
            $text,
            $matches
        );

        if ($matched === 1) {
            return [
                'value'      => trim($matches[1]),
                'confidence' => self::SUPPLIER_NAME_CONFIDENCE,
            ];
        }

        return ['value' => null, 'confidence' => 0.0];

    }//end extractSupplierName()

    /**
     * Heuristic invoice-number match: a labelled alphanumeric token.
     *
     * @param string $text The text to search.
     *
     * @return array{value: string|null, confidence: float}
     */
    private function extractInvoiceNumber(string $text): array
    {
        $labelPattern = implode(
            '|',
            array_map(static fn (string $label): string => preg_quote($label, '/'), self::INVOICE_NUMBER_LABELS)
        );

        $matched = preg_match(
            '/\b(?:'.$labelPattern.')\b\.?\s*[:\-]?\s*([A-Za-z0-9\-\/]{3,30})/i',
            $text,
            $matches
        );

        if ($matched === 1) {
            return [
                'value'      => $matches[1],
                'confidence' => self::INVOICE_NUMBER_CONFIDENCE,
            ];
        }

        return ['value' => null, 'confidence' => 0.0];

    }//end extractInvoiceNumber()

    /**
     * Heuristic currency marker: `€` or `EUR` anywhere in the text.
     *
     * @param string $text The text to search.
     *
     * @return array{value: string|null, confidence: float}
     */
    private function extractCurrency(string $text): array
    {
        if (preg_match('/€|\bEUR\b/i', $text) === 1) {
            return [
                'value'      => 'EUR',
                'confidence' => self::CURRENCY_CONFIDENCE,
            ];
        }

        return ['value' => null, 'confidence' => 0.0];

    }//end extractCurrency()

    /**
     * Extract a VAT breakdown (rate/base/amount) per distinct VAT rate
     * mentioned in the text. For each `NN%` occurrence, the amount tokens on
     * the same line are inspected: the larger is treated as the base, the
     * smaller as the VAT amount (REQ-FIN-03).
     *
     * @param string $text The text to search.
     *
     * @return array<int, array{rate: int, base: float|null, amount: float|null}>
     */
    private function extractVatBreakdown(string $text): array
    {
        $matchCount = preg_match_all('/([0-9]{1,2})\s?%/', $text, $rateMatches, PREG_OFFSET_CAPTURE);
        if ($matchCount === false || $matchCount === 0) {
            return [];
        }

        $breakdown = [];
        $seenRates = [];
        $lines     = explode("\n", $text);

        foreach ($rateMatches[1] as $rateMatch) {
            $rate = (int) $rateMatch[0];
            if (isset($seenRates[$rate]) === true) {
                continue;
            }

            $offset   = $rateMatch[1];
            $lineText = $this->lineContaining(lines: $lines, offset: $offset);
            $amounts  = $this->amountExtractor->extractAll($lineText);
            $values   = array_column($amounts, 'value');

            [$base, $amount] = $this->splitBaseAndAmount(values: $values);

            $seenRates[$rate] = true;
            $breakdown[]      = [
                'rate'   => $rate,
                'base'   => $base,
                'amount' => $amount,
            ];
        }//end foreach

        return $breakdown;

    }//end extractVatBreakdown()

    /**
     * Split VAT-line amount tokens into a (base, amount) pair: with two or
     * more values the larger is the base and the smaller the VAT amount
     * (valid for rates under 100%); with a single value it is treated as
     * the VAT amount only.
     *
     * @param array<int, float> $values Amount values found on the VAT-rate line.
     *
     * @return array{0: float|null, 1: float|null} `[base, amount]`.
     */
    private function splitBaseAndAmount(array $values): array
    {
        if (count($values) >= 2) {
            return [max($values), min($values)];
        }

        if (count($values) === 1) {
            return [null, $values[0]];
        }

        return [null, null];

    }//end splitBaseAndAmount()

    /**
     * Find the line of text containing a given byte offset.
     *
     * @param array<int, string> $lines  Pre-split lines of the full text.
     * @param int                $offset Byte offset within the original text.
     *
     * @return string The line containing the offset.
     */
    private function lineContaining(array $lines, int $offset): string
    {
        $consumed = 0;
        foreach ($lines as $line) {
            $length = strlen($line) + 1;
            if ($offset < ($consumed + $length)) {
                return $line;
            }

            $consumed += $length;
        }

        $lastLine = end($lines);
        if ($lastLine === false) {
            return '';
        }

        return $lastLine;

    }//end lineContaining()

    /**
     * Aggregate populated field confidences into a single overall score.
     *
     * @param array<string, float> $confidence Per-field confidence map.
     *
     * @return float The aggregate confidence (0..1), or 0 when empty.
     */
    private function aggregateConfidence(array $confidence): float
    {
        if ($confidence === []) {
            return 0.0;
        }

        $average = array_sum($confidence) / count($confidence);

        return round(min(1.0, max(0.0, $average)), 2);

    }//end aggregateConfidence()

    /**
     * Determine which scalar fields are eligible for AI enhancement: null or
     * below the low-confidence threshold, and not checksum-locked.
     *
     * @param array{fields: array<string, mixed>, fieldConfidence: array<string, float>, reconciled: bool} $result Pipeline result.
     *
     * @return array<int, string> Fillable field names.
     */
    private function fillableFields(array $result): array
    {
        $locked = $this->lockedFields(result: $result);
        $target = [];

        foreach ($result['fields'] as $field => $value) {
            if (in_array($field, ['vatBreakdown', 'lines'], true) === true) {
                // AI enhancement targets scalar fields only.
                continue;
            }

            if (in_array($field, $locked, true) === true) {
                continue;
            }

            $confidence = ($result['fieldConfidence'][$field] ?? 0.0);
            if ($value === null || $confidence < self::LOW_CONFIDENCE_THRESHOLD) {
                $target[] = $field;
            }
        }

        return $target;

    }//end fillableFields()

    /**
     * Determine which fields are checksum-locked and must never be
     * overwritten by the AI enhancement step (REQ-FIN-06).
     *
     * @param array{fields: array<string, mixed>, reconciled: bool} $result Pipeline result.
     *
     * @return array<int, string> Locked field names.
     */
    private function lockedFields(array $result): array
    {
        $locked = [];
        if (($result['fields']['supplierIban'] ?? null) !== null) {
            $locked[] = 'supplierIban';
        }

        if ($result['reconciled'] === true) {
            $locked = array_merge($locked, ['totalExcl', 'totalVat', 'totalIncl']);
        }

        return $locked;

    }//end lockedFields()

    /**
     * Resolve the preferred available local AI text-processing manager.
     *
     * Prefers `OCP\TaskProcessing\IManager` (NC 30+), falls back to the
     * deprecated `OCP\TextProcessing\IManager`. Both are resolved lazily and
     * guarded so this class loads cleanly when neither namespace exists.
     *
     * @return array{type: string, manager: object}|null Null when unavailable.
     */
    private function resolveAiManager(): ?array
    {
        if (interface_exists('OCP\\TaskProcessing\\IManager') === true) {
            try {
                return [
                    'type'    => 'task',
                    'manager' => $this->container->get('OCP\\TaskProcessing\\IManager'),
                ];
            } catch (Throwable $e) {
                // Fall through to the legacy TextProcessing manager.
            }
        }

        if (interface_exists('OCP\\TextProcessing\\IManager') === true) {
            try {
                return [
                    'type'    => 'text',
                    'manager' => $this->container->get('OCP\\TextProcessing\\IManager'),
                ];
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;

    }//end resolveAiManager()

    /**
     * Run a single structured-extraction prompt through the resolved local
     * AI manager and return its raw text output.
     *
     * @param array{type: string, manager: object} $manager     Resolved AI manager.
     * @param string                               $text        Document text (prompt context).
     * @param array<int, string>                   $fillable    Field names the AI may fill.
     * @param string                               $requestedBy Nextcloud user id (task attribution).
     *
     * @return string Raw model output.
     */
    private function runAiTask(array $manager, string $text, array $fillable, string $requestedBy): string
    {
        $prompt = $this->buildPrompt(text: $text, fillable: $fillable);
        $userId = $requestedBy;
        if ($userId === '') {
            $userId = null;
        }

        if ($manager['type'] === 'task') {
            $task = new TaskProcessingTask(
                TextToText::ID,
                ['input' => $prompt],
                'docudesk',
                $userId,
            );

            $completed = $manager['manager']->runTask($task);
            $output    = $completed->getOutput();
            return (string) ($output['output'] ?? '');
        }

        $task = new TextProcessingTask(
            FreePromptTaskType::class,
            $prompt,
            'docudesk',
            $userId,
        );

        return (string) $manager['manager']->runTask($task);

    }//end runAiTask()

    /**
     * Build the structured-extraction prompt for the AI enhancement step.
     *
     * @param string             $text     Document text (truncated for prompt size).
     * @param array<int, string> $fillable Field names the AI should attempt to fill.
     *
     * @return string The prompt.
     */
    private function buildPrompt(string $text, array $fillable): string
    {
        $excerpt = mb_substr($text, 0, 4000);
        $fields  = implode(', ', $fillable);

        return 'You extract structured fields from a Dutch/English financial document (receipt or '
            .'supplier invoice). Return ONLY a strict JSON object (no markdown, no commentary) with '
            .'exactly these keys: '.$fields.'. Use null for a field you cannot determine with '
            .'confidence. Do not invent values.'."\n\nDocument text:\n".$excerpt;

    }//end buildPrompt()

    /**
     * Strip ```json ... ``` / ``` ... ``` code fences from a model response, if present.
     *
     * @param string $text Raw model output.
     *
     * @return string
     */
    private function stripCodeFences(string $text): string
    {
        $trimmed = trim($text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
            return trim($matches[1]);
        }

        return $trimmed;

    }//end stripCodeFences()

    /**
     * Dispatch the canonical `nl.conduction.docudesk.extraction.completed`
     * event. Fail-soft: the already-persisted result is never rolled back on
     * a dispatch failure (mirrors SigningService::emitConclusionIfDelegated).
     *
     * @param string               $documentUri Source document URI.
     * @param string               $requestedBy Requesting user id.
     * @param string               $sourceApp   Requesting app id.
     * @param string               $docType     `receipt` or `supplier-invoice`.
     * @param array<string, mixed> $pipeline    Pipeline result (`{fields, fieldConfidence, overallConfidence}`).
     *
     * @return void
     *
     * @spec openspec/changes/financial-document-field-extraction/specs/financial-document-field-extraction/spec.md
     */
    private function dispatchCompletionEvent(
        string $documentUri,
        string $requestedBy,
        string $sourceApp,
        string $docType,
        array $pipeline
    ): void {
        try {
            $event = new FinancialExtractionCompletedEvent(
                documentUri: $documentUri,
                requestedBy: $requestedBy,
                sourceApp: $sourceApp,
                docType: $docType,
                fields: $pipeline['fields'],
                fieldConfidence: $pipeline['fieldConfidence'],
                overallConfidence: $pipeline['overallConfidence'],
            );

            $this->eventDispatcher->dispatchTyped($event);
        } catch (Throwable $e) {
            $this->logger->error(
                'DocuDesk: financial extraction completed but event dispatch failed: '.$e->getMessage(),
                ['exception' => $e]
            );
        }

    }//end dispatchCompletionEvent()

    /**
     * Resolve document text for a Nextcloud file id: reuse embedded PDF text
     * when present, otherwise fall back to OCR via OcrService (REQ-FIN-01).
     *
     * @param int|null $fileId The Nextcloud file id, or null when unresolvable.
     *
     * @return array{text: string, ocrConfidence: float}
     */
    private function resolveText(?int $fileId): array
    {
        if ($fileId === null || $fileId <= 0) {
            return ['text' => '', 'ocrConfidence' => 0.0];
        }

        $file = $this->resolveFile(fileId: $fileId);
        if ($file === null) {
            return ['text' => '', 'ocrConfidence' => 0.0];
        }

        $mimeType = $file->getMimeType();
        $embedded = '';
        if ($mimeType === 'application/pdf') {
            $embedded = $this->extractEmbeddedPdfText(file: $file);
        }

        if ($this->ocrService->needsOcr(mimeType: $mimeType, existingText: $embedded) === false) {
            return ['text' => $embedded, 'ocrConfidence' => 1.0];
        }

        if ($this->ocrService->isTesseractAvailable() === false) {
            // Graceful degradation: keep whatever embedded text we have.
            return ['text' => $embedded, 'ocrConfidence' => 0.0];
        }

        return $this->runOcr(file: $file, mimeType: $mimeType, embedded: $embedded);

    }//end resolveText()

    /**
     * Stage the file to a temp path and run Tesseract OCR on it. Absent-safe:
     * any failure at any stage falls back to the embedded text (possibly
     * empty) with zero OCR confidence.
     *
     * @param File   $file     The Nextcloud file to OCR.
     * @param string $mimeType The file MIME type.
     * @param string $embedded Embedded text already extracted (fallback value).
     *
     * @return array{text: string, ocrConfidence: float}
     */
    private function runOcr(File $file, string $mimeType, string $embedded): array
    {
        try {
            $tempFile = $this->writeToTemp(file: $file);
        } catch (Throwable $e) {
            $this->logger->warning('DocuDesk: financial extraction could not stage file for OCR: '.$e->getMessage());
            return ['text' => $embedded, 'ocrConfidence' => 0.0];
        }

        try {
            $languages = $this->ocrService->getOcrLanguages();
            $dpi       = $this->ocrService->getOcrDpi();
            $isImage   = str_starts_with($mimeType, 'image/');

            $result = $this->ocrService->extractTextFromPdf(filePath: $tempFile, languages: $languages, dpi: $dpi);
            if ($isImage === true) {
                $result = $this->ocrService->extractTextFromImage(filePath: $tempFile, languages: $languages, dpi: $dpi);
            }

            return [
                'text'          => $result['text'],
                'ocrConfidence' => ($result['confidence'] / 100),
            ];
        } catch (Throwable $e) {
            $this->logger->warning('DocuDesk: financial extraction OCR failed: '.$e->getMessage());
            return ['text' => $embedded, 'ocrConfidence' => 0.0];
        } finally {
            if (file_exists($tempFile) === true) {
                unlink($tempFile);
            }
        }//end try

    }//end runOcr()

    /**
     * Extract embedded PDF text via the `pdftotext` CLI (poppler-utils), when
     * available. Absent-safe: any failure yields an empty string so the
     * caller falls through to OCR.
     *
     * @param File $file The PDF file.
     *
     * @return string The embedded text, or '' when unavailable/unextractable.
     */
    private function extractEmbeddedPdfText(File $file): string
    {
        try {
            $tempFile = $this->writeToTemp(file: $file);
        } catch (Throwable $e) {
            return '';
        }

        try {
            $output     = [];
            $returnCode = 0;
            exec('pdftotext '.escapeshellarg($tempFile).' - 2>/dev/null', $output, $returnCode);
            if ($returnCode !== 0) {
                return '';
            }

            return trim(implode("\n", $output));
        } finally {
            if (file_exists($tempFile) === true) {
                unlink($tempFile);
            }
        }

    }//end extractEmbeddedPdfText()

    /**
     * Resolve a Nextcloud file by id, scoped to the current user's folder.
     *
     * @param int $fileId The Nextcloud file id.
     *
     * @return File|null The file, or null when not found/not accessible.
     */
    private function resolveFile(int $fileId): ?File
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return null;
        }

        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        $nodes      = $userFolder->getById($fileId);
        if (empty($nodes) === true) {
            return null;
        }

        $node = $nodes[0];
        if ($node instanceof File === false) {
            return null;
        }

        return $node;

    }//end resolveFile()

    /**
     * Write a Nextcloud file to a temporary location for processing.
     *
     * @param File $file The Nextcloud file.
     *
     * @return string Path to the temporary file.
     *
     * @throws RuntimeException If writing fails.
     */
    private function writeToTemp(File $file): string
    {
        $extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
        $tempFile  = sys_get_temp_dir().'/docudesk_extraction_'.uniqid().'.'.$extension;

        $content = $file->getContent();
        if (file_put_contents($tempFile, $content) === false) {
            throw new RuntimeException('Failed to write file to temporary location');
        }

        return $tempFile;

    }//end writeToTemp()

    /**
     * Normalise an ObjectService result to an array (mirrors
     * SigningService::toArray()).
     *
     * @param mixed $object The ObjectEntity (or array) to normalise.
     *
     * @return array<string, mixed> The serialized object.
     */
    private function toArray(mixed $object): array
    {
        if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
            return $object->jsonSerialize();
        }

        return (array) $object;

    }//end toArray()
}//end class
