<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObservationRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'species' => ['required', 'string', 'max:255'],
            'observed_at' => ['required', 'date', 'before_or_equal:now'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_name' => ['required', 'string', 'max:255'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['file', 'mimetypes:video/mp4,video/quicktime', 'max:102400'],
            'description' => ['nullable', 'string', 'max:5000'],
            'water_temperature' => ['nullable', 'numeric', 'between:-5,50'],
            'depth_meters' => ['nullable', 'numeric', 'between:0,500'],
            'weather' => ['nullable', 'string', 'max:255'],
        ];
    }
}
