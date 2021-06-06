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
 * This file initializes the HTML page template each time the page is loaded.
 */

require_once(APP_BASE_PATH.'view/common/game.view.php');

class view_texasfortytwo_texasfortytwo extends game_view {
  public function getGameName() {
    return "texasfortytwo";
  }

  // Initializes the HTML page template.
  // Called each time the game interface is displayed to a player, ie:
  //   * when the game starts
  //   * when a player refreshes the game page (F5)
  public function build_page($viewArgs) {
    $players = $this->game->loadPlayersBasicInfos();
    $template = self::getGameName()."_".self::getGameName();

    // Assign each player a seat at the table. Use compass directions to label
    // the seats, bridge-style.
    $seats = ['S', 'W', 'N', 'E'];
    $this->page->begin_block($template, "player");
    foreach ($players as $player_id => $info) {
      $seat = array_shift($seats);
      $this->page->insert_block("player", [
          "PLAYER_ID" => $player_id,
          "PLAYER_NAME" => $info['player_name'],
          "PLAYER_COLOR" => $info['player_color'],
          "SEAT" => $seat]);
    }

    // Translate UI strings.
    $this->tpl['MY_HAND'] = self::_("My hand");
  }
}
