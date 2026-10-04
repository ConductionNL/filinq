<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/GrondslagenSummaryAttacher.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\GrondslagenSummaryAttacher
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-daefdfb87576996e248b122c5b3d2e2915211592175669516653c8bff89d3b3f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/GrondslagenSummaryAttacher.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
    'shortName' => 'GrondslagenSummaryAttacher',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Attaches the grondslagen summary to an anonymised file.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 134,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'grondslagenSummary' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'name' => 'grondslagenSummary',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 3,
        'endColumn' => 63,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'grondslagenSummary' => 
          array (
            'name' => 'grondslagenSummary',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\LegalBasesSummaryService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 3,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor for GrondslagenSummaryAttacher
 *
 * @param LoggerInterface $logger Logger for the non-fatal failure warning.
 * @param LegalBasesSummaryService $grondslagenSummary Renderer for the per-document grondslagen
 *                                                     summary page.
 *
 * @return void
 */',
        'startLine' => 56,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'aliasName' => NULL,
      ),
      'attachGrondslagenSummary' => 
      array (
        'name' => 'attachGrondslagenSummary',
        'parameters' => 
        array (
          'anonymisedNode' => 
          array (
            'name' => 'anonymisedNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 43,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourceFileId' => 
          array (
            'name' => 'sourceFileId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 66,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'resultInfo' => 
          array (
            'name' => 'resultInfo',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 85,
            'endColumn' => 101,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'placeholderMap' => 
          array (
            'name' => 'placeholderMap',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 90,
                'endLine' => 90,
                'startTokenPos' => 103,
                'startFilePos' => 3676,
                'endTokenPos' => 104,
                'endFilePos' => 3677,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 104,
            'endColumn' => 129,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Render and attach the grondslagen summary to a freshly-anonymised file.
 *
 * If the anonymised file is a PDF, the summary is appended to it in place (one
 * extra page); for other formats it is saved as a separate
 * `<base>_grondslagen.pdf` beside the anonymised file.
 *
 * Summary-step failure is **non-fatal**: the anonymise call still returns
 * success and the result gets a structured `warning` field, so the caller can
 * surface the issue without rolling back the anonymisation.
 *
 * @param mixed $anonymisedNode The Node/File returned by OR\'s anonymizeDocument.
 * @param int $sourceFileId The pre-anonymisation source file id (used to look
 *                          up the EntityRelation rows that carry the bases).
 * @param array<string, mixed> $resultInfo The current result info — extended with the
 *                                         summary\'s `summaryFileId` / `warning` fields and
 *                                         returned.
 * @param array<string, string> $placeholderMap OpenRegister\'s per-entity placeholder map
 *                                              (global entity id → emitted placeholder, e.g.
 *                                              `"7" => "[PERSOON: 1]"`) so the summary renders
 *                                              the SAME placeholder the document carries. Empty
 *                                              → summary uses its own scope-local map or omits.
 *
 * @return array<string, mixed> The (possibly-extended) result info.
 *
 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
 */',
        'startLine' => 90,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'implementingClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'currentClassName' => 'OCA\\Filinq\\Service\\GrondslagenSummaryAttacher',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));