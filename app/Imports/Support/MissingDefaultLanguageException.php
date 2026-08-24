<?php

namespace App\Imports\Support;

/**
 * Thrown when a sheet importer requires a tenant default language and none exists.
 * Catchable so the import service can skip that sheet without aborting others.
 */
class MissingDefaultLanguageException extends \RuntimeException
{
    public function __construct(string $entity = 'projects/properties')
    {
        parent::__construct(
            "Tenant has no default language configured — {$entity} skipped"
        );
    }
}
