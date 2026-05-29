<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceSampleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recording' => [
                'required',
                'file',
                'max:20480',
                function (string $attribute, $file, Closure $fail): void {
                    $acceptedMimeTypes = [
                        'audio/webm',
                        'video/webm',
                        'audio/mp4',
                        'video/mp4',
                        'audio/mpeg',
                        'audio/mp3',
                        'audio/wav',
                        'audio/x-wav',
                        'audio/wave',
                        'audio/ogg',
                        'audio/x-m4a',
                        'audio/aac',
                        'audio/3gpp',
                        'audio/3gpp2',
                        'application/octet-stream',
                    ];
                    $acceptedExtensions = ['webm', 'mp4', 'm4a', 'mp3', 'wav', 'ogg', 'aac', '3gp', '3gpp'];

                    $mimeType = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
                    $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));

                    if (in_array($mimeType, $acceptedMimeTypes, true) || in_array($extension, $acceptedExtensions, true)) {
                        return;
                    }

                    $fail('Định dạng file ghi âm này chưa được hỗ trợ trên thiết bị của bạn.');
                },
            ],
        ], [
            'recording.required' => 'Vui lòng ghi âm trước khi tải lên.',
            'recording.file' => 'Bản ghi tải lên không hợp lệ.',
            'recording.max' => 'File ghi âm quá lớn, vui lòng ghi âm ngắn hơn.',
        ]);

        $profile = $this->profileFor($request);

        if ($profile->voice_sample_path) {
            Storage::disk('local')->delete($profile->voice_sample_path);
        }

        $path = $data['recording']->store('protected/voice-samples');
        $deleteAfter = now()->addMinutes(15);

        $profile->update([
            'voice_sample_path' => $path,
            'voice_sample_uploaded_at' => now(),
            'voice_sample_delete_after_at' => $deleteAfter,
            'voice_sample_completed_at' => null,
        ]);

        return response()->json([
            'message' => 'Đã tải bản ghi tạm thời lên hệ thống.',
            'delete_after_at' => $deleteAfter->toIso8601String(),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $profile = $this->profileFor($request);

        if (! $profile->voice_sample_path) {
            return response()->json([
                'message' => 'Vui lòng ghi âm trước khi hoàn thành.',
            ], 422);
        }

        $profile->update([
            'voice_sample_completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Đã hoàn thành bước ghi âm tối ưu hóa bài học.',
        ]);
    }

    private function profileFor(Request $request): UserProfile
    {
        return $request->user()->profile()->firstOrCreate([], [
            'accepted_terms' => true,
            'accepted_terms_at' => now(),
        ]);
    }
}
