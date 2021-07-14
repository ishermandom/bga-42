<?php
/* This file contains stubs for testing / local static analysis. */

if (!defined('APP_GAMEMODULE_PATH')) {
  define('APP_GAMEMODULE_PATH', '');
}

/**
 * Collection of stub classes for testing and stubs
 */
class APP_Object {
  public function dump($v, $value): void {
    echo "$v=";
    var_dump($value);
  }

  public function info($value): void {
    echo "$value\n";
  }

  public function trace($value): void {
    echo "$value\n";
  }

  public function debug($value): void {
    echo "$value\n";
  }

  public function watch($value): void {
    echo "$value\n";
  }

  public function warn($value): void {
    echo "$value\n";
  }

  public function error($msg): void {
    echo "$msg\n";
  }
}

class APP_DbObject extends APP_Object {
  /** @var string */
  public $query;

  public function DbQuery(string $str): void {
    $this->query = $str;
    echo "dbquery: $str\n";
  }

  /** @return string */
  public function getUniqueValueFromDB(string $sql) {
    return 0;
  }

  ///** @return array<string, array<string, string>> */
  public function getCollectionFromDB(string $query, bool $single = false) {
    echo "dbquery coll: $query\n";
    return [];
  }

  /** @return non-empty-array<string, array<string, string>> */
  public function getNonEmptyCollectionFromDB(string $sql) {
    return [];
  }

  /** @return array<string, string> */
  public function getObjectFromDB(string $sql) {
    return [];
  }

  public function getNonEmptyObjectFromDB(string $sql) {
    return [];
  }

  public function getObjectListFromDB($query, $single = false) {
    echo "dbquery list: $query\n";
    return [];
  }

  public function getDoubleKeyCollectionFromDB($sql, $bSingleValue = false) {
    return [];
  }

  public function DbGetLastId() {
  }

  public function DbAffectedRow() {
  }

  public function escapeStringForDB($string) {
  }
}

class APP_GameClass extends APP_DbObject {
  public function __construct() {
  }
}

class GameState {
  public function GameState() {
  }

  public function state() {
    return [];
  }

  public function changeActivePlayer($player_id) {
  }

  public function setAllPlayersMultiactive() {
  }

  public function setAllPlayersNonMultiactive($next_state) {
  }

  public function setPlayersMultiactive($players, $next_state, $bExclusive = false) {
  }

  public function setPlayerNonMultiactive($player_id, $next_state) {
  }

  public function getActivePlayerList() {
  }

  public function updateMultiactiveOrNextState($next_state_if_none) {
  }

  public function nextState($transition) {
  }

  public function checkPossibleAction($action) {
  }
}

class BgaUserException extends Exception {
}

class BgaVisibleSystemException extends Exception {
}

class feException extends Exception {
}

abstract class Table extends APP_GameClass {
  public $players = [];
  public $gamename;
  public $gamestate = null;

  public function __construct() {
    parent::__construct();
    $this->gamestate = new GameState();
    $this->players = [1 => ['player_name' => $this->getActivePlayerName(),'player_color' => 'ff0000' ],
      2 => ['player_name' => 'player2','player_color' => '0000ff' ] ];
  }

  /** Report gamename for translation function */
  abstract protected function getGameName();

  public function getActivePlayerId() {
    return 1;
  }

  public function getActivePlayerName() {
    return "player1";
  }

  public function getTableOptions() {
    return [ ];
  }

  public function getTablePreferences() {
    return [ ];
  }

  public function loadPlayersBasicInfos() {
    $default_colors = ["ff0000","008000","0000ff","ffa500","4c1b5b" ];
    $values = [];
    $id = 1;
    foreach ($default_colors as $color) {
      $values [$id] = ['player_id' => $id,'player_color' => $color,'player_name' => "player$id" ];
      $id++;
    }
    return $values;
  }

  protected function getCurrentPlayerId() {
    return 0;
  }

  protected function getCurrentPlayerName() {
    return '';
  }

  protected function getCurrentPlayerColor() {
    return '';
  }

  public function isCurrentPlayerZombie() {
    return false;
  }


  /**
   * Setup correspondance "labels to id"
   * @param [] $labels - map string -> int (label of state variable -> numeric id in the database)
   */
  public function initGameStateLabels($labels) {
  }

  public function setGameStateInitialValue($value_label, $value_value) {
  }

  /**
   * @return int|string
   */
  public function getGameStateValue(string $value_label) {
    return 0;
  }

  public function setGameStateValue($value_label, $value_value) {
  }

  public function incGameStateValue($value_label, $increment) {
    return 0;
  }

  /**
   *   Make the next player active (in natural order)
   */
  protected function activeNextPlayer() {
  }

  /**
   *   Make the previous player active  (in natural order)
   */
  protected function activePrevPlayer() {
  }

  /**
   * Check if action is valid regarding current game state (exception if fails)
   if "bThrowException" is set to "false", the function return false in case of failure instead of throwing and exception
   * @param string $actionName
   * @param boolean $bThrowException
   */
  public function checkAction($actionName, $bThrowException = true) {
  }

  public function getNextPlayerTable() {
    return 0;
  }

  public function getPrevPlayerTable() {
    return 0;
  }

  public function getPlayerAfter($player_id) {
    return 0;
  }

  public function getPlayerBefore($player_id) {
    return 0;
  }

  public function createNextPlayerTable($players, $bLoop = true) {
    return [];
  }

  public function createPrevPlayerTable($players, $bLoop = true) {
    return [];
  }

  public function notifyAllPlayers($type, $message, $args) {
    $args2 = [];
    foreach ($args as $key => $val) {
      $key = '${' . $key . '}';
      $args2 [$key] = $val;
    }
    echo "$type: $message\n";
    //. strtr($message,                $args2)
    echo "\n";
  }

  public function notifyPlayer($player_id, $notification_type, $notification_log, $notification_args) {
  }

  public function getStatTypes() {
    return [];
  }

  public function initStat($table_or_player, $name, $value, $player_id = null) {
  }

  public function setStat($value, $name, $player_id = null, $bDoNotLoop = false) {
    echo "stat: $name=$value\n";
  }

  public function incStat($delta, $name, $player_id = null) {
  }

  public function getStat($name, $player_id = null) {
    return 0;
  }

  public function _($s) {
    return $s;
  }

  public function getPlayersNumber() {
    return 2;
  }

  /**
   * @param int|string $id
   * @return int|string
   */
  public function getPlayerNoById($id) {
    return '0';
  }

  public function reattributeColorsBasedOnPreferences($players, $colors) {
  }

  public function reloadPlayersBasicInfos() {
  }

  public function getNew($deck_definition) {
  }

  // Give standard extra time to this player
  // (standard extra time is a game option)
  public function giveExtraTime($player_id, $specific_time=null) {
  }

  public function getStandardGameResultObject() {
    return [];
  }

  public function applyDbChangeToAllDB($sql) {
  }

  /**
   *
   * @deprecated
   */
  public function applyDbUpgradeToAllDB($sql) {
  }


  public function getGameinfos() {
    unset($gameinfos);
    require('gameinfos.inc.php');
    if (isset($gameinfos)) {
      return $gameinfos;
    }
    throw new feException("gameinfos.inp.php suppose to define \$gameinfos variable");
  }

  /* Method to override to set up each game */
  abstract protected function setupNewGame(array $players, array $options = []);

  public function stMakeEveryoneActive() {
    $this->gamestate->setAllPlayersMultiactive();
  }
}

class Page {
  public $blocks = [];

  public function begin_block($template, $block) {
    $this->blocks [$block] = [];
  }

  public function insert_block($block, $args) {
    $this->blocks [$block] [] = $args;
  }
}

class GUser {
  public function get_id() {
    return 1;
  }
}

class game_view {
}

class APP_GameAction {
}

function totranslate($text) {
  return $text;
}

function clienttranslate($x) {
  return $x;
}

function mysql_fetch_assoc($res) {
  return [];
}

function bga_rand($min, $max) {
  return 0;
}

function getKeysWithMaximum($array, $bWithMaximum=true) {
  return [];
}

function getKeyWithMaximum($array) {
  return '';
}
