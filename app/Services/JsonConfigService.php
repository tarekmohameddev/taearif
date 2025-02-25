<?php

namespace App\Services;

use InvalidArgumentException;

class JsonConfigService
{
    /**
     * Store JSON configuration data into an Eloquent model.
     *
     * @param string $modelClass   Fully qualified model class name.
     * @param string $jsonString   JSON configuration string.
     * @param array  $overrides    Additional key/value pairs to merge into the decoded data.
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \InvalidArgumentException
     */
    public function store(string $modelClass, string $jsonString, array $overrides = [])
    {
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON string provided.");
        }

        // Merge in any additional overrides (for example, user_id)
        $data = array_merge($data, $overrides);

        // Create a new model instance, fill, and save it.
        $instance = new $modelClass;
        $instance->fill($data);
        $instance->save();

        return $instance;
    }
}
