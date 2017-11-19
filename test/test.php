<?php
/**
 * Test & demo application for SpaceCLI
 * @author Spaceboy
 */

namespace Spaceboy\Cli;

require_once(__DIR__ . '/../src/SpaceCli.php');

class TestCliApp extends SpaceCli {

    protected static $progressBarSize   = 40;

    /*
    public function construct () {
        echo "Let's try some fun on command line!\n";
        //echo static::setColor('white:red', __FILE__) . "\n";
    }

    public function destruct () {
        echo (static::setColor('green', "That's all, folks!\n"));
    }
    */

    public function getTitle () {
        return static::setColor('lime', "SpaceCLI TEST") . "\nTest & demo for SpaceCLI";
    }

    /**
     * Colors demo
     * @return void
     */
    public function commandColors () {
        foreach (static::$colors AS $color => $val) {
            foreach (static::$backgrounds AS $background => $val) {
                $col    = "{$color}:{$background}";
                write(static::setColor($col, $col));
            }
        }
    }

    /**
     * Progressbar demo
     * @return void
     */
    public function commandProgress () {
        write('Progress bar test:');
        $size   = 17;
        for ($i = 0; $i <= $size; $i++) {
            sleep(1);
            static::showProgress(static::setColor('yellow', 'Testing:'), $i, $size);
        }
    }

    /**
     * @desc Writes football team name in color; says whether named one is the best
     * @example php test.php team --team "Bohemians 1905" --color=white:green --best
     * @param string team
     * @param string colors
     * @return void
     */
    public function commandTeam ($team = 'Boheminas Praha 1905', $color = 'white:green', $best = NULL) {
        write(static::setColor($color, $team) . ($best ? ' is the best!' : ''));
    }

}

$myCliApp = new TestCliApp($argv);
