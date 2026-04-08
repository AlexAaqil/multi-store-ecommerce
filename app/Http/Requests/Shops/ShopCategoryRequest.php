<?php

namespace App\Http\Requests\Shops;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopCategoryRequest extends FormRequest
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
        $category = $this->route('shop_category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('shop_categories', 'name')->ignore($category?->id)],
        ];
    }
}
