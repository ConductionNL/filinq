<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/AgentArtefactMarker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Editing\AgentArtefactMarker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-aa9415a7b56863500da5eed71ecaafb71cd2054a31f1149af350e11a8c9534d7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Editing/AgentArtefactMarker.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Editing',
    'name' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
    'shortName' => 'AgentArtefactMarker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Marks agent-produced files with the fleet-wide ADR-088 system tag.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service\\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-marked-as-agent-authored-at-write-time
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 186,
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
      'TAG_NAME' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'name' => 'TAG_NAME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'Agent authored\'',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 60,
            'startFilePos' => 2202,
            'endTokenPos' => 60,
            'endFilePos' => 2217,
          ),
        ),
        'docComment' => '/**
 * The ADR-088 tag name.
 *
 * Deliberately NOT translated. The tag is one row in one database and every
 * app in the fleet has to agree on it -- Hermiq marks calendar events and
 * contacts with the same string. A per-language name would fragment the tag
 * per user locale and make "show me everything an agent touched" return a
 * different set for every user.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 42,
      ),
      'OBJECT_TYPE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'name' => 'OBJECT_TYPE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'files\'',
          'attributes' => 
          array (
            'startLine' => 71,
            'endLine' => 71,
            'startTokenPos' => 73,
            'startFilePos' => 2344,
            'endTokenPos' => 73,
            'endFilePos' => 2350,
          ),
        ),
        'docComment' => '/**
 * Nextcloud\'s object type for files in the system-tag mapper.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'tagManager' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'name' => 'tagManager',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\SystemTag\\ISystemTagManager',
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
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'tagMapper' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'name' => 'tagMapper',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\SystemTag\\ISystemTagObjectMapper',
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
        'endColumn' => 52,
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
          'tagManager' => 
          array (
            'name' => 'tagManager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\SystemTag\\ISystemTagManager',
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
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tagMapper' => 
          array (
            'name' => 'tagMapper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\SystemTag\\ISystemTagObjectMapper',
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
            'endColumn' => 52,
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
 * Constructor.
 *
 * @param ISystemTagManager $tagManager The system tag manager.
 * @param ISystemTagObjectMapper $tagMapper The system tag object mapper.
 *
 * @return void
 */',
        'startLine' => 81,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'aliasName' => NULL,
      ),
      'mark' => 
      array (
        'name' => 'mark',
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
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 23,
            'endColumn' => 33,
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
 * Mark a file as agent-authored.
 *
 * Throws rather than returning false. A caller that gets a boolean back
 * tends to log it and carry on, and "the file was written but not marked"
 * is the single outcome nothing downstream will ever re-examine.
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return bool True when this call added the tag, false when it was already present.
 *
 * @throws RuntimeException When the tag cannot be resolved or assigned.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-marked-as-agent-authored-at-write-time
 */',
        'startLine' => 103,
        'endLine' => 129,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'aliasName' => NULL,
      ),
      'unmark' => 
      array (
        'name' => 'unmark',
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
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 25,
            'endColumn' => 35,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove the mark this service added.
 *
 * Used only to undo a mark applied moments earlier by a write that then
 * failed, so a file the agent did not in the end change is not left
 * claiming it did.
 *
 * @param int $fileId The Nextcloud file id.
 *
 * @return void
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-every-produced-file-is-marked-as-agent-authored-at-write-time
 */',
        'startLine' => 144,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'aliasName' => NULL,
      ),
      'resolveTag' => 
      array (
        'name' => 'resolveTag',
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
 * Resolve the tag id, creating the tag the first time it is needed.
 *
 * User-visible and user-assignable: the point is that a person can see it in
 * Files and filter on it.
 *
 * @return string The system tag id.
 *
 * @throws RuntimeException When the tag can be neither found nor created.
 */',
        'startLine' => 164,
        'endLine' => 185,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Editing',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
        'currentClassName' => 'OCA\\Filinq\\Service\\Editing\\AgentArtefactMarker',
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