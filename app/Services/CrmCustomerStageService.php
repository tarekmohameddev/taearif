<?php

namespace App\Services;

use App\Models\ApiCustomer;
use App\Models\Api\UserApiCustomerStage;

class CrmCustomerStageService
{
    /**
     * Central place to change a customer's CRM stage.
     *
     * This updates the customer's stage and is the right hook
     * to add any extra side effects (e.g. syncing other CRM boards).
     */
    public function changeStage(ApiCustomer $customer, UserApiCustomerStage $stage): ApiCustomer
    {
        $customer->stage_id = $stage->id;
        $customer->save();

        // Future: add any additional syncing logic here
        // (e.g. update related CRM request records)

        return $customer;
    }
}

