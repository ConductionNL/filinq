<?php

/**
 * EML Template Loader
 *
 * Loads a bundled Twig template from DocuDesk's template root, refusing any
 * name that resolves outside it. Extracted from {@see EmlPdfAssemblyService}
 * so the envelope and attachment renderers share one sandboxed loader instead
 * of each carrying a copy of the path check.
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

use RuntimeException;

/**
 * Reads bundled template files from the sandboxed template root.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlTemplateLoader
{
    /**
     * Load a bundled template file's content.
     *
     * The resolved path must stay inside `lib/Resources/templates`; a name
     * that escapes the root — including via a config-supplied override — is
     * rejected rather than read.
     *
     * @param string $name Template path relative to lib/Resources/templates.
     *
     * @return string Template content.
     *
     * @throws RuntimeException When the template file is missing or outside the root.
     */
    public function load(string $name): string
    {
        $path = dirname(__DIR__).'/Resources/templates/'.$name;
        $real = realpath($path);
        $base = realpath(dirname(__DIR__).'/Resources/templates');
        if ($real === false || $base === false || strncmp($real, $base, strlen($base)) !== 0) {
            throw new RuntimeException('Template not found or outside template root: '.$name);
        }

        $content = file_get_contents($real);
        if ($content === false) {
            throw new RuntimeException('Could not read template: '.$name);
        }

        return $content;

    }//end load()
}//end class
