<?php

/**
 * PDF Conversion Service
 *
 * Implements the file-to-PDF conversion cascade. Backends in priority
 * order: Office app → PhpWord → mPDF direct → EML assembler. First
 * success wins; total failure throws ConversionFailedException with
 * per-backend attempt records.
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
 * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\Conversion\ConversionBackendInterface;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Walks a cascade of conversion backends, returning the first success
 * and aggregating failures into a ConversionFailedException.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
 */
class PdfConversionService
{
    /**
     * Constructor.
     *
     * @param array<int, ConversionBackendInterface> $backends Ordered list of backends; first success wins.
     * @param LoggerInterface                        $logger   Logger for per-attempt diagnostics.
     */
    public function __construct(
        private readonly array $backends,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()

    /**
     * Convert the source file to PDF via the backend cascade.
     *
     * @param File                $source Source file (any supported input format).
     * @param array<string,mixed> $opts   Reserved for future per-call options.
     *
     * @return File The newly written PDF file node.
     *
     * @throws ConversionFailedException When no backend in the cascade succeeded.
     *
     * @spec openspec/changes/anonymise-output-as-pdf-by-default/tasks.md
     */
    public function convertToPdf(File $source, array $opts=[]): File
    {
        $mimeType = (string) $source->getMimeType();
        $name     = $source->getName();
        $dotPos   = strrpos($name, '.');
        if ($dotPos === false) {
            $ext = '';
        } else {
            $ext = strtolower(substr($name, ($dotPos + 1)));
        }

        $attempts = [];

        foreach ($this->backends as $backend) {
            if ($backend instanceof ConversionBackendInterface === false) {
                continue;
            }

            $backendName = $backend->name();

            $available = $backend->isAvailable();
            if ($available === false) {
                $attempts[] = [
                    'name'      => $backendName,
                    'available' => false,
                    'supports'  => false,
                    'reason'    => 'backend disabled or prerequisites not present',
                ];
                continue;
            }

            $supports = $backend->canHandle($mimeType, $ext);
            if ($supports === false) {
                if ($ext === '') {
                    $extLabel = '(none)';
                } else {
                    $extLabel = $ext;
                }

                $attempts[] = [
                    'name'      => $backendName,
                    'available' => true,
                    'supports'  => false,
                    'reason'    => sprintf(
                        'backend does not support MIME %s / extension %s',
                        $mimeType,
                        $extLabel
                    ),
                ];
                continue;
            }

            try {
                $result = $backend->convert($source);
                $this->logger->info(
                    '[PdfConversionService] Conversion succeeded',
                    [
                        'backend' => $backendName,
                        'source'  => $source->getPath(),
                        'output'  => $result->getPath(),
                    ]
                );
                return $result;
            } catch (Throwable $e) {
                $attempts[] = [
                    'name'      => $backendName,
                    'available' => true,
                    'supports'  => true,
                    'reason'    => $e->getMessage(),
                ];
                $this->logger->warning(
                    '[PdfConversionService] Backend failed; falling through',
                    [
                        'backend'   => $backendName,
                        'source'    => $source->getPath(),
                        'exception' => get_class($e),
                        'message'   => $e->getMessage(),
                    ]
                );
                continue;
            }//end try
        }//end foreach

        throw new ConversionFailedException(
            message: 'Conversion to PDF failed; no backend in the cascade succeeded.',
            attempts: $attempts
        );

    }//end convertToPdf()
}//end class
