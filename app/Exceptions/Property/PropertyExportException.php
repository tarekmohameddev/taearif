<?php

namespace App\Exceptions\Property;

use App\Exceptions\Property\PropertyException;

/**
 * Property Export Exception
 *
 * Specific exceptions for property export operations
 */
class PropertyExportException extends PropertyException
{
    // Error codes
    public const CODE_VALIDATION_ERROR = 'EXPORT_VALIDATION_ERROR';
    public const CODE_NO_PROPERTIES = 'EXPORT_NO_PROPERTIES';
    public const CODE_FILE_GENERATION_ERROR = 'EXPORT_FILE_GENERATION_ERROR';
    public const CODE_PERMISSION_DENIED = 'EXPORT_PERMISSION_DENIED';
    public const CODE_SYSTEM_ERROR = 'EXPORT_SYSTEM_ERROR';

    protected int $statusCode = 400;
    protected string $errorCode = self::CODE_SYSTEM_ERROR;

    /**
     * Validation error during export
     */
    public static function validationError(array $errors, array $details = []): self
    {
        return new self(
            message: 'Export validation failed',
            code: self::CODE_VALIDATION_ERROR,
            statusCode: 422,
            details: array_merge(['validation_errors' => $errors], $details),
            safeMessage: 'Please check your export parameters and try again'
        );
    }

    /**
     * No properties found matching criteria
     */
    public static function noProperties(array $filters = []): self
    {
        return new self(
            message: 'No properties found matching the specified criteria',
            code: self::CODE_NO_PROPERTIES,
            statusCode: 404,
            details: [
                'filters_applied' => $filters,
                'suggestion' => 'Try adjusting your filters (date range, purpose, type, etc.) or check if you have any available properties.',
            ],
            safeMessage: 'No properties found matching your criteria'
        );
    }

    /**
     * File generation error
     */
    public static function fileGenerationError(string $error, array $details = []): self
    {
        return new self(
            message: "Failed to generate export file: {$error}",
            code: self::CODE_FILE_GENERATION_ERROR,
            statusCode: 500,
            details: array_merge(['error' => $error], $details),
            safeMessage: 'An error occurred while generating the export file. Please try again.'
        );
    }

    /**
     * Permission denied
     */
    public static function permissionDenied(?int $userId = null): self
    {
        return new self(
            message: 'Permission denied to export properties',
            code: self::CODE_PERMISSION_DENIED,
            statusCode: 403,
            details: [
                'user_id' => $userId,
                'suggestion' => 'Please ensure you have the necessary permissions to export properties.',
            ],
            safeMessage: 'You do not have permission to export properties'
        );
    }

    /**
     * System error
     */
    public static function systemError(string $error, array $details = []): self
    {
        return new self(
            message: "Export system error: {$error}",
            code: self::CODE_SYSTEM_ERROR,
            statusCode: 500,
            details: array_merge(['error' => $error], $details),
            safeMessage: 'An unexpected error occurred during export. Please try again later.'
        );
    }
}
