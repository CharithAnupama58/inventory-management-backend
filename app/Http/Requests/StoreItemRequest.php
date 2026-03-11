<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'place_id'      => 'required|exists:places,id',
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:50|unique:items,code',
            'quantity'      => 'required|integer|min:0',
            'serial_number' => 'nullable|string|max:100',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description'   => 'nullable|string|max:1000',
            'status'        => 'sometimes|in:instore,damaged,missing',
        ];
    }
}
