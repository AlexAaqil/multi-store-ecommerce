<?php

namespace App\Http\Requests\Products;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Products\Discount;

class DiscountRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:' . Discount::TYPE_PERCENTAGE . ',' . Discount::TYPE_FIXED_AMOUNT],
            'value' => ['required', 'numeric', 'min:0.01'],
            'scope' => 'required|in:' . Discount::SCOPE_SHOP_WIDE . ',' . Discount::SCOPE_PRODUCT_CATEGORY . ',' . Discount::SCOPE_SPECIFIC_PRODUCTS,
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'min_quantity'     => ['nullable', 'integer', 'min:1'],
            'starts_at'        => ['required', 'date'],
            'expires_at'       => ['required', 'date', 'after:starts_at'],
            'is_active'        => ['boolean'],
        ];

        // Conditional validation based on scope
        if ($this->input('scope') == Discount::SCOPE_PRODUCT_CATEGORY) {
            $rules['category_ids'] = 'required|array|min:1';
            $rules['category_ids.*'] = 'exists:product_categories,id';
        }

        if ($this->input('scope') == Discount::SCOPE_SPECIFIC_PRODUCTS) {
            $rules['product_ids'] = 'required|array|min:1';
            $rules['product_ids.*'] = 'exists:products,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Please select at least one category',
            'product_ids.required' => 'Please select at least one product',
            'category_ids.*.exists' => 'Selected category is invalid',
            'product_ids.*.exists' => 'Selected product is invalid',
        ];
    }
}
