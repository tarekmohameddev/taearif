<?php

namespace App\Http\Resources\Api\V1\Calling;

use Illuminate\Http\Resources\Json\JsonResource;

class SoftphoneConfigResource extends JsonResource
{
    public function toArray($request): array
    {
        $ext      = $this->resource['extension'];
        $password = $this->resource['password'];

        return [
            'sip' => [
                'username' => $ext->sip_username,
                'password' => $password,
                'domain'   => config('calling.softphone.sip_domain'),
                'wss_url'  => config('calling.softphone.wss_url'),
            ],
            'ice' => [
                'turn' => [
                    'url'        => config('calling.softphone.turn_url'),
                    'username'   => config('calling.softphone.turn_username'),
                    'credential' => config('calling.softphone.turn_credential'),
                ],
                'stun' => [
                    'url' => config('calling.softphone.stun_url'),
                ],
            ],
        ];
    }
}
