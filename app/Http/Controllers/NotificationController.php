<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\AnnouncementFeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, AnnouncementFeedService $announcements): View
    {
        [$announcementRows] = $announcements->forUser($request->user());

        return view('notifications.index', [
            'announcementRows' => $announcementRows,
        ]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->session()->put($this->sessionKey($request), now()->toIso8601String());

        return back();
    }

    public function readAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->is_active, 404);

        $announcement->readBy($request->user());

        return back()->with('status', 'Đã xác nhận đọc thông báo.');
    }

    private function sessionKey(Request $request): string
    {
        return 'notifications_read_at_'.$request->user()->id;
    }
}
