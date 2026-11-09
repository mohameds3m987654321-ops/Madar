<?php

namespace Tests\Feature;

use App\Models\SupportInquiry;
use App\Models\User;
use App\Mail\SupportInquiryReplyMail;
use App\Notifications\NewSupportInquiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class SupportAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_answers_a_known_question_from_the_faq(): void
    {
        $this->postJson(route('assistant.message'), [
            'question' => 'نسيت كلمة المرور؟',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('question', 'نسيت كلمة المرور، ماذا أفعل؟')
            ->assertJsonPath('suggestions.0', 'مش قادر أسجل الدخول')
            ->assertJsonPath('answer', 'من صفحة تسجيل الدخول اختر «نسيت كلمة المرور؟»، واكتب البريد المرتبط بحسابك. استخدم رابط إعادة التعيين الذي يصلك، ولا تشارك الرابط أو كلمة مرورك مع أي شخص.');
    }

    public function test_assistant_prefers_specific_topics_and_understands_arabic_variants(): void
    {
        $this->postJson(route('assistant.message'), [
            'question' => 'عايز اعرف ازاي اطلع شهادة الدورة',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('question', 'كيف أحصل على شهادة الدورة؟')
            ->assertJsonPath('suggestions.0', 'كيف أفتح اختبار الدورة؟');

        $this->postJson(route('assistant.message'), [
            'question' => 'ازاي استخدم كوبون خصم؟',
        ])
            ->assertOk()
            ->assertJsonPath('question', 'كيف أستخدم كود الخصم؟');
    }

    public function test_unmatched_question_is_not_guessed_and_can_be_escalated_anonymously(): void
    {
        Notification::fake();
        config(['assistant.support_email' => 'support@example.test']);

        $this->postJson(route('assistant.message'), [
            'question' => 'هل يمكن تغيير موعد الامتحان بعد بدايته؟',
        ])
            ->assertOk()
            ->assertJsonPath('matched', false)
            ->assertJsonPath('question', null)
            ->assertJsonPath('suggestions.0', 'كيف أنشئ حسابًا؟');

        $response = $this->postJson(route('assistant.escalate'), [
            'question' => 'هل يمكن تغيير موعد الامتحان بعد بدايته؟',
            'messages' => [
                ['role' => 'user', 'text' => 'هل يمكن تغيير موعد الامتحان بعد بدايته؟'],
                ['role' => 'assistant', 'text' => 'مش لاقي إجابة مؤكدة.'],
                ['role' => 'user', 'text' => 'هل يمكن تغيير موعد الامتحان بعد بدايته؟'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'وصلت المحادثة لفريق الدعم. يمكنك متابعة الرد هنا، واحتفظ بهذه الجلسة للعودة إلى المحادثة.')
            ->assertJsonStructure(['conversation_token']);

        $this->assertDatabaseHas('support_inquiries', [
            'question' => 'هل يمكن تغيير موعد الامتحان بعد بدايته؟',
            'status' => 'new',
        ]);
        $this->assertDatabaseCount('support_inquiries', 1);
        $inquiry = SupportInquiry::firstOrFail();
        $token = $response->json('conversation_token');
        $this->assertNotSame($token, $inquiry->access_token_hash);
        $this->assertCount(3, $inquiry->conversation);
        $this->assertSame('user', $inquiry->conversation[0]['role']);
        $this->assertNull($inquiry->contact_email);
        $this->assertNull($inquiry->contact_phone);
        Notification::assertSentOnDemand(NewSupportInquiryNotification::class);

        $this->getJson(route('assistant.conversation.show', $token))
            ->assertOk()
            ->assertJsonPath('messages.1.text', 'مش لاقي إجابة مؤكدة.');

        $this->postJson(route('assistant.conversation.reply', $token), [
            'message' => 'أحتاج متابعة حالة سؤالي.',
        ])
            ->assertCreated()
            ->assertJsonPath('messages.3.role', 'user');

        $this->assertSame('new', $inquiry->fresh()->status);
    }

    public function test_admin_can_review_reply_to_and_delete_support_conversations(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $inquiry = SupportInquiry::create([
            'question' => 'أحتاج مساعدة في الاشتراك.',
            'conversation' => [
                ['role' => 'user', 'text' => 'أحتاج مساعدة في الاشتراك.'],
                ['role' => 'assistant', 'text' => 'مش لاقي إجابة مؤكدة.'],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support-inquiries.index'))
            ->assertOk()
            ->assertSee('أحتاج مساعدة في الاشتراك.')
            ->assertSee('سجل المحادثة')
            ->assertSee('صندوق الاستفسارات')
            ->assertSee('تفاصيل الاستفسار')
            ->assertSee('بيانات التواصل');

        $this->post(route('admin.support-inquiries.respond', $inquiry), [
            'message' => 'يسعدنا مساعدتك. هل ظهرت لك رسالة خطأ؟',
        ])->assertRedirect()->assertSessionHas(
            'success',
            'تم حفظ الرد في المحادثة. لم يضف الزائر بريدًا إلكترونيًا.',
        );

        $this->assertSame('in_progress', $inquiry->fresh()->status);
        $this->assertSame('support', $inquiry->fresh()->conversation[2]['role']);
        Mail::assertNothingSent();
        $token = Str::random(64);
        $inquiry->update(['access_token_hash' => hash('sha256', $token)]);
        $this->getJson(route('assistant.conversation.show', $token))
            ->assertOk()
            ->assertJsonPath('messages.2.text', 'يسعدنا مساعدتك. هل ظهرت لك رسالة خطأ؟');

        $this->patch(route('admin.support-inquiries.update', $inquiry), [
            'status' => 'in_progress',
        ])->assertRedirect();

        $this->assertSame('in_progress', $inquiry->fresh()->status);

        $this->delete(route('admin.support-inquiries.destroy', $inquiry))
            ->assertRedirect();

        $this->assertDatabaseMissing('support_inquiries', ['id' => $inquiry->id]);
    }

    public function test_admin_support_inbox_searches_and_filters_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SupportInquiry::create([
            'question' => 'مشكلة في الاشتراك.',
            'status' => 'in_progress',
            'contact_name' => 'طالب الاشتراك',
        ]);
        SupportInquiry::create([
            'question' => 'استفسار عن الشهادة.',
            'status' => 'new',
            'contact_name' => 'طالب الشهادة',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support-inquiries.index', [
                'status' => 'in_progress',
                'search' => 'الاشتراك',
            ]))
            ->assertOk()
            ->assertSee('مشكلة في الاشتراك.')
            ->assertDontSee('استفسار عن الشهادة.')
            ->assertSee('طالب الاشتراك');
    }

    public function test_admin_reply_is_saved_and_sent_to_the_guest_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $inquiry = SupportInquiry::create([
            'question' => 'أحتاج مساعدة في الاشتراك.',
            'conversation' => [
                ['role' => 'user', 'text' => 'أحتاج مساعدة في الاشتراك.'],
            ],
            'contact_name' => 'زائر مدار',
            'contact_email' => 'visitor@example.test',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support-inquiries.respond', $inquiry), [
                'message' => 'يسعدنا مساعدتك.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'تم حفظ الرد وإرساله إلى المحادثة والبريد الإلكتروني للزائر.');

        $inquiry->refresh();
        $this->assertSame('يسعدنا مساعدتك.', $inquiry->conversation[1]['text']);
        $this->assertTrue($inquiry->conversation[1]['email_sent']);
        Mail::assertSent(
            SupportInquiryReplyMail::class,
            function (SupportInquiryReplyMail $mail): bool {
                $mail->assertSeeInHtml('مدار');

                return $mail->hasTo('visitor@example.test')
                    && $mail->reply === 'يسعدنا مساعدتك.'
                    && $mail->recipientName === 'زائر مدار';
            },
        );

        $this->get(route('admin.support-inquiries.index'))
            ->assertOk()
            ->assertSee('أُرسل بالبريد');
    }

    public function test_admin_can_send_a_reply_as_json_without_a_redirect(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $inquiry = SupportInquiry::create([
            'question' => 'أحتاج مساعدة في الاشتراك.',
            'conversation' => [
                ['role' => 'user', 'text' => 'أحتاج مساعدة في الاشتراك.'],
            ],
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.support-inquiries.respond', $inquiry), [
                'message' => 'تم استلام استفسارك.',
            ])
            ->assertOk()
            ->assertJsonPath('reply.role', 'support')
            ->assertJsonPath('reply.text', 'تم استلام استفسارك.')
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('email_sent', false)
            ->assertJsonPath('stats.in_progress_count', 1)
            ->assertJsonPath('message', 'تم حفظ الرد في المحادثة. لم يضف الزائر بريدًا إلكترونيًا.');

        $this->assertSame('تم استلام استفسارك.', $inquiry->fresh()->conversation[1]['text']);
        Mail::assertNothingSent();
    }

    public function test_ajax_reply_reports_email_failure_after_persisting_the_reply(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->with('visitor@example.test')
            ->andThrow(new TransportException('SMTP unavailable'));
        Log::spy();
        $admin = User::factory()->create(['role' => 'admin']);
        $inquiry = SupportInquiry::create([
            'question' => 'أحتاج مساعدة في الاشتراك.',
            'conversation' => [
                ['role' => 'user', 'text' => 'أحتاج مساعدة في الاشتراك.'],
            ],
            'contact_email' => 'visitor@example.test',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.support-inquiries.respond', $inquiry), [
                'message' => 'تم حفظ الرد.',
            ])
            ->assertOk()
            ->assertJsonPath('reply.text', 'تم حفظ الرد.')
            ->assertJsonPath('email_sent', false)
            ->assertJsonPath('email_failed', true)
            ->assertJsonPath('status', 'in_progress');

        $this->assertSame('تم حفظ الرد.', $inquiry->fresh()->conversation[1]['text']);
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Support reply email could not be sent.', \Mockery::type('array'));
    }

    public function test_failed_support_reply_email_does_not_undo_saved_reply(): void
    {
        $email = 'visitor@example.test';
        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andThrow(new TransportException('SMTP unavailable'));
        Log::spy();
        $admin = User::factory()->create(['role' => 'admin']);
        $inquiry = SupportInquiry::create([
            'question' => 'أحتاج مساعدة في الاشتراك.',
            'conversation' => [
                ['role' => 'user', 'text' => 'أحتاج مساعدة في الاشتراك.'],
            ],
            'contact_email' => $email,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support-inquiries.respond', $inquiry), [
                'message' => 'تم حفظ هذا الرد.',
            ])
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'تم حفظ الرد في المحادثة، لكن تعذر إرسال البريد الإلكتروني للزائر. راجع إعدادات البريد والسجل.',
            );

        $inquiry->refresh();
        $this->assertSame('تم حفظ هذا الرد.', $inquiry->conversation[1]['text']);
        $this->assertArrayNotHasKey('email_sent', $inquiry->conversation[1]);
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Support reply email could not be sent.', \Mockery::type('array'));
    }

    public function test_escalation_saves_optional_guest_contact_and_authenticated_profile_details(): void
    {
        $this->postJson(route('assistant.escalate'), [
            'question' => 'أحتاج متابعة طلبي.',
            'contact' => [
                'name' => 'زائر مدار',
                'email' => 'visitor@example.test',
                'phone' => '+201000000000',
            ],
        ])->assertCreated();

        $guestInquiry = SupportInquiry::firstOrFail();
        $this->assertSame('زائر مدار', $guestInquiry->contact_name);
        $this->assertSame('visitor@example.test', $guestInquiry->contact_email);
        $this->assertSame('+201000000000', $guestInquiry->contact_phone);

        $user = User::factory()->create([
            'name' => 'طالب مسجل',
            'email' => 'student@example.test',
            'student_phone' => '+201111111111',
        ]);
        $this->actingAs($user)->postJson(route('assistant.escalate'), [
            'question' => 'أحتاج مساعدة في حسابي.',
            'contact' => [
                'name' => 'اسم مزيف',
                'email' => 'fake@example.test',
                'phone' => '+202222222222',
            ],
        ])->assertCreated();

        $accountInquiry = SupportInquiry::latest('id')->firstOrFail();
        $this->assertSame('طالب مسجل', $accountInquiry->contact_name);
        $this->assertSame('student@example.test', $accountInquiry->contact_email);
        $this->assertSame('+201111111111', $accountInquiry->contact_phone);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.support-inquiries.index'))
            ->assertOk()
            ->assertSee('زائر مدار')
            ->assertSee('visitor@example.test')
            ->assertSee('+201000000000')
            ->assertSee('طالب مسجل')
            ->assertSee('student@example.test');
    }

    public function test_non_admin_cannot_open_the_support_inbox(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'student',
            'email_verified_at' => now(),
        ]))
            ->get(route('admin.support-inquiries.index'))
            ->assertRedirect(route('users.profile'));
    }

    public function test_assistant_rejects_empty_or_oversized_questions(): void
    {
        $this->postJson(route('assistant.message'), ['question' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('question');

        $this->postJson(route('assistant.escalate'), [
            'question' => str_repeat('س', 2001),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('question');

        $this->postJson(route('assistant.escalate'), [
            'question' => 'سؤال صالح',
            'messages' => [
                ['role' => 'system', 'text' => 'محتوى غير موثوق'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('messages.0.role');
    }

    public function test_chat_and_escalation_endpoints_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson(route('assistant.message'), [
                'question' => 'نسيت كلمة المرور؟',
            ])->assertOk();
        }

        $this->postJson(route('assistant.message'), [
            'question' => 'نسيت كلمة المرور؟',
        ])->assertTooManyRequests();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson(route('assistant.escalate'), [
                'question' => 'أحتاج مساعدة في الاشتراك.',
            ])->assertCreated();
        }

        $this->postJson(route('assistant.escalate'), [
            'question' => 'أحتاج مساعدة في الاشتراك.',
        ])->assertTooManyRequests();

        $this->assertDatabaseCount('support_inquiries', 3);
    }
}
