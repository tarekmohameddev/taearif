<?php

namespace Tests\Unit\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EmployeeRulesRequestValidationTest extends TestCase
{
    public function test_store_accepts_valid_employee_rules_with_empty_rules_array(): void
    {
        $rules = (new StoreEmployeeRequest())->rules();

        $validator = Validator::make([
            'email' => 'employee-rules-store@example.test',
            'password' => 'secret12',
            'employeeRules' => [
                ['isActive' => false, 'rules' => []],
            ],
        ], $rules);

        $this->assertTrue($validator->passes(), (string) $validator->errors());
    }

    public function test_store_rejects_more_than_one_employee_rules_block(): void
    {
        $rules = (new StoreEmployeeRequest())->rules();

        $validator = Validator::make([
            'email' => 'employee-rules-store2@example.test',
            'password' => 'secret12',
            'employeeRules' => [
                ['isActive' => true, 'rules' => []],
                ['isActive' => true, 'rules' => []],
            ],
        ], $rules);

        $this->assertFalse($validator->passes());
    }

    public function test_update_accepts_employee_rules_when_employee_id_matches_route(): void
    {
        $payload = [
            'employeeRules' => [
                [
                    'employeeId' => '99',
                    'isActive' => true,
                    'rules' => [
                        [
                            'field' => 'source',
                            'operator' => 'equals',
                            'value' => 'web',
                        ],
                    ],
                ],
            ],
        ];

        $base = Request::create('/api/v1/employees/99', 'PUT', $payload);
        $route = new Route(['PUT'], 'employees/{employee}', []);
        $route->bind($base);
        $route->setParameter('employee', '99');
        $base->setRouteResolver(static fn () => $route);

        $form = UpdateEmployeeRequest::createFrom($base);
        $validator = Validator::make($form->all(), $form->rules());

        $this->assertTrue($validator->passes(), (string) $validator->errors());
    }

    public function test_update_rejects_employee_rules_when_employee_id_mismatch(): void
    {
        $payload = [
            'employeeRules' => [
                [
                    'employeeId' => '100',
                    'isActive' => true,
                    'rules' => [],
                ],
            ],
        ];

        $base = Request::create('/api/v1/employees/99', 'PUT', $payload);
        $route = new Route(['PUT'], 'employees/{employee}', []);
        $route->bind($base);
        $route->setParameter('employee', '99');
        $base->setRouteResolver(static fn () => $route);

        $form = UpdateEmployeeRequest::createFrom($base);
        $validator = Validator::make($form->all(), $form->rules());

        $this->assertFalse($validator->passes());
    }
}
