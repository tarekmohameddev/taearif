<?php

namespace App\Exceptions\Property;

use App\Exceptions\Property\PropertyException;

/**
 * Property Import Exception
 *
 * Specific exceptions for property import operations
 */
class PropertyImportException extends PropertyException
{
    // Error codes
    public const CODE_FILE_REQUIRED = 'IMPORT_FILE_REQUIRED';
    public const CODE_FILE_INVALID = 'IMPORT_FILE_INVALID';
    public const CODE_FILE_TOO_LARGE = 'IMPORT_FILE_TOO_LARGE';
    public const CODE_VALIDATION_ERROR = 'IMPORT_VALIDATION_ERROR';
    public const CODE_PROCESSING_ERROR = 'IMPORT_PROCESSING_ERROR';
    public const CODE_PERMISSION_DENIED = 'IMPORT_PERMISSION_DENIED';

    protected int $statusCode = 400;
    protected string $errorCode = self::CODE_PROCESSING_ERROR;

    /**
     * File not provided
     */
    public static function fileRequired(): self
    {
        return new self(
            message: 'A file is required for import',
            code: self::CODE_FILE_REQUIRED,
            statusCode: 422,
            details: [
                'suggestion' => 'Please select an Excel (.xlsx) or CSV (.csv) file to import.',
            ],
            safeMessage: 'Please provide a file to import'
        );
    }

    /**
     * Invalid file format
     */
    public static function fileInvalid(string $error = '', array $details = []): self
    {
        return new self(
            message: "Invalid file format: {$error}",
            code: self::CODE_FILE_INVALID,
            statusCode: 422,
            details: array_merge([
                'error' => $error,
                'suggestion' => 'Please ensure the file is a valid Excel (.xlsx) or CSV (.csv) file. Try opening it in Excel first to verify it\'s not corrupted.',
            ], $details),
            safeMessage: 'The uploaded file is invalid or corrupted'
        );
    }

    /**
     * File too large
     */
    public static function fileTooLarge(int $fileSize, int $maxSize): self
    {
        return new self(
            message: "File size ({$fileSize} bytes) exceeds maximum allowed size ({$maxSize} bytes)",
            code: self::CODE_FILE_TOO_LARGE,
            statusCode: 422,
            details: [
                'file_size' => $fileSize,
                'max_size' => $maxSize,
                'max_size_mb' => round($maxSize / 1048576, 2) . 'MB',
                'suggestion' => 'Please split your file into smaller files (max ' . round($maxSize / 1048576, 2) . 'MB each) or reduce the number of rows.',
            ],
            safeMessage: 'The file is too large. Please use a smaller file.'
        );
    }

    /**
     * Validation error during import
     */
    public static function validationError(array $errors, int $row = null, array $details = []): self
    {
        $message = 'Import validation failed';
        if ($row !== null) {
            $message .= " at row {$row}";
        }

        return new self(
            message: $message,
            code: self::CODE_VALIDATION_ERROR,
            statusCode: 422,
            details: array_merge([
                'row' => $row,
                'validation_errors' => $errors,
            ], $details),
            safeMessage: 'Please check your import data and fix the validation errors'
        );
    }

    /**
     * Processing error during import
     */
    public static function processingError(string $error, int $row = null, array $details = []): self
    {
        $message = "Import processing error: {$error}";
        if ($row !== null) {
            $message .= " at row {$row}";
        }

        return new self(
            message: $message,
            code: self::CODE_PROCESSING_ERROR,
            statusCode: 500,
            details: array_merge([
                'row' => $row,
                'error' => $error,
                'suggestion' => 'Please verify your file format and data, then try again. If the problem persists, contact support.',
            ], $details),
            safeMessage: 'An error occurred while processing the import'
        );
    }

    /**
     * Permission denied
     */
    public static function permissionDenied(?int $userId = null, string $reason = ''): self
    {
        return new self(
            message: "Permission denied to import properties. {$reason}",
            code: self::CODE_PERMISSION_DENIED,
            statusCode: 403,
            details: [
                'user_id' => $userId,
                'reason' => $reason,
                'suggestion' => 'Please ensure you have an active membership package and sufficient property listing quota.',
            ],
            safeMessage: 'You do not have permission to import properties'
        );
    }
}
