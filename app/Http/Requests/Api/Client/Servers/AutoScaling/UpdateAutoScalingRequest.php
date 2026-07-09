<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\AutoScaling;

use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class UpdateAutoScalingRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'enabled' => 'boolean|nullable',
            'cpu_threshold_up' => 'integer|min:1|max:100|nullable',
            'cpu_threshold_down' => 'integer|min:1|max:100|nullable',
            'ram_threshold_up' => 'integer|min:1|max:100|nullable',
            'ram_threshold_down' => 'integer|min:1|max:100|nullable',
            'disk_threshold_up' => 'integer|min:1|max:100|nullable',
            'disk_threshold_down' => 'integer|min:1|max:100|nullable',
            'scale_up_limit' => 'integer|min:0|nullable',
            'scale_down_limit' => 'integer|min:0|nullable',
            'scale_up_step' => 'integer|min:0|nullable',
            'scale_down_step' => 'integer|min:0|nullable',
            'cooldown_minutes' => 'integer|min:0|nullable',
        ];
    }
}
