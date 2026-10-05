<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\TrainingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    private const COLORS = [
        'approved' => '#22c55e', // hijau
        'pending' => '#f59e0b',  // kuning/oranye
    ];

    public function index()
    {
        $rooms = Room::orderBy('name')->get();

        return view('training.calendar.index', compact('rooms'));
    }

    /**
     * FullCalendar otomatis mengirim ?start=...&end=... (ISO8601) sesuai range yang tampil.
     * Filter opsional: ?room_id=3&include_pending=0
     * Session-authenticated (bukan token) karena dipanggil dari halaman Blade yang sama.
     */
    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
            'room_id' => ['nullable', 'integer'],
            'include_pending' => ['nullable', 'boolean'],
        ]);

        $statuses = $request->boolean('include_pending', true)
            ? ['approved', 'pending']
            : ['approved'];

        $events = TrainingRequest::with(['room:id,name', 'karyawan:id,nama'])
            ->whereIn('status', $statuses)
            ->when($request->room_id, fn ($q, $v) => $q->where('room_id', $v))
            ->when($request->start && $request->end, fn ($q) => $q->overlapping($request->start, $request->end))
            ->orderBy('start_time')
            ->get()
            ->map(fn (TrainingRequest $t) => [
                'id' => $t->id,
                'title' => ($t->status === 'pending' ? '(Pending) ' : '') . $t->title,
                'start' => $t->start_time->toIso8601String(),
                'end' => $t->end_time->toIso8601String(),
                'color' => self::COLORS[$t->status] ?? '#64748b',
                'extendedProps' => [
                    'status' => $t->status,
                    'kind' => $t->kind,
                    'type' => $t->type,
                    'description' => $t->description,
                    'room' => $t->room?->name,
                    'requester' => $t->karyawan?->nama,
                ],
            ]);

        return response()->json($events);
    }
}
