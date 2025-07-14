<?php

namespace christopheraseidl\ModelFiler\Jobs;

use christopheraseidl\ModelFiler\Contracts\FileDeleter;
use christopheraseidl\ModelFiler\Contracts\FileMover;
use christopheraseidl\ModelFiler\Contracts\RichTextScanner;
use christopheraseidl\ModelFiler\Events\ProcessingComplete;
use christopheraseidl\ModelFiler\Events\ProcessingFailure;
use christopheraseidl\ModelFiler\ValueObjects\ChangeManifest;
use christopheraseidl\ModelFiler\ValueObjects\FileOperation;
use christopheraseidl\ModelFiler\ValueObjects\OperationType;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFileOperations
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public $uniqueFor = 60 * 30;

    public function __construct(
        private readonly ChangeManifest $manifest
    ) {
        $this->onConnection(config('model-filer.queue_connection'));
        $this->onQueue(config('model-filer.queue'));
    }

    /**
     * Process all file operations in the change manifest.
     */
    public function handle(FileMover $mover, FileDeleter $deleter, RichTextScanner $scanner): void
    {
        try {
            DB::transaction(function () use ($mover, $deleter, $scanner) {
                foreach ($this->manifest->operations as $operation) {
                    match ($operation->type) {
                        OperationType::Move => $this->handleMove($operation, $mover),
                        OperationType::MoveRichText => $this->handleRichTextMove($operation, $mover, $scanner),
                        OperationType::Delete => $deleter->delete($operation->source),
                        default => throw new \InvalidArgumentException('Unknown operation type: '.$operation->type),
                    };
                }

                broadcast(new ProcessingComplete);
            });
        } catch (\Throwable $e) {
            Log::error('Model Filer: ProcessFileOperations job temporarily failed', [
                'error' => $e->getMessage(),
            ]);

            broadcast(new ProcessingFailure($e));

            throw $e; // Rethrow to maintain Laravel's job lifecycle
        }
    }

    /**
     * Set retry timeout for failed jobs to 5 minutes.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Handle permanent job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Model Filer: ProcessFileOperations job PERMANENTLY failed', [
            'change_manifest' => $this->manifest->operations->toArray(),
        ]);
    }

    /**
     * Define middleware for job execution with throttling and rate limiting.
     */
    public function middleware(): array
    {
        // By default, allow 10 exceptions in 5 minutes
        $maxAttempts = config('model-filer.throttle_exception_attempts', 10);
        $period = config('model-filer.throttle_exception_period', 5);

        return [
            new ThrottlesExceptions($maxAttempts, $period),
            new RateLimited('model-filer'),
        ];
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        $signature = $this->manifest->operations
            ->map(fn ($op) => $op->type->value.':'.$op->source.':'.$op->destination)
            ->sort()
            ->join('|');

        return static::class.'_'.hash('sha256', $signature);
    }

    /**
     * Handle file move and model attribute update in the database.
     */
    protected function handleMove(FileOperation $operation, FileMover $mover): void
    {
        $mover->move($operation->source, $operation->destination);

        // Get the model for updating after successful move
        $model = $operation->modelClass::find($operation->modelId);

        if (! $model) {
            throw new \RuntimeException("Model not found: {$operation->modelClass}#{$operation->modelId}");
        }

        // Get the current path
        $current = Arr::wrap($model->{$operation->attribute});

        // Replace the old path with the new path
        $updated = collect($current)
            ->map(fn ($path) => $path === $operation->source ? $operation->destination : $path)
            ->all();

        $model->{$operation->attribute} = count($updated) === 1 ? $updated[0] : $updated;
        $model->saveQuietly();
    }

    /**
     * Handle rich text move with content update.
     */
    protected function handleRichTextMove(FileOperation $operation, FileMover $mover, RichTextScanner $scanner): void
    {
        // Move the file
        $mover->move($operation->source, $operation->destination);

        // Get the model for updating after successful move
        $model = $operation->modelClass::find($operation->modelId);
        if (! $model) {
            throw new \RuntimeException("Model not found: {$operation->modelClass}#{$operation->modelId}");
        }

        // Update the content
        $content = $model->{$operation->attribute};
        $updated = $scanner->updatePaths($content, [
            $operation->source => $operation->destination,
        ]);

        $model->{$operation->attribute} = $updated;
        $model->saveQuietly();
    }
}
