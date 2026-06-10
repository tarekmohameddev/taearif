<?php

namespace App\Support;

class SourceBrokerNormalizer
{
    /**
     * Normalize source broker fields based on type.
     * Only runs when source_broker_type is present in the payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        if (! array_key_exists('source_broker_type', $data)) {
            return $data;
        }

        $type = $data['source_broker_type'];

        if ($type === null || $type === '') {
            $data['source_broker_type'] = null;
            $data['source_broker_id'] = null;
            $data['source_broker_name'] = null;
            $data['source_broker_phone'] = null;

            return $data;
        }

        if ($type === 'internal') {
            $data['source_broker_name'] = null;
            $data['source_broker_phone'] = null;

            return $data;
        }

        if ($type === 'external') {
            $data['source_broker_id'] = null;

            return $data;
        }

        return $data;
    }
}
