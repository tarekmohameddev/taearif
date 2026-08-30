<?php

namespace Tests\Unit\Calling;

use App\Domain\Calling\Services\AmiClient;
use PHPUnit\Framework\TestCase;

class AmiEventParserTest extends TestCase
{
    /** @test */
    public function test_it_flattens_repeated_chanvariable_lines(): void
    {
        $raw = implode("\r\n", [
            'Event: Hangup',
            'Channel: PJSIP/agent_1430_1430-00000021',
            'Uniqueid: 1787700790.33',
            'Cause: 17',
            'ChanVariable: TAEARIF_DEST=966566064451',
            'ChanVariable: TAEARIF_CALL_ID=7f100dd3-4591-4253-8ac1-f033dfb03f15',
            'ChanVariable: TAEARIF_TRUNK=loopback',
        ]);

        $event = AmiClient::parseEventBlock($raw);

        $this->assertSame('Hangup', $event['Event']);
        $this->assertSame('1787700790.33', $event['Uniqueid']);
        $this->assertSame('7f100dd3-4591-4253-8ac1-f033dfb03f15', $event['TAEARIF_CALL_ID']);
        $this->assertSame('966566064451', $event['TAEARIF_DEST']);
        $this->assertArrayNotHasKey('ChanVariable', $event);
    }

    /** @test */
    public function test_it_parses_chanvariable_with_channel_name_key(): void
    {
        $raw = implode("\r\n", [
            'Event: DialBegin',
            'ChanVariable(PJSIP/agent_1430_1430-00000021): TAEARIF_CALL_ID=7f100dd3-4591-4253-8ac1-f033dfb03f15',
            'Uniqueid: 1787700790.33',
        ]);

        $event = AmiClient::parseEventBlock($raw);

        $this->assertSame('7f100dd3-4591-4253-8ac1-f033dfb03f15', $event['TAEARIF_CALL_ID']);
        $this->assertSame('1787700790.33', $event['Uniqueid']);
    }
}
