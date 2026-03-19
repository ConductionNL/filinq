<?php
/**
 * Template Renderer
 *
 * Service for rendering Twig templates in a sandboxed environment.
 * Extracted from PdfService to reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

/**
 * Service for rendering Twig templates in a sandboxed environment
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TemplateRenderer
{

    /**
     * Allowed Twig filters in the sandbox
     *
     * @var string[]
     */
    private const ALLOWED_FILTERS = [
        'escape',
        'e',
        'upper',
        'lower',
        'trim',
        'nl2br',
        'date',
        'number_format',
        'join',
        'split',
        'first',
        'last',
        'length',
        'default',
        'raw',
        'sort',
        'reverse',
        'keys',
        'values',
        'merge',
        'slice',
        'batch',
        'column',
        'round',
        'abs',
    ];

    /**
     * Allowed Twig functions in the sandbox
     *
     * @var string[]
     */
    private const ALLOWED_FUNCTIONS = [
        'range',
        'cycle',
        'date',
        'max',
        'min',
    ];

    /**
     * Allowed Twig tags in the sandbox
     *
     * @var string[]
     */
    private const ALLOWED_TAGS = [
        'if',
        'for',
        'set',
        'block',
        'extends',
        'include',
        'macro',
        'spaceless',
        'apply',
        'autoescape',
    ];


    /**
     * Constructor for TemplateRenderer
     *
     * @param LoggerInterface $logger Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Render a Twig template string with the given data context
     *
     * Uses a sandboxed Twig environment that only allows safe filters,
     * functions, and tags. Objects cannot have methods or properties called.
     *
     * @param string $templateContent Twig template content
     * @param array  $data            Data context for rendering
     *
     * @return string Rendered HTML
     *
     * @throws Exception If Twig rendering fails (syntax error, security violation)
     */
    public function renderTemplate(string $templateContent, array $data): string
    {
        $loader = new ArrayLoader(templates: ['document' => $templateContent]);
        $twig   = new Environment(loader: $loader, options: ['strict_variables' => false]);

        $policy  = new SecurityPolicy(
            allowedTags: self::ALLOWED_TAGS,
            allowedFilters: self::ALLOWED_FILTERS,
            allowedMethods: [],
            allowedProperties: [],
            allowedFunctions: self::ALLOWED_FUNCTIONS
        );
        $sandbox = new SandboxExtension(policy: $policy, sandboxed: true);
        $twig->addExtension(extension: $sandbox);

        try {
            return $twig->render(name: 'document', context: $data);
        } catch (Exception $e) {
            $this->logger->error(
                message: 'Twig template rendering failed: '.$e->getMessage(),
                context: ['exception' => $e]
            );
            throw new Exception(
                message: 'Template rendering failed: '.$e->getMessage(),
                code: 400,
                previous: $e
            );
        }

    }//end renderTemplate()


}//end class
