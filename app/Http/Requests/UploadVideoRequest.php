<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,video/avi|max:3072000'
        ];
    }

    public function messages()
    {
        return [
            'file.maxlength' => 'File size must be less than 3GB'
        ];
    }
}
