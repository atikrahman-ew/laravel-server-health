<?php

namespace atikrahman\ServerHealth\Models;


use stdClass;
use Symfony\Component\Process\Process;

class SystemBios
{

    /**
     * Return Os Information in Json.
     *
     * @return  object
     */

    public function getOsInfo(): object
    {
        if (PHP_OS == 'WINNT') {
            $raw_info = exec('Powershell -C "systeminfo /fo CSV | ConvertFrom-Csv | convertto-json" ', $output_os_info, $raw_info);
            $joined_information = implode('', $output_os_info);
            return json_decode($joined_information);
        }else {
            $os         = shell_exec('cat /etc/os-release');
            $listIds    = preg_match_all('/.*=/', $os, $matchListIds);
            $listIds    = $matchListIds[0];

            $listVal    = preg_match_all('/=.*/', $os, $matchListVal);
            $listVal    = $matchListVal[0];

            array_walk($listIds, function(&$v, $k){
                $v = strtolower(str_replace('=', '', $v));
            });

            array_walk($listVal, function(&$v, $k){
                $v = preg_replace('/=|"/', '', $v);
            });

            $os_info= array_combine($listIds, $listVal);

            return collect($os_info);
        }

    }


    /**
     * Return Os Tasklists in Json.
     *
     * @return  object
     */

    public function getOsTasks(): object
    {
        $top_process_array = array();
        if (PHP_OS == 'WINNT') {
            $raw_info = exec('tasklist -fo csv /nh /fi "memusage gt 100000" | sort /r /+68', $output_os_info, $raw_info);
            foreach ($output_os_info as $output_os){
                $process_level_name_arr = array_filter(explode('"',$output_os));
                foreach($process_level_name_arr as $key => $value) {

                    if(in_array($value, [','])) {
                        unset($process_level_name_arr[$key]);
                    }

                }
                $top_process_array[] =array_values($process_level_name_arr);
            }
        }else{
            $process_level = array(  "USER", "PID", "%MEM", "%CPU", "COMMAND");
            $raw_info =   exec("ps -eo user,pid,%mem,%cpu,command  --sort=-pcpu | head -n 6 | sed -E 's/ +/,/g'", $output_os_info, $raw_info);

            foreach ($output_os_info as $output_os){
                $raw_extracts = explode(',',$output_os);
                if ($raw_extracts[1] == "PID"){
                    continue;
                }
                $process_details = array();
                $level_index=0;
                foreach ($raw_extracts as $raw_extract){
                    if (@$process_level[$level_index]){
                        $process_details[$process_level[$level_index]]=$raw_extract;
                    }else{
                        $process_details[$process_level[4]] .= " ".$raw_extract;
                    }
                    $level_index++;
                }
                $top_process_array[] = $process_details;
            }

        }


        return  collect($top_process_array)->take(5);
    }

    /**
     * Return RAM Total in Bytes.
     *
     * @return int Bytes
     */

    public function getTotalRam(): int
    {
        $result = 0;
        if (PHP_OS == 'WINNT') {
            $lines = null;
            $matches = null;
            exec('wmic ComputerSystem get TotalPhysicalMemory /Value', $lines);
            if (preg_match('/^TotalPhysicalMemory\=(\d+)$/', $lines[2], $matches)) {
                $result = $matches[1];
            }
        } else {
            $fh = fopen('/proc/meminfo', 'r');
            while ($line = fgets($fh)) {
                $pieces = array();
                if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
                    $result = $pieces[1];
                    /*KB to Bytes*/
                    $result = $result * 1024;
                    break;
                }
            }
            fclose($fh);
        }
        /*MB RAM Total*/
        return round($result / 1024 / 1024);
    }

    /**
     * Return free RAM in Bytes.
     *
     * @return int Bytes
     */
    public function getFreeRam(): int
    {
        $result = 0;
        if (PHP_OS == 'WINNT') {
            $lines = null;
            $matches = null;
            exec('wmic OS get FreePhysicalMemory /Value', $lines);
            if (preg_match('/^FreePhysicalMemory\=(\d+)$/', $lines[2], $matches)) {
                $result = $matches[1] * 1024;
            }
        } else {
            $fh = fopen('/proc/meminfo', 'r');
            while ($line = fgets($fh)) {
                $pieces = array();
                if (preg_match('/^MemFree:\s+(\d+)\skB$/', $line, $pieces)) {
                    // KB to Bytes
                    $result = $pieces[1] * 1024;
                    break;
                }
            }
            fclose($fh);
        }
        /*MB RAM Total*/
        return round($result / 1024 / 1024);
    }

    /**
     * Return disk info.
     *
     * @param string $path Drive or path
     * @return object Disk info
     */
    public function getDiskSize(string $path = '/')
    {
        $result =new stdClass();
        $result->size = 0;
        $result->free= 0;
        $result->used = 0;

        if (PHP_OS == 'WINNT') {
            $lines = null;
            $path='C:';
            exec('wmic logicaldisk get FreeSpace^,Name^,Size /Value', $lines);
            foreach ($lines as $index => $line) {
                if ($line != "Name=$path") {
                    continue;
                }
                $result->free= round((explode('=', $lines[$index - 1])[1]) / 1024 / 1024);
                $result->size = round((explode('=', $lines[$index + 1])[1]) / 1024 / 1024);
                $result->used = round($result->size - $result->free);
                break;
            }
        } else {
            $lines = null;
            exec(sprintf('df /P %s', $path), $lines);
            foreach ($lines as $index => $line) {
                if ($index != 1) {
                    continue;
                }
                $values = preg_split('/\s{1,}/', $line);
                $result->size = round(($values[1] * 1024)/ 1024 / 1024);
                $result->free = round(($values[3] * 1024)/ 1024 / 1024);
                $result->used = round(($values[2] * 1024)/ 1024 / 1024);
                break;
            }
        }
        return $result;
    }

    /**
     * Get CPU Load Percentage.
     *
     * @return float load percentage
     */
    public function getCpuLoadPercentage(): float
    {
        $result = -1;
        $lines = null;
        if (PHP_OS == 'WINNT') {
            $matches = null;
            exec('wmic.exe CPU get loadpercentage /Value', $lines);
            if (preg_match('/^LoadPercentage\=(\d+)$/', $lines[2], $matches)) {
                $result = $matches[1];
            }
        } else {
            $checks = array();
            foreach (array(0, 1) as $i) {
                $cmd = '/proc/stat';
                $lines = array();
                $fh = fopen($cmd, 'r');
                while ($line = fgets($fh)) {
                    $lines[] = $line;
                }
                fclose($fh);
                foreach ($lines as $line) {
                    $ma = array();
                    if (!preg_match('/^cpu  (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+)$/', $line, $ma)) {
                        continue;
                    }

                    $total = $ma[1] + $ma[2] + $ma[3] + $ma[4] + $ma[5] + $ma[6] + $ma[7] + $ma[8] + $ma[9];
                    $ma['total'] = $total;
                    $checks[] = $ma;
                    break;
                }

                if ($i == 0) {
                    /* Waiting for checking again */
                    sleep(1);
                }
            }

            // Idle - prev idle
            $diffIdle = $checks[1][4] - $checks[0][4];

            // Total - prev total
            $diffTotal = $checks[1]['total'] - $checks[0]['total'];

            // Usage in %
            $diffUsage = (1000 * ($diffTotal - $diffIdle) / $diffTotal + 5) / 10;
            $result = $diffUsage;
        }
        return (float) $result;
    }
}
