<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserFavoriteRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user();
    }

    public function rules()
    {
        return [];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->user()->id === $this->route('user')->id) {
                $validator->errors()->add('user', 'You cannot favorite yourself.');
            }
        });
    }
}
