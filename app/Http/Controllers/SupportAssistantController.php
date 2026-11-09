<?php

namespace App\Http\Controllers;

use App\Mail\SupportInquiryReplyMail;
use App\Models\SupportInquiry;
use App\Notifications\NewSupportInquiryNotification;
use App\Services\AssistantFaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SupportAssistantController extends Controller
{
    public function answer(Request $request, AssistantFaqService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        return response()->json($assistant->answer($validated['question']));
    }

    public function escalate(Request $request, AssistantFaqService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:2000'],
            'messages' => ['sometimes', 'array', 'max:50'],
            'messages.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'messages.*.text' => ['required', 'string', 'min:1', 'max:2000'],
            'messages.*.created_at' => ['nullable', 'string', 'max:100'],
            'contact' => ['sometimes', 'array'],
            'contact.name' => ['nullable', 'string', 'max:255'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
        ]);

        $conversation = collect($validated['messages'] ?? [])
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'text' => trim($message['text']),
                'created_at' => isset($message['created_at']) && is_string($message['created_at'])
                    ? $message['created_at']
                    : now()->toIso8601String(),
            ])
            ->values();
        $question = trim($validated['question']);
        if (($conversation->last()['role'] ?? null) !== 'user'
            || ($conversation->last()['text'] ?? null) !== $question) {
            $conversation->push([
                'role' => 'user',
                'text' => $question,
                'created_at' => now()->toIso8601String(),
            ]);
        }

        $user = $request->user();
        $contact = $user
            ? [
                'contact_name' => $user->name,
                'contact_email' => $user->email,
                'contact_phone' => $user->student_phone,
            ]
            : [
                'contact_name' => filled($validated['contact']['name'] ?? null)
                    ? trim($validated['contact']['name'])
                    : null,
                'contact_email' => filled($validated['contact']['email'] ?? null)
                    ? trim($validated['contact']['email'])
                    : null,
                'contact_phone' => filled($validated['contact']['phone'] ?? null)
                    ? trim($validated['contact']['phone'])
                    : null,
            ];

        $accessToken = Str::random(64);
        $inquiry = SupportInquiry::create([
            'question' => $question,
            'conversation' => $conversation->all(),
            'access_token_hash' => hash('sha256', $accessToken),
            ...$contact,
        ]);

        $supportEmail = config('assistant.support_email');
        if (is_string($supportEmail) && filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $supportEmail)
                ->notify(new NewSupportInquiryNotification($inquiry));
        }

        return response()->json([
            'message' => 'وصلت المحادثة لفريق الدعم. يمكنك متابعة الرد هنا، واحتفظ بهذه الجلسة للعودة إلى المحادثة.',
            'conversation_token' => $accessToken,
            'status' => $inquiry->status,
        ], 201);
    }

    public function conversation(string $token): JsonResponse
    {
        $inquiry = $this->findInquiryByToken($token);

        return response()->json([
            'messages' => $inquiry->conversation ?? [],
            'status' => $inquiry->status,
        ]);
    }

    public function reply(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ]);
        $inquiry = $this->findInquiryByToken($token);
        $messages = $inquiry->conversation ?? [];
        $messages[] = [
            'role' => 'user',
            'text' => trim($validated['message']),
            'created_at' => now()->toIso8601String(),
        ];
        $inquiry->update([
            'conversation' => $messages,
            'status' => 'new',
        ]);

        return response()->json([
            'message' => 'تم إرسال رسالتك لفريق الدعم.',
            'messages' => $messages,
            'status' => $inquiry->status,
        ], 201);
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'new', 'in_progress', 'resolved'])],
            'search' => ['nullable', 'string', 'max:255'],
            'inquiry' => ['nullable', 'integer', 'min:1'],
        ]);

        $status = $validated['status'] ?? 'all';
        $search = trim($validated['search'] ?? '');

        $inquiryQuery = SupportInquiry::query()->latest();
        if ($status !== 'all') {
            $inquiryQuery->where('status', $status);
        }
        if ($search !== '') {
            $inquiryQuery->where(function ($query) use ($search) {
                $query->where('question', 'like', '%' . $search . '%')
                    ->orWhere('contact_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_email', 'like', '%' . $search . '%');
            });
        }

        $stats = $this->inquiryStats();

        return view('support.inquiries', [
            'inquiries' => $inquiryQuery->paginate(20)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, SupportInquiry $inquiry): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_progress', 'resolved'])],
        ]);

        $inquiry->update($validated);

        return $request->expectsJson()
            ? response()->json(['status' => $inquiry->status])
            : back()->with('success', 'تم تحديث حالة الاستفسار.');
    }

    public function respond(Request $request, SupportInquiry $inquiry): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ]);
        $reply = [
            'role' => 'support',
            'text' => trim($validated['message']),
            'created_at' => now()->toIso8601String(),
        ];
        $messages = $inquiry->conversation ?? [];
        $messages[] = $reply;
        $inquiry->update([
            'conversation' => $messages,
            'status' => 'in_progress',
        ]);

        if (!filled($inquiry->contact_email)) {
            return $this->replyResponse(
                $request,
                $inquiry,
                $reply,
                'تم حفظ الرد في المحادثة. لم يضف الزائر بريدًا إلكترونيًا.',
                false,
            );
        }

        try {
            Mail::to($inquiry->contact_email)->send(new SupportInquiryReplyMail(
                $inquiry->contact_name ?: 'زائر مدار',
                $inquiry->question,
                $reply['text'],
            ));

            $reply['email_sent'] = true;
            $messages[array_key_last($messages)] = $reply;
            $inquiry->update(['conversation' => $messages]);
        } catch (TransportExceptionInterface $exception) {
            Log::error('Support reply email could not be sent.', [
                'inquiry_id' => $inquiry->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            return $this->replyResponse(
                $request,
                $inquiry,
                $reply,
                'تم حفظ الرد في المحادثة، لكن تعذر إرسال البريد الإلكتروني للزائر. راجع إعدادات البريد والسجل.',
                false,
                true,
            );
        }

        return $this->replyResponse(
            $request,
            $inquiry,
            $reply,
            'تم حفظ الرد وإرساله إلى المحادثة والبريد الإلكتروني للزائر.',
            true,
        );
    }

    private function inquiryStats(): object
    {
        return SupportInquiry::query()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN status != ? AND created_at < ? THEN 1 ELSE 0 END) as late_count',
                ['new', 'in_progress', 'resolved', 'resolved', now()->subMinutes(120)],
            )
            ->firstOrFail();
    }

    private function replyResponse(
        Request $request,
        SupportInquiry $inquiry,
        array $reply,
        string $message,
        bool $emailSent,
        bool $emailFailed = false,
    ): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'reply' => $reply,
                'status' => $inquiry->status,
                'email_sent' => $emailSent,
                'email_failed' => $emailFailed,
                'message' => $message,
                'stats' => $this->inquiryStats()->toArray(),
            ]);
        }

        return back()->with($emailSent || !filled($inquiry->contact_email) ? 'success' : 'error', $message);
    }

    public function destroy(SupportInquiry $inquiry): \Illuminate\Http\RedirectResponse
    {
        $inquiry->delete();

        return back()->with('success', 'تم حذف الاستفسار.');
    }

    private function findInquiryByToken(string $token): SupportInquiry
    {
        abort_unless(preg_match('/\A[A-Za-z0-9]{64}\z/', $token) === 1, 404);

        return SupportInquiry::query()
            ->where('access_token_hash', hash('sha256', $token))
            ->firstOrFail();
    }
}
