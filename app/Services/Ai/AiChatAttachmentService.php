<?php

namespace App\Services\Ai;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\File;
use Laravel\Ai\Files\Image;

class AiChatAttachmentService
{
    /**
     * @return list<string>
     */
    public static function allowedMimes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'text/plain',
            'text/csv',
            'application/csv',
        ];
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{name: string, path: string, mime: string, size: int, attachment: File}>
     */
    public function storeForUser(int $userId, array $files): array
    {
        $maxFiles = (int) config('ai.chat_attachments.max_files', 5);
        $maxKb = (int) config('ai.chat_attachments.max_kb', 10_240);

        if (count($files) > $maxFiles) {
            throw new InvalidArgumentException("You can attach at most {$maxFiles} files per message.");
        }

        $stored = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! $file->isValid()) {
                throw new InvalidArgumentException('One of the uploads is invalid.');
            }

            if ($file->getSize() > $maxKb * 1024) {
                throw new InvalidArgumentException(
                    "{$file->getClientOriginalName()} is too large (max {$maxKb} KB).",
                );
            }

            $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
            if (! in_array($mime, self::allowedMimes(), true)) {
                throw new InvalidArgumentException(
                    "{$file->getClientOriginalName()} type is not allowed. Use PDF, images, TXT, or CSV.",
                );
            }

            $dir = 'ai-chat/'.$userId.'/'.now()->format('Y/m/d');
            $name = Str::uuid()->toString().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $path = $file->storeAs($dir, $name.'.'.$ext, 'local');

            if ($path === false) {
                throw new InvalidArgumentException('Could not store uploaded file.');
            }

            $absolute = Storage::disk('local')->path($path);
            $attachment = $this->toAiAttachment($absolute, $mime, $file->getClientOriginalName());

            $stored[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime' => $mime,
                'size' => (int) $file->getSize(),
                'attachment' => $attachment,
            ];
        }

        return $stored;
    }

    protected function toAiAttachment(string $absolutePath, string $mime, string $originalName): File
    {
        if (str_starts_with($mime, 'image/')) {
            return Image::fromPath($absolutePath, $mime)->as($originalName);
        }

        return Document::fromPath($absolutePath)->as($originalName);
    }
}
