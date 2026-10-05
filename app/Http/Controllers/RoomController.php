<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::withCount('trainingRequests')->orderBy('name')->paginate(15);

        return view('training.rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        Room::create($data);

        return back()->with('success', "Ruang \"{$data['name']}\" berhasil ditambahkan.");
    }

    public function update(Request $request, Room $room)
    {
        $data = $this->validasi($request);

        $room->update($data);

        return back()->with('success', "Ruang \"{$room->name}\" berhasil diperbarui.");
    }

    public function destroy(Room $room)
    {
        if ($room->trainingRequests()->exists()) {
            return back()->with('error', "Ruang \"{$room->name}\" tidak bisa dihapus karena sudah punya riwayat pengajuan. Nonaktifkan saja lewat toggle Aktif.");
        }

        $nama = $room->name;
        $room->delete();

        return back()->with('success', "Ruang \"{$nama}\" berhasil dihapus.");
    }

    protected function validasi(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
