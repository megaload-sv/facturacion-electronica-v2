<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Dte extends BaseConfig
{
    public string $environment = '';

    public function selectedEnvironment(): string
    {
        // Never silently enable real fiscal emission when disabling debug mode.
        $environment = $this->environment;
        if ($environment === '' && ENVIRONMENT !== 'production') {
            $environment = ENVIRONMENT;
        }
        if (!in_array($environment, ['development', 'testing', 'production'], true)) {
            throw new \RuntimeException('Configure dte.environment explícitamente antes de operar DTE.');
        }
        return $environment;
    }
}
