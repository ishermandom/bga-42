<?php
define("APP_GAMEMODULE_PATH", "misc/"); // include path to stubs, which defines "table.game.php" and other classes
// require_once ('texasfortytwo.game.php');

// <?php declare(strict_types=1);
// use PHPUnit\Framework\TestCase;

// class TexasFortyTwoTest extends TexasFortyTwo { // this is your game class defined in ggg.game.php
//     function __construct() {
//         parent::__construct();
//         include '../material.inc.php';// this is how this normally included, from constructor
//     }
//
//     // override/stub methods here that access db and stuff
//     function getGameStateValue(string $var) : string {
//         if ($var == 'round')
//             return 3;
//         return 0;
//     }
//
//     public function getNew($deck_definition) {
//
//     }
// }
// $x = new TexasFortyTwoTest(); // instantiate your class
// $p = $x->getGameProgression(); // call one of the methods to test
// if ($p != 50)
//     echo "Test1: FAILED";
// else
//     echo "Test1: PASSED";



// final class DominoTest extends TestCase
// {
//   public function testCanCreateDomino(): void
//   {
//     $args = [
//       'id' => 1,
//       'high' => 0,
//       'low' => 0,
//     ];
//     $domino = new Domino($args);
//     $this->assertInstanceOf(
//         Domino::class,
//         $domino
//     );
//   }
// }
