<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingRequestController extends Controller
{
    public function index(Request $request)
    {
        $items = TrainingRequest::with(['karyawan:id,nama,nik', 'room:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->input('kind')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->input('search');
                $q->where(function ($qq) use ($s) {
                    $qq->where('title', 'like', "%{$s}%")
                       ->orWhereHas('karyawan', fn ($k) => $k->where('nama', 'like', "%{$s}%"));
                });
            })
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')") // MySQL; pending di atas
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('training.requests-index', compact('items'));
    }

    public function approve(TrainingRequest $trainingRequest)
    {
        return $this->ubahStatus($trainingRequest, TrainingRequest::STATUS_APPROVED);
    }

    public function reject(Request $request, TrainingRequest $trainingRequest)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string'],
        ], [
            'admin_note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        return $this->ubahStatus($trainingRequest, TrainingRequest::STATUS_REJECTED, $data['admin_note']);
    }

    protected function ubahStatus(TrainingRequest $trainingRequest, string $status, ?string $adminNote = null)
    {
        try {
            DB::transaction(function () use ($trainingRequest, $status, $adminNote) {
                // Kunci baris pengajuan supaya tidak diproses dua admin sekaligus.
                $current = TrainingRequest::whereKey($trainingRequest->id)->lockForUpdate()->firstOrFail();

                if ($current->status !== TrainingRequest::STATUS_PENDING) {
                    throw ValidationException::withMessages([
                        'status' => 'Pengajuan ini sudah diproses sebelumnya.',
                    ]);
                }

                // Re-check bentrok saat approve (terhadap pengajuan approved lain).
                if ($status === TrainingRequest::STATUS_APPROVED && $current->room_id) {
                    Room::whereKey($current->room_id)->lockForUpdate()->first();

                    $bentrok = TrainingRequest::query()
                        ->inRoom($current->room_id)
                        ->where('status', TrainingRequest::STATUS_APPROVED)
                        ->where('id', '!=', $current->id)
                        ->overlapping($current->start_time, $current->end_time)
                        ->exists();

                    if ($bentrok) {
                        throw ValidationException::withMessages([
                            'status' => 'Tidak bisa disetujui: jadwal bentrok dengan pengajuan lain yang sudah disetujui.',
                        ]);
                    }
                }

                $current->update([
                    'status' => $status,
                    'admin_note' => $adminNote,
                    'reviewed_by' => auth('web')->id(),
                    'reviewed_at' => now(),
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $pesan = $status === TrainingRequest::STATUS_APPROVED
            ? "Pengajuan \"{$trainingRequest->title}\" disetujui."
            : "Pengajuan \"{$trainingRequest->title}\" ditolak.";

        return redirect()->route('training.requests.index')->with('success', $pesan);
    }
}
