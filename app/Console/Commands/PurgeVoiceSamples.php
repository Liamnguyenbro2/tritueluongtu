<?php

namespace App\Console\Commands;

use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeVoiceSamples extends Command
{
    protected $signature = 'voice-samples:purge';

    protected $description = 'Delete expired temporary voice recordings after 15 minutes.';

    public function handle(): int
    {
        UserProfile::query()
            ->whereNotNull('voice_sample_path')
            ->whereNotNull('voice_sample_delete_after_at')
            ->where('voice_sample_delete_after_at', '<=', now())
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    Storage::disk('local')->delete($profile->voice_sample_path);

                    $profile->update([
                        'voice_sample_path' => null,
                        'voice_sample_uploaded_at' => null,
                        'voice_sample_delete_after_at' => null,
                    ]);

                    $this->line("Purged voice sample for user #{$profile->user_id}");
                }
            });

        return self::SUCCESS;
    }
}
