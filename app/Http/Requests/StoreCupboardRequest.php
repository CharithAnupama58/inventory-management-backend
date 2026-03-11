<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCupboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Works for both store and update
        // On update, $this->route('cupboard') gives the existing model
        $id = $this->route('cupboard')?->id;

        return [
            'name'        => 'required|string|max:100',
            'code'        => ['required', 'string', 'max:20', Rule::unique('cupboards', 'code')->ignore($id)],
            'description' => 'nullable|string|max:500',
            'location'    => 'nullable|string|max:200',
            'color'       => 'nullable|string|max:10',
            'bg_color'    => 'nullable|string|max:10',
        ];
    }
}
