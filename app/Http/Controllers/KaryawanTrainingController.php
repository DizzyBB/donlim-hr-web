<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingRequestRequest;
use App\Models\Room;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Notifications\NewTrainingRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Karyawan: Buat pengajuan training / pesan ruangan
 */
class KaryawanTrainingController extends Controller
{
    /**
     * Daftar pengajuan milik karyawan yang login.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $requests = TrainingRequest::with('room:id,name')
            ->where('karyawan_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('training.my-index', compact('requests'));
    }

    /**
     * Form buat pengajuan baru.
     */
    public function create()
    {
        $rooms = Room::where('is_active', true)->orderBy('name')->get(['id', 'name', 'capacity', 'location']);

        return view('training.my-create', compact('rooms'));
    }

    /**
     * Simpan pengajuan baru.
     */
    public function store(StoreTrainingRequestRequest $request): RedirectResponse
    {
        $data  = $request->validated();
        $start = Carbon::parse($data['start_time']);
        $end   = Carbon::parse($data['end_time']);

        $trainingRequest = DB::transaction(function () use ($data, $start, $end, $request) {
            if (! empty($data['room_id'])) {
                // Lock ruangan untuk cek bentrok atomic
                Room::whereKey($data['room_id'])->lockForUpdate()->first();

                $conflict = TrainingRequest::query()
                    ->inRoom((int) $data['room_id'])
                    ->blocking() // pending + approved
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
            }

            return TrainingRequest::create([
                ...$data,
                'type'        => $data['type'] ?? 'internal',
                'start_time'  => $start,
                'end_time'    => $end,
                'user_id'     => null, // users table (not used for karyawan)
                'karyawan_id' => $request->user()->id, // karyawans table
                'nik'         => $request->user()->nik,
                'status'      => TrainingRequest::STATUS_PENDING,
            ]);
        });

        // Notifikasi ke admin (database notification) - fire & forget
        try {
            $adminRole = config('training.admin_role');
            $admins = $adminRole
                ? User::where('role', $adminRole)->get()
                : User::all(); // Semua user di tabel users dianggap admin
            Notification::send($admins, new NewTrainingRequestSubmitted($trainingRequest->load(['karyawan', 'room'])));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('karyawan.training.index')
            ->with('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan admin.');
    }
}