<?php

/**
 * Office App Conversion Backend
 *
 * Wraps the Nextcloud-managed conversion API (`IConversionManager`,
 * NC 31+). Office app integrations — Collabora, OnlyOffice, and
 * Euro Office — register themselves as conversion providers; this
 * backend dispatches uniformly through that abstraction instead of
 * coding to each app's bespoke convert API.
 *
 * Highest-priority backend in the cascade: when one of the supported
 * Office apps is installed and configured, it produces the best-fidelity
 * rendering. Falls through to PhpWord / mPDF when no provider is
 * registered or when no provider claims the source MIME → PDF
 * conversion.
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
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Uses Nextcloud's IConversionManager (NC 31+) to route conversions
 * through whichever Office app integration is installed.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class OfficeAppBackend implements ConversionBackendInterface
{


    /**
     * App config key controlling whether this backend is attempted.
     * Default true; tenants disable for air-gapped installs that
     * don't want HTTP probing into Office app endpoints.
     */
    private const ENABLED_KEY = 'docudesk.conversion.backends.office_app_enabled';


    /**
     * App identifier used for IAppConfig reads/writes.
     */
    private const APP_ID = 'docudesk';


    /**
     * Target MIME for all conversions in this cascade.
     */
    private const TARGET_MIME = 'application/pdf';

    /**
     * Cached `hasProviders()` result per request to avoid repeated
     * HTTP probing across multiple isAvailable() calls within one
     * conversion attempt.
     *
     * @var boolean|null
     */
    private ?bool $hasProvidersCache = null;

    /**
     * Cached provider list per request.
     *
     * @var array<int, \OCP\Files\Conversion\ConversionMimeProvider>|null
     */
    private ?array $providersCache = null;

    /**
     * Constructor.
     *
     * IConversionManager and IRootFolder are nullable so the backend
     * degrades cleanly on Nextcloud versions older than 31 (interface
     * not present) — `isAvailable()` returns false rather than crashing
     * at autowire time.
     *
     * @param IConversionManager|null $conversionManager NC's unified converter (31+).
     * @param IRootFolder             $rootFolder        For looking up the converted-file path.
     * @param IUserSession            $userSession       Active session — providers expect a user context.
     * @param IAppConfig              $appConfig         Tenant configuration.
     * @param LoggerInterface         $logger            Diagnostics.
     */
    public function __construct(
        private readonly ?IConversionManager $conversionManager,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
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
        return 'office_app';

    }//end name()

    /**
     * Available iff:
     *   - tenant flag is `true`
     *   - IConversionManager was bound (NC ≥ 31 with at least one
     *     conversion provider app installed)
     *   - the manager reports at least one registered provider
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        $value = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
        if ($value === 'false') {
            return false;
        }

        if ($this->conversionManager === null) {
            return false;
        }

        if ($this->hasProvidersCache === null) {
            try {
                $this->hasProvidersCache = $this->conversionManager->hasProviders();
            } catch (Throwable $e) {
                $this->logger->warning(
                    '[OfficeAppBackend] IConversionManager::hasProviders threw; treating as unavailable',
                    ['exception' => get_class($e), 'message' => $e->getMessage()]
                );
                $this->hasProvidersCache = false;
            }
        }

        return $this->hasProvidersCache === true;

    }//end isAvailable()

    /**
     * Declare whether any registered conversion provider can map this source MIME to PDF.
     *
     * @param string $mimeType  Source MIME.
     * @param string $extension Source extension (lowercased, no dot). Unused here.
     *
     * @return bool True iff some registered provider can convert this MIME → application/pdf.
     */
    public function canHandle(string $mimeType, string $extension): bool
    {
        if ($this->conversionManager === null) {
            return false;
        }

        $providers = $this->getProvidersCached();
        foreach ($providers as $provider) {
            if ($provider->getFrom() === $mimeType
                && $provider->getTo() === self::TARGET_MIME
            ) {
                return true;
            }
        }

        return false;

    }//end canHandle()

    /**
     * Delegate to IConversionManager. The provider writes the converted
     * file into the user's Files area at the destination path we
     * supply; we then resolve that path back to a File node for the
     * caller.
     *
     * @param File $source Source file node.
     *
     * @return File Newly written PDF file node.
     *
     * @throws ConversionFailedException On manager failure or path resolution failure.
     */
    public function convert(File $source): File
    {
        if ($this->conversionManager === null) {
            throw new ConversionFailedException(
                message: 'OfficeAppBackend reached convert() without IConversionManager bound.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => false,
                        'supports'  => false,
                        'reason'    => 'IConversionManager not present (NC < 31?)',
                    ],
                ]
            );
        }

        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new ConversionFailedException(
                message: 'OfficeAppBackend requires a user session.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'no active user session',
                    ],
                ]
            );
        }

        $sourceName     = $source->getName();
        $sourceFolder   = $source->getParent();
        $targetBaseName = $this->stripExtension(name: $sourceName).'.pdf';
        $destFullPath   = $sourceFolder->getPath().'/'.$targetBaseName;

        // If a previous conversion left a file behind, delete it —
        // IConversionManager refuses to overwrite.
        if ($sourceFolder->nodeExists($targetBaseName) === true) {
            $sourceFolder->get($targetBaseName)->delete();
        }

        try {
            $writtenPath = $this->conversionManager->convert(
                $source,
                self::TARGET_MIME,
                $destFullPath
            );
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                message: 'IConversionManager->convert threw: '.$e->getMessage(),
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'manager convert exception: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }

        // Manager returned a path; resolve it to a File node for the
        // caller. The path is in the user's filesystem view.
        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        try {
            // The manager returns a path relative to the user folder OR
            // an absolute one depending on provider; normalise by
            // looking up the file via the parent + basename.
            $resolved = $sourceFolder->get($targetBaseName);
        } catch (Throwable $e) {
            $this->logger->warning(
                '[OfficeAppBackend] Conversion reported success but result lookup failed',
                [
                    'expected'    => $destFullPath,
                    'manager_ret' => $writtenPath,
                    'exception'   => get_class($e),
                ]
            );
            throw new ConversionFailedException(
                message: 'Office-app conversion reported success but the resulting PDF could not be located.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'post-convert file lookup failed: '.$e->getMessage(),
                    ],
                ],
                previous: $e
            );
        }//end try

        if ($resolved instanceof File === false) {
            throw new ConversionFailedException(
                message: 'Office-app conversion result is not a regular file node.',
                attempts: [
                    [
                        'name'      => $this->name(),
                        'available' => true,
                        'supports'  => true,
                        'reason'    => 'result node is not File',
                    ],
                ]
            );
        }

        return $resolved;

    }//end convert()

    /**
     * Cached accessor for the provider list. Memoised per request to
     * avoid hitting the manager's `getProviders()` repeatedly during
     * a single cascade walk.
     *
     * @return array<int, \OCP\Files\Conversion\ConversionMimeProvider>
     */
    private function getProvidersCached(): array
    {
        if ($this->providersCache !== null) {
            return $this->providersCache;
        }

        if ($this->conversionManager === null) {
            $this->providersCache = [];
            return $this->providersCache;
        }

        try {
            $this->providersCache = $this->conversionManager->getProviders();
        } catch (Throwable $e) {
            $this->logger->warning(
                '[OfficeAppBackend] IConversionManager::getProviders threw; treating as no providers',
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
            $this->providersCache = [];
        }

        return $this->providersCache;

    }//end getProvidersCached()

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
