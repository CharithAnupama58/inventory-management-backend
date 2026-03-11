<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id'              => 'required|exists:items,id',
            'borrower_name'        => 'required|string|max:255',
            'contact'              => 'required|string|max:100',
            'quantity'             => 'required|integer|min:1',
            'borrow_date'          => 'required|date|before_or_equal:today',
            'expected_return_date' => 'required|date|after:borrow_date',
            'notes'                => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'expected_return_date.after' => 'Return date must be after the borrow date.',
            'borrow_date.before_or_equal' => 'Borrow date cannot be in the future.',
        ];
    }
}
