<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/DocumentAgentService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\DocumentAgentService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-8ce9834e83b454430e733ab8a735a52c07a6494bb1eb4f7d22463ff023dee094',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/DocumentAgentService.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
    'shortName' => 'DocumentAgentService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The agent-facing document operations.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-no-document-attachment-or-signature-bytes-leave-through-this-capability
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 56,
    'endLine' => 687,
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
      'editSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'name' => 'editSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pdfConversion' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'name' => 'pdfConversion',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\PdfConversionService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'marker' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'name' => 'marker',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 3,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'documentLogger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'name' => 'documentLogger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\GeneratedDocumentLogger',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 3,
        'endColumn' => 58,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rootFolder' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'name' => 'rootFolder',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\Files\\IRootFolder',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 3,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'userSession' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'name' => 'userSession',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IUserSession',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
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
        'startLine' => 78,
        'endLine' => 78,
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
          'editSession' => 
          array (
            'name' => 'editSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\EditSessionService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'pdfConversion' => 
          array (
            'name' => 'pdfConversion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\PdfConversionService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'marker' => 
          array (
            'name' => 'marker',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 3,
            'endColumn' => 46,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'documentLogger' => 
          array (
            'name' => 'documentLogger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\GeneratedDocumentLogger',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 3,
            'endColumn' => 58,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'rootFolder' => 
          array (
            'name' => 'rootFolder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\IRootFolder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'userSession' => 
          array (
            'name' => 'userSession',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IUserSession',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 5,
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
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param EditSessionService $editSession The edit session owner.
 * @param PdfConversionService $pdfConversion The PDF conversion cascade.
 * @param AgentArtefactMarker $marker The ADR-088 artefact marker.
 * @param GeneratedDocumentLogger $documentLogger The generated-document audit logger.
 * @param IRootFolder $rootFolder The Nextcloud root folder.
 * @param IUserSession $userSession The acting user\'s session.
 * @param LoggerInterface $logger Logger for diagnostics.
 *
 * @return void
 */',
        'startLine' => 71,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'readDocument' => 
      array (
        'name' => 'readDocument',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 31,
            'endColumn' => 41,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'readDocument\'',
                'attributes' => 
                array (
                  'startLine' => 99,
                  'endLine' => 99,
                  'startTokenPos' => 184,
                  'startFilePos' => 3656,
                  'endTokenPos' => 184,
                  'endFilePos' => 3669,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'document\'',
                'attributes' => 
                array (
                  'startLine' => 100,
                  'endLine' => 100,
                  'startTokenPos' => 190,
                  'startFilePos' => 3683,
                  'endTokenPos' => 190,
                  'endFilePos' => 3692,
                ),
              ),
              'action' => 
              array (
                'code' => '\'get\'',
                'attributes' => 
                array (
                  'startLine' => 101,
                  'endLine' => 101,
                  'startTokenPos' => 196,
                  'startFilePos' => 3705,
                  'endTokenPos' => 196,
                  'endFilePos' => 3709,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Read a Word (.docx) or OpenDocument (.odt) file as a list of anchored text blocks, \' . \'one per paragraph. Use this before editDocument: it returns the anchors and the version \' . \'that editDocument requires. Returns text, never the file bytes.\'',
                'attributes' => 
                array (
                  'startLine' => 102,
                  'endLine' => 104,
                  'startTokenPos' => 202,
                  'startFilePos' => 3727,
                  'endTokenPos' => 210,
                  'endFilePos' => 3978,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 105,
                  'endLine' => 105,
                  'startTokenPos' => 216,
                  'startFilePos' => 3997,
                  'endTokenPos' => 216,
                  'endFilePos' => 4000,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 106,
                  'endLine' => 106,
                  'startTokenPos' => 222,
                  'startFilePos' => 4022,
                  'endTokenPos' => 222,
                  'endFilePos' => 4026,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 107,
                  'endLine' => 107,
                  'startTokenPos' => 228,
                  'startFilePos' => 4047,
                  'endTokenPos' => 228,
                  'endFilePos' => 4050,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'read\'',
                'attributes' => 
                array (
                  'startLine' => 108,
                  'endLine' => 108,
                  'startTokenPos' => 234,
                  'startFilePos' => 4062,
                  'endTokenPos' => 234,
                  'endFilePos' => 4067,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 98,
        'endLine' => 129,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'editDocument' => 
      array (
        'name' => 'editDocument',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 44,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 58,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'outputMode' => 
          array (
            'name' => 'outputMode',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 175,
                'endLine' => 175,
                'startTokenPos' => 400,
                'startFilePos' => 7624,
                'endTokenPos' => 400,
                'endFilePos' => 7625,
              ),
            ),
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
            'startLine' => 175,
            'endLine' => 175,
            'startColumn' => 75,
            'endColumn' => 97,
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
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'editDocument\'',
                'attributes' => 
                array (
                  'startLine' => 132,
                  'endLine' => 132,
                  'startTokenPos' => 292,
                  'startFilePos' => 5087,
                  'endTokenPos' => 292,
                  'endFilePos' => 5100,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'document\'',
                'attributes' => 
                array (
                  'startLine' => 133,
                  'endLine' => 133,
                  'startTokenPos' => 298,
                  'startFilePos' => 5114,
                  'endTokenPos' => 298,
                  'endFilePos' => 5123,
                ),
              ),
              'action' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 134,
                  'endLine' => 134,
                  'startTokenPos' => 304,
                  'startFilePos' => 5136,
                  'endTokenPos' => 304,
                  'endFilePos' => 5143,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Change a Word (.docx) or OpenDocument (.odt) file by replacing, inserting after, or \' . \'deleting anchored paragraphs, or by restyling one with action "style". A style edit carries a \' . \'"style" object instead of text: bold, italic, underline (true/false), alignment \' . \'(left/center/right/justify), heading (0-9, where 0 means body text), list (true/false), \' . \'pageBreakBefore (true/false). Style edits need a .docx -- .odt is refused by name. Call \' . \'readDocument first to get the anchors and version. Writes into the file by default (restorable \' . \'via Nextcloud file versions); pass outputMode "sibling" to write a new file instead. Refuses if \' . \'the document changed since it was read, if it is open in an editor, if it is under a signing \' . \'request, or if it is anonymisation output.\'',
                'attributes' => 
                array (
                  'startLine' => 135,
                  'endLine' => 143,
                  'startTokenPos' => 310,
                  'startFilePos' => 5161,
                  'endTokenPos' => 342,
                  'endFilePos' => 5986,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 144,
                  'endLine' => 144,
                  'startTokenPos' => 348,
                  'startFilePos' => 6005,
                  'endTokenPos' => 348,
                  'endFilePos' => 6009,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 145,
                  'endLine' => 145,
                  'startTokenPos' => 354,
                  'startFilePos' => 6031,
                  'endTokenPos' => 354,
                  'endFilePos' => 6034,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 146,
                  'endLine' => 146,
                  'startTokenPos' => 360,
                  'startFilePos' => 6055,
                  'endTokenPos' => 360,
                  'endFilePos' => 6059,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 147,
                  'endLine' => 147,
                  'startTokenPos' => 366,
                  'startFilePos' => 6071,
                  'endTokenPos' => 366,
                  'endFilePos' => 6078,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 131,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'readSpreadsheet' => 
      array (
        'name' => 'readSpreadsheet',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 34,
            'endColumn' => 44,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'readSpreadsheet\'',
                'attributes' => 
                array (
                  'startLine' => 205,
                  'endLine' => 205,
                  'startTokenPos' => 596,
                  'startFilePos' => 8418,
                  'endTokenPos' => 596,
                  'endFilePos' => 8434,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'spreadsheet\'',
                'attributes' => 
                array (
                  'startLine' => 206,
                  'endLine' => 206,
                  'startTokenPos' => 602,
                  'startFilePos' => 8448,
                  'endTokenPos' => 602,
                  'endFilePos' => 8460,
                ),
              ),
              'action' => 
              array (
                'code' => '\'get\'',
                'attributes' => 
                array (
                  'startLine' => 207,
                  'endLine' => 207,
                  'startTokenPos' => 608,
                  'startFilePos' => 8473,
                  'endTokenPos' => 608,
                  'endFilePos' => 8477,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Read a spreadsheet (.ods or .xlsx) as a list of cells addressed Sheet!Cell, each with \' . \'its value and, when it has one, its formula. Use this before editSpreadsheet: it returns the \' . \'version that call requires. A cell address is a durable identity, so there are no anchors here.\'',
                'attributes' => 
                array (
                  'startLine' => 208,
                  'endLine' => 210,
                  'startTokenPos' => 614,
                  'startFilePos' => 8495,
                  'endTokenPos' => 622,
                  'endFilePos' => 8786,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 211,
                  'endLine' => 211,
                  'startTokenPos' => 628,
                  'startFilePos' => 8805,
                  'endTokenPos' => 628,
                  'endFilePos' => 8808,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 212,
                  'endLine' => 212,
                  'startTokenPos' => 634,
                  'startFilePos' => 8830,
                  'endTokenPos' => 634,
                  'endFilePos' => 8834,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 213,
                  'endLine' => 213,
                  'startTokenPos' => 640,
                  'startFilePos' => 8855,
                  'endTokenPos' => 640,
                  'endFilePos' => 8858,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'read\'',
                'attributes' => 
                array (
                  'startLine' => 214,
                  'endLine' => 214,
                  'startTokenPos' => 646,
                  'startFilePos' => 8870,
                  'endTokenPos' => 646,
                  'endFilePos' => 8875,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 204,
        'endLine' => 229,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'editSpreadsheet' => 
      array (
        'name' => 'editSpreadsheet',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 257,
            'endLine' => 257,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 257,
            'endLine' => 257,
            'startColumn' => 47,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 257,
            'endLine' => 257,
            'startColumn' => 61,
            'endColumn' => 75,
            'parameterIndex' => 2,
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
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'editSpreadsheet\'',
                'attributes' => 
                array (
                  'startLine' => 232,
                  'endLine' => 232,
                  'startTokenPos' => 704,
                  'startFilePos' => 9443,
                  'endTokenPos' => 704,
                  'endFilePos' => 9459,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'spreadsheet\'',
                'attributes' => 
                array (
                  'startLine' => 233,
                  'endLine' => 233,
                  'startTokenPos' => 710,
                  'startFilePos' => 9473,
                  'endTokenPos' => 710,
                  'endFilePos' => 9485,
                ),
              ),
              'action' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 234,
                  'endLine' => 234,
                  'startTokenPos' => 716,
                  'startFilePos' => 9498,
                  'endTokenPos' => 716,
                  'endFilePos' => 9505,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Write literal values into spreadsheet cells addressed Sheet!Cell. Read the sheet first \' . \'and pass back its version. Writing over a cell that holds a FORMULA is refused unless that \' . \'edit sets replaceFormula true — the flag is per cell and is not carried across a bulk write. \' . \'The result lists cells whose cached values no longer follow from their inputs.\'',
                'attributes' => 
                array (
                  'startLine' => 235,
                  'endLine' => 238,
                  'startTokenPos' => 722,
                  'startFilePos' => 9523,
                  'endTokenPos' => 734,
                  'endFilePos' => 9899,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 239,
                  'endLine' => 239,
                  'startTokenPos' => 740,
                  'startFilePos' => 9918,
                  'endTokenPos' => 740,
                  'endFilePos' => 9922,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 240,
                  'endLine' => 240,
                  'startTokenPos' => 746,
                  'startFilePos' => 9944,
                  'endTokenPos' => 746,
                  'endFilePos' => 9947,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 241,
                  'endLine' => 241,
                  'startTokenPos' => 752,
                  'startFilePos' => 9968,
                  'endTokenPos' => 752,
                  'endFilePos' => 9972,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 242,
                  'endLine' => 242,
                  'startTokenPos' => 758,
                  'startFilePos' => 9984,
                  'endTokenPos' => 758,
                  'endFilePos' => 9991,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 231,
        'endLine' => 276,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'readPresentation' => 
      array (
        'name' => 'readPresentation',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 301,
            'endLine' => 301,
            'startColumn' => 35,
            'endColumn' => 45,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'readPresentation\'',
                'attributes' => 
                array (
                  'startLine' => 279,
                  'endLine' => 279,
                  'startTokenPos' => 934,
                  'startFilePos' => 11112,
                  'endTokenPos' => 934,
                  'endFilePos' => 11129,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'presentation\'',
                'attributes' => 
                array (
                  'startLine' => 280,
                  'endLine' => 280,
                  'startTokenPos' => 940,
                  'startFilePos' => 11143,
                  'endTokenPos' => 940,
                  'endFilePos' => 11156,
                ),
              ),
              'action' => 
              array (
                'code' => '\'get\'',
                'attributes' => 
                array (
                  'startLine' => 281,
                  'endLine' => 281,
                  'startTokenPos' => 946,
                  'startFilePos' => 11169,
                  'endTokenPos' => 946,
                  'endFilePos' => 11173,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Read a presentation (.pptx or .odp) as a list of shapes, each carrying its slide id, \' . \'shape id, region (slide or notes) and text. Use this before editPresentation: it returns the \' . \'version that call requires. Slides are identified by ID, never by position.\'',
                'attributes' => 
                array (
                  'startLine' => 282,
                  'endLine' => 284,
                  'startTokenPos' => 952,
                  'startFilePos' => 11191,
                  'endTokenPos' => 960,
                  'endFilePos' => 11461,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 285,
                  'endLine' => 285,
                  'startTokenPos' => 966,
                  'startFilePos' => 11480,
                  'endTokenPos' => 966,
                  'endFilePos' => 11483,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 286,
                  'endLine' => 286,
                  'startTokenPos' => 972,
                  'startFilePos' => 11505,
                  'endTokenPos' => 972,
                  'endFilePos' => 11509,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 287,
                  'endLine' => 287,
                  'startTokenPos' => 978,
                  'startFilePos' => 11530,
                  'endTokenPos' => 978,
                  'endFilePos' => 11533,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'read\'',
                'attributes' => 
                array (
                  'startLine' => 288,
                  'endLine' => 288,
                  'startTokenPos' => 984,
                  'startFilePos' => 11545,
                  'endTokenPos' => 984,
                  'endFilePos' => 11550,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 278,
        'endLine' => 303,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'editPresentation' => 
      array (
        'name' => 'editPresentation',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'edits' => 
          array (
            'name' => 'edits',
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
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 48,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 62,
            'endColumn' => 76,
            'parameterIndex' => 2,
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
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'editPresentation\'',
                'attributes' => 
                array (
                  'startLine' => 306,
                  'endLine' => 306,
                  'startTokenPos' => 1042,
                  'startFilePos' => 12125,
                  'endTokenPos' => 1042,
                  'endFilePos' => 12142,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'presentation\'',
                'attributes' => 
                array (
                  'startLine' => 307,
                  'endLine' => 307,
                  'startTokenPos' => 1048,
                  'startFilePos' => 12156,
                  'endTokenPos' => 1048,
                  'endFilePos' => 12169,
                ),
              ),
              'action' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 308,
                  'endLine' => 308,
                  'startTokenPos' => 1054,
                  'startFilePos' => 12182,
                  'endTokenPos' => 1054,
                  'endFilePos' => 12189,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Replace the text of presentation shapes, addressed by slide id and shape id from \' . \'readPresentation. Never address a slide by its position: slide order changes and the ids do \' . \'not. Set region to notes to write speaker notes; it defaults to the slide, so talking points \' . \'are never put on screen by accident.\'',
                'attributes' => 
                array (
                  'startLine' => 309,
                  'endLine' => 312,
                  'startTokenPos' => 1060,
                  'startFilePos' => 12207,
                  'endTokenPos' => 1072,
                  'endFilePos' => 12534,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 313,
                  'endLine' => 313,
                  'startTokenPos' => 1078,
                  'startFilePos' => 12553,
                  'endTokenPos' => 1078,
                  'endFilePos' => 12557,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 314,
                  'endLine' => 314,
                  'startTokenPos' => 1084,
                  'startFilePos' => 12579,
                  'endTokenPos' => 1084,
                  'endFilePos' => 12582,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 315,
                  'endLine' => 315,
                  'startTokenPos' => 1090,
                  'startFilePos' => 12603,
                  'endTokenPos' => 1090,
                  'endFilePos' => 12607,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 316,
                  'endLine' => 316,
                  'startTokenPos' => 1096,
                  'startFilePos' => 12619,
                  'endTokenPos' => 1096,
                  'endFilePos' => 12626,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 305,
        'endLine' => 350,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'addDocumentChart' => 
      array (
        'name' => 'addDocumentChart',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 392,
            'endLine' => 392,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'chart' => 
          array (
            'name' => 'chart',
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
            'startLine' => 393,
            'endLine' => 393,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 394,
            'endLine' => 394,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'afterAnchor' => 
          array (
            'name' => 'afterAnchor',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 395,
                'endLine' => 395,
                'startTokenPos' => 1369,
                'startFilePos' => 15837,
                'endTokenPos' => 1369,
                'endFilePos' => 15838,
              ),
            ),
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
            'startLine' => 395,
            'endLine' => 395,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'outputMode' => 
          array (
            'name' => 'outputMode',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 396,
                'endLine' => 396,
                'startTokenPos' => 1378,
                'startFilePos' => 15864,
                'endTokenPos' => 1378,
                'endFilePos' => 15865,
              ),
            ),
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
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 4,
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
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'addDocumentChart\'',
                'attributes' => 
                array (
                  'startLine' => 353,
                  'endLine' => 353,
                  'startTokenPos' => 1272,
                  'startFilePos' => 13700,
                  'endTokenPos' => 1272,
                  'endFilePos' => 13717,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'documentChart\'',
                'attributes' => 
                array (
                  'startLine' => 354,
                  'endLine' => 354,
                  'startTokenPos' => 1278,
                  'startFilePos' => 13731,
                  'endTokenPos' => 1278,
                  'endFilePos' => 13745,
                ),
              ),
              'action' => 
              array (
                'code' => '\'create\'',
                'attributes' => 
                array (
                  'startLine' => 355,
                  'endLine' => 355,
                  'startTokenPos' => 1284,
                  'startFilePos' => 13758,
                  'endTokenPos' => 1284,
                  'endFilePos' => 13765,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Add a bar, line or pie chart to a Word (.docx) file. The chart is a real chart the user \' . \'can select and resize in Word or Nextcloud Office, not a picture. Give it a type, a title, a list \' . \'of categories, and one or more series each carrying exactly one value per category (a pie chart \' . \'takes one series). Call readDocument first and pass back the version; pass an anchor to place the \' . \'chart after that paragraph, or omit it to append at the end. Charts need a .docx -- .odt is \' . \'refused by name. The file is tagged "Agent authored" and the previous version stays restorable.\'',
                'attributes' => 
                array (
                  'startLine' => 356,
                  'endLine' => 361,
                  'startTokenPos' => 1290,
                  'startFilePos' => 13783,
                  'endTokenPos' => 1310,
                  'endFilePos' => 14391,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 362,
                  'endLine' => 362,
                  'startTokenPos' => 1316,
                  'startFilePos' => 14410,
                  'endTokenPos' => 1316,
                  'endFilePos' => 14414,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 363,
                  'endLine' => 363,
                  'startTokenPos' => 1322,
                  'startFilePos' => 14436,
                  'endTokenPos' => 1322,
                  'endFilePos' => 14440,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 364,
                  'endLine' => 364,
                  'startTokenPos' => 1328,
                  'startFilePos' => 14461,
                  'endTokenPos' => 1328,
                  'endFilePos' => 14465,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 365,
                  'endLine' => 365,
                  'startTokenPos' => 1334,
                  'startFilePos' => 14477,
                  'endTokenPos' => 1334,
                  'endFilePos' => 14484,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 352,
        'endLine' => 417,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'readDocumentMetadata' => 
      array (
        'name' => 'readDocumentMetadata',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 39,
            'endColumn' => 49,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'readDocumentMetadata\'',
                'attributes' => 
                array (
                  'startLine' => 420,
                  'endLine' => 420,
                  'startTokenPos' => 1505,
                  'startFilePos' => 16273,
                  'endTokenPos' => 1505,
                  'endFilePos' => 16294,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'documentMetadata\'',
                'attributes' => 
                array (
                  'startLine' => 421,
                  'endLine' => 421,
                  'startTokenPos' => 1511,
                  'startFilePos' => 16308,
                  'endTokenPos' => 1511,
                  'endFilePos' => 16325,
                ),
              ),
              'action' => 
              array (
                'code' => '\'get\'',
                'attributes' => 
                array (
                  'startLine' => 422,
                  'endLine' => 422,
                  'startTokenPos' => 1517,
                  'startFilePos' => 16338,
                  'endTokenPos' => 1517,
                  'endFilePos' => 16342,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Read a Word (.docx) or OpenDocument (.odt) file\\\'s document properties: title, subject, \' . \'creator, keywords and description. Use this before setDocumentMetadata: it returns the version \' . \'that call requires. A property the document does not carry comes back as an empty string.\'',
                'attributes' => 
                array (
                  'startLine' => 423,
                  'endLine' => 425,
                  'startTokenPos' => 1523,
                  'startFilePos' => 16360,
                  'endTokenPos' => 1531,
                  'endFilePos' => 16649,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 426,
                  'endLine' => 426,
                  'startTokenPos' => 1537,
                  'startFilePos' => 16668,
                  'endTokenPos' => 1537,
                  'endFilePos' => 16671,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 427,
                  'endLine' => 427,
                  'startTokenPos' => 1543,
                  'startFilePos' => 16693,
                  'endTokenPos' => 1543,
                  'endFilePos' => 16697,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 428,
                  'endLine' => 428,
                  'startTokenPos' => 1549,
                  'startFilePos' => 16718,
                  'endTokenPos' => 1549,
                  'endFilePos' => 16721,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'read\'',
                'attributes' => 
                array (
                  'startLine' => 429,
                  'endLine' => 429,
                  'startTokenPos' => 1555,
                  'startFilePos' => 16733,
                  'endTokenPos' => 1555,
                  'endFilePos' => 16738,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 419,
        'endLine' => 449,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'setDocumentMetadata' => 
      array (
        'name' => 'setDocumentMetadata',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 489,
            'endLine' => 489,
            'startColumn' => 3,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
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
            'startLine' => 490,
            'endLine' => 490,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 491,
            'endLine' => 491,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'outputMode' => 
          array (
            'name' => 'outputMode',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 492,
                'endLine' => 492,
                'startTokenPos' => 1706,
                'startFilePos' => 19450,
                'endTokenPos' => 1706,
                'endFilePos' => 19451,
              ),
            ),
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
            'startLine' => 492,
            'endLine' => 492,
            'startColumn' => 3,
            'endColumn' => 25,
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
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'setDocumentMetadata\'',
                'attributes' => 
                array (
                  'startLine' => 452,
                  'endLine' => 452,
                  'startTokenPos' => 1613,
                  'startFilePos' => 17618,
                  'endTokenPos' => 1613,
                  'endFilePos' => 17638,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'documentMetadata\'',
                'attributes' => 
                array (
                  'startLine' => 453,
                  'endLine' => 453,
                  'startTokenPos' => 1619,
                  'startFilePos' => 17652,
                  'endTokenPos' => 1619,
                  'endFilePos' => 17669,
                ),
              ),
              'action' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 454,
                  'endLine' => 454,
                  'startTokenPos' => 1625,
                  'startFilePos' => 17682,
                  'endTokenPos' => 1625,
                  'endFilePos' => 17689,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Set a Word (.docx) or OpenDocument (.odt) file\\\'s document properties. Supported fields: \' . \'title, subject, creator, keywords, description. Call readDocumentMetadata first and pass back \' . \'the version it returned. Fields you do not name are left unchanged. Created and modified \' . \'timestamps cannot be set. The file is tagged "Agent authored" and the previous version stays \' . \'restorable in Nextcloud.\'',
                'attributes' => 
                array (
                  'startLine' => 455,
                  'endLine' => 459,
                  'startTokenPos' => 1631,
                  'startFilePos' => 17707,
                  'endTokenPos' => 1647,
                  'endFilePos' => 18129,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 460,
                  'endLine' => 460,
                  'startTokenPos' => 1653,
                  'startFilePos' => 18148,
                  'endTokenPos' => 1653,
                  'endFilePos' => 18152,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 461,
                  'endLine' => 461,
                  'startTokenPos' => 1659,
                  'startFilePos' => 18174,
                  'endTokenPos' => 1659,
                  'endFilePos' => 18178,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'true',
                'attributes' => 
                array (
                  'startLine' => 462,
                  'endLine' => 462,
                  'startTokenPos' => 1665,
                  'startFilePos' => 18199,
                  'endTokenPos' => 1665,
                  'endFilePos' => 18202,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'update\'',
                'attributes' => 
                array (
                  'startLine' => 463,
                  'endLine' => 463,
                  'startTokenPos' => 1671,
                  'startFilePos' => 18214,
                  'endTokenPos' => 1671,
                  'endFilePos' => 18221,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 451,
        'endLine' => 507,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'convertDocumentToPdf' => 
      array (
        'name' => 'convertDocumentToPdf',
        'parameters' => 
        array (
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 541,
            'endLine' => 541,
            'startColumn' => 39,
            'endColumn' => 49,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'OCA\\OpenRegister\\Mcp\\Attribute\\McpTool',
            'isRepeated' => false,
            'arguments' => 
            array (
              'name' => 
              array (
                'code' => '\'convertDocumentToPdf\'',
                'attributes' => 
                array (
                  'startLine' => 510,
                  'endLine' => 510,
                  'startTokenPos' => 1799,
                  'startFilePos' => 19763,
                  'endTokenPos' => 1799,
                  'endFilePos' => 19784,
                ),
              ),
              'subject' => 
              array (
                'code' => '\'document\'',
                'attributes' => 
                array (
                  'startLine' => 515,
                  'endLine' => 515,
                  'startTokenPos' => 1813,
                  'startFilePos' => 20059,
                  'endTokenPos' => 1813,
                  'endFilePos' => 20068,
                ),
              ),
              'action' => 
              array (
                'code' => '\'convert\'',
                'attributes' => 
                array (
                  'startLine' => 516,
                  'endLine' => 516,
                  'startTokenPos' => 1819,
                  'startFilePos' => 20081,
                  'endTokenPos' => 1819,
                  'endFilePos' => 20089,
                ),
              ),
              'description' => 
              array (
                'code' => '\'Convert a document in the user\\\'s files to PDF, writing a new PDF file and leaving the \' . \'source untouched. Reports which conversion backend produced the PDF. The produced file is \' . \'tagged "Agent authored" in Files.\'',
                'attributes' => 
                array (
                  'startLine' => 517,
                  'endLine' => 519,
                  'startTokenPos' => 1825,
                  'startFilePos' => 20107,
                  'endTokenPos' => 1833,
                  'endFilePos' => 20334,
                ),
              ),
              'readOnlyHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 520,
                  'endLine' => 520,
                  'startTokenPos' => 1839,
                  'startFilePos' => 20353,
                  'endTokenPos' => 1839,
                  'endFilePos' => 20357,
                ),
              ),
              'destructiveHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 521,
                  'endLine' => 521,
                  'startTokenPos' => 1845,
                  'startFilePos' => 20379,
                  'endTokenPos' => 1845,
                  'endFilePos' => 20383,
                ),
              ),
              'idempotentHint' => 
              array (
                'code' => 'false',
                'attributes' => 
                array (
                  'startLine' => 522,
                  'endLine' => 522,
                  'startTokenPos' => 1851,
                  'startFilePos' => 20404,
                  'endTokenPos' => 1851,
                  'endFilePos' => 20408,
                ),
              ),
              'scope' => 
              array (
                'code' => '\'create\'',
                'attributes' => 
                array (
                  'startLine' => 523,
                  'endLine' => 523,
                  'startTokenPos' => 1857,
                  'startFilePos' => 20420,
                  'endTokenPos' => 1857,
                  'endFilePos' => 20427,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 509,
        'endLine' => 579,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'record' => 
      array (
        'name' => 'record',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 26,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 52,
            'endColumn' => 63,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 66,
            'endColumn' => 79,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'note' => 
          array (
            'name' => 'note',
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 82,
            'endColumn' => 93,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Record the produced artefact in the generated-document audit trail.
 *
 * There is no template behind an agent edit or a conversion, so `templateId`
 * is genuinely empty rather than filled with a plausible-looking id. The row
 * exists so the artefact is findable from Filinq\'s own register; the
 * authoritative account of who asked for it is Hermiq\'s invocation record,
 * which the returned `artefact` descriptor feeds.
 *
 * A failure to record is logged and swallowed: the file is already written
 * and already tagged, and throwing here would report a failure that did not
 * happen.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The produced file id.
 * @param string $path The produced file path.
 * @param string $format The produced format.
 * @param string $note A human-readable note about the operation.
 *
 * @return void
 */',
        'startLine' => 602,
        'endLine' => 626,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'resolveReadableFile' => 
      array (
        'name' => 'resolveReadableFile',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fileId' => 
          array (
            'name' => 'fileId',
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 52,
            'endColumn' => 62,
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
            'name' => 'OCP\\Files\\File',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolve a file the acting user can read.
 *
 * @param string $uid The acting user id.
 * @param int $fileId The Nextcloud file id.
 *
 * @return File The file.
 *
 * @throws RuntimeException When the id names nothing the user can reach.
 */',
        'startLine' => 638,
        'endLine' => 650,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'relativePath' => 
      array (
        'name' => 'relativePath',
        'parameters' => 
        array (
          'uid' => 
          array (
            'name' => 'uid',
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
            'startLine' => 660,
            'endLine' => 660,
            'startColumn' => 32,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\Files\\File',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 660,
            'endLine' => 660,
            'startColumn' => 45,
            'endColumn' => 54,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Express a file\'s path relative to the acting user\'s root.
 *
 * @param string $uid The acting user id.
 * @param File $file The file.
 *
 * @return string The relative path, or the file name when it cannot be derived.
 */',
        'startLine' => 660,
        'endLine' => 667,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'aliasName' => NULL,
      ),
      'requireUid' => 
      array (
        'name' => 'requireUid',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The acting user, or a refusal.
 *
 * These tools run in the caller\'s ambient Nextcloud session (ADR-041). There
 * is no service user and no impersonation, so no session means no operation.
 *
 * @return string The acting user id.
 *
 * @throws RuntimeException When there is no signed-in user.
 */',
        'startLine' => 679,
        'endLine' => 686,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\DocumentAgentService',
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