<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Texas 42 implementation : © Stardust Spikes <sdspikes@cs.stanford.edu> & Jason Turner-Maier <jasonptm@gmail.com> & Ilya Sherman <ishermandom@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * List of possible Texas 42 game states.
 *
 */

// Docs: http://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php

//    !! It is not a good idea to modify this file when a game is running !!
// (jturner) LOL

$machinestates = array(
  // Game setup. Must match BoardGameArea expectations.
  1 => array(
    "name" => "gameSetup",
    "description" => clienttranslate("Game setup"),
    "type" => "manager",
    "action" => "stGameSetup",
    "transitions" => array( "" => 20 )
  ),

  /// New hand
  20 => array(
    "name" => "newHand",
    // TODO(isherman): Can we remove empty descriptions if they're not used for anything?
    "description" => "",
    "type" => "game",
    "action" => "stNewHand",
    "updateGameProgression" => true,
    // TODO(isherman): Change to 21 once actually working on 42.
    "transitions" => array( "" => 30 )
  ),
  // TODO(isherman): Finish updating these.
  21 => array(
    "name" => "playerBid",
    "description" => clienttranslate("${actplayer} must bid or pass"),
    "myturndescription" => clienttranslate("${you} must bid or pass"),
    "type" => "activeplayer",
    // TODO(isherman): Dunno whether these make sense...
    "possibleactions" => array( "bid", "pass" ),
    "transitions" => array( "" => 22 )
  ),
  22 => array(
    "name" => "nextPlayerBid",
    "description" => "",
    "type" => "game",
    "action" => "stNextPlayerBid",
    "transitions" => array( "nextPlayer" => 21, "startTrick" => 30 )
  ),

  23 => array(
    "name" => "lastPlayerBid",
    "description" => "",
    "type" => "game",
    "action" => "stNextPlayerBid",
    "transitions" => array( "nextPlayer" => 21, "startTrick" => 30 )
  ),
  // Trick

  30 => array(
    "name" => "newTrick",
    "description" => "",
    "type" => "game",
    "action" => "stNewTrick",
    // TODO(isherman): Do we want this, yah?
    "updateGameProgression" => true,
    "transitions" => array( "" => 31 )
  ),
  31 => array(
    "name" => "playerTurn",
    "description" => clienttranslate('${actplayer} must play a card'),
    "descriptionmyturn" => clienttranslate('${you} must play a card'),
    "type" => "activeplayer",
    "possibleactions" => array( "playCard" ),
    "transitions" => array( "playCard" => 32 )
  ),
  32 => array(
    "name" => "nextPlayer",
    "description" => "",
    "type" => "game",
    "action" => "stNextPlayer",
    "transitions" => array( "nextPlayer" => 31, "nextTrick" => 30, "endHand" => 40 )
  ),


  // End of the hand (scoring, etc...)
  40 => array(
    "name" => "endHand",
    "description" => "",
    "type" => "game",
    "action" => "stEndHand",
    "transitions" => array( "nextHand" => 20, "endGame" => 99 )
  ),

  // Final state.
  // Please do not modify.
  99 => array(
    "name" => "gameEnd",
    "description" => clienttranslate("End of game"),
    "type" => "manager",
    "action" => "stGameEnd",
    "args" => "argGameEnd"
  )

);
