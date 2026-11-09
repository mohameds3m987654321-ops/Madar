<?php

namespace App\Services;

use App\Models\PaymobCallbackLog;
use Illuminate\Http\Request;

class PaymobCallbackAuditService
{
    public function record(Request $request, bool $signatureValid): PaymobCallbackLog
    {
        $order = $request->input('order');
        $orderId = is_array($order) ? ($order['id'] ?? null) : $order;
        $body = $request->getContent();

        return PaymobCallbackLog::create([
            'order_id' => $this->identifier($orderId),
            'transaction_id' => $this->identifier($request->input('id')),
            'integration_id' => $this->identifier($request->input('integration_id')),
            'request_method' => $request->method(),
            'ip_address' => $request->ip(),
            'payload_hash' => hash('sha256', $body !== '' ? $body : http_build_query($request->query())),
            'signature_valid' => $signatureValid,
        ]);
    }

    public function finish(
        PaymobCallbackLog $log,
        string $outcome,
        ?string $failureReason = null
    ): void {
        $log->update([
            'outcome' => $outcome,
            'failure_reason' => $failureReason,
        ]);
    }

    private function identifier(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        return mb_substr((string) $value, 0, 128);
    }
}
