<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Room;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Versi PUBLIK (tanpa login, tanpa API) dari fitur Request Training & Pesan Ruang
 * Training, khusus untuk welcome.blade.php. Siapa pun yang tahu NIK bisa mengajukan
 * & melihat riwayat atas nama NIK itu — TIDAK ada verifikasi identitas tambahan.
 * Kalau nanti butuh lebih aman, pertimbangkan tambah kolom PIN sebagai verifikasi
 * kedua, atau arahkan ke Karyawan\TrainingRequestController yang pakai login.
 */
class PublicTrainingController extends Controller
{
    protected const MAX_RIWAYAT_PER_HALAMAN = 5;

    /**
     * Halaman welcome: form + (opsional) riwayat kalau ada ?nik=... di URL.
     */
    public function index(Request $request): View
    {
        $rooms = Room::where('is_active', true)->orderBy('name')->get();

        $nikDicari = trim((string) $request->query('nik', ''));
        $riwayat = null;
        $karyawanDitemukan = null;

        if ($nikDicari !== '') {
            $karyawan = Karyawan::where('nik', $nikDicari)->first();
            $karyawanDitemukan = (bool) $karyawan;

            if ($karyawan) {
                $riwayat = TrainingRequest::with('room:id,name')
                    ->where('karyawan_id', $karyawan->id)
                    ->latest()
                    ->paginate(self::MAX_RIWAYAT_PER_HALAMAN)
                    ->withQueryString();
            }
        }

        return view('welcome', compact('rooms', 'riwayat', 'nikDicari', 'karyawanDitemukan'));
    }

    public function storeTraining(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('training', [
            'nik' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:internal,external'],
            'reason' => ['required', 'string'],
            'objective' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'room_id' => [
                $request->input('type') === 'internal' ? 'required' : 'nullable',
                'nullable',
                'exists:rooms,id',
            ],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        return $this->simpan($validated, 'training', 'training', '#request-training');
    }

    /**
     * Pilihan tetap di dropdown form Pesan Ruang. "hrd" otomatis mengarah ke
     * record Room bernama "Ruang Pelatihan HRD" (dibuat otomatis kalau belum ada).
     * "lainnya" = ruangan bebas di luar data rooms, TIDAK ikut dicek bentrok jadwal.
     */
    protected const NAMA_RUANG_HRD = 'Ruang Pelatihan HRD';

    public function storeRoom(Request $request): RedirectResponse
    {
        $maxHours = (int) config('training.room_max_hours', 24);

        $validated = $request->validateWithBag('room', [
            'nik' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'room_choice' => ['required', 'in:hrd,lainnya'],
            'room_name_custom' => ['required_if:room_choice,lainnya', 'nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        $mulai = \Carbon\Carbon::parse($validated['start_time']);
        $selesai = \Carbon\Carbon::parse($validated['end_time']);

        if ($mulai->diffInMinutes($selesai) > $maxHours * 60) {
            return back()
                ->withErrors(['end_time' => "Durasi pemesanan ruangan maksimal {$maxHours} jam."], 'room')
                ->withInput();
        }

        if ($validated['room_choice'] === 'hrd') {
            // firstOrCreate aman dipanggil di sini karena ini handler POST (memang boleh menulis data),
            // beda dengan index() yang GET dan tidak boleh punya efek samping nulis ke DB.
            $ruangHrd = Room::firstOrCreate(
                ['name' => self::NAMA_RUANG_HRD],
                ['is_active' => true]
            );
            $validated['room_id'] = $ruangHrd->id;
            $validated['room_name_custom'] = null;
        } else {
            $validated['room_id'] = null;
            // room_name_custom sudah terisi dari input
        }

        $validated['type'] = 'internal';

        return $this->simpan($validated, 'room', 'room', '#pesan-ruang');
    }

    /**
     * Cari karyawan by NIK, cek bentrok ruangan (kalau ada room_id), simpan.
     */
    protected function simpan(array $data, string $kind, string $errorBag, string $anchor): RedirectResponse
    {
        $karyawan = Karyawan::where('nik', $data['nik'])->first();

        if (! $karyawan) {
            return back()
                ->withErrors(['nik' => 'NIK tidak ditemukan. Pastikan NIK yang diketik benar.'], $errorBag)
                ->withInput();
        }

        try {
            $trainingRequest = DB::transaction(function () use ($data, $kind, $karyawan) {
                if (! empty($data['room_id'])) {
                    Room::whereKey($data['room_id'])->lockForUpdate()->first();

                    $bentrok = TrainingRequest::query()
                        ->inRoom((int) $data['room_id'])
                        ->blocking()
                        ->overlapping($data['start_time'], $data['end_time'])
                        ->first();

                    if ($bentrok) {
                        throw new \RuntimeException(sprintf(
                            'Ruangan sudah terpakai pada %s - %s (%s).',
                            $bentrok->start_time->format('d/m/Y H:i'),
                            $bentrok->end_time->format('d/m/Y H:i'),
                            $bentrok->title
                        ));
                    }
                }

                return TrainingRequest::create([
                    'karyawan_id' => $karyawan->id,
                    'kind' => $kind,
                    'type' => $data['type'] ?? 'internal',
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'reason' => $data['reason'] ?? null,
                    'objective' => $data['objective'] ?? null,
                    'room_id' => $data['room_id'] ?? null,
                    'room_name_custom' => $data['room_name_custom'] ?? null,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'status' => TrainingRequest::STATUS_PENDING,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['room_id' => $e->getMessage()], $errorBag)->withInput();
        }

        // Redirect balik ke welcome dengan ?nik=... supaya riwayat & pengajuan baru
        // langsung kelihatan, plus anchor supaya browser scroll ke kartu yang relevan.
        return redirect(route('welcome', ['nik' => $karyawan->nik]) . $anchor)
            ->with("success_{$kind}", "Pengajuan \"{$trainingRequest->title}\" berhasil dikirim dan menunggu persetujuan admin.");
    }
}
