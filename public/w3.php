<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
$cmd_str = "MODE COM4: BAUD=9600 PARITY=N DATA=8 STOP=1 XON=OFF TO=OFF OCTS=OFF ODSR=OFF IDSR=OFF RTS=OFF DTR=OFF";
$output = array();
exec($cmd_str, $output, $result);

set_time_limit(0);

$serial_port = fopen("COM4","rn");

$hasil = fgets($serial_port,9600);
fflush($serial_port);
fclose($serial_port);
  print("<pre>");
print_r($cmd_str);
print("\n");
//print_r($output);
print("\n");
print_r($hasil);
print("\n");
print("</pre>");

	

