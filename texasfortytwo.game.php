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

// General FYI: The server output of phpversion() as of 2021.07.08 is:
// 7.2.12-1+ubuntu16.04.1+deb.sury.org+10.

abstract class BidType {
  const TRUMP = 0;
  const NELLO = 1;
  const SEVENS = 2;
  const SPLASH = 3;
  const PLUNGE = 4;
}

abstract class StandardBidSuit {
  const BLANKS = 0;
  const ONES = 1;
  const TWOS = 2;
  const THREES = 3;
  const FOURS = 4;
  const FIVES = 5;
  const SIXES = 6;
  const DOUBLES = 7;
  const NO_TRUMP = 8;
}

abstract class NelloBidSuit {
  // TODO(isherman): Should these be moved to a separate enum?
  const NELLO_DOUBLES_LOW = 0;
  const NELLO_DOUBLES_HIGH = 1;
  const NELLO_DOUBLES_SUIT_OF_THEIR_OWN = 2;
}

abstract class CardLocation {
  // Dominoes in the deck, prior to dealing.
  const DECK = 'deck';
  // Dominoes in a player's hand.
  const HAND = 'hand';
  // Dominoes played on the table as part of the current trick.
  const TABLE = 'table';
  // Dominoes in tricks won by a team.
  const TEAM = 'team';
}

/**
 * An unsuited domino, represented as the number of pips on each (lower and
 * higher) side.
 */
class Domino {
  /**
   * @param array{
   *     low: int|string,
   *     high: int|string,
   *     id?: int|string,
   *     location_arg?: int|string,
   * } $data
   */
  public function __construct($data) {
    $this->low = intval($data['low']);
    $this->high = intval($data['high']);

    $this->id = self::computeId($this);
    if (array_key_exists('id', $data) && intval($data['id']) !== $this->id) {
      throw new Error(sprintf(
        'Unexpected id %d for domino [%d : %d] – expected id %d',
        intval($data['id']),
        $this->high,
        $this->low,
        $this->id
      ));
    }

    if (array_key_exists('location_arg', $data)) {
      $this->location_arg = intval($data['location_arg']);
    }
  }

  /** Returns a debug string representation for this domino. */
  public function toDebugString(): string {
    return "[$this->id -> $this->high : $this->low]";
  }

  /**
   * Returns a JSON serialization for this domino.
   * @return array<string, mixed>
   */
  public function toJson(): array {
    $json = [
      'id' => $this->id,
      'high' => $this->high,
      'low' => $this->low,
    ];
    if (!is_null($this->location_arg)) {
      $json['location_arg'] = $this->location_arg;
    }
    return $json;
  }

  /**
   * Returns the suit and rank for this domino given the suit of the played
   * trick and the trump suit.
   */
  public function toSuitedDomino(int $trick_suit, int $trump_suit): SuitedDomino {
    $suit = -1;
    $rank = -1;
    if ($this->high !== $trump_suit &&
        ($this->low === $trump_suit || $this->low === $trick_suit)) {
      $suit = $this->low;
      $rank = $this->high;
    } else {
      $suit = $this->high;
      $rank = $this->low;
    }
    return new SuitedDomino($suit, $rank);
  }

  /**
   * Returns `true` iff this domino is a double, i.e., both sides are the same.
   */
  public function isDouble(): bool {
    return $this->low === $this->high;
  }

  /**
   * Returns `true` iff this domino is of the given `suit`. Note that dominoes
   * which are trump are not part of any other suit.
   */
  public function followsSuit(int $suit, int $trump_suit): bool {
    if ($suit !== $trump_suit && $this->hasSuit($trump_suit)) {
      return false;
    }
    return $this->hasSuit($suit);
  }

  /**
   * Returns the expected id for the domino.
   * Note: This is often called on a partially constructed domino; but that
   * domino must have at least `low` and `high` initialized!
   */
  private static function computeId(Domino $domino): int {
    // Dominoes are 1-indexed and added in the order [0:0], [0:1], [1:1], [0:2],
    // etc. Thus compute a triangle number for the number of dominoes to skip in
    // order to land in the `high` suit, and then index into the suit to get
    // the `low`th rank card.
    return 1 + ($domino->high * ($domino->high + 1)) / 2 + $domino->low;
  }

  /**
   * Returns `true` iff this domino matches the suit. Note that this function
   * *does not* check whether the domino might actually be part of the trump
   * suit instead; this is why it's a private function.
   */
  private function hasSuit(int $suit): bool {
    if ($suit === StandardBidSuit::DOUBLES) {
      return $this->isDouble();
    }
    return $this->low === $suit || $this->high === $suit;
  }

  /**
   * Returns the point value for this domino.
   */
  public function getScore(): int {
    $sum = $this->low + $this->high;
    return $sum % 5 === 0 ? $sum : 0;
  }

  /** @var int */
  public $id;
  /** @var int */
  public $low;
  /** @var int */
  public $high;
  /**
   * Additional metadata about the domino's location, e.g., the id of the
   * player whose hand contains this domino.
   * @var int|null
   */
  public $location_arg = null;
}

/**
 * A suited domino, represented as the suit and rank in the suit. Note that the
 * "rank" for a double is identical to the suit.
 */

class SuitedDomino {
  /**
   * @param int|string $suit
   * @param int|string $rank
   */
  public function __construct($suit, $rank) {
    $this->suit = intval($suit);
    $this->rank = intval($rank);
  }

  public function isDouble(): bool {
    return $this->suit === $this->rank;
  }

  public function beats(SuitedDomino $other, int $trump_suit): bool {
    if ($this->suit === $other->suit) {
      return $this->isDouble() ||
             (!$other->isDouble() && $this->rank > $other->rank);
    }
    // If not following the previously winning suit, trump always wins, and any
    // other suit always loses.
    return $this->suit === $trump_suit;
  }

  /** @var int */
  public $suit;
  /** @var int */
  public $rank;
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

  private const SUIT_TO_DISPLAY_NAME = [
    'Blanks',
    'Ones',
    'Twos',
    'Threes',
    'Fours',
    'Fives',
    'Sixes',
    'Doubles',
    'No Trump',
  ];

  private const NELLO_SUIT_TO_DISPLAY_NAME = [
    'Nello Doubles Low',
    'Nello Doubles High',
    'Nello Doubles are a suit of their own',
  ];

  // The number of suits: blanks through sixes.
  // HACK: It can be useful to set this to 3 for debugging.
  private const NUM_SUITS = 7;

  // TODO(isherman): This disables type-checking for $this->dominoes; maybe
  // provide stubs (a la misc/table.game.php) instead?
  /** @var mixed */
  private $dominoes;

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
      'trumpSuit' => 13,
      // The suit of the current trick, e.g. Jane led a four.
      'trickSuit' => 14,
      // Which player is dealt the current trick, e.g. player id 0xB0BAC0DE is
      // dealer.
      'currentDealer' => 15,
      // 'my_first_game_variant' => 100,
    ]);
  }

  protected function getGameName(): string {
    // Used for translations and stuff. Please do not modify.
    return 'texasfortytwo';
  }

  /**
   * Called once, when a new game is launched. Initializes game state.
   * @param array<int|string, mixed> $players Map from id to player info.
   * @param array<int|string> $options TODO(isherman): Figure out what this data
   *     is, exactly.
   */
  protected function setupNewGame($players, $options = []): void {
    self::initializePlayers($players);

    // Initialize game state.
    self::initializeDeck();
    self::setGameStateInitialValue('highestBidder', -1);
    self::setGameStateInitialValue('bidValue', -1);
    self::setGameStateInitialValue('bidType', -1);
    self::setGameStateInitialValue('trumpSuit', -1);
    self::setGameStateInitialValue('trickSuit', -1);
    self::setGameStateInitialValue('currentDealer', self::getFirstDealer());

    // Initialize game statistics. Must match the list of stats defined in
    // stats.inc.php.
    // TODO(isherman): Call self::initStat for all defined statistics.

    // Begin the game by activating the first player.
    $this->activeNextPlayer();
  }

  private function getDisplayStringForBid(int $bid_value): string {
    if ($bid_value < 42) {
      return strval($bid_value);
    }
    $marks = intdiv($bid_value, 42);
    if ($bid_value % 42 === 0) {
      return "$marks marks";
    }
    if ($bid_value % 42 === 1) {
      return "splash ($marks marks)";
    }
    if ($bid_value % 42 === 2) {
      return "plunge ($marks marks)";
    }
    return '';
  }

  private function getPlayerIdByPlayerNo(int $player_no): int {
    return intval(self::getUniqueValueFromDB(
      "SELECT player_id id FROM player WHERE player_no = $player_no"
    ));
  }

  private function getFirstDealer(): int {
    // TODO(isherman): I think this is somehow being called and then the dealer
    // is rotating before the first hand? Anyway, setting this to 2 seems to
    // make player 0 the first to play in the first hand?
    return self::getPlayerIdByPlayerNo(2);
  }

  private function getDealer(): int {
    return intval(self::getGameStateValue('currentDealer'));
  }

  /**
   * Returns whether the given player id is the dealer for this hand.
   * @param int|string $player_id
   */
  private function isDealer($player_id): bool {
    return self::getDealer() === intval($player_id);
  }

  // TODO(isherman): Fix calls to this function to have the correct data type!
  private function getSuitAndRank(Domino $domino): SuitedDomino {
    self::trace(print_r($domino, true));
    $trick_suit = self::getTrickSuit();
    $trump_suit = self::getTrumpSuit();
    return $domino->toSuitedDomino($trick_suit, $trump_suit);
  }

  /**
   * Inserts a set of fields into the database named `$db_name`.
   * @param array<string> $fields An array of field names, e.g. 'player_id'.
   * @param array<array<mixed>> $rows An array of arrays, where each inner array
   *     defines the values for one row, specified in the same order as
   *     `$field_names`.
   */
  private function insertIntoDatabase(string $db_name, array $fields, $rows): void {
    $to_sql_row = function ($row) {
      return "('".join("','", $row)."')";
    };
    $fields = join(',', $fields);
    $values = join(',', array_map($to_sql_row, $rows));
    $this->DbQuery("INSERT INTO $db_name ($fields) VALUES $values");
  }

  /**
   * Initializes the player database for the game. Called once, when a new game
   * is launched.
   * @param array<int|string, mixed> $players
   */
  private function initializePlayers(array $players): void {
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
    foreach ($players as $player_id => $player) {
      $color = array_shift($default_colors);
      $rows[] = [
        $player_id,
        $color,
        $player['player_canal'],
        addslashes($player['player_name']),
        addslashes($player['player_avatar']),
        // HACK: During development, it's useful to have a fixed order.
        // Order based on the player number, which is a suffix on the player
        // name, 0-9.
        intval($player['player_name'][-1]),
      ];
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
  private function initializeDeck(): void {
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
        $rows[] = [$high, $low, CardLocation::DECK, 0, '', 0];
      }
    }
    // HACK: As a debugging aid, it can be useful to set a low value for
    // `self::NUM_SUITS`. Support that by ensuring that the number of dominoes
    // is always divisible evenly by the number of players.
    $rows = array_slice($rows, 0, count($rows) - (count($rows) % 4));
    self::insertIntoDatabase('dominoes', $fields, $rows);
  }

  /**
   * Returns the dominoes in a location. Analogue to `Deck::getCardsInLocation`.
   * @param CardLocation::* $location
   * @param int|string|null $location_arg
   * @return array<Domino>
   */
  private function getDominoesInLocation($location, $location_arg = null): array {
    $fields = 'card_id id, high, low, card_location_arg location_arg';
    $where = "card_location='$location'";
    if (!is_null($location_arg)) {
      $where .= "AND card_location_arg=$location_arg";
    }
    $dominoes = self::getObjectListFromDB(
      "SELECT $fields FROM dominoes WHERE $where"
    );
    $make_domino = function ($args) { return new Domino($args); };
    return array_map($make_domino, $dominoes);
  }

  /**
   * Returns the dominoes in a location, serialized to JSON.
   * @param CardLocation::* $location
   * @param int|string|null $location_arg
   * @return array<string, mixed>
   */
  private function serializeDominoesInLocation($location, $location_arg = null): array {
    $dominoes = $this->getDominoesInLocation($location, $location_arg);
    $serialize = function ($domino) { return $domino->toJson(); };
    return array_map($serialize, $dominoes);
  }

  /**
   * Returns all game state visible to the current player.
   * Called each time the game interface is displayed to a player, ie:
   *   * when the game starts
   *   * when a player refreshes the game page (F5)
   * @return array<string, mixed>
   */
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
    $result['hand'] =
        $this->serializeDominoesInLocation(CardLocation::HAND, $current_player_id);
    // Dominoes in play on the table.
    $result['table'] = $this->serializeDominoesInLocation(CardLocation::TABLE);
    $result['trickSuit'] = self::getTrickSuit();
    $result['bidValue'] = $this->getGameStateValue('bidValue');
    $result['highestBidder'] = $this->getGameStateValue('highestBidder');
    $result['bidType'] = $this->getGameStateValue('bidType');
    $result['trumpSuit'] = self::getTrumpSuit();
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
  public function getGameProgression(): int {
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
  public function pass(): void {
    self::checkAction('pass');

    $player_id = self::getActivePlayerId();
    $current_bid_value = intval(self::getGameStateValue('bidValue'));
    if ($this->isDealer($player_id) && $current_bid_value === -1) {
      // TODO(isherman): How best to indicate an error?
      return;
    }

    // TODO(isherman): Dealer shouldn't be allowed to pass.
    self::notifyAllPlayers(
      'pass',
      clienttranslate('${player_name} passes'),
      [
        // 'i18n' => array ('color_displayed','value_displayed' ),
        'player_id' => $player_id,
        'player_name' => self::getActivePlayerName()
      ]
    );
    $this->gamestate->nextState('nextPlayerBid');
  }

  public function gamestatehack(): void {
    self::trace(print_r($this->gamestate->state(), true));
  }

  /** @param int|string $bid_value */
  public function bid($bid_value): void {
    self::checkAction('bid');
    // TODO(isherman): Not sure whether this type coersion is needed or not...
    $bid_value = intval($bid_value);
    $player_id = self::getActivePlayerId();
    // only allow bids higher than current bid if it exists
    $current_bid_value = intval(self::getGameStateValue('bidValue'));
    self::trace(sprintf('got bid value: %d', $bid_value));
    self::trace(sprintf('current bid value: %d', $current_bid_value));
    if (($current_bid_value === -1 && $bid_value >= 30) || $bid_value > $current_bid_value) {
      self::setGameStateValue('bidValue', $bid_value);
      self::setGameStateValue('highestBidder', $player_id);

      self::notifyAllPlayers(
        'bid',
        clienttranslate('${player_name} bids ${bidString}'),
        [
          // 'i18n' => array ('color_displayed','value_displayed' ),
          'player_id' => $player_id,
          'player_name' => self::getActivePlayerName(),
          'bidString' => self::getDisplayStringForBid($bid_value)
        ]
      );
      $this->gamestate->nextState('nextPlayerBid');
    } else {
      self::trace('Bad bid!');
      // TODO(sdspikes): throw error?
    }
  }


  /** @param int|string $trump_suit */
  public function chooseBidSuit($trump_suit): void {
    self::checkAction('chooseBidSuit');
    self::setGameStateValue('trumpSuit', $trump_suit);
    // TODO(sdspikes): special case for no trump
    $display_name = self::SUIT_TO_DISPLAY_NAME[$trump_suit];
    self::notifyAllPlayers(
      'setBidSuit',
      clienttranslate('${trump_suit} are trump'),
      [
        'trump_suit' => $display_name,
      ]
    );
    $this->gamestate->nextState();
  }

  /**
   * Each time a player is doing some game action, one of the methods below is called.
   * (note: each method below must match an input method in template.action.php)
   * @param int|string $card_id
   */
  public function playCard($card_id): void {
    self::checkAction('playCard');
    /** @phpstan-ignore-next-line */
    $domino = new Domino(self::getNonEmptyObjectFromDB(
      "SELECT card_id id, high, low FROM dominoes WHERE card_id=$card_id"
    ));

    self::trace(sprintf('played domino: %s', $domino->toDebugString()));

    $trumpSuit = self::getTrumpSuit();
    $trickSuit = self::getTrickSuit();
    $play = self::getSuitAndRank($domino);

    // TODO(isherman): Reuse getPlayableDominoIdsForPlayer here.
    $player_id = self::getActivePlayerId();
    $hand = $this->getDominoesInLocation(CardLocation::HAND, $player_id);
    $could_have_followed_suit = false;
    foreach ($hand as $domino_in_hand) {
      if ($domino_in_hand->followsSuit($trickSuit, $trumpSuit)) {
        self::trace('Could have followed suit.');
        self::trace(print_r($domino_in_hand, true).'\n');
        $could_have_followed_suit = true;
        break;
      }
    }

    // XXX check rules here
    // Set the trick suit if it hasn't been set yet.
    if ($trickSuit === -1) {
      self::setGameStateValue('trickSuit', $play->suit);
    } elseif ($play->suit !== $trickSuit &&
              $could_have_followed_suit) {
      // TODO: How do we report an error for an invalid play?
      return;
    }

    $this->dominoes->moveCard($card_id, CardLocation::TABLE, $player_id);

    // And notify
    self::notifyAllPlayers(
      'playCard',
      clienttranslate('${player_name} plays the [${high} : ${low}]'),
      [
        // 'i18n' => array ('color_displayed','value_displayed' ),
        'card_id' => $card_id,
        'player_id' => $player_id,
        'player_name' => self::getActivePlayerName(),
        'high' => $domino->high,
        'low' => $domino->low,
      ]
    );
    // 'value_displayed' => $this->values_label [$current_card ['type_arg']],'color' => $current_card ['type'],
    // 'color_displayed' => $this->colors [$current_card ['type']] ['name'] ));
    // Next player
    $this->gamestate->nextState('playCard');
  }

  public function stChooseBidType(): void {
    // TODO
    self::checkAction('chooseBidType');
    //self::setGameStateValue('trumpSuit', $trump_suit);
    $this->gamestate->nextState();
  }

  //////////////////////////////////////////////////////////////////////////////
  //////////// Game state arguments
  ////////////
  /*
   * Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
   * These methods function is to return some additional information that is specific to the current
   * game state.
   */

  // TODO(isherman): This method leaks whether a player has enough doubles to
  // splash/plunge to _all_ players. Yikes!
  // TODO(isherman): This is probably not l18n-friendly; needs translation of
  // some sort.
  /**
   * @return array<int, string> The list of currently possible bids, mapped from
   *     constants to human-readable names.
   */
  public function argCurrentBid() {
    // A shorter version:
    /*
    $possible_dibs = [];
    for ($i = 30; $i < 42; $i++) {
      $possible_dibs[$i] = strval($i);
    }
    $possible_dibs[1 * 42] = 'one mark';
    $possible_dibs[2 * 42] = 'two marks';
    $possible_dibs[3 * 42] = 'splash';
    $possible_dibs[4 * 42] = 'plunge';

    $current_bid = self::getGameStateValue('bidValue') || 0;
    foreach ($possible_dibs as $bid => $name) {
      if ($bid <= $current_bid) {
      unset($possible_dibs[$bid]);
      }
    }

    return $possible_dibs;
    */

    $lowest_bid = 30;
    $bid_value = intval(self::getGameStateValue('bidValue'));
    if ($bid_value !== -1 && $bid_value >= $lowest_bid) {
      $lowest_bid = $bid_value + 1;
    }
    $possible_bids = [];
    for ($i = $lowest_bid; $i < 42; $i++) {
      // convert i to string
      $possible_bids[$i] = strval($i);
    }
    if ($bid_value < 42) {
      $possible_bids[42] = '1 mark';
    }
    if ($bid_value < 84) {
      $possible_bids[84] = '2 marks';
    }
    $marks = intdiv($bid_value, 42) + 1;
    if ($bid_value >= 84) {
      $possible_bids[$marks * 42] = "$marks marks";
    }

    // Only show splash/plunge if you have the doubles to support the bid.
    $player_id = self::getActivePlayerId();
    $hand = $this->getDominoesInLocation(CardLocation::HAND, $player_id);
    $num_doubles = 0;
    foreach ($hand as $domino) {
      if ($domino->isDouble()) {
        $num_doubles++;
      }
    }
    if ($num_doubles >= 3) {
      $possible_bids[42 * max(3, $marks) + 1] = 'splash';
    }
    if ($num_doubles >= 4) {
      $possible_bids[42 * max(4, $marks) + 2] = 'plunge';
    }

    return $possible_bids;
  }

  // TODO(isherman): This probably needs to be translated for l18n.
  /**
   * @return array<int, string> A mapping from suit constants to their
   *     human-readable labels, in English.
   */
  public function argChooseBidSuit() {
    return self::SUIT_TO_DISPLAY_NAME;
  }

  /**
   * Returns a list of domino ids corresponding to the dominoes in the player's
   * hand that are currently playable.
   * @param int|string $player_id
   * @return array<int>
   */
  private function getPlayableDominoIdsForPlayer($player_id) {
    $trick_suit = self::getTrickSuit();  // -1 if no domino has been played
    $trump_suit = self::getTrumpSuit();
    $hand = $this->getDominoesInLocation(CardLocation::HAND, $player_id);
    $active_id = self::getActivePlayerId();

    // TODO(isherman): Add type checking to catch this == vs. ===
    if ($trick_suit === -1 and $player_id == $active_id) {
      $valid_plays = $hand;
    } elseif ($trick_suit === -1 or $this->dominoes->countCardInLocation(CardLocation::TABLE, $player_id) != 0) {
      $valid_plays = [];
    } else {
      // If the player can follow suit, they must play a domino from that suit.
      // Otherwise, any domino is playable.
      $follows_suit = function (Domino $domino) use ($trick_suit, $trump_suit) {
        return $domino->followsSuit($trick_suit, $trump_suit);
      };
      $valid_plays = array_filter($hand, $follows_suit);
      if (count($valid_plays) === 0) {
        $valid_plays = $hand;
      }
    }

    $get_id = function (Domino $domino) { return $domino->id; };
    return array_map($get_id, $valid_plays);
  }

  // TODO(isherman): Docs.
  /** @return array<string, mixed> */
  public function argPlayerTurn(): array {
    // Docs for sending private data to players:
    // https://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php#Private_info_in_args
    $player_ids = array_keys(self::loadPlayersBasicInfos());
    $playable_dominoes = [];
    foreach ($player_ids as $player_id) {
      $playable_dominoes[$player_id] = [
        'playableDominoes' => self::getPlayableDominoIdsForPlayer($player_id),
      ];
    }
    return [
      'trickSuit' => self::getTrickSuit(),
      // TODO(jasonptm): Update this maybe?
      '_private' => $playable_dominoes,
    ];
  }

  //////////////////////////////////////////////////////////////////////////////
  ///////////////////////////// Game state actions /////////////////////////////
  //////////////////////////////////////////////////////////////////////////////
  // Each `action` property in states.inc.php corresponds to a method defined //
  // here. The action method of state X is called everytime the current game  //
  // state is set to X.                                                       //
  //////////////////////////////////////////////////////////////////////////////

  // Washes (shuffles) the dominoes and deals new hands to each player.
  public function stNewHand(): void {
    self::setGameStateValue('highestBidder', -1);
    self::setGameStateValue('bidValue', -1);
    self::setGameStateValue('bidType', -1);
    self::setGameStateValue('trumpSuit', -1);
    self::setGameStateValue('trickSuit', -1);

    $new_dealer = self::getPlayerAfter(self::getDealer());
    self::setGameStateValue('currentDealer', $new_dealer);
    $this->gamestate->changeActivePlayer(self::getPlayerAfter($new_dealer));

    // Wash the dominoes.
    // Note: Moving cards from location `null` means from any/all locations.
    $this->dominoes->moveAllCardsInLocation(null, CardLocation::DECK);
    $this->dominoes->shuffle(CardLocation::DECK);

    // Deal a new hand to each player.
    $players = self::loadPlayersBasicInfos();
    $hand_size = self::NUM_SUITS * (self::NUM_SUITS + 1) / 2 / count($players);
    // HACK: As a debugging aid, it can be useful to set a low value for
    // `self::NUM_SUITS`. Support that by ensuring that the number of dominoes
    // is always divisible evenly by the number of players.
    $hand_size = intval($hand_size);
    foreach ($players as $player_id => $player) {
      $this->dominoes->pickCards($hand_size, CardLocation::DECK, $player_id);
      $dominoes = $this->serializeDominoesInLocation(CardLocation::HAND, $player_id);
      self::notifyPlayer($player_id, 'newHand', '', ['hand' => $dominoes]);
    }
    $this->gamestate->nextState();
  }

  private function getPartnerId(int $player_id): int {
    $player_no = intval(self::getPlayerNoById($player_id));
    $partner_no = ($player_no + 2) % 4;
    return self::getPlayerIdByPlayerNo($partner_no);
  }

  public function stNextPlayerBid(): void {
    $player_id = self::getActivePlayerId();
    self::trace(sprintf('player id: %d', $player_id));
    $players = self::loadPlayersBasicInfos();
    $player_name = self::getActivePlayerName();
    self::trace(sprintf('player name: %s', $player_name));
    self::trace(sprintf('isdealer?: %s', $this->isDealer($player_id) ? 'true' : 'false'));
    self::trace(sprintf('dealer: %d', self::getDealer()));

    if ($this->isDealer($player_id)) {
      self::trace('current player is dealer');
      $highest_bidder = intval(self::getGameStateValue('highestBidder'));
      $bid_value = intval(self::getGameStateValue('bidValue'));
      self::notifyAllPlayers(
        'bidWin',
        clienttranslate('${player_name} wins the bid'),
        [
          // 'i18n' => array ('color_displayed','value_displayed' ),
          'player_id' => $player_id,
          'player_name' => $players[$highest_bidder]['player_name'],
        ]
      );

      // TODO(sdspikes): only allow on dump? Need to track that in state if so
      // if ($highest_bidder == $player_id) {
      //   $this->gamestate->nextState('chooseBidType');
      //
      // }
      // if ($bid_value % 42 == 0) {
      //   $this->gamestate->nextState('chooseBidType');
      // } else {
      // }

      if ($bid_value < 42 || $bid_value % 42 === 0) {
        $this->gamestate->changeActivePlayer($highest_bidder);
      } else {
        $this->gamestate->changeActivePlayer(self::getPartnerId($highest_bidder));
      }
      $this->gamestate->nextState('chooseBidSuit');
    } else {
      self::trace('Current player not dealer, go to next player');
      self::activeNextPlayer();
      $this->gamestate->nextState('playerBid');
    }
  }

  public function stNewTrick(): void {
    // New trick: active the player who wins the last trick, or the player who own the club-2 card
    // Reset trick color to 0 (= no color)
    //self::setGameStateInitialValue('trickColor', 0);
    self::setGameStateValue('trickSuit', -1);
    $this->gamestate->nextState();
  }

  private function getTrumpSuit(): int {
    return intval(self::getGameStateValue('trumpSuit'));
  }

  private function getTrickSuit(): int {
    return intval(self::getGameStateValue('trickSuit'));
  }

  private function getTeamForPlayer(int $player_id): int {
    return self::getPlayerNoById($player_id) % 2;
  }

  /**
   * Returns the ids of players on a given team.
   * @return array<int>
   */
  private function getPlayersForTeam(int $team_id): array {
    return [
      self::getPlayerIdByPlayerNo($team_id),
      self::getPlayerIdByPlayerNo($team_id + 2),
    ];
  }

  public function stNextPlayer(): void {
    // If some players haven't played a domino yet, simply activate the next
    // player.
    if ($this->dominoes->countCardInLocation(CardLocation::TABLE) != self::getPlayersNumber()) {
      $player_id = self::activeNextPlayer();
      self::giveExtraTime($player_id);
      $this->gamestate->nextState('nextPlayer');
      return;
    }

    // All players have played a domino in this trick, so figure out who won the
    // trick.
    $dominoes_on_table = $this->getDominoesInLocation(CardLocation::TABLE);
    // The player after the final player is the player that lead.
    $winning_player_id = self::getPlayerAfter(self::getActivePlayerId());
    // TODO(isherman): Would be nice to assert that there is exactly one
    // domino returned here:
    $lead_domino =
      $this->getDominoesInLocation(CardLocation::TABLE, $winning_player_id)[0];
    $winning_play = self::getSuitAndRank($lead_domino);
    $trump_suit = self::getTrumpSuit();
    foreach ($dominoes_on_table as $domino) {
      // Note: type = card color
      $play = self::getSuitAndRank($domino);
      self::trace(print_r($play, true));
      if ($play->beats($winning_play, $trump_suit)) {
        self::trace('beats previous!');
        $winning_player_id = $domino->location_arg; // location_arg is the player id
        $winning_play = $play;
      }
    }

    // Activate this player, they have the lead
    $this->gamestate->changeActivePlayer($winning_player_id);

    // Move all dominoes to the won pile for the given player's team.
    $this->dominoes->moveAllCardsInLocation(
      CardLocation::TABLE,
      CardLocation::TEAM,
      null,
      self::getTeamForPlayer($winning_player_id)
    );

    // Notify
    // Note: we use 2 notifications here to pause the display during the first notification
    //  before we move all cards to the winner (during the second)
    $players = self::loadPlayersBasicInfos();
    self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick'), [
      'player_id' => $winning_player_id,
      'player_name' => $players[ $winning_player_id ]['player_name']
    ]);
    self::notifyAllPlayers('giveAllCardsToPlayer', '', [
      'player_id' => $winning_player_id
    ]);

    // TODO(isherman): I don't think this check makes sense for nello, where one
    // of the players doesn't play.
    if ($this->dominoes->countCardInLocation(CardLocation::HAND) == 0) {
      // All dominoes have been played; the hand is over.
      $this->gamestate->nextState('endHand');
    } else {
      // Some dominoes remain unplayed, so proceed onto the next trick.
      $this->gamestate->nextState('nextTrick');
    }
  }

  public function stEndHand(): void {
    // Count and score points, then end the game or go to the next hand.
    $players = self::loadPlayersBasicInfos();

    $team_points = [];
    foreach (range(0, 1) as $team) {
      $dominoes = $this->getDominoesInLocation(CardLocation::TEAM, $team);
      $team_points[$team] = 0;
      foreach ($dominoes as $domino) {
        $team_points[$team] += $domino->getScore();
      }
      $team_points[$team] += count($dominoes) / count($players);
    }
    self::trace(print_r($team_points, true));
    $bidder = intval(self::getGameStateValue('highestBidder'));
    $bidder_team = intval(self::getTeamForPlayer($bidder));
    $bid = intval(self::getGameStateValue('bidValue'));
    self::trace(sprintf('bidder_team: %d', $bidder_team));
    self::trace(sprintf('bid: %d', $bid));
    $needed_points = $bid < 42 ? $bid : 42;
    self::trace(sprintf('needed_points: %d', $needed_points));
    $winning_team = $bidder_team;
    if ($team_points[$bidder_team] < $needed_points) {
      $winning_team = ($bidder_team + 1) % 2;
    }
    self::trace(sprintf('winning_team: %d', $winning_team));
    // Apply scores to player
    foreach (self::getPlayersForTeam($winning_team) as $player_id) {
      $marks = $bid < 42 ? 1 : intdiv($bid, 42);
      $sql = "UPDATE player SET player_score=player_score+$marks WHERE player_id='$player_id'";
      self::DbQuery($sql);
      self::notifyAllPlayers("points", clienttranslate('${player_name} gets ${marks} mark(s)'), [
        'player_id' => $player_id,'player_name' => $players [$player_id] ['player_name'],
        'marks' => $marks ]);
    }
    $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
    self::notifyAllPlayers("newScores", '', [ 'newScores' => $newScores ]);

    ///// Test if this is the end of the game
    foreach ($newScores as $player_id => $score) {
      if ($score >= 7) {
        // Trigger the end of the game !
        $this->gamestate->nextState("endGame");
        return;
      }
    }
    $this->gamestate->nextState('nextHand');
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

  /**
   * @param array<string, mixed> $state
   * @param int $active_player
   */
  public function zombieTurn($state, $active_player): void {
    $statename = $state['name'];

    if ($state['type'] == 'activeplayer') {
      switch ($statename) {
        default:
          $this->gamestate->nextState('zombiePass');
          break;
      }

      return;
    }

    if ($state['type'] == 'multipleactiveplayer') {
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

    throw new feException('Zombie mode not supported at this game state: '.$statename);
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

  /**
   * @param int $from_version
   */
  public function upgradeTableDb($from_version): void {
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
