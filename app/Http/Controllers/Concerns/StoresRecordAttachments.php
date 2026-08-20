<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait StoresRecordAttachments
{
    /** @return array<string, mixed> */
    protected function attachmentValidationRules(): array
    {
        return [
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|max:20480',
        ];
    }

    protected function storeRecordAttachments(Request $request, Model $model): void
    {
        if (! method_exists($model, 'storeAttachment') || ! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents', []) as $file) {
            if (! $file) {
                continue;
            }

            $model->storeAttachment($file, $file->getClientOriginalName(), $request->user()?->id);
        }
    }
}
