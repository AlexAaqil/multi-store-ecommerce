<?php

namespace App\Http\Requests\Products;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductCategoryRequest extends FormRequest
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
        $category_id = $this->route('product_category')?->id;

        return [
            'name' => [
                'required', 
                'string', 
                'max:150', 
                Rule::unique('product_categories', 'name')->ignore($category_id),
            ],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
