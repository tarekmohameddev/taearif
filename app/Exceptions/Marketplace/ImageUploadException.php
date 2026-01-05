<?php

namespace App\Exceptions\Marketplace;

use App\Exceptions\BusinessLogicException;

/**
 * Image Upload Exception
 *
 * Thrown when image upload or validation fails
 */
class ImageUploadException extends BusinessLogicException
{
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param string $errorCode
     * @param int $httpCode
     */
    public function __construct(
        string $message = 'Image upload failed',
        string $errorCode = 'IMAGE_UPLOAD_FAILED',
        int $httpCode = 422
    ) {
        parent::__construct($message, $errorCode, $httpCode);
    }
}

