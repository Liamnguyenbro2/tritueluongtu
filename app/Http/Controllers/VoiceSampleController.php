<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceSampleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recording' => ['required', 'file', 'mimetypes:audio/webm,audio/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/ogg', 'max:20480'],
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
