<?php

namespace App\Notifications;

use App\Models\TrainingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class TrainingRequestStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public TrainingRequest $trainingRequest
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $tr = $this->trainingRequest;
        $karyawan = $tr->karyawan;
        $statusLabel = match ($tr->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucfirst($tr->status),
        };

        return [
            'title'       => "Pengajuan {$statusLabel}",
            'message'     => "Pengajuan \"{$tr->title}\" Anda telah " . strtolower($statusLabel) . "." .
                ($tr->admin_note ? " Catatan: {$tr->admin_note}" : ''),
            'action_url'  => route('karyawan.training.index'),
            'reference_id' => $tr->id,
            'reference_type' => 'training_request',
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}