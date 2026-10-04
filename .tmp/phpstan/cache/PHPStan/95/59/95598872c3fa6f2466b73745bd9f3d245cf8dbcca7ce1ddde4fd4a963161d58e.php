<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/ConsolidateRegistersDecisions.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Repair\ConsolidateRegistersDecisions
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-99e083cb5beddeea72f4c80e44aa9a42227ca52cd1197431ef98e5d07081e7f7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Repair/ConsolidateRegistersDecisions.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Repair',
    'name' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
    'shortName' => 'ConsolidateRegistersDecisions',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Pure predicates for the five-into-one register consolidation.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
    'endLine' => 432,
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
      'TABLE_MARKER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'name' => 'TABLE_MARKER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'openregister_table_\'',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 35,
            'startFilePos' => 2124,
            'endTokenPos' => 35,
            'endFilePos' => 2144,
          ),
        ),
        'docComment' => '/**
 * The marker every OpenRegister shard table carries in its name.
 *
 * MATCHED ON THE MARKER, NEVER ON A COMPUTED `oc_` PREFIX. The Nextcloud
 * table prefix is an install-time setting (`dbtableprefix`), so hard-coding
 * `oc_` is a guess. A prefix guess that misses does not error — it returns
 * ZERO tables, which reads exactly like "this install has no objects" and
 * would let this step report a successful consolidation having moved
 * nothing at all.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
      'CHUNK_SIZE' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'name' => 'CHUNK_SIZE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '250',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 48,
            'startFilePos' => 2719,
            'endTokenPos' => 48,
            'endFilePos' => 2721,
          ),
        ),
        'docComment' => '/**
 * How many uuids may ride in a single IN clause.
 *
 * Bound low deliberately. Every database has a cap on bound parameters
 * (SQLite\'s default is 999, MySQL\'s max_prepared_stmt_count is per
 * connection), and a step that only ever runs on somebody else\'s install
 * must not discover that cap there. Chunking is also the failure
 * granularity: an insert-verify-delete cycle runs per chunk, so a
 * mid-migration failure leaves whole chunks moved and whole chunks
 * untouched, never a half-written row.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 31,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'tablePattern' => 
      array (
        'name' => 'tablePattern',
        'parameters' => 
        array (
          'registerId' => 
          array (
            'name' => 'registerId',
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 32,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'schemaId' => 
          array (
            'name' => 'schemaId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 91,
                'endLine' => 91,
                'startTokenPos' => 71,
                'startFilePos' => 3674,
                'endTokenPos' => 71,
                'endFilePos' => 3677,
              ),
            ),
            'type' => 
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 49,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * The regex a shard table name must match to be touched at all.
 *
 * Anchored at both ends and built from integers that have already been cast
 * to `int` by the caller, so there is nothing here an attacker-controlled
 * string could reach. This exists because a table name CANNOT be a bound
 * parameter — it is interpolated into SQL — and interpolating anything that
 * has not been shape-checked is how a repair step becomes an injection
 * point.
 *
 * @param int $registerId The register id the table must belong to.
 * @param int|null $schemaId The schema id, or null to accept any.
 *
 * @return string A PCRE pattern.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 91,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'shardTablesFor' => 
      array (
        'name' => 'shardTablesFor',
        'parameters' => 
        array (
          'names' => 
          array (
            'name' => 'names',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'registerId' => 
          array (
            'name' => 'registerId',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 47,
            'endColumn' => 61,
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
 * Pick this register\'s shard tables out of a list of table names.
 *
 * The list must be one an `information_schema` query returned. This method
 * only ever NARROWS it — it never composes a name — so a table this step
 * goes on to address is a table the database said exists.
 *
 * TWO NAMES FOR ONE SCHEMA ID IS AMBIGUITY, NOT A CHOICE. On MySQL,
 * `information_schema.tables` spans every database on the server, so an
 * install sharing a server with a second Nextcloud (`nc_` beside `oc_`) can
 * legitimately return two names matching the same (register, schema) pair.
 * Guessing which one is "ours" from a prefix is exactly the guess this
 * class refuses to make, so the pair is dropped and reported.
 *
 * @param array<int, string> $names Table names as the database reported them.
 * @param int $registerId The register whose shards are wanted.
 *
 * @return array{tables: array<int, string>, ambiguous: array<int, int>}
 *                                                                       `tables` is schema id => table name; `ambiguous` lists the schema ids
 *                                                                       dropped because more than one name claimed them.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 125,
        'endLine' => 156,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'isSafeTableName' => 
      array (
        'name' => 'isSafeTableName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 34,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'registerId' => 
          array (
            'name' => 'registerId',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 48,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'schemaId' => 
          array (
            'name' => 'schemaId',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 65,
            'endColumn' => 77,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Final gate before a table name is interpolated into SQL.
 *
 * Deliberately re-checks what shardTablesFor() already filtered on, against
 * the exact (register, schema) pair this call is about. The two checks are
 * not redundant: the first says "this is a shard table of register N", this
 * one says "this is the shard table of register N and schema M, the one the
 * caller believes it is holding". A step that moves rows between tables has
 * exactly one chance to notice it is holding the wrong one.
 *
 * @param string $name The candidate table name.
 * @param int $registerId The register the table must belong to.
 * @param int $schemaId The schema the table must belong to.
 *
 * @return bool True when the name is safe to interpolate.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 178,
        'endLine' => 180,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'missingColumns' => 
      array (
        'name' => 'missingColumns',
        'parameters' => 
        array (
          'sourceColumns' => 
          array (
            'name' => 'sourceColumns',
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
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetColumns' => 
          array (
            'name' => 'targetColumns',
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
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 55,
            'endColumn' => 74,
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
 * Columns the source carries that the target does not.
 *
 * Both tables are generated from the SAME schema id, so in the ordinary
 * case this is empty and that is what makes `INSERT INTO target SELECT ...
 * FROM source` viable at all. It is checked anyway: MagicMapper ADDS a
 * column when a schema property appears and never removes one, so a source
 * table that was synced against a newer schema version than the target can
 * genuinely carry a column the target lacks. Copying it silently drops that
 * property from every migrated object — a data loss no count reconciles,
 * because the ROWS all arrive.
 *
 * `_id` is excluded because it is deliberately not carried across: it is a
 * per-table sequence, and the target assigns its own. `_uuid` is the
 * identity that survives the move.
 *
 * @param array<int, string> $sourceColumns Columns of the source table.
 * @param array<int, string> $targetColumns Columns of the target table.
 *
 * @return array<int, string> The unmatched source columns, sorted.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 207,
        'endLine' => 216,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'insertColumns' => 
      array (
        'name' => 'insertColumns',
        'parameters' => 
        array (
          'sourceColumns' => 
          array (
            'name' => 'sourceColumns',
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
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 32,
            'endColumn' => 51,
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
        ),
        'docComment' => '/**
 * The columns an INSERT should carry, in a stable order.
 *
 * Lower-cased and de-duplicated so the comparison against the target\'s
 * columns is not defeated by a database that reports identifiers in a
 * different case than another one does.
 *
 * @param array<int, string> $sourceColumns Columns of the source table.
 *
 * @return array<int, string> Columns to carry, `_id` removed.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 233,
        'endLine' => 243,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'looksLikeShardTable' => 
      array (
        'name' => 'looksLikeShardTable',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 38,
            'endColumn' => 51,
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
 * Whether a column list is shaped like an OpenRegister shard table.
 *
 * `_uuid` is the identity every step of the move is keyed on and `_register`
 * is the column the move exists to rewrite. A table missing either is not
 * one of ours whatever its name says, and this step must not write to it.
 *
 * @param array<int, string> $columns The column list to check.
 *
 * @return bool True when both required columns are present.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 260,
        'endLine' => 265,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'partitionUuids' => 
      array (
        'name' => 'partitionUuids',
        'parameters' => 
        array (
          'sourceUuids' => 
          array (
            'name' => 'sourceUuids',
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
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 33,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'targetUuids' => 
          array (
            'name' => 'targetUuids',
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
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 53,
            'endColumn' => 70,
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
 * Split the source uuids into the ones that may move and the ones that may not.
 *
 * A uuid already present in the target is a CONFLICT, and this step refuses
 * it rather than resolving it. Two objects sharing a uuid across the two
 * tables can mean a previous run inserted and then failed to delete, or it
 * can mean two genuinely different objects that were assigned the same
 * identity. Those want opposite treatments and nothing available here tells
 * them apart, so both rows are left exactly where they are and the conflict
 * is logged. Merging is a decision about data, not a migration.
 *
 * @param array<int, string> $sourceUuids Uuids in the source table.
 * @param array<int, string> $targetUuids Uuids already in the target table.
 *
 * @return array{movable: array<int, string>, conflicts: array<int, string>}
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 287,
        'endLine' => 300,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'chunk' => 
      array (
        'name' => 'chunk',
        'parameters' => 
        array (
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 314,
            'endLine' => 314,
            'startColumn' => 24,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'size' => 
          array (
            'name' => 'size',
            'default' => 
            array (
              'code' => 'self::CHUNK_SIZE',
              'attributes' => 
              array (
                'startLine' => 314,
                'endLine' => 314,
                'startTokenPos' => 874,
                'startFilePos' => 12906,
                'endTokenPos' => 876,
                'endFilePos' => 12921,
              ),
            ),
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
            'startLine' => 314,
            'endLine' => 314,
            'startColumn' => 38,
            'endColumn' => 65,
            'parameterIndex' => 1,
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
 * Cut a list of uuids into IN-clause sized batches.
 *
 * @param array<int, string> $items The uuids.
 * @param int $size Maximum batch size.
 *
 * @return array<int, array<int, string>> The batches.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 314,
        'endLine' => 320,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'placeholders' => 
      array (
        'name' => 'placeholders',
        'parameters' => 
        array (
          'count' => 
          array (
            'name' => 'count',
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
            'startLine' => 337,
            'endLine' => 337,
            'startColumn' => 31,
            'endColumn' => 40,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the `?,?,?` placeholder list for an IN clause.
 *
 * Here rather than inline because a mismatch between the placeholder count
 * and the bound parameters is the kind of error that only shows up at
 * runtime, inside a repair step, on somebody else\'s install.
 *
 * @param int $count Number of bound parameters.
 *
 * @return string The placeholder list.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 337,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'selectExpressions' => 
      array (
        'name' => 'selectExpressions',
        'parameters' => 
        array (
          'columns' => 
          array (
            'name' => 'columns',
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
            'startLine' => 358,
            'endLine' => 358,
            'startColumn' => 36,
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
        ),
        'docComment' => '/**
 * The SELECT list that rewrites `_register` while copying everything else.
 *
 * `_register` becomes a bound `?` — the one value the move changes — and
 * every other column is copied through by name. Returning the expressions
 * alongside the column list keeps the two in lockstep; building them at two
 * call sites is how an INSERT ends up writing the right values into the
 * wrong columns.
 *
 * @param array<int, string> $columns The columns to carry, from insertColumns().
 *
 * @return array<int, string> One SELECT expression per column, same order.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 358,
        'endLine' => 370,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'reconciled' => 
      array (
        'name' => 'reconciled',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 391,
            'endLine' => 391,
            'startColumn' => 29,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'observed' => 
          array (
            'name' => 'observed',
            'default' => NULL,
            'type' => 
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 391,
            'endLine' => 391,
            'startColumn' => 44,
            'endColumn' => 57,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether an insert reconciled, i.e. whether the target now holds the batch.
 *
 * The whole point of the insert-verify-delete ordering hangs on this
 * returning false when it should. It compares what the target holds against
 * what was asked for, not against what the driver\'s affected-row count
 * said — a driver reporting `n` rows written is a claim, and the count
 * queried back out of the target is an observation.
 *
 * @param int $expected How many uuids the batch contained.
 * @param int|null $observed How many of them the target now holds, or null
 *                           when the verification query itself failed.
 *
 * @return bool True only when every uuid in the batch was observed.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 391,
        'endLine' => 397,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'aliasName' => NULL,
      ),
      'column' => 
      array (
        'name' => 'column',
        'parameters' => 
        array (
          'rows' => 
          array (
            'name' => 'rows',
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
            'startLine' => 424,
            'endLine' => 424,
            'startColumn' => 25,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
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
            'startLine' => 424,
            'endLine' => 424,
            'startColumn' => 38,
            'endColumn' => 51,
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
 * Pull one string column out of a result set, dropping blanks.
 *
 * Defensive on purpose: a row with a null value must yield an empty string
 * rather than a TypeError inside a repair step, where an escaping exception
 * aborts the install and the app never enables — and then that empty string
 * is DISCARDED rather than returned. Every caller here reads an identifier:
 * a table name, a column name, a uuid. A blank one addresses nothing, and
 * carrying it forward would put an empty string into an IN clause or, worse,
 * into a name that gets interpolated into SQL.
 *
 * The upper-cased fallback is not decoration: some drivers report
 * `information_schema` column labels in upper case, and reading only the
 * lower-cased key there returns a list of blanks — which, before this
 * dropped them, was a list of empty table names that looked like data.
 *
 * @param array<int, array<string, mixed>> $rows The result rows.
 * @param string $column The column to pull.
 *
 * @return array<int, string> The non-blank values.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq\'s five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */',
        'startLine' => 424,
        'endLine' => 431,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Repair',
        'declaringClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'implementingClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
        'currentClassName' => 'OCA\\Filinq\\Repair\\ConsolidateRegistersDecisions',
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