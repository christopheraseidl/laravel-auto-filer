<?php

namespace christopheraseidl\ModelFiler\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BaseException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected bool $shouldNotifyAdmin = false,
        protected ?string $adminEmail = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->adminEmail ??= config('model-filer.admin_email');
    }

    /**
     * Laravel calls this method automatically when the exception is thrown.
     */
    public function report(): void
    {
        $this->logError();
        $this->sendAdminNotification();
    }

    protected function logError(): void
    {
        Log::channel($this->getLogChannel())->error($this->getMessage(), [
            'exception' => get_class($this),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ]);
    }

    protected function getLogChannel(): string
    {
        return 'stack';
    }

    protected function sendAdminNotification(): void
    {
        if ($this->shouldNotifyAdmin && $this->adminEmail) {
            $content = $this->getMailContent();
            $appName = $this->getAppName();

            Mail::raw($content, function ($mail) use ($appName) {
                $mail->to($this->adminEmail)
                    ->subject("[{$appName}] Exception: ".get_class($this));
            });
        }
    }

    private function getMailContent(): string
    {
        $appName = $this->getAppName();
        $appEnv = $this->getAppEnv();

        $message = "An exception occurred in {$appName} ({$appEnv}):\n";
        $message .= "{$this->getMessage()}\n\n";
        $message .= "Please check the logs for full details.\n";
        $message .= 'Time: '.now()->toDateTimeString();

        return $message;
    }

    private function getAppName(): string
    {
        return config('app.name');
    }

    private function getAppEnv(): string
    {
        return config('app.env');
    }
}
