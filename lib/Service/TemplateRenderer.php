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
 *
 * @spec openspec/specs/pdf-generation/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Service\Charts\ChartSvgRenderer;
use OCA\DocuDesk\Service\Charts\TableHtmlRenderer;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\TwigFunction;

/**
 * Service for rendering Twig templates in a sandboxed environment
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
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
        'chart',
        'data_table',
    ];

    /**
     * Maximum number of `chart()` calls rendered per document. Beyond this,
     * further chart() calls degrade to a visible placeholder instead of
     * growing the document unboundedly (template-charts guardrail).
     *
     * @var int
     */
    private const MAX_CHARTS_PER_DOCUMENT = 20;

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
     * Generation warnings collected by the visual-content functions
     * (`chart()`, `data_table()`) during the most recent {@see renderTemplate()}
     * call. Reset at the start of every call.
     *
     * @var string[]
     */
    private array $lastRenderWarnings = [];

    /**
     * Constructor for TemplateRenderer
     *
     * @param LoggerInterface   $logger        Logger for error reporting
     * @param ChartSvgRenderer  $chartRenderer Renderer for the `chart()` Twig function
     * @param TableHtmlRenderer $tableRenderer Renderer for the `data_table()` Twig function
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ChartSvgRenderer $chartRenderer,
        private readonly TableHtmlRenderer $tableRenderer
    ) {

    }//end __construct()

    /**
     * Render a Twig template string with the given data context
     *
     * Uses a sandboxed Twig environment that only allows safe filters,
     * functions, and tags. Objects cannot have methods or properties called.
     * The `chart()` and `data_table()` functions (template-charts) render
     * local, deterministic SVG/HTML — no writes, no network I/O.
     *
     * @param string     $templateContent Twig template content
     * @param array      $data            Data context for rendering
     * @param array|null $huisstijl       Optional huisstijl config; when set,
     *                                    `huisstijl['primaryColor']` seeds the
     *                                    default chart palette (REQ-DDTCH-001)
     *
     * @return string Rendered HTML
     *
     * @throws Exception If Twig rendering fails (syntax error, security violation)
     *
     * @spec openspec/specs/pdf-generation/spec.md
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
     */
    public function renderTemplate(string $templateContent, array $data, ?array $huisstijl=null): string
    {
        $this->lastRenderWarnings = [];

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
        $twig->addFunction(function: $this->buildChartFunction(huisstijl: $huisstijl));
        $twig->addFunction(function: $this->buildDataTableFunction());

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
     * Generation warnings recorded by `chart()`/`data_table()` during the
     * most recent {@see renderTemplate()} call (e.g. a chart that fell back
     * to a placeholder, or the per-document chart cap being hit).
     *
     * @return string[]
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-002
     */
    public function getLastRenderWarnings(): array
    {
        return $this->lastRenderWarnings;

    }//end getLastRenderWarnings()

    /**
     * Build the sandbox-registered `chart(type, data, options)` Twig
     * function. Pure with respect to the instance: no writes, no network
     * I/O — only local SVG string assembly (REQ-DDTCH-005).
     *
     * @param array|null $huisstijl Optional huisstijl config for the default
     *                              palette seed color.
     *
     * @return TwigFunction
     *
     * @spec openspec/changes/template-charts/specs/pdf-generation/spec.md#REQ-DDTCH-005
     */
    private function buildChartFunction(?array $huisstijl): TwigFunction
    {
        $chartRenderer = $this->chartRenderer;

        $seedColor = null;
        if (is_array($huisstijl) === true) {
            $seedColor = ($huisstijl['primaryColor'] ?? null);
        }

        $callCount = 0;
        $maxCharts = self::MAX_CHARTS_PER_DOCUMENT;

        return new TwigFunction(
            name: 'chart',
            callable: function ($type, $data=[], $options=[]) use ($chartRenderer, $seedColor, $maxCharts, &$callCount) {
                $callCount++;

                if ($callCount > $maxCharts) {
                    $message = 'chart error: document exceeds the maximum of '.$maxCharts.' charts';
                    $this->lastRenderWarnings[] = $message;
                    return '<div>['.$message.']</div>';
                }

                if (is_array($data) === false) {
                    $data = [];
                }

                if (is_array($options) === false) {
                    $options = [];
                }

                if ($seedColor !== null && isset($options['huisstijlPrimaryColor']) === false) {
                    $options['huisstijlPrimaryColor'] = $seedColor;
                }

                $svg = $chartRenderer->render(type: (string) $type, data: $data, options: $options);

                $warning = $chartRenderer->getLastWarning();
                if ($warning !== null) {
                    $this->lastRenderWarnings[] = $warning;
                }

                return $svg;
            },
            options: ['is_safe' => ['html']]
        );

    }//end buildChartFunction()

    /**
     * Build the sandbox-registered `data_table(collection, columns, options)`
     * Twig function. Pure with respect to the instance: no writes, no
     * network I/O (REQ-DDTCH-005).
     *
     * @return TwigFunction
     *
     * @spec openspec/changes/template-charts/specs/pdf-generation/spec.md#REQ-DDTCH-005
     */
    private function buildDataTableFunction(): TwigFunction
    {
        $tableRenderer = $this->tableRenderer;

        return new TwigFunction(
            name: 'data_table',
            callable: function ($collection=[], $columns=[], $options=[]) use ($tableRenderer) {
                if (is_array($columns) === false) {
                    $columns = [];
                }

                if (is_array($options) === false) {
                    $options = [];
                }

                return $tableRenderer->render(collection: $collection, columns: $columns, options: $options);
            },
            options: ['is_safe' => ['html']]
        );

    }//end buildDataTableFunction()

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
     *
     * @spec openspec/changes/advanced-template-management/tasks.md#task-7
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
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
     * `[$this, 'replaceConditionalSection']` from preg_replace_callback() at
     * line 342. PHPMD resolves only direct `$this->method()` calls, so a
     * callable-array reference reads to it as no caller at all — a false
     * positive, verified by grep.
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
