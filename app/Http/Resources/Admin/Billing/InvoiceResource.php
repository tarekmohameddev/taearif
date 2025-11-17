<?php

namespace App\Http\Resources\Admin\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Invoice Resource
 *
 * Transforms Invoice model into JSON response
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => [
                'total' => (float) $this->price,
                'package_price' => (float) $this->package_price,
                'discount' => (float) $this->discount,
                'currency' => $this->currency,
                'currency_symbol' => $this->currency_symbol,
                'formatted' => $this->formatted_amount,
            ],
            'status' => $this->status_text,
            'status_code' => $this->status,
            'payment_method' => $this->payment_method,
            'is_trial' => $this->is_trial,
            'trial_days' => $this->trial_days,
            'coupon_code' => $this->coupon_code,
            'receipt' => $this->receipt ? asset('assets/receipts/' . $this->receipt) : null,
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'full_name' => trim($this->user->first_name . ' ' . $this->user->last_name),
            ],
            'package' => [
                'id' => $this->package->id,
                'title' => $this->package->title,
                'slug' => $this->package->slug,
                'term' => $this->package->term,
                'price' => (float) $this->package->price,
                'is_trial' => $this->package->is_trial ?? false,
            ],
            'dates' => [
                'start' => $this->start_date?->format('Y-m-d'),
                'expire' => $this->expire_date?->format('Y-m-d'),
                'created' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
            'transaction_details' => $this->transaction_details,
            'settings' => $this->settings,
            'modified' => $this->modified,
        ];
    }
}

