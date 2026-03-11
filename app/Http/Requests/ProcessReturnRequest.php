<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_condition' => 'required|in:good,fair,damaged',
            'notes'            => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'return_condition.in' => 'Condition must be one of: good, fair, or damaged.',
        ];
    }
}
