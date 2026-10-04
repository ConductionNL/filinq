<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PlainLanguageRenditionService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PlainLanguageRenditionService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-9bd0e86d67ac4cd5805d1c9bc715141e3f79a99c9f4d6f594ea13ac6c2212a46',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PlainLanguageRenditionService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
    'shortName' => 'PlainLanguageRenditionService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reads a template\'s plain-language declaration and renders its counterpart.
 *
 * 🔴 THE FORMAL RENDITION IS THE LEGAL TEXT AND NOTHING HERE TOUCHES IT.
 * REQ-DIO-04 says so in as many words. Every method on this class produces or
 * refuses the PLAIN rendition; the formal one is passed in so it can be NAMED,
 * never so it can be changed.
 *
 * 🔴 NO COUNTERPART MEANS NO RENDITION, NOT A GENERATED ONE. A template that
 * declares nothing gets the formal letter only. Inventing plain text from the
 * formal text would put words the organisation never approved in front of
 * somebody who is being told what they may no longer do, and it would do it
 * silently, because a plain letter that reads fluently looks right.
 *
 * 🔴 A REQUIRED STATEMENT THAT CANNOT BE RESOLVED REFUSES THE WHOLE GENERATION,
 * BEFORE EITHER RENDITION IS FILED. The alternative is a plain letter with a
 * hole where the bezwaartermijn should be, which is worse than no plain letter:
 * the reader believes they have been told the term and they have not. So the
 * plan is made before the formal document is stored, and the refusal is a throw
 * rather than a warning on the outcome.
 *
 * 🔑 A CORRECTION TAKES THE TWIN WITH IT. Both renditions come out of ONE
 * generation, so regenerating the formal one regenerates the plain one by
 * construction rather than by anybody remembering. `stale()` exists for the
 * records written before that was true, and for any path that ever files one
 * without the other: a plain rendition older than the formal document it
 * explains reads as stale rather than as current.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 63,
    'endLine' => 309,
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
      'DECLARATION_KEY' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'name' => 'DECLARATION_KEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'plainLanguage\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 50,
            'startFilePos' => 2822,
            'endTokenPos' => 50,
            'endFilePos' => 2836,
          ),
        ),
        'docComment' => '/**
 * The template key carrying the declaration.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
      'templates' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'name' => 'templates',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\TemplateService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 82,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'gate' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'name' => 'gate',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 3,
        'endColumn' => 53,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
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
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'templates' => 
          array (
            'name' => 'templates',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\TemplateService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'gate' => 
          array (
            'name' => 'gate',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PlainRenditionAcceptanceGate',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 3,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Collaborators.
 *
 * @param TemplateService              $templates The template store.
 * @param PlainRenditionAcceptanceGate $gate      Holds an unaccepted machine draft inside.
 * @param LoggerInterface              $logger    Structured logger.
 *
 * @return void
 */',
        'startLine' => 81,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'aliasName' => NULL,
      ),
      'counterpartOf' => 
      array (
        'name' => 'counterpartOf',
        'parameters' => 
        array (
          'template' => 
          array (
            'name' => 'template',
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
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 32,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The plain-language counterpart a template declares, or null.
 *
 * @param array<string, mixed> $template The template.
 *
 * @return array{templateId: string, requiredStatements: array<int, string>, source: string}|null The declaration.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 98,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'aliasName' => NULL,
      ),
      'unresolved' => 
      array (
        'name' => 'unresolved',
        'parameters' => 
        array (
          'required' => 
          array (
            'name' => 'required',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 29,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 46,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
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
 * Which required statements the data cannot answer.
 *
 * @param array<int, string>   $required The statements the template declares.
 * @param array<string, mixed> $data     The resolved generation data.
 *
 * @return array<int, string> The unresolved statements, in declaration order.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 143,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'aliasName' => NULL,
      ),
      'plan' => 
      array (
        'name' => 'plan',
        'parameters' => 
        array (
          'template' => 
          array (
            'name' => 'template',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 23,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'acceptance' => 
          array (
            'name' => 'acceptance',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 186,
                'endLine' => 186,
                'startTokenPos' => 535,
                'startFilePos' => 6837,
                'endTokenPos' => 536,
                'endFilePos' => 6838,
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 53,
            'endColumn' => 74,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Plan the plain rendition, or refuse the generation.
 *
 * Called BEFORE the formal document is filed, so a refusal leaves neither
 * rendition behind.
 *
 * @param array<string, mixed> $template   The formal template.
 * @param array<string, mixed> $data       The resolved generation data.
 * @param array<string, mixed> $acceptance The acceptance a caller offers for a machine draft.
 *
 * @return array{templateId: string, content: string, statements: array<int, string>, source: string,
 *               acceptedBy: string, acceptedAt: string}|null The plan, or null when no counterpart
 *               is declared.
 *
 * @throws PlainRenditionRefusedException When a statement is unresolved, the acceptance is missing,
 *                                        or the counterpart template cannot be read.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 186,
        'endLine' => 237,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'aliasName' => NULL,
      ),
      'recordFields' => 
      array (
        'name' => 'recordFields',
        'parameters' => 
        array (
          'plan' => 
          array (
            'name' => 'plan',
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'formal' => 
          array (
            'name' => 'formal',
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 44,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'plainFile' => 
          array (
            'name' => 'plainFile',
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 59,
            'endColumn' => 74,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'moment' => 
          array (
            'name' => 'moment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 77,
            'endColumn' => 90,
            'parameterIndex' => 3,
            'isOptional' => false,
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
 * The record fields a produced plain rendition adds to the generation entry.
 *
 * @param array<string, mixed> $plan       The plan.
 * @param array<string, mixed> $formal     The formal document as it was filed: `fileId` and `path`.
 * @param array<string, mixed> $plainFile  The plain rendition as it was filed: `fileId` and `path`.
 * @param string               $moment     When the plain rendition was produced (ISO 8601).
 *
 * @return array<string, mixed> The fields.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 251,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'aliasName' => NULL,
      ),
      'stale' => 
      array (
        'name' => 'stale',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
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
            'startLine' => 283,
            'endLine' => 283,
            'startColumn' => 24,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a generation record\'s plain rendition is older than the formal document.
 *
 * @param array<string, mixed> $record The generated-document record.
 *
 * @return bool True when the plain rendition is stale.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */',
        'startLine' => 283,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
        'currentClassName' => 'OCA\\Filinq\\Service\\PlainLanguageRenditionService',
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