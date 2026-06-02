<?php

/**
 * Conversion Failed Exception
 *
 * Raised by PdfConversionService when no backend in the cascade was
 * able to convert the source file to PDF. Carries the per-backend
 * attempt records so the anonymise endpoint can emit the documented
 * HTTP 422 body.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when PdfConversionService::convertToPdf cannot complete the
 * conversion via any registered backend. The aggregated `attempts`
 * array surfaces which backends were tried and why each failed.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
 * @spec openspec/changes/eml-pdf-assembly/tasks.md#task-9
 */
class ConversionFailedException extends RuntimeException
{

    /**
     * Per-backend attempt records.
     *
     * @var array<int, array{name:string,available:bool,supports:bool,reason:string}>
     */
    private array $attempts;

    /**
     * Constructor.
     *
     * @param string                                                                    $message  Human-readable summary.
     * @param array<int, array{name:string,available:bool,supports:bool,reason:string}> $attempts Per-backend records.
     * @param Throwable|null                                                            $previous Underlying cause if any.
     */
    public function __construct(string $message, array $attempts, ?Throwable $previous=null)
    {
        parent::__construct(message: $message, code: 0, previous: $previous);
        $this->attempts = $attempts;

    }//end __construct()

    /**
     * Get the per-backend attempt records.
     *
     * @return array<int, array{name:string,available:bool,supports:bool,reason:string}>
     *
     * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
     */
    public function getAttempts(): array
    {
        return $this->attempts;

    }//end getAttempts()
}//end class
