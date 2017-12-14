<?php
/**
 * CLI tools
 * @author Spaceboy
 */

namespace Spaceboy\Cli;

function write () {
    foreach (func_get_args() AS $line) {
        echo "{$line}\n";
    }
}

abstract class SpaceCli {

    const       EXT_INI = '.ini';

    /** @var array */
    protected   $params = [];

    /** @var array */
    protected   $argv = [];

    /** @var array of colors */
    protected static    $colors = [
        'black'     => "\e[0;30m",
        'gray'      => "\e[1;30m",
        'silver'    => "\e[0;37m",
        'white'     => "\e[1;37m",
        'navy'      => "\e[0;34m",
        'blue'      => "\e[1;34m",
        'green'     => "\e[0;32m",
        'lime'      => "\e[1;32m",
        'teal'      => "\e[0;36m",
        'aqua'      => "\e[1;36m",
        'maroon'    => "\e[0;31m",
        'red'       => "\e[1;31m",
        'purple'    => "\e[0;35m",
        'fuchsia'   => "\e[1;35m",
        'olive'     => "\e[0;33m",
        'yellow'    => "\e[1;33m",
    ];
    protected static    $backgrounds = [
        'black'     => "\e[40m",
        'red'       => "\e[41m",
        'green'     => "\e[42m",
        'yellow'    => "\e[43m",
        'blue'      => "\e[44m",
        'purple'    => "\e[45m",
        'cyan'      => "\e[46m",
        'white'     => "\e[47m",
    ];

    /** @var integer */
    protected static $progressBarSize   = 50;

    /** @var array -- available commands */
    protected   $commands;

    /** @var string script name */
    protected   $script;

    /** @var string command name */
    protected   $command;

    /** @var array configuration parameters from *.INI file */
    protected   $config = [];


    /**
     * Class constructor
     * @return void
     */
    public function __construct ($argv) {
        echo $this->getTitle() . "\n";
        $this->commands = $this->_getCommandsAvailable();
        $this->_setDirs(debug_backtrace()[0]);
        $this->_parseIniFile();
        $this->_parseArgv($argv);
        $this->construct();
        $this->_handleCommand($this->command);
        $this->destruct();
    }

    /**
     * Set constants
     * @return void
     */
    private function _setDirs ($bckTrace) {
        // basename
        // dirname
        // pathinfo
        define('__SCRIPT__', $bckTrace['file']);
        define('__ROOT__', DIRECTORY_SEPARATOR);
        //echo __DIR__ . "\n";
        //echo __FILE__ . "\n";
        //echo __SCRIPT__ . "\n";
        //echo __ROOT__ . "\n";
    }

    /**
     * Load configuration
     * @return void
     */
    private function _parseIniFile () {
        $fileName   = preg_replace('/\.php$/', static::EXT_INI, __SCRIPT__);
        if (!file_exists($fileName)) {
            return;
        }
        $this->config   = parse_ini_file($fileName, TRUE);
    }

    /**
     * Translate script parameters to command and command parameters
     * @param array $argv
     * @return void
     */
    private function _parseArgv ($argv) {
        $this->argv = $argv;
        $this->script   = array_shift($argv);
        if (!sizeof($argv)) {
            return;
        }
        $this->command  = array_shift($argv);
        if (!sizeof($argv)) {
            return;
        }
        $paramName  = NULL;
        foreach ($argv AS $item) {
            if (preg_match('/^\-+([a-z0-9_\-\+]+)=(.*)$/', $item)) {
                preg_replace_callback('/^\-+([a-z0-9_\-\+]+)=(.*)$/', function ($m) {
                    $this->params[$m[1]]    = $m[2];
                }, $item);
                continue;
            }
            if (preg_match('/^\-+/', $item)) {
                if ($paramName) {
                    $this->params[$paramName]   = TRUE;
                    $paramName                  = NULL;
                    continue;
                }
                $paramName  = preg_replace('/^\-*(.*)$/', '$1', $item);
                continue;
            }
            $this->params[$paramName]   = $item;
            $paramName                  = NULL;
        }
        if ($paramName) {
            $this->params[$paramName]   = TRUE;
        }
    }

    /**
     * Find and call proper method
     * @param string $command
     * @return mixed
     */
    private function _handleCommand ($command) {
        if (!in_array($command, $this->commands)) {
            return $this->commandDefault($command);
        }
        $methodName = "command{$command}";
        $reflex = new \ReflectionMethod($this, $methodName);
        $params = [];
        foreach ($reflex->getParameters() AS $param) {
            if (array_key_exists($param->name, $this->params)) {
                $params[$param->name]   = $this->params[$param->name];
                continue;
            }
            if ($param->allowsNull()) {
                $params[$param->name]   = $param->getDefaultValue();
                continue;
            }
            write("Required parameter \"{$param->name}\" is missing.");
            return;
        }
        return call_user_func_array([$this, $methodName], $params);
    }

    /**
     * Find possible commands (each "commandXxxx" method)
     * @return array
     */
    private function _getCommandsAvailable () {
        $commands   = [];
        foreach (get_class_methods($this) AS $command) {
            if ('command' == strtolower(substr($command, 0, 7))) {
                $c  = strtolower(substr($command, 7));
                if ('default' != $c) {
                    $commands[] = $c;
                }
            }
        }
        return $commands;
    }

    /**
     * Returns command line parameter
     * @param string
     * @param NULL
     * @return mixed
     */
    protected function getParam ($paramName, $defaultValue = NULL) {
        return (
            array_key_exists($paramName, $this->params)
            ? $this->params[$paramName]
            : $defaultValue
        );
    }

    /**
     * Returns CLI application title & version in printable format
     */
    protected function getTitle () {
        return static::setColor('lime', " SpaceCLI 0.1 ");
    }

    /**
     * Sets color for output
     * @param string color
     * @param string text
     * @return string
     */
    protected static function setColor ($color, $text = NULL) {
        if (!is_null($text)) {
            return static::setColor($color) . $text . static::setColor(NULL);
        }
        if (is_null($color)) {
            return "\e[0m";
        }
        $c  = split(':', $color);
        return (
            array_key_exists($c[0], static::$colors)
            ? static::$colors[$c[0]]
            : "\e[0m"
        ).(
            2 == sizeof($c)
            ? (
                array_key_exists($c[1], static::$backgrounds)
                ? static::$backgrounds[$c[1]]
                : ''
            )
            : ''
        );
    }

    /**
     * Shows progressbar
     * @param string label
     * @param numeric progress
     * @param numeric limit
     * @return void
     */
    protected static function showProgress ($label, $progress, $limit) {
        $ratio  = $progress / $limit;
        $perc   = round(100 * $ratio);
        $size   = round(static::$progressBarSize * $ratio);
        $eol    = ($progress == $limit ? "\n" : "\r");
        echo "{$label} [" . str_repeat('#', $size) . str_repeat('.', static::$progressBarSize - $size) . "] {$perc}%{$eol}";
    }

    /**
     * CLI application constructor
     */
    protected function construct () {
    }

    /**
     * CLI application destructor
     */
    protected function destruct () {
    }

    /**
     * Default command; can be overwrited by descendant
     * @param string calledCommand (not implemented)
     */
    protected function commandDefault ($calledCommand = NULL) {
        if ($calledCommand) {
            write(static::setColor('red', "Unrecognized command \"{$calledCommand}\"."));
        }
        write('Available commands:');
        foreach ($this->commands AS $command) {
            write(" * {$command}");
        }
    }

    protected function commandHelp () {
        if (!isset($this->argv[2])) {
            write("Type {$this->argv[0]} help COMMAND");
            return $this->commandDefault();
        }
        if (!in_array($this->argv[2], $this->commands)) {
            return $this->commandDefault($this->argv[2]);
        }
        write(static::setColor('lime', "command {$this->argv[2]}:"));
        $reflex = new \ReflectionMethod($this, 'command' . $this->argv[2]);
        $doc    = $reflex->getDocComment();
        if (!$doc) {
            return write("No description for command {$this->argv[2]}.");
        }
        $desc   = array_filter(explode("\n", $doc), function ($item) {
            return (boolean)preg_match('/^\ *\*\ /', $item);
        });
        array_walk($desc, function (&$item) {
            $item   = trim(preg_replace('/^\ *\*/', '', $item));
        });
        $descFn = array_filter($desc, function ($item) {
            return (boolean)preg_match('/^\@(example|desc|description)\ /', $item);
        });
        if (!sizeof($descFn)) {
            foreach ($desc AS $line) {
                write($line);
            }
            return;
        }
        foreach ($descFn AS $line) {
            preg_replace_callback('/^\@(example|desc|description)\ (.*)$/', function ($match) {
                switch ($match[1]) {
                    case 'example':
                        write(static::setColor('yellow', 'example: ') . static::setColor('green', $match[2]));
                        break;
                    case 'desc':
                    case 'description':
                        write($match[2]);
                        break;
                }
            }, $line);
        }
    }

}
