<?php

namespace Trench\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as IlluminateRequest;

abstract class AbstractRequest extends IlluminateRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
