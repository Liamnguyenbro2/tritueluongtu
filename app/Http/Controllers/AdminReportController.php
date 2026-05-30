<?php

namespace App\Http\Controllers;

use App\Models\AdminReportSnapshot;
use App\Services\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $snapshots = AdminReportSnapshot::query()
            ->orderByDesc('report_date')
            ->get();

        $selectedSnapshot = $snapshots
            ->first(fn (AdminReportSnapshot $snapshot) => $snapshot->report_date->toDateString() === (string) $request->query('date'))
            ?? $snapshots->first();

        if ($selectedSnapshot) {
            $selectedSnapshot->load([
                'logs' => fn ($query) => $query->orderBy('occurred_at')->orderBy('id'),
                'poolShareRows' => fn ($query) => $query->orderBy('group_code')->orderByDesc('payout_vnd')->orderBy('name'),
            ]);
        }

        $logGroups = $selectedSnapshot
            ? $selectedSnapshot->logs
                ->groupBy('log_type')
                ->sortBy(fn ($items, $type) => $this->logTypeOrder()->search($type))
            : collect();

        return view('admin.reports.index', [
            'snapshots' => $snapshots,
            'selectedSnapshot' => $selectedSnapshot,
            'selectedGroupStats' => collect($selectedSnapshot?->pool_group_stats ?? []),
            'selectedLogGroups' => $logGroups,
            'logTypeLabels' => $this->logTypeLabels(),
        ]);
    }

    public function export(AdminReportSnapshot $snapshot, SimpleXlsxExporter $exporter): StreamedResponse
    {
        $snapshot->load([
            'poolShareRows' => fn ($query) => $query->orderBy('group_code')->orderByDesc('payout_vnd')->orderBy('name'),
        ]);

        $rows = [
            ['ID', 'Họ tên', 'Email', 'Nhóm', 'Số F1 hợp lệ', 'Tiền nhận', 'Ngày chia', 'Trạng thái tài khoản'],
        ];

        foreach ($snapshot->poolShareRows as $row) {
            $rows[] = [
                $row->user_id ?? '',
                $row->name,
                $row->email,
                $row->group_code,
                $row->active_referrals_count,
                number_format($row->payout_vnd, 0, ',', '.'),
                $snapshot->report_date->format('d/m/Y'),
                $row->account_status,
            ];
        }

        $path = $exporter->build('Pool Share Report', $rows);
        $filename = 'pool-share-report-'.$snapshot->report_date->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($path) {
            $stream = fopen($path, 'rb');

            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }

            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function logTypeLabels(): Collection
    {
        return collect([
            'referral_commission' => 'Hoa hồng affiliate',
            'company_vat' => 'VAT',
            'company_revenue' => 'Doanh thu công ty',
            'payment_shared_pool' => 'Pool Share',
            'pool_share_distribution_out' => 'Chi Pool Share',
        ]);
    }

    private function logTypeOrder(): Collection
    {
        return $this->logTypeLabels()->keys()->values();
    }
}
