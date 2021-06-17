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

$machinestates = [
  // Game setup. Must match BoardGameArea expectations.
  1 => [
    "name" => "gameSetup",
    "description" => "",
    "type" => "manager",
    "action" => "stGameSetup",
    "transitions" => [ "" => 20 ]
  ],

  /// New hand
  20 => [
    "name" => "newHand",
    // TODO(isherman): Can we remove empty descriptions if they're not used for anything?
    // (jturner) description is MANDATORY
    "description" => "",
    "type" => "game",
    "action" => "stNewHand",
    "updateGameProgression" => true,
    // TODO(isherman): Change to 21 once actually working on 42.
    "transitions" => [ "" => 21 ]
  ],

  /// Bidding
  // TODO(isherman): Finish updating these.
  21 => [
    "name" => "playerBid",
    "description" => clienttranslate('${actplayer} must bid or pass'),
    "descriptionmyturn" => clienttranslate('${you} must bid or pass'),
    "type" => "activeplayer",
    // TODO(isherman): Dunno whether these make sense...
    "possibleactions" => [ "bid", "pass" ],
    "transitions" => [ "nextPlayerBid" => 22 ],
    "args" => "argCurrentBid"
  ],
  22 => [
    "name" => "nextPlayerBid",
    "description" => "",
    "type" => "game",
    "action" => "stNextPlayerBid",
    "transitions" => [ "playerBid" => 21, "chooseBidSuit" => 23 ]
  ],
  23 => [
    "name" => "chooseBidSuit",
    "description" => clienttranslate('${actplayer} must choose trump suit'),
    "descriptionmyturn" => clienttranslate('${you} must choose trump suit'),
    "type" => "activeplayer",
    "possibleactions" => [ "chooseBidSuit" ],
    "transitions" => ["startTrick" => 30 ]
  ],

  /// Trick
  30 => [
    "name" => "newTrick",
    "description" => "",
    "type" => "game",
    "action" => "stNewTrick",
    // TODO(isherman): Do we want this, yah?
    // (jturner) If I understand this correctly, this is mainly related to the %done messages. If so
    //  we should probably just not worry too much about it for now.
    "updateGameProgression" => true,
    "transitions" => [ "" => 31 ]
  ],
  31 => [
    "name" => "playerTurn",
    "description" => clienttranslate('${actplayer} must play a domino'),
    "descriptionmyturn" => clienttranslate('${you} must play a domino'),
    "type" => "activeplayer",
    "possibleactions" => [ "playCard" ],
    "transitions" => [ "playCard" => 32 ]
  ],
  32 => [
    "name" => "nextPlayer",
    "description" => "",
    "type" => "game",
    "action" => "stNextPlayer",
    "transitions" => [ "nextPlayer" => 31, "nextTrick" => 30, "endHand" => 40 ]
  ],


  // End of the hand (scoring, etc...)
  40 => [
    "name" => "endHand",
    "description" => "",
    "type" => "game",
    "action" => "stEndHand",
    "transitions" => [ "nextHand" => 20, "endGame" => 99 ]
  ],

  // Final state.
  // Please do not modify.
  99 => [
    "name" => "gameEnd",
    "description" => clienttranslate("End of game"),
    "type" => "manager",
    "action" => "stGameEnd",
    "args" => "argGameEnd"
  ]

];
