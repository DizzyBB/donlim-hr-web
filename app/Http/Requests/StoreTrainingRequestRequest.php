<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pengecekan login sebenarnya sudah dilakukan middleware 'auth:karyawan' di routes.
        return auth('karyawan')->check();
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['training', 'room'])],
            'type' => ['nullable', Rule::in(['internal', 'external'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            // wajib jika Pesan Ruang Training; opsional untuk training eksternal
            'room_id' => [
                Rule::requiredIf(fn () => $this->input('kind') === 'room'
                    || $this->input('type', 'internal') === 'internal'),
                'nullable',
                Rule::exists('rooms', 'id')->where('is_active', true),
            ],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Form "Pesan Ruang Training": maksimal 24 jam
            if ($this->input('kind') === 'room' && ! $v->errors()->hasAny(['start_time', 'end_time'])) {
                $start = \Carbon\Carbon::parse($this->input('start_time'));
                $end = \Carbon\Carbon::parse($this->input('end_time'));

                if ($start->diffInMinutes($end) > 24 * 60) {
                    $v->errors()->add('end_time', 'Durasi pemesanan ruangan maksimal 24 jam.');
                }
            }
        });
    }
}
