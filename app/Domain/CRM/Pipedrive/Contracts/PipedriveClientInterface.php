<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Contracts;

interface PipedriveClientInterface
{
    /**
     * Create a person (contact) in Pipedrive.
     *
     * @param  array{name: string, emails?: array, phones?: array, org_id?: int}  $data
     * @return array{data: array{id: int}}
     */
    public function createPerson(array $data): array;

    /**
     * Create an organization in Pipedrive.
     *
     * @param  array{name: string}  $data
     * @return array{data: array{id: int}}
     */
    public function createOrganization(array $data): array;

    /**
     * Create a deal in Pipedrive.
     *
     * @param  array{title: string, person_id: int, pipeline_id: int, stage_id: int, org_id?: int}  $data
     * @return array{data: array{id: int}}
     */
    public function createDeal(array $data): array;

    /**
     * Test the connection by hitting a lightweight Pipedrive endpoint.
     */
    public function testConnection(): bool;
}
