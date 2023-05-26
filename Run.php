<?php

namespace Command\Pull;

class Run
{
    public static function go(int $pid, $i)
    {
        echo '===============>Свободный слот: pid_'.$i. "\n";
        echo '===============>PID: '.$pid. "\n";

        echo 'in job';
        while (preg_match('/.*?PID.*TTY.*TIME.*CMD\s+('.$pid.')\s+.*/m', (string)shell_exec("pid -p ".$pid))) {
            echo '...';
            sleep(3);
        }
        echo "===============>!!!Завершение процесса: $pid \n";
        $event = Event::init();
        $event->unset($pid);
    }
}

Run::go($_SERVER['argv'][1],$_SERVER['argv'][2]);
