<?php
/**
 * Table Data Normalizer
 *
 * Coerces the collection and column definitions handed to the Twig
 * `data_table()` function into the fixed row/column shapes the table renderer
 * consumes, skipping anything malformed rather than erroring.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/specs/template-charts/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Charts;

use ArrayAccess;
use Traversable;

/**
 * Normalizes table collections and column definitions.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/template-charts/tasks.md#task-1.2
 */
class TableDataNormalizer
{

    /**
     * Column formats a column definition may declare.
     *
     * @var string[]
     */
    private const VALID_FORMATS = ['text', 'number', 'date', 'currency'];

    /**
     * Cell alignments a column definition may declare.
     *
     * @var string[]
     */
    private const VALID_ALIGNS = ['left', 'right', 'center'];

    /**
     * Normalize the incoming collection into a plain array of associative
     * rows, tolerating iterables and array-accessible objects.
     *
     * @param mixed $collection Raw collection value.
     *
     * @return array<int, array<string, mixed>>
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
     */
    public function normalizeCollection($collection): array
    {
        if (is_array($collection) === false && $collection instanceof Traversable) {
            $collection = iterator_to_array($collection);
        }

        if (is_array($collection) === false) {
            return [];
        }

        $rows = [];
        foreach ($collection as $item) {
            if (is_array($item) === true) {
                $rows[] = $item;
                continue;
            }

            if ($item instanceof ArrayAccess || is_object($item) === true) {
                $rows[] = (array) $item;
                continue;
            }

            // Scalars in the collection are not row-shaped: skip silently
            // (forgiving; the row simply does not appear).
        }

        return $rows;

    }//end normalizeCollection()

    /**
     * Derive a default column list from the keys of the first row when the
     * caller supplies no explicit `columns` definition.
     *
     * @param array $firstRow The first normalized row.
     *
     * @return array<int, array{key: string, label: string}>
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
     */
    public function deriveColumns(array $firstRow): array
    {
        $columns = [];
        foreach (array_keys($firstRow) as $key) {
            if (is_string($key) === false) {
                continue;
            }

            $columns[] = [
                'key'   => $key,
                'label' => $key,
            ];
        }

        return $columns;

    }//end deriveColumns()

    /**
     * Normalize/validate the column definitions, skipping malformed entries.
     *
     * @param array $columns Raw column definitions.
     *
     * @return array<int, array{key: string, label: string, align: string, format: string}>
     *
     * @spec openspec/changes/template-charts/specs/template-charts/spec.md#REQ-DDTCH-004
     */
    public function normalizeColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $column) {
            if (is_array($column) === false || isset($column['key']) === false || is_string($column['key']) === false) {
                continue;
            }

            $format = $this->resolveFormat(raw: ($column['format'] ?? 'text'));

            $normalized[] = [
                'key'    => $column['key'],
                'label'  => (string) ($column['label'] ?? $column['key']),
                'align'  => $this->resolveAlign(raw: ($column['align'] ?? null), format: $format),
                'format' => $format,
            ];
        }

        return $normalized;

    }//end normalizeColumns()

    /**
     * Resolve a column's declared format, falling back to 'text'.
     *
     * @param mixed $raw The declared format value.
     *
     * @return string A valid format name.
     */
    private function resolveFormat($raw): string
    {
        $format = (string) $raw;
        if (in_array(needle: $format, haystack: self::VALID_FORMATS, strict: true) === false) {
            return 'text';
        }

        return $format;

    }//end resolveFormat()

    /**
     * Resolve a column's alignment, defaulting from its format.
     *
     * @param mixed  $raw    The declared alignment value, or null when absent.
     * @param string $format The already-resolved column format.
     *
     * @return string A valid alignment name.
     */
    private function resolveAlign($raw, string $format): string
    {
        $defaultAlign = 'left';
        if ($format === 'number' || $format === 'currency') {
            $defaultAlign = 'right';
        }

        $align = (string) ($raw ?? $defaultAlign);
        if (in_array(needle: $align, haystack: self::VALID_ALIGNS, strict: true) === false) {
            return 'left';
        }

        return $align;

    }//end resolveAlign()
}//end class
