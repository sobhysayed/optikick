<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class AssessmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'issue_type' => 'required|string|in:injury,illness,other',
            'message' => 'required|string|max:500',
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $requestDateTime = \Carbon\Carbon::parse($value . ' ' . $this->hour);
                    if ($requestDateTime->isPast()) {
                        $fail('The appointment time must be in the future.');
                    }
                },
            ],
            'hour' => 'required|date_format:H:i'
        ];
    }

    public function messages()
    {
        return [
            'date.after_or_equal' => 'The appointment date must be today or a future date',
            'hour.date_format' => 'The time must be in 24-hour format (e.g., 14:30)'
        ];
    }
}