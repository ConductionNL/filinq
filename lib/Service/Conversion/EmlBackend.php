<?php

/**
 * EML Conversion Backend (stubbed)
 *
 * Reserved slot in the cascade for EML (email) inputs. Once
 * OpenRegister's `TextExtractionService` adds `message/rfc822` support,
 * this backend will: extract the EML body (and a small From/To/Subject
 * header block), wrap it as HTML, and delegate to `MpdfBackend` for
 * the final PDF/A-3b emission.
 *
 * Until OR's EML extractor lands, `isAvailable()` returns false so the
 * cascade falls through to its 422 terminus on EML inputs. Operators
 * who need to anonymise EML now can pass `outputFormat: "preserve"`
 * to bypass conversion entirely.
 *
 * Cross-app soft dependency tracked in the proposal under
 * "Cross-app Dependencies".
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Stub backend. `isAvailable()` always returns false until OR's EML
 * extraction capability lands; `convert()` throws ConversionFailedException
 * as a defensive backstop (should never be reached via the cascade because
 * the manager calls isAvailable first).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlBackend implements ConversionBackendInterface
{


    /**
     * App config key for tenant override; even when this is `true`,
     * the backend stays unavailable because the OR-side prerequisite
     * isn't shipped yet.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.eml_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';

    /**
     * Constructor.
     *
     * @param IAppConfig      $appConfig Tenant configuration provider.
     * @param LoggerInterface $logger    Logger for diagnostics.
     */
    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger,
    ) {

    }//end __construct()

    /**
     * Backend identifier surfaced in the 422 body's `conversionAttempts[].name`.
     *
     * @return string
     */
    public function name(): string
    {
        return 'eml';

    }//end name()

    /**
     * Permanently false until OR ships EML text extraction. The tenant
     * flag is still read so the value is observable in diagnostics,
     * but it can't override the missing-prerequisite gate.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        // Tenant flag respected for observability; value is unused in
        // the return path because the OR-side prerequisite is the
        // hard gate.
        $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');

        // TODO: when openregister:text-extraction-eml lands, probe the
        // OR TextExtractionService here (e.g. reflection or feature
        // flag) and return true when it advertises message/rfc822
        // support.
        return false;

    }//end isAvailable()

    /**
     * Declare the input formats this backend claims for cascade routing.
     *
     * @param string $mimeType  Source MIME.
     * @param string $extension Source extension (lowercased, no dot).
     *
     * @return bool True for `message/rfc822` (.eml). Filtered out in
     *              practice by `isAvailable()`; declared here so the
     *              cascade's attempt records correctly report
     *              `supports: true, available: false` for EML inputs.
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
        return $mimeType === 'message/rfc822' || $extension === 'eml';

    }//end canHandle()

    /**
     * Defensive backstop. The cascade calls isAvailable first, which
     * returns false, so this should not be reached in normal flow.
     *
     * @param File $source Source file node.
     *
     * @return File Never returns.
     *
     * @throws ConversionFailedException Always.
     */
    public function convert(File $source): File
    {
        $this->logger->warning(
            '[EmlBackend] convert() called despite isAvailable=false; this is a cascade bug.',
            ['source' => $source->getPath()]
        );

        throw new ConversionFailedException(
            message: 'EML conversion is not yet supported — depends on a forthcoming OpenRegister EML extractor.',
            attempts: [
                [
                    'name'      => $this->name(),
                    'available' => false,
                    'supports'  => true,
                    'reason'    => 'OpenRegister TextExtractionService does not yet support message/rfc822',
                ],
            ]
        );

    }//end convert()
}//end class
