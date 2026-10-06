<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gambar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'gambar.image' => 'Foto menu harus berupa file gambar.',
            'gambar.max' => 'Ukuran foto menu maksimal 2 MB.',
        ];
    }
}
