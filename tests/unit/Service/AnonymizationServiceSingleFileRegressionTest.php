<?php

/**
 * Regression guard: the per-document anonymise path MUST NOT consume the
 * folder-layout helper that drives the batch-output flow.
 *
 * `anonymisation-batch-output-folder-layout` Task 7 + the sibling change
 * `anonymisation-folder-output-folder-layout` Task 5 both call out that the
 * single-file (per-document) anonymise flow stays untouched: it returns the
 * anonymised file at the location OR's anonymiser hands back, with no subfolder
 * rewrite, no source-discovery filter, and no `_anonymized` suffix handling.
 *
 * This test enforces that contract at the source level: `AnonymizationService`
 * MUST NOT import or reference `OutputLayoutResolver` (the helper that owns the
 * batch-/folder-flow layout rewrite). If a future refactor wires the helper into
 * the per-document path, this test fails and the refactor must either justify
 * the change or carve out the per-document flow.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Regression guard against folder-layout helper leaking into the per-document path.
 *
 * @internal
 *
 * @coversNothing  This is a structural / source-level guard, not a behaviour test.
 */
final class AnonymizationServiceSingleFileRegressionTest extends TestCase
{
    /**
     * Path to AnonymizationService source, resolved from this test file's
     * location so the test is portable across worktrees.
     */
    private string $servicePath;

    /**
     * Path to FolderBatchService source — the batch flow IS allowed to consume
     * the helper. Used as a positive control so the assertion below does not
     * trivially fail on a missing/empty file.
     */
    private string $batchServicePath;

    protected function setUp(): void
    {
        parent::setUp();
        $repoRoot = dirname(__DIR__, 3);
        $this->servicePath      = $repoRoot . '/lib/Service/AnonymizationService.php';
        $this->batchServicePath = $repoRoot . '/lib/Service/FolderBatchService.php';
    }

    /**
     * The per-document anonymise path MUST NOT reference `OutputLayoutResolver`.
     */
    public function testAnonymizationServiceDoesNotReferenceOutputLayoutResolver(): void
    {
        $this->assertFileExists(
            $this->servicePath,
            'AnonymizationService source not found at expected location'
        );

        $source = file_get_contents($this->servicePath);
        $this->assertIsString($source, 'Failed to read AnonymizationService source');

        $forbidden = [
            'OutputLayoutResolver',
            'resolveBatchDestination',
            'isLegacyAnonymizedOutput',
        ];

        foreach ($forbidden as $symbol) {
            $this->assertStringNotContainsString(
                $symbol,
                $source,
                sprintf(
                    'Per-document anonymise path leaked the batch helper symbol "%s". '
                    . 'Per anonymisation-batch-output-folder-layout Task 7 + '
                    . 'anonymisation-folder-output-folder-layout Task 5, the single-file flow MUST '
                    . 'remain untouched by the folder/batch layout rewrite.',
                    $symbol
                )
            );
        }
    }

    /**
     * Positive control: the FolderBatchService SHOULD reference the helper (or
     * its planned integration point) — at minimum the file MUST exist so the
     * negative assertion above is a real signal, not a false negative caused
     * by a missing file. We do not assert the call-site shape here because the
     * batch integration is itself deferred (Task 3 of the batch spec).
     */
    public function testFolderBatchServiceExists(): void
    {
        $this->assertFileExists(
            $this->batchServicePath,
            'FolderBatchService source missing — the regression test depends on it as a control file.'
        );
    }
}
