<?php
/**
 * Template Renderer
 *
 * Service for rendering Twig templates in a sandboxed environment.
 * Extracted from PdfService to reduce class complexity.
 * Supports conditional section conversion from HTML data attributes
 * to Twig if blocks.
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


    /**
     * Convert conditional section data attributes to Twig if blocks.
     *
     * Finds HTML elements with data-condition-field, data-condition-op, and
     * data-condition-value attributes and wraps their inner content in Twig
     * conditional blocks.
     *
     * Supported operators: equals, not_equals, contains, is_empty, is_not_empty.
     *
     * @param string $html HTML content with conditional data attributes
     *
     * @return string HTML with data attributes replaced by Twig if blocks
     */
    public function convertConditionalSections(string $html): string
    {
        // Match elements with data-condition-field attribute.
        $pattern  = '/<([a-z][a-z0-9]*)\b([^>]*?)';
        $pattern .= 'data-condition-field="([^"]*)"';
        $pattern .= '([^>]*?)data-condition-op="([^"]*)"';
        $pattern .= '(\s*)(?:data-condition-value="([^"]*)")?';
        $pattern .= '([^>]*?)>([\s\S]*?)<\/\1>/i';

        $result = preg_replace_callback(
            $pattern,
            [$this, 'replaceConditionalSection'],
            $html
        );

        return $result ?? $html;

    }//end convertConditionalSections()


    /**
     * Replace a single conditional section match with Twig if block
     *
     * @param array $matches The regex match groups
     *
     * @return string The replacement string with Twig conditional
     */
    private function replaceConditionalSection(array $matches): string
    {
        $tag      = $matches[1];
        $field    = $matches[3];
        $operator = $matches[5];
        $value    = $matches[7] ?? '';
        $content  = $matches[9];

        // Build remaining attributes (strip data-condition-* attributes).
        $allAttrs   = $matches[2].$matches[4].$matches[6].$matches[8];
        $cleanAttrs = preg_replace(
            '/\s*data-condition-(field|op|value)="[^"]*"/',
            '',
            $allAttrs
        );
        $cleanAttrs = trim($cleanAttrs);
        $attrStr    = '';
        if (empty($cleanAttrs) === false) {
            $attrStr = ' '.$cleanAttrs;
        }

        $twigCondition = $this->buildTwigCondition(
            field: $field,
            operator: $operator,
            value: $value
        );

        $output  = '{% if '.$twigCondition.' %}';
        $output .= '<'.$tag.$attrStr.'>';
        $output .= $content;
        $output .= '</'.$tag.'>';
        $output .= '{% endif %}';

        return $output;

    }//end replaceConditionalSection()


    /**
     * Build a Twig condition expression from field, operator, and value
     *
     * @param string $field    The data field name
     * @param string $operator The condition operator
     * @param string $value    The comparison value
     *
     * @return string Twig condition expression
     */
    private function buildTwigCondition(string $field, string $operator, string $value): string
    {
        $safeField = preg_replace('/[^a-zA-Z0-9_.]/', '', $field);

        switch ($operator) {
            case 'equals':
                return $safeField.' == "'.$this->escapeTwigString(value: $value).'"';
            case 'not_equals':
                return $safeField.' != "'.$this->escapeTwigString(value: $value).'"';
            case 'contains':
                return '"'.$this->escapeTwigString(value: $value).'" in '.$safeField;
            case 'is_empty':
                return $safeField.' is empty';
            case 'is_not_empty':
                return $safeField.' is not empty';
            default:
                return $safeField.' is not empty';
        }

    }//end buildTwigCondition()


    /**
     * Escape a string for safe use inside Twig string literals
     *
     * @param string $value The string value to escape
     *
     * @return string The escaped string
     */
    private function escapeTwigString(string $value): string
    {
        return str_replace(
            ['"', '\\'],
            ['\\"', '\\\\'],
            $value
        );

    }//end escapeTwigString()


}//end class
