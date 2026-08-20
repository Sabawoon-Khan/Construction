<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /** @var list<string> */
    private const INLINE_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'text/plain',
    ];

    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        abort_unless($request->user(), 403);
        abort_unless($attachment->canDownload($request->user()), 403);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $mime = $attachment->mime_type
            ?: (Storage::disk('local')->mimeType($attachment->file_path) ?: 'application/octet-stream');
        $inline = in_array($mime, self::INLINE_MIMES, true)
            || (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml');

        return Storage::disk('local')->response(
            $attachment->file_path,
            $attachment->original_filename ?: basename($attachment->file_path),
            [
                'Content-Type' => $mime,
                'X-Content-Type-Options' => 'nosniff',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        abort_unless($request->user(), 403);
        abort_unless($attachment->canDelete($request->user()), 403);

        $attachment->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Attachment moved to trash.'),
            ]);
        }

        return back()->with('success', __('Attachment moved to trash.'));
    }
}
