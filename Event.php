<?php

namespace Command\Pull;

use http\Encoding\Stream;

class Event
{
    private static Event $event;
    public CONST EVENT = 'Event.json';

    public const EVENT_EXPORT = 'scriptExportCatalog.php';

    public  ?int  $pid_1; public ?string $script_pid_1;
    public  ?int  $pid_2; public ?string $script_pid_2;
    public  ?int  $pid_3; public ?string $script_pid_3;
    public  ?int  $pid_4; public ?string $script_pid_4;
    public  ?int  $pid_5; public ?string $script_pid_5;
    public  ?int  $pid_6; public ?string $script_pid_6;
    public  ?int  $pid_7; public ?string $script_pid_7;
    public  ?int  $pid_8; public ?string $script_pid_8;
    public  ?int  $pid_9; public ?string $script_pid_9;
    public  ?int  $pid_10; public ?string $script_pid_10;

    private const TOTAL = 10;
    private int $maxThreads;

    public static function init():self
    {
        $threads = fopen('./Pull/'.self::EVENT, 'r+');
        $json = fgets($threads);
        $event = json_decode($json, false);

        if($event) {
            self::$event =new self();
            for($i = 1; $i <= self::TOTAL; $i ++) {
                if(isset($event->{'pid_'.$i})) {
                    self::$event->{'pid_'.$i} = $event->{'pid_'.$i};
                }
                if(isset($event->{'script_pid_'.$i})) {
                    self::$event->{'script_pid_'.$i} = $event->{'script_pid_'.$i};
                }
            }
            fclose($threads);
            return self::$event;
        }

        fclose($threads);
        return self::$event = new self();
    }

    /**
     * @throws \JsonException
     */
    public function onJob(string $COMMAND):array
    {
        [$this, 'checkCommand']($COMMAND);
        $tasks = [];
        for($i = 1; $i<= self::TOTAL; $i++) {
            if(isset($this->{'pid_'.$i})){
                if(!preg_match('/.*?PID.*TTY.*TIME.*CMD\s+('.$this->{'pid_'.$i}.')\s+.*/m', (string)shell_exec("ps -p ".$this->{'pid_'.$i}))) {
                    echo '===============> Высвобожден pid_'.$i."\n";
                    $this->unset($this->{'pid_'.$i});
                }else{
                    continue;
                }

            }

            $pid = exec(PHP_BINARY.' ./'.$COMMAND. ' > /dev/null 2>&1 & echo $!; ', $output);
            if($pid) {
                $pid = (int) $pid;
                $this->{'pid_'.$i} = $pid;
                $this->{'script_pid_'.$i} = $COMMAND;
                $threads = fopen('./Pull/'.Event::EVENT,'w');
                fwrite($threads, json_encode($this, JSON_THROW_ON_ERROR));
                fclose($threads);

                //proc_open(PHP_BINARY.' ./command/Pull/Run.php '.$pid.' '.$i, $descriptorspec, $pipes);
                //exec(PHP_BINARY.' ./command/Pull/Run.php '.(print $pid).' '.$i);
                $tasks[] = $pid;
            }

            if($this->maxThreads === count($tasks)) {
                break;
            }
        }

        return $tasks;
    }

    public function unset(int $pid):void
    {
        for ($i = 1; $i <= self::TOTAL; $i++) {
            if($this->{'pid_'.$i} === $pid) {
                unset(
                    $this->{'pid_'.$i},
                    $this->{'script_pid_'.$i}
                );

                break;
            }
        }

        $threads = fopen('./Pull/'.Event::EVENT,'w');
        fwrite($threads, json_encode($this, JSON_THROW_ON_ERROR));
        fclose($threads);
    }

    private function checkCommand(string $command)
    {
        if($command === self::EVENT_EXPORT) {
            $this->maxThreads = 10;
        }else {
            throw new \RuntimeException('Неопределенная команда!');
        }

    }

    public function checkFreePid():bool
    {
        for($i = 1; $i<=self::TOTAL; $i++) {
            if(!isset($this->{'pid_'.$i})){
                return true;
            }
            if(!preg_match('/.*?PID.*TTY.*TIME.*CMD\s+('.$this->{'pid_'.$i}.')\s+.*/m', (string)shell_exec("ps -p ".$this->{'pid_'.$i}))) {
                $this->unset($this->{'pid_'.$i});
                return true;
            }
        }

        return false;
    }
}

$Event = Event::init();
$Event->onJob($_SERVER['argv'][1]);
