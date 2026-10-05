<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingRequestRequest;
use App\Models\Room;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingRequestController extends Controller
{
    public function index()
    {
        $karyawan = auth('karyawan')->user();

        $riwayat = TrainingRequest::with('room:id,name')
            ->where('karyawan_id', $karyawan->id)
            ->latest()
            ->paginate(10);

        return view('karyawan.training.index', compact('riwayat'));
    }

    public function create()
    {
        $rooms = Room::where('is_active', true)->orderBy('name')->get();

        return view('karyawan.training.create', compact('rooms'));
    }

    public function store(StoreTrainingRequestRequest $request)
    {
        $data = $request->validated();
        $karyawan = auth('karyawan')->user();

        try {
            $trainingRequest = DB::transaction(function () use ($data, $karyawan) {
                if (! empty($data['room_id'])) {
                    // Kunci baris ruangan supaya 2 pengajuan bersamaan tidak lolos cek bentrok berdua-duanya.
                    Room::whereKey($data['room_id'])->lockForUpdate()->first();

                    $bentrok = TrainingRequest::query()
                        ->inRoom((int) $data['room_id'])
                        ->blocking() // pending + approved
                        ->overlapping($data['start_time'], $data['end_time'])
                        ->first();

                    if ($bentrok) {
                        throw ValidationException::withMessages([
                            'room_id' => sprintf(
                                'Ruangan sudah terpakai pada %s - %s (%s, status: %s).',
                                $bentrok->start_time->format('d/m/Y H:i'),
                                $bentrok->end_time->format('d/m/Y H:i'),
                                $bentrok->title,
                                $bentrok->status
                            ),
                        ]);
                    }
                }

                return TrainingRequest::create([
                    ...$data,
                    'type' => $data['type'] ?? 'internal',
                    'karyawan_id' => $karyawan->id,
                    'status' => TrainingRequest::STATUS_PENDING,
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('karyawan.training.index')
            ->with('success', "Pengajuan \"{$trainingRequest->title}\" berhasil dikirim dan menunggu persetujuan admin.");
    }
}
