<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('item')->id;

        return [
            'place_id'      => 'sometimes|exists:places,id',
            'name'          => 'sometimes|string|max:255',
            'code'          => ['sometimes', 'string', 'max:50', Rule::unique('items', 'code')->ignore($itemId)],
            'serial_number' => 'nullable|string|max:100',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description'   => 'nullable|string|max:1000',
        ];
    }
}
