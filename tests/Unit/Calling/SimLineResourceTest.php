<?php

namespace Tests\Unit\Calling;

use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Models\CallTrunk;
use App\Http\Resources\Api\V1\Calling\SimLineResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class SimLineResourceTest extends TestCase
{
    /** @test */
    public function test_it_omits_trunk_when_relation_is_loaded_but_null(): void
    {
        $line = new CallSimLine([
            'label'      => 'GSM Port 1',
            'msisdn'     => '+966501111111',
            'port_index' => 1,
            'is_active'  => true,
            'user_id'    => null,
        ]);
        $line->id = 1;
        $line->setRelation('trunk', null);

        $array = (new SimLineResource($line))->resolve(Request::create('/'));

        $this->assertSame(1, $array['id']);
        $this->assertSame('GSM Port 1', $array['label']);
        $this->assertArrayNotHasKey('trunk', $array);
    }

    /** @test */
    public function test_it_includes_trunk_when_present(): void
    {
        $trunk = new CallTrunk([
            'name'   => 'Loopback',
            'type'   => 'sip',
            'status' => 'registered',
        ]);
        $trunk->id = 2;

        $line = new CallSimLine([
            'label'      => 'Loopback test',
            'msisdn'     => '+966500000002',
            'port_index' => 2,
            'is_active'  => true,
        ]);
        $line->id = 2;
        $line->setRelation('trunk', $trunk);

        $array = (new SimLineResource($line))->resolve(Request::create('/'));

        $this->assertSame([
            'id'     => 2,
            'name'   => 'Loopback',
            'type'   => 'sip',
            'status' => 'registered',
        ], $array['trunk']);
    }
}
