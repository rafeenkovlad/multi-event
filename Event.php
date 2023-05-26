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

    private array $events;

    public static function init():self
    {
        $threads = fopen('./command/Pull/'.self::EVENT, 'r');
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
    public function onJob(string $COMMAND, $descriptorspec = []):?int
    {
        [Event::class, 'checkCommand']($COMMAND);

        $pid = false;
        for($i = 1; $i<= self::TOTAL; $i++) {
            if(isset($this->{'pid_'.$i})){
                continue;
            }

            $current = proc_open(PHP_BINARY.' ./command/'.$COMMAND, $descriptorspec, $pipes);
            if(is_resource($current)) {
                $status = proc_get_status($current);
                $pid = (int)$status['pid']??0;
                $this->{'pid_'.$i} = $pid;
                $this->{'script_pid_'.$i} = $COMMAND;
                $threads = fopen('./command/Pull/'.Event::EVENT,'w');
                fwrite($threads, json_encode($this, JSON_THROW_ON_ERROR));
                fclose($threads);

                $run = proc_open(PHP_BINARY.' ./command/Pull/Run.php '.$pid.' '.$i, [], $pipes);
                //$status = proc_get_status($run);
                break;
            }
        }

        if(!$pid) {
            //echo '===============>!!!Нет доступных слотов, добавляем задачу в очередь. '."\n";
            return null;
        }

        return $pid;
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

        $threads = fopen('./command/Pull/'.Event::EVENT,'w');
        fwrite($threads, json_encode($this, JSON_THROW_ON_ERROR));
        fclose($threads);
    }

    private function checkCommand(string $command)
    {
        if(!in_array($command, [self::EVENT_EXPORT])) {
            throw new \RuntimeException('Неопределенная команда!');
        }
    }

    public function getStatus():array
    {
        $values = [];
        foreach ($this as $name => $propertie) {
            $values[$name] = $propertie;
        }
        return $values;
    }

    public function checkFreePid():bool
    {
        for($i = 1; $i<=self::TOTAL; $i++) {
            if(!isset($this->{'pid_'.$i})){
                return true;
            }
        }

        return false;
    }
}

//$Event = Event::init();
//$Event->onJob(Event::EVENT_EXPORT);
