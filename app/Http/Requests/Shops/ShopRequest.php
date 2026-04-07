<?php

namespace App\Http\Requests\Shops;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Shops\Shop;

class ShopRequest extends FormRequest
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
        $shop_id = $this->route('shop')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'custom_slug' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:shops,custom_slug,' . $shop_id,
                function ($attribute, $value, $fail) {
                    if ($value && !Shop::isValidCustomSlug($value)) {
                        $fail('Custom slug can only contain lowercase letters, numbers, and hyphens.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'logo_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'shop_category_id' => ['nullable', 'exists:shop_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'custom_slug.regex' => 'Custom slug can only contain lowercase letters, numbers, and hyphens.',
            'custom_slug.unique' => 'This custom URL is already taken. Please choose another.',
        ];
    }
}
