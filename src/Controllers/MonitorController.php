<?php

namespace atikrahman\ServerHealth\Controllers;

use atikrahman\ServerHealth\Models\SystemBios;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\Process\Process;

class MonitorController extends Controller
{
    public function index(){
        $system_bios = new SystemBios();
        //$system_bios->getOsInfo()
        //$system_bios->getOsInfo()

        dd($system_bios->getTotalRam(),$system_bios->getFreeRam(),$system_bios->getCpuLoadPercentage(),$system_bios->getDiskSize(),$system_bios->getOsTasks(),$system_bios->getOsInfo());
        $process = new Process(['systeminfo']);
        $ab = $process->run();
        $data['os_info'] = $process->getOutput();
        $raw_info = exec('Powershell -C "systeminfo /fo CSV | ConvertFrom-Csv | convertto-json" ', $output_os_info, $raw_info);
        $joined_information='';
        foreach ($output_os_info as $dRow) {
            $joined_information.= $dRow;
        }
        $data['os_info'] = json_decode($joined_information);



    /*    $rv = exec('Powershell -C "systeminfo /fo CSV | ConvertFrom-Csv | convertto-json"', $out, $rv);
        dd($out,$buffer);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo 'This is a server using Windows!';
        }
        elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
            echo 'This is a server using Linux!';
        } else {
            echo 'Not Supported!';
        }*/

       // dd(substr(PHP_OS, 0, 3));
        return view('monitor::monitor')->with($data);
    }
}
