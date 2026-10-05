<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TrainingRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'karyawan_id', 'kind', 'type', 'title', 'description',
        'reason', 'objective', 'room_id', 'start_time', 'end_time',
        'status', 'admin_note', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /* ---------- Relations ---------- */

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** Admin (tabel users) yang menyetujui/menolak. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ---------- Scopes ---------- */

    /** Status yang "memblokir" ruangan: pending & approved. */
    public function scopeBlocking(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Irisan waktu: existing.start < new.end  AND  existing.end > new.start
     * (back-to-back, mis. 09-10 lalu 10-11, TIDAK dianggap bentrok).
     */
    public function scopeOverlapping(Builder $q, CarbonInterface|string $start, CarbonInterface|string $end): Builder
    {
        return $q->where('start_time', '<', $end)->where('end_time', '>', $start);
    }

    public function scopeInRoom(Builder $q, int $roomId): Builder
    {
        return $q->where('room_id', $roomId);
    }
}
