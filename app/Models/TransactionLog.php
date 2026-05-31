<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    public const TYPE_MONEY_IN = 'money_in';
    public const TYPE_MONEY_OUT = 'money_out';
    public const TYPE_PLAN_UPGRADE = 'plan_upgrade';
    public const TYPE_PLAN_RENEWAL = 'plan_renewal';
    public const TYPE_AFFILIATE = 'affiliate';
    public const TYPE_POOL_SHARE = 'pool_share';
    public const TYPE_REFUND = 'refund';
    public const TYPE_OTHER = 'other';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'user_id',
        'transaction_type',
        'amount',
        'description',
        'notes',
        'status',
        'reference_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_MONEY_IN => 'Tiền vào',
            self::TYPE_MONEY_OUT => 'Tiền ra',
            self::TYPE_AFFILIATE => 'Affiliate',
            self::TYPE_POOL_SHARE => 'Pool Share',
            self::TYPE_PLAN_UPGRADE => 'Nâng cấp gói',
            self::TYPE_PLAN_RENEWAL => 'Gia hạn gói',
            self::TYPE_REFUND => 'Hoàn tiền',
            self::TYPE_OTHER => 'Khác',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUCCESS => 'Thành công',
            self::STATUS_FAILED => 'Thất bại',
            self::STATUS_PENDING => 'Đang xử lý',
        ];
    }

    public function typeLabel(): string
    {
        return static::typeOptions()[$this->transaction_type] ?? 'Khác';
    }

    public function statusLabel(): string
    {
        return static::statusOptions()[$this->status] ?? 'Đang xử lý';
    }
}
