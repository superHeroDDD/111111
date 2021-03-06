<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Hotel;
use Illuminate\Validation\Rule;

class HotelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->get('id');
        $rules = [
            'name' => 'required|required_not_empty|max:40',
            'address' => 'required|required_not_empty',
            'email' => 'required|required_not_empty|email',
            'person_in_charge' => 'required|required_not_empty|max:40',
            'tel' => 'required|required_not_empty|digits_between:10,11|regex:/^\d{10,11}$/',
            'checkin_start' => 'required|required_not_empty',
            'checkin_end' => 'required|required_not_empty',
            'checkout_end' => 'required|required_not_empty',
            'tema_login_id' => [
                'max:40',  
                Rule::unique('hotels', 'tema_login_id')->where(function ($query) use ($id) {
                    if ($query->where('id', '!=', $id) && $query->where('tema_login_id', '!=', null)) {
                        return $query;
                    }
                }),
            ],
            'tema_login_password' => 'max:128',
        ];

        
        $hotelId = $this->get('hotel_id');
        if (!empty($hotelId)) {
            $hotel = Hotel::find($hotelId);

            if (!empty($hotel) && $hotel->business_type == 1) {
                $hotelKidsPolicies = $this->get('hotelKidsPolicies');
                foreach ($hotelKidsPolicies as $key => $hotelKidsPolicy) {
                    $rules['hotelKidsPolicies.' . $key . '.age_start'] = 'required|required_not_empty|Integer|min:0';
                    $rules['hotelKidsPolicies.' . $key . '.age_end'] = 'required|required_not_empty|Integer|min:0|gt:hotelKidsPolicies.' . $key . '.age_start';
                    $rateType = $hotelKidsPolicy['rate_type'];
                    if ($rateType == 1) {
                        $rules['hotelKidsPolicies.' . $key . '.fixed_amount'] = 'required|required_not_empty|Integer|min:0';
                    }
                    if ($rateType == 2) {
                        $rules['hotelKidsPolicies.' . $key . '.rate'] = 'required|required_not_empty|Integer|min:0';
                    }
                    $isAllRoom = $hotelKidsPolicy['is_all_room'];
                    if ($isAllRoom != 1) {
                        $rules['hotelKidsPolicies.' . $key . '.room_type_ids'] = 'required|required_not_empty';
                    }
                }
            }
        }

        $hotelNotes = $this->get('hotelNotes');
        if (!empty($hotelNotes)) {
            $rules['hotelNotes.*.title'] = 'required|required_not_empty';
            $rules['hotelNotes.*.content'] = 'required|required_not_empty';
        }
        
        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'name' => '????????????',
            'address' => '??????',
            'email' => '?????????????????????',
            'person_in_charge' => '??????????????????',
            'tel' => '????????????',
            'tema_login_id' => '???????????? ID(Temairazu)',
            'tema_login_password' => '???????????????????????????(Temairazu)'
        ];
        $hotelKidsPolicies = $this->get('hotelKidsPolicies');
        foreach ($hotelKidsPolicies as $key => $hotelKidsPolicy) {
            $attributes['hotelKidsPolicies.' . $key . '.age_start'] = '????????????';
            $attributes['hotelKidsPolicies.' . $key . '.age_end'] = '????????????';
            $attributes['hotelKidsPolicies.' . $key . '.fixed_amount'] = '????????????';
            $attributes['hotelKidsPolicies.' . $key . '.rate'] = '???????????????%??????';
            $attributes['hotelKidsPolicies.' . $key . '.room_type_ids'] = '???????????????';
        }
        $attributes['hotelNotes.*.title'] = '????????????';
        $attributes['hotelNotes.*.content'] = '??????';
        return $attributes;
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(response()->json([
            'code' => 1422,
            'status' => 'FAIL',
            'message' => $validator->errors(),
        ], 200)));
    }
}
