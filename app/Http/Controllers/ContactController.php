<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = ContactMessage::query()->create([
            ...$data,
            'ip_address' => $request->ip(),
        ]);

        $company = Company::query()->first();
        $to = $company?->email ?: config('mail.from.address');

        if (filled($to)) {
            try {
                Mail::raw(
                    "New website inquiry\n\n".
                    "Name: {$message->name}\n".
                    'Phone: '.($message->phone ?: '—')."\n".
                    "Email: {$message->email}\n\n".
                    "{$message->message}",
                    function ($mail) use ($to, $message): void {
                        $mail->to($to)
                            ->replyTo($message->email, $message->name)
                            ->subject('Website contact: '.$message->name);
                    },
                );
            } catch (Throwable $e) {
                Log::warning('Contact form mail failed', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', __('Thanks — we received your message and will reply soon.'));
    }
}
