<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> &
 *                  Emmanuel Colin <ecolin@boardgamearena.com>
 * Texas 42 implementation: © Stardust Spikes <sdspikes@cs.stanford.edu> &
 *                            Jason Turner-Maier <jasonptm@gmail.com> &
 *                            Ilya Sherman <ishermandom@gmail.com>
 * This code has been produced on the BGA studio platform for use on
 * http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio
 * for more information.
 * -----
 *
 * The main file defining the game rules and logic.
 */

require_once(APP_GAMEMODULE_PATH.'module/table/table.game.php');

abstract class BidType {
  const TRUMP = 0;
  const NELLO = 1;
  const SEVENS = 2;
  const SPLASH = 3;
  const PLUNGE = 4;
}

abstract class BidSuit {
  const BLANKS = 0;
  const ONES = 1;
  const TWOS = 2;
  const THREES = 3;
  const FOURS = 4;
  const FIVES = 5;
  const SIXES = 6;
  const DOUBLES = 7;
  const NO_TRUMP = 8;
  // TODO(isherman): Should these be moved to a separate enum?
  const NELLO_DOUBLES_LOW = 9;
  const NELLO_DOUBLES_HIGH = 10;
  const NELLO_DOUBLES_SUIT_OF_THEIR_OWN = 11;
}

class TexasFortyTwo extends Table {
  // All possible colors that players might have set as their preferred color.
  // The list of options is defined at
  // https://en.doc.boardgamearena.com/Main_game_logic:_yourgamename.game.php#Player_color_preferences
  // Note that the first four are also used as the default player colors.
  // TODO(isherman): This feels like a real hack. A list of colors is also
  // defined in gameinfos.inc.php, and we can probably look at the list of
  // colors that players prefer via the result of `loadPlayersBasicInfos()``.
  private const POSSIBLE_PLAYER_COLORS = [
        'ff0000',  // red
        '008000',  // green
        '0000ff',  // blue
        'ffa500',  // yellow
        '000000',  // black
        'ffffff',  // white
        'e94190',  // pink
        '982fff',  // purple
        '72c3b1',  // cyan
        'f07f16',  // orange
        'bdd002',  // khaki green
        '7b7b7b',  // gray
    ];

  // The number of suits: blanks through sixes.
  // HACK: It can be useful to set this to 3 for debugging.
  private const NUM_SUITS = 7;

  private const NUM_PLAYERS = 4;

  public function __construct() {
    parent::__construct();

    $this->dominoes = self::getNew('module.common.deck');
    $this->dominoes->init('dominoes');

    // Global variables used in the game. Must have IDs between 10 and 99.
    // Game variants must be specified here as well, with ID set to match the
    // corresponding ID in gameoptions.inc.php.
    // Note: These variables can be accessed via
    // getGameStateValue/setGameStateInitialValue/setGameStateValue
    self::initGameStateLabels([
      // The player who has bid highest so far.
      'highestBidder' => 10,
      // The winning player bid (or current highest player bid) – e.g. 30, or
      // 84 for two marks.
      'bidValue' => 11,
      // Of type `BidType`, e.g. nello.
      'bidType' => 12,
      // The trump suit for the bid, e.g. sixes are trump.
      'bidSuit' => 13,
      // The suit of the current trick, e.g. Jane led a four.
      'trickSuit' => 14,
      // 'my_first_game_variant' => 100,
    ]);
  }

  protected function getGameName() {
    // Used for translations and stuff. Please do not modify.
    return 'texasfortytwo';
  }

  // Called once, when a new game is launched. Initializes game state.
  protected function setupNewGame($players, $options = []) {
    self::initializePlayers($players);

    // Initialize game state.
    // TODO(isherman): Call self::setGameStateInitialValue for any relevant
    // game state variables here.

    // Initialize game statistics. Must match the list of stats defined in
    // stats.inc.php.
    // TODO(isherman): Call self::initStat for all defined statistics.

    self::initializeDeck();
    self::setGameStateInitialValue('highestBidder', null);
    self::setGameStateInitialValue('bidValue', null);
    self::setGameStateInitialValue('bidType', null);
    self::setGameStateInitialValue('bidSuit', null);
    self::setGameStateInitialValue('trickSuit', null);

    // Begin the game by activating the first player.
    $this->activeNextPlayer();
  }

  // Returns whether the given player id is the dealer for this hand.
  private function isDealer($player_id) {
    $first_player_seat = self::getUniqueValueFromDB(
      'SELECT player_no seat FROM player WHERE is_first_player = true'
    );
    $dealer_seat =
        ($first_player_seat + self::NUM_PLAYERS - 1) % self::NUM_PLAYERS;
    $dealer_id = self::getUniqueValueFromDB(
      "SELECT player_id id FROM player WHERE player_no = $dealer_seat"
    );
    return $player_id == $dealer_id;
  }

  // Inserts a set of fields into the database named `$db_name`;
  // `$fields`: an array of field names, e.g. 'player_id'.
  // `$rows`: an array of arrays, where each inner array defines the values for
  //     one row, specified in the same order as
  //     `$field_names`.
  private static function insertIntoDatabase($db_name, $fields, $rows) {
    $to_sql_row = function ($row) {
      return "('".join("','", $row)."')";
    };
    $fields = join(',', $fields);
    $values = join(',', array_map($to_sql_row, $rows));
    self::DbQuery("INSERT INTO $db_name ($fields) VALUES $values");
  }

  // Initializes the player database for the game. Called once, when a new game
  // is launched.
  private function initializePlayers($players) {
    $fields = [
        'player_id',
        'player_color',
        'player_canal',
        'player_name',
        'player_avatar',
        // HACK: During development, it's useful to have a fixed order.
        'player_no',
    ];
    $default_colors =
            array_slice(self::POSSIBLE_PLAYER_COLORS, 0, count($players));
    $rows = [];
    $player_order_index_hack = 0;
    foreach ($players as $player_id => $player) {
      $color = array_shift($default_colors);
      $rows[] = [
          $player_id,
          $color,
          $player['player_canal'],
          addslashes($player['player_name']),
          addslashes($player['player_avatar']),
          $player_order_index_hack,
      ];
      $player_order_index_hack++;
    }
    self::insertIntoDatabase('player', $fields, $rows);

    // Allow all possible player color preferences.
    self::reattributeColorsBasedOnPreferences(
      $players,
      self::POSSIBLE_PLAYER_COLORS
    );
    self::reloadPlayersBasicInfos();
  }

  // Initializes the domino deck for the game. Called once, when a new game is
  // launched. Analogous to `Deck::createCards`, but with additional logic to
  // initialize custom database fields correctly.
  private function initializeDeck() {
    $fields = [
            'high',
            'low',
            'card_location',
            'card_location_arg',
            'card_type',
            'card_type_arg',
        ];

    $rows = [];
    for ($high = 0; $high < self::NUM_SUITS; ++$high) {
      for ($low = 0; $low <= $high; ++$low) {
        // Note that the final three field values are not meaningful, and
        // therefore just set to some default values to appease the database
        // schema.
        $rows[] = [$high, $low, 'deck', 0, '', 0];
      }
    }
    // HACK: As a debugging aid, it can be useful to set a low value for
    // `self::NUM_SUITS`. Support that by ensuring that the number of dominoes
    // is always divisible evenly by the number of players.
    $rows = array_slice($rows, 0, count($rows) - (count($rows) % 4));
    self::insertIntoDatabase('dominoes', $fields, $rows);
  }

  // Returns the dominoes in a location. Analogue to `Deck::getCardsInLocation`.
  private function getDominoesInLocation($location, $location_arg = null) {
    $fields = 'card_id id, high, low, card_location_arg';
    $where = "card_location='$location'";
    if (!is_null($location_arg)) {
      $where .= "AND card_location_arg=$location_arg";
    }
    $dominoes = self::getObjectListFromDB(
      "SELECT $fields FROM dominoes WHERE $where"
    );

    $fix_data_types = function ($domino) {
      $fixed = [];
      foreach ($domino as $field => $value) {
        // All the queried fields are ints!
        $fixed[$field] = intval($value);
      }
      return $fixed;
    };
    return array_map($fix_data_types, $dominoes);
  }

  // Returns all game state visible to the current player.
  // Called each time the game interface is displayed to a player, ie:
  //   * when the game starts
  //   * when a player refreshes the game page (F5)
  protected function getAllDatas() {
    // Important: Must only return game state visible to this player!
    $current_player_id = self::getCurrentPlayerId();
    $result = [];

    // TODO(isherman): Do we need to return player scores at all? I doubt our
    // custom game logic cares about it, and I bet this is returned separately
    // for the pre-canned BGA UI surfaces.
    // Publicly visible state about the players.
    $result['players'] = self::getCollectionFromDb(
      'SELECT player_id id, player_score score FROM player'
    );

    // Dominoes in the current player's hand.
    $result['hand'] = $this->getDominoesInLocation('hand', $current_player_id);
    // Dominoes in play on the table.
    $result['table'] = $this->getDominoesInLocation('table');
    return $result;
  }

  /*
      getGameProgression:

      Compute and return the current game progression.
      The number returned must be an integer beween 0 (=the game just started) and
      100 (= the game is finished or almost finished).

      This method is called each time we are in a game state with the "updateGameProgression" property set to true
      (see states.inc.php)
  */
  public function getGameProgression() {
    // TODO: compute and return the game progression

    return 0;
  }

  //////////////////////////////////////////////////////////////////////////////
  //////////// Utility functions
  ////////////
  /*
     * In this space, you can put any utility methods useful for your game logic
     */
  //////////////////////////////////////////////////////////////////////////////
  //////////// Player actions
  ////////////
  /*
     * Each time a player is doing some game action, one of the methods below is called.
     * (note: each method below must match an input method in template.action.php)
     */
  public function pass() {
    self::checkAction("pass");
    // TODO(isherman): Dealer shouldn't be allowed to pass.
    $player_id = self::getActivePlayerId();
    $current_bid_value = self::getGameStateValue('bidValue') ;
    if (!($this->isDealer($player_id) && is_null($current_bid_value))) {
      $this->gamestate->nextState('nextPlayerBid');
    }
  }

  public function gamestatehack() {
    self::debug($this->gamestate->state());
  }

  public function bid($bid_value) {
    self::checkAction("bid");
    $player_id = self::getActivePlayerId();
    // only allow bids higher than current bid if it exists
    $current_bid_value = self::getGameStateValue('bidValue') ;
    if ((is_null($bid_value) && $bid_value > 30) || $bid_value > $current_bid_value) {
      self::setGameStateValue('bidValue', $bid_value);
      self::setGameStateValue('highestBidder', $player_id);

      $this->gamestate->nextState('nextPlayerBid');
    } else {
      self::debug("Bad bid!");
      // TODO(sdspikes): throw error?
    }
  }


  public function chooseBidSuit($bid_suit) {
    self::checkAction("chooseBidSuit");
    self::setGameStateValue('bidSuit', $bid_suit);
    $this->gamestate->nextState();
  }

  /*
     * Each time a player is doing some game action, one of the methods below is called.
     * (note: each method below must match an input method in template.action.php)
     */
  public function playCard($card_id) {
    self::checkAction("playCard");
    $player_id = self::getActivePlayerId();
    $this->dominoes->moveCard($card_id, 'table', $player_id);
    $current_card = self::getCollectionFromDb(
      "SELECT card_id id, high, low FROM dominoes WHERE card_id=$card_id"
    )[$card_id];
    self::debug("current_card [%d, %d, %d]\n", $current_card['id'], $current_card['low'], $current_card['high']);
    //print_r($current_card);

    // XXX check rules here
    // Set the trick color if it hasn't been set yet
    //$currentTrickColor = self::getGameStateValue( 'trickColor' ) ;
    //if( $currentTrickColor == 0 )
    // TODO(sdspikes): if it's trump, use trump
    //self::setGameStateValue( 'trickColor', $current_card['high'] );
    // And notify
    self::notifyAllPlayers(
      'playCard',
      clienttranslate('${player_name} plays the ${high} : ${low}'),
      [
        // 'i18n' => array ('color_displayed','value_displayed' ),
        'card_id' => $card_id,
        'player_id' => $player_id,
        'player_name' => self::getActivePlayerName(),
        'high' => $current_card['high'],
        'low' => $current_card['low']
      ]
    );
    // 'value_displayed' => $this->values_label [$current_card ['type_arg']],'color' => $current_card ['type'],
    // 'color_displayed' => $this->colors [$current_card ['type']] ['name'] ));
    // Next player
    $this->gamestate->nextState('playCard');
  }

  //////////////////////////////////////////////////////////////////////////////
  //////////// Game state arguments
  ////////////
  /*
     * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
     * These methods function is to return some additional information that is specific to the current
     * game state.
     */
  public function argGiveCards() {
    return [];
  }

  public function argCurrentBid() {
    return ['bidValue' => self::getGameStateValue('bidValue')];
  }

  //////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Game state actions /////////////////////////////
  //////////////////////////////////////////////////////////////////////////////
  // Each `action` property in states.inc.php corresponds to a method defined //
  // here. The action method of state X is called everytime the current game  //
  // state is set to X.                                                       //
  //////////////////////////////////////////////////////////////////////////////

  // Washes (shuffles) the dominoes and deals new hands to each player.
  public function stNewHand() {
    // Wash the dominoes.
    // Note: Moving cards from location `null` means from any/all locations.
    $this->dominoes->moveAllCardsInLocation(null, 'deck');
    $this->dominoes->shuffle('deck');

    // Deal a new hand to each player.
    $players = self::loadPlayersBasicInfos();
    $hand_size = self::NUM_SUITS * (self::NUM_SUITS + 1) / 2 / count($players);
    // HACK: As a debugging aid, it can be useful to set a low value for
    // `self::NUM_SUITS`. Support that by ensuring that the number of dominoes
    // is always divisible evenly by the number of players.
    $hand_size = intval($hand_size);
    foreach ($players as $player_id => $player) {
      $this->dominoes->pickCards($hand_size, 'deck', $player_id);
      $dominoes = $this->getDominoesInLocation('hand', $player_id);
      self::notifyPlayer($player_id, 'newHand', '', ['hand' => $dominoes]);
    }
    $this->gamestate->nextState("");
  }

  public function stNextPlayerBid() {
    $player_id = self::getActivePlayerId();
    if ($this->isDealer($player_id)) {
      $highest_bidder = self::getGameStateValue('highestBidder');
      $this->gamestate->changeActivePlayer($highest_bidder);
      $this->gamestate->nextState('chooseBidSuit');
    } else {
      self::activeNextPlayer();
      $this->gamestate->nextState('playerBid');
    }
  }

  public function stNewTrick() {
    // New trick: active the player who wins the last trick, or the player who own the club-2 card
    // Reset trick color to 0 (= no color)
    //self::setGameStateInitialValue('trickColor', 0);
    $this->gamestate->nextState();
  }

  public function stNextPlayer() {
    // Active next player OR end the trick and go to the next trick OR end the hand
    if ($this->dominoes->countCardInLocation('table') == 4) {
      // This is the end of the trick
      $cards_on_table = $this->dominoes->getCardsInLocation('table');
      $best_value = 0;
      $best_value_player_id = null;
      //$currentTrickColor = self::getGameStateValue('trickColor');
      foreach ($cards_on_table as $card) {
        // Note: type = card color
                // if ($card ['type'] == $currentTrickColor) {
                //     if ($best_value_player_id === null || $card ['type_arg'] > $best_value) {
                //         $best_value_player_id = $card ['location_arg']; // Note: location_arg = player who played this card on table
                //         $best_value = $card ['type_arg']; // Note: type_arg = value of the card
                //     }
                // }
      }

      // Active this player => he's the one who starts the next trick
      //$this->gamestate->changeActivePlayer( $best_value_player_id );
      // TODO(isherman): Temporary hack while we don't have rules implemented :)
      $best_value_player_id = self::activeNextPlayer();

      // Move all cards to "cardswon" of the given player
      $this->dominoes->moveAllCardsInLocation('table', 'cardswon', null, $best_value_player_id);

      // Notify
      // Note: we use 2 notifications here in order we can pause the display during the first notification
      //  before we move all cards to the winner (during the second)
      $players = self::loadPlayersBasicInfos();
      self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), [
                    'player_id' => $best_value_player_id,
                    'player_name' => $players[ $best_value_player_id ]['player_name']
            ]);
      self::notifyAllPlayers('giveAllCardsToPlayer', '', [
                    'player_id' => $best_value_player_id
            ]);

      if ($this->dominoes->countCardInLocation('hand') == 0) {
        // End of the hand
        $this->gamestate->nextState("endHand");
      } else {
        // End of the trick
        $this->gamestate->nextState("nextTrick");
      }
    } else {
      // Standard case (not the end of the trick)
      // => just active the next player
      $player_id = self::activeNextPlayer();
      self::giveExtraTime($player_id);
      $this->gamestate->nextState('nextPlayer');
    }
  }

  public function stEndHand() {
    // Count and score points, then end the game or go to the next hand.
    $players = self::loadPlayersBasicInfos();
    // Gets all "hearts" + queen of spades

    $player_to_points = [];
    foreach ($players as $player_id => $player) {
      $player_to_points [$player_id] = 0;
    }
    $cards = $this->dominoes->getCardsInLocation("cardswon");
    foreach ($cards as $card) {
      $player_id = $card ['location_arg'];
      // Note: 2 = heart
      if ($card ['type'] == 2) {
        $player_to_points [$player_id] ++;
      }
    }
    // Apply scores to player
    foreach ($player_to_points as $player_id => $points) {
      if ($points != 0) {
        $sql = "UPDATE player SET player_score=player_score-$points  WHERE player_id='$player_id'";
        self::DbQuery($sql);
        $heart_number = $player_to_points [$player_id];
        self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${nbr} hearts and looses ${nbr} points'), [
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'],
                        'nbr' => $heart_number ]);
      } else {
        // No point lost (just notify)
        self::notifyAllPlayers("points", clienttranslate('${player_name} did not get any hearts'), [
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'] ]);
      }
    }
    $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
    self::notifyAllPlayers("newScores", '', [ 'newScores' => $newScores ]);

    ///// Test if this is the end of the game
    foreach ($newScores as $player_id => $score) {
      if ($score <= -100) {
        // Trigger the end of the game !
        $this->gamestate->nextState("endGame");
        return;
      }
    }


    $this->gamestate->nextState("nextHand");
  }


  //////////////////////////////////////////////////////////////////////////////
  //////////// Zombie
  ////////////

  /*
      zombieTurn:

      This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
      You can do whatever you want in order to make sure the turn of this player ends appropriately
      (ex: pass).
  */

  public function zombieTurn($state, $active_player) {
    $statename = $state['name'];

    if ($state['type'] == "activeplayer") {
      switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

      return;
    }

    if ($state['type'] == "multipleactiveplayer") {
      // Make sure player is in a non blocking status for role turn
      $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
      self::DbQuery($sql);

      $this->gamestate->updateMultiactiveOrNextState('');
      return;
    }

    throw new feException("Zombie mode not supported at this game state: ".$statename);
  }

  ///////////////////////////////////////////////////////////////////////////////////:
  ////////// DB upgrade
  //////////

  /*
      upgradeTableDb:

      You don't have to care about this until your game has been published on BGA.
      Once your game is on BGA, this method is called everytime the system detects a game running with your old
      Database scheme.
      In this case, if you change your Database scheme, you just have to apply the needed changes in order to
      update the game database and allow the game to continue to run with your new version.

  */

  public function upgradeTableDb($from_version) {
    // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//
  }
}
