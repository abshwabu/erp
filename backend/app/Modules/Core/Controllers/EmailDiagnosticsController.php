<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailDiagnosticsController extends Controller
{
    /**
     * Get mail server diagnostic information and connectivity status.
     */
    public function diagnostics(): JsonResponse
    {
        $info = EmailService::getDiagnostics();

        return response()->json([
            'data' => $info,
        ]);
    }

    /**
     * Send live test email to any specified address.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'name'  => ['nullable', 'string', 'max:100'],
        ]);

        $recipientEmail = $request->input('email');
        $recipientName = $request->input('name', 'Administrator');

        $result = EmailService::sendTestEmail($recipientEmail, $recipientName);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data'    => $result,
            ], 500);
        }

        return response()->json([
            'message' => $result['message'],
            'data'    => $result,
        ]);
    }
}
