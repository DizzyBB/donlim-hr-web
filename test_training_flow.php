<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Karyawan;
use App\Models\Room;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Notifications\NewTrainingRequestSubmitted;
use App\Notifications\TrainingRequestStatusChanged;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

echo "=== TEST FLOW TRAINING MVC (SIMULASI CONTROLLER) ===\n\n";

// Clean up first
TrainingRequest::truncate();
User::where('email', 'admin@donlim.test')->first()->notifications()->delete();
Karyawan::where('nik', 'KRY001')->first()->notifications()->delete();

// 1. Get test data
$karyawan = Karyawan::where('nik', 'KRY001')->first();
$room = Room::first();
$admin = User::where('email', 'admin@donlim.test')->first();

echo "1. DATA AWAL:\n";
echo "   Karyawan: {$karyawan->nama} ({$karyawan->nik})\n";
echo "   Room: {$room->name}\n";
echo "   Admin: {$admin->name}\n\n";

// 2. Karyawan creates training request (SIMULASI KaryawanTrainingController@store)
echo "2. KARYAWAN BUAT PENGAJUAN #1 (simulasi controller store):\n";
$start = Carbon::now()->addDays(1)->setTime(9, 0);
$end = Carbon::now()->addDays(1)->setTime(12, 0);

$request1 = DB::transaction(function () use ($karyawan, $room, $start, $end) {
    Room::whereKey($room->id)->lockForUpdate()->first();

    $conflict = TrainingRequest::query()
        ->inRoom((int) $room->id)
        ->blocking()
        ->overlapping($start, $end)
        ->first();

    if ($conflict) {
        throw ValidationException::withMessages([
            'room_id' => sprintf(
                'Ruangan sudah terpakai pada %s - %s (%s, status: %s).',
                $conflict->start_time->format('d/m/Y H:i'),
                $conflict->end_time->format('d/m/Y H:i'),
                $conflict->title,
                $conflict->status
            ),
        ]);
    }

    return TrainingRequest::create([
        'kind' => 'training',
        'type' => 'internal',
        'title' => 'Training Laravel Advanced',
        'description' => 'Training lanjutan untuk pengembangan web',
        'reason' => 'Perlu meningkatkan skill tim',
        'objective' => 'Mampu membuat aplikasi Laravel complex',
        'room_id' => $room->id,
        'start_time' => $start,
        'end_time' => $end,
        'user_id' => $karyawan->id,
        'nik' => $karyawan->nik,
        'status' => TrainingRequest::STATUS_PENDING,
    ]);
});

// Notifikasi ke admin
try {
    $adminRole = config('training.admin_role');
    $admins = $adminRole
        ? User::where('role', $adminRole)->get()
        : User::all();
    Notification::send($admins, new NewTrainingRequestSubmitted($request1->load(['user', 'room'])));
    echo "   Notifikasi ke admin: TERKIRIM\n";
} catch (\Throwable $e) {
    echo "   Notifikasi error: " . $e->getMessage() . "\n";
}

echo "   Created: ID={$request1->id}, Title={$request1->title}, Status={$request1->status}\n\n";

// 3. Check admin notification
echo "3. CEK NOTIFIKASI ADMIN:\n";
$admin->refresh();
$adminNotifications = $admin->notifications;
echo "   Total notifications: " . $adminNotifications->count() . "\n";
foreach ($adminNotifications as $notif) {
    echo "   - {$notif->data['title']}: {$notif->data['message']}\n";
}
echo "\n";

// 4. Admin views list
echo "4. ADMIN LIHAT DAFTAR:\n";
$requests = TrainingRequest::with(['user:id,name', 'room:id,name'])
    ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
    ->latest()
    ->paginate(20);
echo "   Total requests: " . $requests->total() . "\n";
foreach ($requests as $r) {
    echo "   - ID={$r->id}, {$r->title}, {$r->status}, Room={$r->room->name}\n";
}
echo "\n";

// 5. Admin approves request #1
echo "5. ADMIN APPROVE PENGAJUAN #1:\n";
try {
    DB::transaction(function () use ($request1, $admin) {
        $current = TrainingRequest::whereKey($request1->id)->lockForUpdate()->firstOrFail();

        if ($current->room_id) {
            Room::whereKey($current->room_id)->lockForUpdate()->first();

            $conflict = TrainingRequest::query()
                ->inRoom($current->room_id)
                ->where('status', TrainingRequest::STATUS_APPROVED)
                ->where('id', '!=', $current->id)
                ->overlapping($current->start_time, $current->end_time)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'status' => 'Tidak bisa disetujui: jadwal bentrok dengan pengajuan lain yang sudah disetujui.',
                ]);
            }
        }

        $current->update([
            'status'      => TrainingRequest::STATUS_APPROVED,
            'admin_note'  => null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    });

    $request1->refresh();
    echo "   Updated status: {$request1->status}\n";

    // Notifikasi ke karyawan
    $fresh = $request1->fresh(['user', 'room:id,name']);
    $fresh->user?->notify(new TrainingRequestStatusChanged($fresh));
    echo "   Notifikasi ke karyawan: TERKIRIM\n";
} catch (ValidationException $e) {
    echo "   VALIDATION ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Check karyawan notification
echo "6. CEK NOTIFIKASI KARYAWAN (setelah approve #1):\n";
$karyawan->refresh();
$karyawanNotifications = $karyawan->notifications;
echo "   Total notifications: " . $karyawanNotifications->count() . "\n";
foreach ($karyawanNotifications as $notif) {
    echo "   - {$notif->data['title']}: {$notif->data['message']}\n";
}
echo "\n";

// 7. Test conflict check on CREATE - try to create overlapping request (should FAIL because #1 is APPROVED)
echo "7. TEST CONFLICT CHECK ON CREATE - OVERLAPPING dengan APPROVED (harus GAGAL):\n";
$start2 = Carbon::now()->addDays(1)->setTime(10, 0);
$end2 = Carbon::now()->addDays(1)->setTime(11, 0);

try {
    $request2 = DB::transaction(function () use ($karyawan, $room, $start2, $end2) {
        Room::whereKey($room->id)->lockForUpdate()->first();

        $conflict = TrainingRequest::query()
            ->inRoom((int) $room->id)
            ->blocking() // pending + approved
            ->overlapping($start2, $end2)
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'room_id' => sprintf(
                    'Ruangan sudah terpakai pada %s - %s (%s, status: %s).',
                    $conflict->start_time->format('d/m/Y H:i'),
                    $conflict->end_time->format('d/m/Y H:i'),
                    $conflict->title,
                    $conflict->status
                ),
            ]);
        }

        return TrainingRequest::create([
            'kind' => 'room',
            'type' => 'internal',
            'title' => 'Meeting Tim IT',
            'description' => 'Meeting rutin',
            'room_id' => $room->id,
            'start_time' => $start2,
            'end_time' => $end2,
            'user_id' => $karyawan->id,
            'nik' => $karyawan->nik,
            'status' => TrainingRequest::STATUS_PENDING,
        ]);
    });
    echo "   Created overlapping request: ID={$request2->id} (UNEXPECTED - should have failed)\n";
} catch (ValidationException $e) {
    echo "   SUCCESS: Blocked on create as expected!\n";
    echo "   Message: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Test: Create non-overlapping request, then approve both
echo "8. TEST: Create non-overlapping request #2, approve both:\n";
$start3 = Carbon::now()->addDays(2)->setTime(9, 0);
$end3 = Carbon::now()->addDays(2)->setTime(12, 0);

$request3 = TrainingRequest::create([
    'kind' => 'training',
    'type' => 'internal',
    'title' => 'Training Vue.js',
    'description' => 'Frontend training',
    'room_id' => $room->id,
    'start_time' => $start3,
    'end_time' => $end3,
    'user_id' => $karyawan->id,
    'nik' => $karyawan->nik,
    'status' => TrainingRequest::STATUS_PENDING,
]);
echo "   Created request #2 (different day): ID={$request3->id}\n";

// Approve request #2
DB::transaction(function () use ($request3, $admin) {
    $current = TrainingRequest::whereKey($request3->id)->lockForUpdate()->firstOrFail();
    $current->update([
        'status'      => TrainingRequest::STATUS_APPROVED,
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
    ]);
});
$request3->refresh();
echo "   Approved request #2: status={$request3->status}\n\n";

// 9. Test reject
echo "9. TEST REJECT:\n";
$rejectRequest = TrainingRequest::create([
    'kind' => 'training',
    'type' => 'external',
    'title' => 'Training Eksternal PHP',
    'description' => 'Training di luar',
    'room_id' => null,
    'start_time' => Carbon::now()->addDays(3)->setTime(9, 0),
    'end_time' => Carbon::now()->addDays(3)->setTime(17, 0),
    'user_id' => $karyawan->id,
    'nik' => $karyawan->nik,
    'status' => TrainingRequest::STATUS_PENDING,
]);

DB::transaction(function () use ($rejectRequest, $admin) {
    $current = TrainingRequest::whereKey($rejectRequest->id)->lockForUpdate()->firstOrFail();
    $current->update([
        'status'      => TrainingRequest::STATUS_REJECTED,
        'admin_note'  => 'Budget tidak mencukupi',
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
    ]);
});

$rejectRequest->refresh();
echo "   Rejected: {$rejectRequest->status}, Note: {$rejectRequest->admin_note}\n";

// Notify karyawan
$fresh = $rejectRequest->fresh(['user', 'room:id,name']);
$fresh->user?->notify(new TrainingRequestStatusChanged($fresh));
echo "   Notifikasi ke karyawan: TERKIRIM\n";

$karyawan->refresh();
$notifs = $karyawan->notifications;
echo "   Karyawan notifications total: " . $notifs->count() . "\n";
foreach ($notifs->take(5) as $notif) {
    echo "   - {$notif->data['title']}\n";
}
echo "\n";

// 10. Calendar events test
echo "10. TEST CALENDAR EVENTS:\n";
$events = TrainingRequest::with(['room:id,name', 'user:id,name'])
    ->whereIn('status', ['approved', 'pending'])
    ->orderBy('start_time')
    ->get()
    ->map(fn ($t) => [
        'id' => $t->id,
        'title' => ($t->status === 'pending' ? '(Pending) ' : '') . $t->title,
        'start' => $t->start_time->toIso8601String(),
        'end' => $t->end_time->toIso8601String(),
        'color' => config('training.colors')[$t->status] ?? '#64748b',
    ])
    ->values()
    ->toArray();

echo "   Total events for calendar: " . count($events) . "\n";
foreach ($events as $e) {
    echo "   - {$e['title']} ({$e['start']} to {$e['end']}) color={$e['color']}\n";
}
echo "\n";

// 11. Test karyawan index
echo "11. TEST KARYAWAN INDEX (My Training Requests):\n";
$myRequests = TrainingRequest::with('room:id,name')
    ->where('user_id', $karyawan->id)
    ->latest()
    ->paginate(10);
echo "   Total my requests: " . $myRequests->total() . "\n";
foreach ($myRequests as $r) {
    echo "   - {$r->title} [{$r->kind}] {$r->status} - {$r->start_time->format('d/m/Y H:i')}\n";
}
echo "\n";

// 12. Test Admin Approvals list with filters
echo "12. TEST ADMIN APPROVALS FILTERS:\n";
$all = TrainingRequest::with(['user:id,name', 'room:id,name'])->get();
echo "   Total: " . $all->count() . "\n";

$pending = TrainingRequest::where('status', 'pending')->count();
$approved = TrainingRequest::where('status', 'approved')->count();
$rejected = TrainingRequest::where('status', 'rejected')->count();
echo "   Pending: $pending, Approved: $approved, Rejected: $rejected\n\n";

echo "=== SEMUA TEST BERHASIL ===\n";