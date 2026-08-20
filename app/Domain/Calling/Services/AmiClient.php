<?php

declare(strict_types=1);

namespace App\Domain\Calling\Services;

use App\Domain\Calling\Contracts\AmiClientInterface;
use App\Domain\Calling\DTOs\AmiOriginateDto;
use App\Domain\Calling\Exceptions\AmiException;
use Illuminate\Support\Facades\Log;

/**
 * Raw TCP AMI client for Asterisk 20.
 *
 * Handles the minimal subset needed for Taearif:
 *   - Login
 *   - Async Originate
 *   - Hangup by channel variable
 *   - Streaming event read (for calling:ami-listen)
 *
 * No external AMI library is used to keep dependencies minimal and
 * to stay compatible with Asterisk 20's exact action/response format.
 */
final class AmiClient implements AmiClientInterface
{
    private mixed $socket = null;

    public function __construct(
        private readonly string  $host,
        private readonly int     $port,
        private readonly string  $username,
        private readonly ?string $secret,
        private readonly int     $timeout = 10,
    ) {}

    // -----------------------------------------------------------------
    // Public interface
    // -----------------------------------------------------------------

    public function originate(AmiOriginateDto $dto): void
    {
        $this->ensureConnected();

        $variables = implode(',', [
            "TAEARIF_CALL_ID={$dto->callId}",
            "TAEARIF_DEST={$dto->destDialString}",
            "TAEARIF_TRUNK={$dto->trunkEndpoint}",
            "TAEARIF_CALLERID={$dto->callerIdE164}",
            "TAEARIF_RECORD=" . ($dto->record ? '1' : '0'),
        ]);

        $action = implode("\r\n", [
            'Action: Originate',
            "Channel: PJSIP/{$dto->sipUsername}",
            "Context: {$dto->context}",
            'Exten: s',
            'Priority: 1',
            "CallerID: \"{$dto->callerIdE164}\" <{$dto->callerIdE164}>",
            "Timeout: {$dto->ringTimeoutMs}",
            'Async: true',
            "Variable: {$variables}",
            "ActionID: orig-{$dto->callId}",
            '',
            '',
        ]);

        $this->write($action);
        $this->readResponse(); // ack only; real status comes via events
    }

    public function hangup(string $channelName): void
    {
        $this->ensureConnected();

        $action = implode("\r\n", [
            'Action: Hangup',
            "Channel: {$channelName}",
            'Cause: 16',
            "ActionID: hangup-" . md5($channelName),
            '',
            '',
        ]);

        $this->write($action);
        $this->readResponse();
    }

    public function isConnected(): bool
    {
        return is_resource($this->socket) && !feof($this->socket);
    }

    /**
     * Read a single AMI event block (terminated by blank line).
     *
     * Returns:
     *   - non-empty array  → a parsed AMI event
     *   - empty array []   → select timed out; connection is alive but idle (caller should continue looping)
     *   - null             → connection dropped (EOF or socket error); caller should reconnect
     *
     * Uses stream_select() so that idle periods (no incoming events) do NOT
     * cause fgets() to return false and trigger a spurious reconnect.
     * The select timeout controls how often the event loop wakes up to run
     * heartbeat / signal checks; it does not imply the connection is lost.
     */
    public function readEvent(): ?array
    {
        if (!$this->isConnected()) {
            return null;
        }

        $read   = [$this->socket];
        $write  = null;
        $except = null;
        // Wait up to 30 seconds for Asterisk to send something.
        // This is a soft timeout; on expiry we return [] so the caller
        // can perform housekeeping (heartbeat etc.) and loop again.
        $ready = stream_select($read, $write, $except, 30, 0);

        if ($ready === false) {
            // stream_select error — treat as disconnect
            return null;
        }

        if ($ready === 0) {
            // Timeout — no data but the connection is still alive
            return [];
        }

        // Data is available; read one complete event block
        $block = [];
        while (true) {
            $line = fgets($this->socket, 4096);

            if ($line === false) {
                // EOF or read error — connection gone
                return empty($block) ? null : $block;
            }

            $line = rtrim($line, "\r\n");
            if ($line === '') {
                break; // blank line terminates an AMI event block
            }

            [$key, $value] = array_pad(explode(': ', $line, 2), 2, '');
            $block[$key] = $value;
        }

        return $block; // may be empty on a bare blank line (Asterisk keepalive)
    }

    // -----------------------------------------------------------------
    // Connection management
    // -----------------------------------------------------------------

    public function connect(): void
    {
        $errno  = 0;
        $errstr = '';

        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$socket) {
            throw new AmiException("Cannot connect to AMI at {$this->host}:{$this->port} — {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;

        // Read AMI banner
        fgets($this->socket, 4096);

        $this->sendLogin();
    }

    public function connectWithEvents(): void
    {
        $this->connect();
        // Re-login requesting all events
        $this->sendLogin(true);
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
    }

    private function sendLogin(bool $withEvents = false): void
    {
        if (!$this->secret) {
            throw new AmiException('AMI secret is not configured (ASTERISK_AMI_SECRET is missing).');
        }

        $events = $withEvents ? 'on' : 'off';
        $action = implode("\r\n", [
            'Action: Login',
            "Username: {$this->username}",
            "Secret: {$this->secret}",
            "Events: {$events}",
            '',
            '',
        ]);

        $this->write($action);
        $response = $this->readResponse();

        if (($response['Response'] ?? '') !== 'Success') {
            throw new AmiException('AMI login failed: ' . ($response['Message'] ?? 'unknown'));
        }
    }

    private function write(string $data): void
    {
        if (fwrite($this->socket, $data) === false) {
            throw new AmiException('Failed to write to AMI socket.');
        }
    }

    private function readResponse(): array
    {
        $block = [];
        while (($line = fgets($this->socket, 4096)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                break;
            }
            [$key, $value] = array_pad(explode(': ', $line, 2), 2, '');
            $block[$key] = $value;
        }
        return $block;
    }
}
