<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEpisodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'audio_file' => 'nullable|file|mimes:mp3,m4a|max:102400', // 100MB max
            'published_date' => 'required|date',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The episode title is required.',
            'title.max' => 'The episode title may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 2000 characters.',
            'audio_file.file' => 'The audio file must be a valid file.',
            'audio_file.mimes' => 'The audio file must be an MP3 or M4A file.',
            'audio_file.max' => 'The audio file may not be larger than 100MB.',
            'published_date.required' => 'The published date is required.',
            'published_date.date' => 'The published date must be a valid date.',
        ];
    }
}
