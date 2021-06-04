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

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

class TexasFortyTwo extends Table {
	function __construct() {
		parent::__construct();

		$this->dominoes = self::getNew("module.common.deck");
		$this->dominoes->init("dominoes");

    // Global variables used in the game. Must have IDs between 10 and 99.
    // Game variants must be specified here as well, with ID set to match the
		// corresponding ID in gameoptions.inc.php.
    // Note: These variables can be accessed via
		// getGameStateValue/setGameStateInitialValue/setGameStateValue
    self::initGameStateLabels(array(
			// TODO(isherman): Set some state here, e.g.:
      // "winningBid" => 10,
      // "trumpSuit" => 11,
			// "trickSuit" => 12,
      // "my_first_game_variant" => 100,
    ));
	}

  protected function getGameName() {
		// Used for translations and stuff. Please do not modify.
    return "texasfortytwo";
  }

  // Called once, when a new game is launched. Initializes game state.
  protected function setupNewGame($players, $options = array()) {
    self::initializePlayers($players);

		// Initialize game state.
		// TODO(isherman): Call self::setGameStateInitialValue for any relevant
		// game state variables here.

		// Initialize game statistics. Must match the list of stats defined in
		// stats.inc.php.
		// TODO(isherman): Call self::initStat for all defined statistics.

    self::initializeDeck();


    // Begin the game by activating the first player.
	  $this->activeNextPlayer();
	}

  // Inserts a set of fields into the database named `$db_name`;
	// `$fields`: an array of field names, e.g. "player_id".
	// `$rows`: an array of arrays, where each inner array defines the values for
	//     one row, specified in the same order as
	//     `$field_names`.
	private static function insertIntoDatabase($db_name, $fields, $rows) {
		$to_sql_row = function($row) {
			return "('".implode($row, "','")."')";
		};
		$fields = implode($fields, ",");
    $values = implode(array_map($to_sql_row, $rows), ",");
		self::DbQuery("INSERT INTO $db_name ($fields) VALUES $values");
	}

  // Initializes the player database for the game. Called once, when a new game
	// is launched.
	private function initializePlayers($players) {
		// Default colors to use for the players: red, green, blue, orange.
    $default_colors = array("ff0000", "008000", "0000ff", "ffa500");

    $fields = [
			"player_id",
			"player_color",
			"player_canal",
			"player_name",
			"player_avatar",
		];
    $rows = array();
    foreach ($players as $player_id => $player) {
      $color = array_shift($default_colors);
      $rows[] = [
				$player_id,
				$color,
				$player['player_canal'],
			  addslashes($player['player_name']),
				addslashes($player['player_avatar']),
			];
		}
		self::insertIntoDatabase('player', $fields, $rows);

		// Allow all possible player color preferences. The list of options is
		// defined at
		// https://en.doc.boardgamearena.com/Main_game_logic:_yourgamename.game.php#Player_color_preferences
    self::reattributeColorsBasedOnPreferences($players, array(
			"ff0000",  // red
			"008000",  // green
			"0000ff",  // blue
			"ffa500",  // yellow
			"000000",  // black
			"ffffff",  // white
			"e94190",  // pink
			"982fff",  // purple
			"72c3b1",  // cyan
			"f07f16",  // orange
			"bdd002",  // khaki green
			"7b7b7b",  // gray
		));
    self::reloadPlayersBasicInfos();
	}

	// Initializes the domino deck for the game. Called once, when a new game is
	// launched.
	private function initializeDeck() {
		$fields = [
			"high",
			"low",
			"card_location",
			"card_location_arg",
			"card_type",
			"card_type_arg",
		];

		$NUM_SUITS = 7;
		$rows = array();
		for ($high = 0; $high < $NUM_SUITS; ++$high) {
			for ($low = 0; $low <= $high; ++$low) {
				$rows[] = [$high, $low, 'deck', 0, '', 0];
			}
		}
		self::insertIntoDatabase('dominoes', $fields, $rows);

    // TODO(isherman): This is... probably not needed, yah?
				// // Shuffle and deal dominoes.
				// $hand_size = 7; // count($deck) / count($players);
				// $this->dominoes->shuffle('deck');
				// $players = self::loadPlayersBasicInfos();
				// foreach ($players as $player_id => $player) {
				// 	$this->dominoes->pickCards($hand_size, 'deck', $player_id);
				// }
  }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // Cards in player hand
        $result['hand'] = $this->dominoes->getCardsInLocation( 'hand', $current_player_id );
				$result['dominohand'] = $this->dominoes->getCardsInLocation( 'hand', $current_player_id );
				$result['alldominoes'] = self::getCollectionFromDb(
					"SELECT card_id id, high, low FROM dominoes"
				);
				$get_id = function($domino) {
						return $domino['id'];
				};
				$ids = array_map($get_id, $result['dominohand']);
				$ids_list = join(',', $ids);
				$result['dominoesinhandtho'] = self::getCollectionFromDb(
					"SELECT card_id id, high, low FROM dominoes WHERE card_id IN ($ids_list)");


        // Cards played on the table
        $result['cardsontable'] = $this->dominoes->getCardsInLocation( 'cardsontable' );

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
    function getGameProgression()
    {
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
    function playCard($card_id) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();
        $this->dominoes->moveCard($card_id, 'cardsontable', $player_id);
				$currentCard = self::getCollectionFromDb(
					"SELECT card_id id, high, low FROM dominoes WHERE card_id=$card_id")[$card_id];
				self::debug("currentCard [%d, %d, %d]\n", $currentCard['id'], $currentCard['low'], $currentCard['high']);
				print_r($currentCard);

        // XXX check rules here
        // Set the trick color if it hasn't been set yet
        //$currentTrickColor = self::getGameStateValue( 'trickColor' ) ;
        //if( $currentTrickColor == 0 )
						// TODO(sdspikes): if it's trump, use trump
            //self::setGameStateValue( 'trickColor', $currentCard['high'] );
        // And notify
        self::notifyAllPlayers('playCard',
								clienttranslate('${player_name} plays the ${high} : ${low}'), array (
                // 'i18n' => array ('color_displayed','value_displayed' ),
								'card_id' => $card_id,
								'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
								'high' => $currentCard['high'],
								'low' => $currentCard['low']));
                // 'value_displayed' => $this->values_label [$currentCard ['type_arg']],'color' => $currentCard ['type'],
                // 'color_displayed' => $this->colors [$currentCard ['type']] ['name'] ));
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
    function argGiveCards() {
        return array ();
    }

        //////////////////////////////////////////////////////////////////////////////
        //////////// Game state actions
        ////////////
        /*
     * Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
     * The action method of state X is called everytime the current game state is set to X.
     */
    function stNewHand() {
        // Take back all cards (from any location => null) to deck
        $this->dominoes->moveAllCardsInLocation(null, "deck");
        $this->dominoes->shuffle('deck');
        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $players = self::loadPlayersBasicInfos();
				$hand_size = 7; // count($deck) / count($players);
        foreach ( $players as $player_id => $player ) {
            $cards = $this->dominoes->pickCards($hand_size, 'deck', $player_id);
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newHand', '', array ('cards' => $cards ));
        }
        //self::setGameStateValue('alreadyPlayedHearts', 0);
        $this->gamestate->nextState("");
    }

    function stNewTrick() {
        // New trick: active the player who wins the last trick, or the player who own the club-2 card
        // Reset trick color to 0 (= no color)
        //self::setGameStateInitialValue('trickColor', 0);
        $this->gamestate->nextState();
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->dominoes->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            $cards_on_table = $this->dominoes->getCardsInLocation('cardsontable');
            $best_value = 0;
            $best_value_player_id = null;
            //$currentTrickColor = self::getGameStateValue('trickColor');
            foreach ( $cards_on_table as $card ) {
                // Note: type = card color
                // if ($card ['type'] == $currentTrickColor) {
                //     if ($best_value_player_id === null || $card ['type_arg'] > $best_value) {
                //         $best_value_player_id = $card ['location_arg']; // Note: location_arg = player who played this card on table
                //         $best_value = $card ['type_arg']; // Note: type_arg = value of the card
                //     }
                // }
            }

            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer( $best_value_player_id );

            // Move all cards to "cardswon" of the given player
            $this->dominoes->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

            // Notify
            // Note: we use 2 notifications here in order we can pause the display during the first notification
            //  before we move all cards to the winner (during the second)
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers( 'trickWin', clienttranslate('${player_name} wins the trick'), array(
                    'player_id' => $best_value_player_id,
                    'player_name' => $players[ $best_value_player_id ]['player_name']
            ) );
            self::notifyAllPlayers( 'giveAllCardsToPlayer','', array(
                    'player_id' => $best_value_player_id
            ) );

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

    function stEndHand() {
            // Count and score points, then end the game or go to the next hand.
        $players = self::loadPlayersBasicInfos();
        // Gets all "hearts" + queen of spades

        $player_to_points = array ();
        foreach ( $players as $player_id => $player ) {
            $player_to_points [$player_id] = 0;
        }
        $cards = $this->dominoes->getCardsInLocation("cardswon");
        foreach ( $cards as $card ) {
            $player_id = $card ['location_arg'];
            // Note: 2 = heart
            if ($card ['type'] == 2) {
                $player_to_points [$player_id] ++;
            }
        }
        // Apply scores to player
        foreach ( $player_to_points as $player_id => $points ) {
            if ($points != 0) {
                $sql = "UPDATE player SET player_score=player_score-$points  WHERE player_id='$player_id'";
                self::DbQuery($sql);
                $heart_number = $player_to_points [$player_id];
                self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${nbr} hearts and looses ${nbr} points'), array (
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'],
                        'nbr' => $heart_number ));
            } else {
                // No point lost (just notify)
                self::notifyAllPlayers("points", clienttranslate('${player_name} did not get any hearts'), array (
                        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'] ));
            }
        }
        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', array( 'newScores' => $newScores ) );

        ///// Test if this is the end of the game
        foreach ( $newScores as $player_id => $score ) {
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

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
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
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
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

    function upgradeTableDb( $from_version )
    {
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
