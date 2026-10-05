<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization di-handle di controller (guard web/admin)
    }

    public function rules(): array
    {
        return [
            'status'     => ['required', Rule::in(['approved', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'required_if:status,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_note.required_if' => 'Catatan penolakan wajib diisi saat menolak pengajuan.',
        ];
    }
}