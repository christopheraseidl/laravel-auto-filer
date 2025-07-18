<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Storage disk
    |
    | The default disk to use for file storage. This should correspond to
    | a disk configured in your filesystems.php configuration file.
    */
    'disk' => 'public',

    /*
    | Temporary upload directory
    |
    | The directory where files are initially uploaded before being organized.
    | This directory will be periodically cleaned if cleanup is enabled.
    */
    'temp_directory' => 'uploads/temp',

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Queue connection
    |
    | The queue connection to use for processing file operations. Set to
    | 'sync' to process immediately without queuing.
    */
    'queue_connection' => null,

    /*
    | Queue name
    |
    | The specific queue to use for file operation jobs.
    */
    'queue' => null,

    /*
    | Broadcast channel
    |
    | The broadcast channel for real-time notifications about file operations.
    | Set to null to disable broadcasting.
    */
    'broadcast_channel' => 'default',

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    */

    /*
    | Maximum file size
    |
    | The maximum file size allowed for uploads, specified in kilobytes.
    | Files exceeding this limit will be rejected during upload.
    */
    'max_size' => 5120, // 5MB

    /*
    | Allowed MIME types
    |
    | Array of allowed MIME types for file uploads. Only files matching
    | these types will be accepted.
    */
    'mimes' => [
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'image/vnd.microsoft.icon',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/rtf',
        'text/plain',
        'text/markdown',
        'video/mp4',
        'audio/mpeg',
    ],

    /*
    | Allowed file extensions
    |
    | Array of allowed file extensions. This provides an additional layer
    | of validation beyond MIME type checking.
    */
    'extensions' => [
        'jpg',
        'jpeg',
        'png',
        'svg',
        'ico',
        'pdf',
        'doc',
        'docx',
        'odt',
        'rtf',
        'txt',
        'md',
        'mp4',
        'mp3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Generation
    |--------------------------------------------------------------------------
    */

    /*
    | Thumbnail configuration
    |
    | Settings for automatic thumbnail generation. When enabled, images
    | will have thumbnails created alongside the original files.
    */
    'thumbnails' => [
        'enabled' => false,
        'width' => 400,
        'height' => null, // null = maintain aspect ratio
        'suffix' => '-thumb',
        'quality' => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Orphaned file cleanup
    |
    | Configuration for automatic cleanup of orphaned temporary files.
    | When enabled, files in the temp directory older than the threshold
    | will be automatically removed.
    */
    'cleanup' => [
        'enabled' => false,
        'dry_run' => true,
        'threshold_hours' => 24,
        'schedule' => 'daily', // daily, hourly, or cron expression
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry & Throttling Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Maximum retry attempts
    |
    | The maximum number of times to retry a failed file operation before
    | giving up and marking it as permanently failed.
    */
    'maximum_file_operation_retries' => 3,

    /*
    | Retry wait seconds
    |
    | The time, in seconds, to wait between retry attempts.
    */

    'retry_wait_seconds' => 1,

    /*
    | Exception throttling
    |
    | Prevents cascading failures by limiting the number of exceptions
    | within a time window. Once exceeded, operations are temporarily blocked.
    */
    'throttle_exception_attempts' => 10,
    'throttle_exception_period' => 10, // minutes

];
