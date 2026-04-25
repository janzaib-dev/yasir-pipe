<?php

// Simple test script to validate ZKTeco connection
require __DIR__.'/vendor/autoload.php';

use Fsuuaas\Zkteco\Lib\ZKTeco;

echo "Testing BC-K40 Connection...\n";
echo "IP: 192.0.0.64\n";
echo "Port: 8000\n\n";

try {
    echo "Creating ZKTeco instance...\n";
    $zk = new ZKTeco('192.0.0.64', 8000);

    echo "Attempting connection...\n";
    $connected = $zk->connect();

    if ($connected) {
        echo "✓ Connection successful!\n";

        try {
            echo "Getting device version...\n";
            $version = $zk->version();
            echo 'Device version: '.json_encode($version)."\n";
        } catch (Exception $e) {
            echo 'Warning: Could not get version: '.$e->getMessage()."\n";
        }

        echo "Disconnecting...\n";
        $zk->disconnect();
        echo "✓ Test completed successfully!\n";
    } else {
        echo "✗ Connection failed - connect() returned false\n";
    }
} catch (Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
    echo 'Trace: '.$e->getTraceAsString()."\n";
}
