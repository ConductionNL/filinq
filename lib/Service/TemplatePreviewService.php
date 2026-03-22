<?php
/**
 * Template Preview Service
 *
 * Service for rendering template previews with sample data.
 * Uses the existing TemplateRenderer for Twig sandbox rendering
 * and converts conditional sections before rendering.
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

/**
 * Service for rendering template previews with sample data
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TemplatePreviewService
{


    /**
     * Constructor for TemplatePreviewService
     *
     * @param TemplateRenderer $templateRenderer Twig template renderer
     * @param TemplateService  $templateService  Template CRUD service
     *
     * @return void
     */
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly TemplateService $templateService
    ) {

    }//end __construct()


    /**
     * Preview template content with sample data
     *
     * Processes conditional sections and renders Twig template
     * with the provided data context.
     *
     * @param string $content Template HTML/Twig content
     * @param array  $data    Sample data context for rendering
     *
     * @return string Rendered HTML output
     *
     * @throws Exception If rendering fails
     */
    public function preview(string $content, array $data): string
    {
        // Convert conditional sections to Twig before rendering.
        $processedContent = $this->templateRenderer->convertConditionalSections(
            html: $content
        );

        return $this->templateRenderer->renderTemplate(
            templateContent: $processedContent,
            data: $data
        );

    }//end preview()


    /**
     * Preview an existing template with sample data
     *
     * Fetches the template by ID and renders it with the provided data.
     *
     * @param string $templateId The template UUID
     * @param array  $data       Sample data context for rendering
     *
     * @return string Rendered HTML output
     *
     * @throws Exception If the template is not found or rendering fails
     */
    public function previewTemplate(string $templateId, array $data): string
    {
        $template = $this->templateService->getTemplate(id: $templateId);

        return $this->preview(
            content: $template['content'],
            data: $data
        );

    }//end previewTemplate()


}//end class
