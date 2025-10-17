<?php

namespace App\Http\Requests\APP_KEEP_MONEY;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'oldPassword' => 'required|string|min:6',
            'newPassword' => 'required|string|min:6|different:oldPassword',
            'confirmPassword' => 'required|string|same:newPassword',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'oldPassword.required' => 'Mật khẩu cũ là bắt buộc',
            'oldPassword.string' => 'Mật khẩu cũ phải là chuỗi',
            'oldPassword.min' => 'Mật khẩu cũ phải có ít nhất 6 ký tự',

            'newPassword.required' => 'Mật khẩu mới là bắt buộc',
            'newPassword.string' => 'Mật khẩu mới phải là chuỗi',
            'newPassword.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
            'newPassword.different' => 'Mật khẩu mới phải khác mật khẩu cũ',

            'confirmPassword.required' => 'Xác nhận mật khẩu là bắt buộc',
            'confirmPassword.string' => 'Xác nhận mật khẩu phải là chuỗi',
            'confirmPassword.same' => 'Xác nhận mật khẩu không khớp với mật khẩu mới',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $validator->errors()
        ], 422));
    }
}
