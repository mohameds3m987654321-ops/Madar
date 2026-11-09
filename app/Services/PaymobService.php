<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymobService
{
    public function createCheckout(
        Course $course,
        User $user,
        CourseEnrollment $enrollment,
        string $phoneNumber
    ): string {
        $secretKey = config('services.paymob.secret_key');
        $publicKey = config('services.paymob.public_key');
        $integrationId = config("services.paymob.integrations.{$enrollment->payment_method}");

        if (!$secretKey || !$publicKey || !$integrationId) {
            throw new RuntimeException('إعدادات الدفع لهذه الطريقة غير مكتملة.');
        }

        $baseUrl = rtrim(config('services.paymob.base_url'), '/');
        $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [];
        $intention = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withToken($secretKey, 'Token')
            ->timeout(20)
            ->post('/v1/intention/', [
                'amount' => $enrollment->amount_cents,
                'currency' => 'EGP',
                'payment_methods' => [(int) $integrationId],
                'special_reference' => 'course-enrollment-' . $enrollment->id . '-' . now()->timestamp,
                'notification_url' => route('payments.paymob.callback'),
                'redirection_url' => route('payments.paymob.callback'),
                'items' => [[
                    'name' => $course->title,
                    'amount' => $enrollment->amount_cents,
                    'description' => mb_substr($course->description, 0, 250),
                    'quantity' => 1,
                ]],
                'billing_data' => [
                    'first_name' => $nameParts[0] ?? 'Student',
                    'last_name' => $nameParts[1] ?? 'Student',
                    'email' => $user->email,
                    'phone_number' => $phoneNumber,
                    'apartment' => 'NA',
                    'floor' => 'NA',
                    'street' => 'NA',
                    'building' => 'NA',
                    'shipping_method' => 'NA',
                    'postal_code' => 'NA',
                    'city' => 'Cairo',
                    'country' => 'EG',
                    'state' => 'Cairo',
                ],
            ])
            ->throw()
            ->json();

        if (empty($intention['id']) || empty($intention['client_secret'])) {
            throw new RuntimeException('تعذر إنشاء جلسة الدفع لدى Paymob.');
        }

        $enrollment->update(['paymob_order_id' => (string) $intention['id']]);

        return $baseUrl . '/unifiedcheckout/?' . http_build_query([
            'publicKey' => $publicKey,
            'clientSecret' => $intention['client_secret'],
        ]);
    }

    public function verifyCallback(Request $request): bool
    {
        $secret = config('services.paymob.hmac_secret');
        $receivedHmac = $request->input('hmac');

        if (!is_string($secret) || $secret === '' ||
            !is_string($receivedHmac) || strlen($receivedHmac) !== 128 ||
            !ctype_xdigit($receivedHmac)) {
            return false;
        }

        $order = $request->input('order');
        $orderId = is_array($order) ? ($order['id'] ?? '') : $order;
        $sourceData = $request->input('source_data', []);
        if (!is_array($sourceData)) {
            return false;
        }

        $signedFields = [
            $request->input('amount_cents', ''),
            $request->input('created_at', ''),
            $request->input('currency', ''),
            $this->booleanString($request->input('error_occured')),
            $this->booleanString($request->input('has_parent_transaction')),
            $request->input('id', ''),
            $request->input('integration_id', ''),
            $this->booleanString($request->input('is_3d_secure')),
            $this->booleanString($request->input('is_auth')),
            $this->booleanString($request->input('is_capture')),
            $this->booleanString($request->input('is_refunded')),
            $this->booleanString($request->input('is_standalone_payment')),
            $this->booleanString($request->input('is_voided')),
            $orderId,
            $request->input('owner', ''),
            $this->booleanString($request->input('pending')),
            data_get($sourceData, 'pan', ''),
            data_get($sourceData, 'sub_type', ''),
            data_get($sourceData, 'type', ''),
            $this->booleanString($request->input('success')),
        ];

        foreach ($signedFields as $value) {
            if (!is_scalar($value) && $value !== null) {
                return false;
            }
        }

        $expectedHmac = hash_hmac('sha512', implode('', $signedFields), $secret);

        return hash_equals($expectedHmac, $receivedHmac);
    }

    private function booleanString(mixed $value): mixed
    {
        if (!is_scalar($value) && $value !== null) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return strtolower((string) $value);
    }
}
