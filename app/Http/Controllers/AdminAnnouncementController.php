<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications', [
            'fixedAnnouncement' => Announcement::fixedNotice(),
            'headerMarqueeText' => SiteSetting::headerMarqueeText(),
            'campaigns' => Announcement::query()
                ->where('type', Announcement::TYPE_CAMPAIGN)
                ->latest()
                ->limit(20)
                ->get(),
            'announcementHistory' => Announcement::query()
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function updateMarquee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'header_marquee_text' => ['required', 'string', 'max:500'],
        ], [
            'header_marquee_text.required' => 'Vui lòng nhập nội dung dòng chữ chạy.',
            'header_marquee_text.max' => 'Dòng chữ chạy tối đa 500 ký tự.',
        ]);

        SiteSetting::setValue('header_marquee_text', trim($data['header_marquee_text']));

        return back()->with('status', 'Đã cập nhật dòng chữ chạy trên header.');
    }

    public function updateFixed(Request $request): RedirectResponse
    {
        $data = $this->validatedAnnouncement($request);
        $fixedAnnouncement = Announcement::fixedNotice();

        $fixedAnnouncement->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'image_url' => $this->imageUrlFrom($request, $data),
            'is_active' => true,
            'published_at' => $fixedAnnouncement->published_at ?? now(),
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Đã cập nhật thông báo cố định.');
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $data = $this->validatedAnnouncement($request);

        Announcement::query()->create([
            'type' => Announcement::TYPE_CAMPAIGN,
            'title' => $data['title'],
            'body' => $data['body'],
            'image_url' => $this->imageUrlFrom($request, $data),
            'is_active' => $request->boolean('is_active', true),
            'published_at' => now(),
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Đã tạo thông báo theo đợt.');
    }

    public function toggle(Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->type === Announcement::TYPE_CAMPAIGN, 404);

        $announcement->update([
            'is_active' => ! $announcement->is_active,
        ]);

        return back()->with('status', $announcement->is_active
            ? 'Đã mở thông báo theo đợt.'
            : 'Đã tắt thông báo theo đợt.'
        );
    }

    private function validatedAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image_file' => ['nullable', 'image', 'max:4096'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề thông báo.',
            'body.required' => 'Vui lòng nhập nội dung thông báo.',
            'image_file.image' => 'Ảnh thông báo không đúng định dạng.',
            'image_file.max' => 'Ảnh thông báo tối đa 4MB.',
        ]);
    }

    private function imageUrlFrom(Request $request, array $data): ?string
    {
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('announcements', 'public');

            return Storage::disk('public')->url($path);
        }

        return $data['image_url'] ?? null;
    }
}
