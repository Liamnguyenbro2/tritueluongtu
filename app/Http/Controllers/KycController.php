<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use App\Models\TransactionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KycController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $kyc = $user->kycVerification()->first();

        return response()->json([
            'completed' => $kyc !== null,
            'required' => $user->requiresKycForPaidAccess(),
            'submitted_at' => $kyc?->submitted_at?->toIso8601String(),
        ]);
    }

    public function index(Request $request): View
    {
        return view('kyc.index', [
            'kyc' => $request->user()->kycVerification()->first(),
            'requiresKyc' => $request->user()->requiresKycForPaidAccess(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:190'],
            'citizen_id' => ['required', 'string', 'max:32', 'regex:/^\d+$/'],
            'address' => ['required', 'string', 'max:1000'],
        ], [
            'full_name.required' => 'Vui long nhap ho va ten theo CCCD.',
            'citizen_id.required' => 'Vui long nhap so CCCD.',
            'citizen_id.regex' => 'So CCCD chi duoc phep nhap so.',
            'address.required' => 'Vui long nhap dia chi thuong tru theo CCCD.',
        ]);

        DB::transaction(function () use ($user, $data) {
            $kyc = $user->kycVerification()->first();
            $action = $kyc ? 'updated' : 'submitted';

            $user->kycVerification()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => trim((string) $data['full_name']),
                    'citizen_id' => preg_replace('/\D+/', '', (string) $data['citizen_id']),
                    'address' => trim((string) $data['address']),
                    'submitted_at' => now(),
                ]
            );

            TransactionLog::query()->create([
                'user_id' => $user->id,
                'transaction_type' => TransactionLog::TYPE_OTHER,
                'amount' => 0,
                'description' => $action === 'submitted'
                    ? 'Nguoi dung da gui thong tin KYC.'
                    : 'Nguoi dung da cap nhat thong tin KYC.',
                'notes' => 'CCCD: '.$this->maskCitizenId((string) $data['citizen_id']),
                'status' => TransactionLog::STATUS_SUCCESS,
                'reference_id' => 'KYC-'.$user->id.'-'.now()->timestamp,
            ]);
        });

        return redirect()
            ->route('kyc.index')
            ->with('status', 'Thong tin KYC da duoc luu thanh cong.');
    }

    private function maskCitizenId(string $citizenId): string
    {
        $digits = preg_replace('/\D+/', '', $citizenId);

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
