<?php

namespace DarkOak\Traits\Commands;

use DarkOak\Exceptions\DarkOaktylException;

trait EnvironmentWriterTrait
{
    /**
     * Escapes an environment value by looking for any characters that could
     * reasonably cause environment parsing issues. Those values are then wrapped
     * in quotes before being returned.
     */
    public function escapeEnvironmentValue(string $value): string
    {
        if (!preg_match('/^\"(.*)\"$/', $value) && preg_match('/([^\w.\-+\/])+/', $value)) {
            return sprintf('"%s"', addslashes($value));
        }

        return $value;
    }

    /**
     * Update the .env file for the application using the passed in values.
     *
     * @throws \DarkOak\Exceptions\DarkOaktylException
     */
    public function writeToEnvironment(array $values = []): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            throw new DarkOaktylException('Cannot locate .env file, was this software installed correctly?');
        }

        if (!is_writable($path)) {
            throw new DarkOaktylException('The .env file is not writable by the panel process.');
        }

        $saveContents = file_get_contents($path);
        collect($values)->each(function ($value, $key) use (&$saveContents) {
            $key = strtoupper($key);
            $saveValue = sprintf('%s=%s', $key, $this->escapeEnvironmentValue($value));

            if (preg_match_all('/^' . $key . '=(.*)$/m', $saveContents) < 1) {
                $saveContents = $saveContents . PHP_EOL . $saveValue;
            } else {
                $saveContents = preg_replace('/^' . $key . '=(.*)$/m', $saveValue, $saveContents);
            }
        });

        if (file_put_contents($path, $saveContents) === false) {
            throw new DarkOaktylException('Unable to persist environment changes to the .env file.');
        }
    }
}


