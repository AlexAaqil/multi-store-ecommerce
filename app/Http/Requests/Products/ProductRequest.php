<?php

namespace App\Http\Requests\Products;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
        $product_id = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product_id)],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product_id)],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'weight_units' => ['nullable', 'string', 'in:kg,g,lb,oz'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'attributes' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already in use. Please use a different one.',
            'barcode.unique' => 'This barcode is already in use.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.max' => 'Each image must be less than 2MB.',
            'images.*.mimes' => 'Only JPEG, PNG, JPG, and WEBP images are allowed.',
        ];
    }
}
