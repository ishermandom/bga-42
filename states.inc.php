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

// TODO(isherman): Update this stuff maybe?
/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
     the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
     action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
     method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
     transitions in order to use transition names in "nextState" PHP method, and use IDs to
     specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
     client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
     method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


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
