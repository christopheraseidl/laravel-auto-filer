<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | The default disk to use for file storage. This should correspond to
    | a disk configured in your filesystems.php configuration file.
    |
    */

    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Storage path
    |--------------------------------------------------------------------------
    |
    | The default path within the disk where files will be stored.
    | Leave empty to store files in the default path.
    |
    */

    'path' => '',

    /*
    |--------------------------------------------------------------------------
    | Broadcast channel
    |--------------------------------------------------------------------------
    |
    | The default broadcast channel to use for real-time notifications
    | and events related to file operations.
    |
    */

    'broadcast_channel' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Maximum file size
    |--------------------------------------------------------------------------
    |
    | The maximum file size allowed for uploads, specified in kilobytes.
    | Files exceeding this limit will be rejected during upload.
    |
    */

    'max_size' => 5120,

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME types
    |--------------------------------------------------------------------------
    |
    | An array of allowed file extensions for uploads. Only files with
    | these extensions will be accepted by the file upload system.
    |
    */

    'mimes' => [
        'jpg',
        'jpeg',
        'png',
        'ico',
        'pdf',
        'doc',
        'docx',
        'txt',
    ],

    /*
    |--------------------------------------------------------------------------
    | File cleanup configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for automatic file cleanup operations.
    | When enabled, files older than the specified threshold will be
    | automatically removed from storage.
    |
    */

    'cleanup' => [
        'enabled' => false,
        'dry_run' => true,
        'threshold_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception throttle maximum attempts
    |--------------------------------------------------------------------------
    |
    | The maximum number of exceptions allowed within the specified time period
    | before throttling is activated. Once this threshold is exceeded, the
    | system will temporarily reject or delay file operations to prevent
    | system overload and cascading failures.
    |
    */

    'throttle_exception_attempts' => 10,

    /*
    |--------------------------------------------------------------------------
    | Exception throttle period
    |--------------------------------------------------------------------------
    |
    | The time window (in minutes) used to calculate the exception threshold.
    | If the configured number of exceptions occurs within this period,
    | throttling will be activated. After this period expires, the exception
    | counter resets and normal operations can resume.
    |
    */

    'throttle_exception_period' => 10,

    /*
    |--------------------------------------------------------------------------
    | Circuit breaker configuration
    |--------------------------------------------------------------------------
    |
    | Circuit breaker settings for file operations to prevent cascading
    | failures and provide graceful degradation when file systems are
    | experiencing issues.
    |
    */

    'circuit_breaker' => [

        /*
        |--------------------------------------------------------------------------
        | Email notifications
        |--------------------------------------------------------------------------
        |
        | Enable or disable email notifications when circuit breakers are triggered.
        | When enabled, an email will be sent to the admin when a circuit breaker
        | transitions to the OPEN state.
        |
        */

        'email_notifications' => env('CIRCUIT_BREAKER_EMAIL_NOTIFICATIONS', false),

        /*
        |--------------------------------------------------------------------------
        | Admin email address
        |--------------------------------------------------------------------------
        |
        | The email address to send circuit breaker notifications to.
        | Defaults to the application's default mail from address if not specified.
        |
        */

        'admin_email' => env('CIRCUIT_BREAKER_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

        /*
        |--------------------------------------------------------------------------
        | Failure threshold
        |--------------------------------------------------------------------------
        |
        | Number of consecutive failures before opening the circuit.
        | Once this threshold is reached, the circuit will open and reject
        | subsequent requests for the configured recovery timeout period.
        |
        */

        'failure_threshold' => env('CIRCUIT_BREAKER_FILE_FAILURE_THRESHOLD', 5),

        /*
        |--------------------------------------------------------------------------
        | Recovery timeout
        |--------------------------------------------------------------------------
        |
        | Time in seconds to wait before attempting recovery (transition to half-open).
        | After this period, the circuit will allow a limited number of test
        | requests to determine if the service has recovered.
        |
        */

        'recovery_timeout' => env('CIRCUIT_BREAKER_FILE_RECOVERY_TIMEOUT', 60),

        /*
        |--------------------------------------------------------------------------
        | Half-open attempts
        |--------------------------------------------------------------------------
        |
        | Number of attempts allowed in half-open state before reopening.
        | If these test requests succeed, the circuit will close. If they fail,
        | the circuit will reopen for another recovery timeout period.
        |
        */

        'half_open_attempts' => env('CIRCUIT_BREAKER_FILE_HALF_OPEN_ATTEMPTS', 3),

        /*
        |--------------------------------------------------------------------------
        | Cache TTL
        |--------------------------------------------------------------------------
        |
        | Time-to-live for circuit breaker state cache in hours.
        | This determines how long the circuit breaker state is cached
        | before being refreshed from the storage backend.
        |
        */

        'cache_ttl' => 1,

    ],

];
