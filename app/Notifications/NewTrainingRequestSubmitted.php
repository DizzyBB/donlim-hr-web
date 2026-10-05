<?php

namespace App\Notifications;

use App\Models\TrainingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewTrainingRequestSubmitted extends Notification
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
        $kindLabel = $tr->kind === 'room' ? 'Pemesanan Ruangan' : 'Request Training';

        return [
            'title'       => "Pengajuan Baru: {$kindLabel}",
            'message'     => "{$karyawan->nama} ({$tr->nik}) mengajukan \"{$tr->title}\" " .
                "pada {$tr->start_time->format('d/m/Y H:i')} - {$tr->end_time->format('H:i')}.",
            'action_url'  => route('training.approvals'),
            'reference_id' => $tr->id,
            'reference_type' => 'training_request',
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}