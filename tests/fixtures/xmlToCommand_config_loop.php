<?php
/* Verbatim excerpt of xmlToCommand() from unraid/webgui, emhttp/plugins/dynamix.docker.manager/include/Helpers.php
 * (master, read 2026-09-19): the loop that turns <Config> entries into docker create arguments.
 * Kept as a fixture so the tests can prove entries with an unknown Type are left out. Refresh it if that function changes. */
function tf_config_loop(array $xml, bool $create_paths = false): array {
  global $driver;
  $Volumes = $Ports = $Variables = $Labels = $Devices = [''];
  foreach ($xml['Config'] as $key => $config) {
    $confType        = strtolower(strval($config['Type']));
    $hostConfig      = strlen($config['Value']) ? $config['Value'] : $config['Default'];
    $containerConfig = strval($config['Target']);
    $Mode            = strval($config['Mode']);
    if ($confType != "device" && !strlen($containerConfig)) continue;
    if ($confType == "path") {
      if ( ! trim($hostConfig) || ! trim($containerConfig) )
        continue;
      $Volumes[] = escapeshellarg($hostConfig).':'.escapeshellarg($containerConfig).':'.escapeshellarg($Mode);
      if (!file_exists($hostConfig) && $create_paths) {
        @mkdir($hostConfig, 0777, true);
        @chown($hostConfig, 99);
        @chgrp($hostConfig, 100);
      }
    } elseif ($confType == 'port') {
      switch ($driver[$xml['Network']]) {
      case 'host':
      case 'macvlan':
      case 'ipvlan':
        // Export ports as variable if network is set to host or macvlan or ipvlan
        $Variables[] = strtoupper(escapeshellarg($Mode.'_PORT_'.$containerConfig).'='.escapeshellarg($hostConfig));
        break;
      case 'bridge':
        // Export ports as port if network is set to (custom) bridge
        $Ports[] = escapeshellarg($hostConfig.':'.$containerConfig.'/'.$Mode);
        break;
      case 'none':
        // No export of ports if network is set to none
      }
    } elseif ($confType == "label") {
      $Labels[] = escapeshellarg($containerConfig).'='.escapeshellarg($hostConfig);
    } elseif ($confType == "variable") {
      $Variables[] = escapeshellarg($containerConfig).'='.escapeshellarg($hostConfig);
    } elseif ($confType == "device") {
      $Devices[] = escapeshellarg($hostConfig);
    }
  }
  return compact('Volumes', 'Ports', 'Variables', 'Labels', 'Devices');
}
