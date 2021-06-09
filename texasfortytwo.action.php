<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * template implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * heartsla.action.php
 *
 * template main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/heartsla/heartsla/myAction.html", ...)
 *
 */
class action_texasfortytwo extends APP_GameAction {

    // Constructor: please do not modify
  public function __default() {
    if (self::isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs ['table'] = self::getArg("table", AT_posint, true);
    } else {
      $this->view = "texasfortytwo_texasfortytwo";
      self::trace("Complete reinitialization of board game");
    }
  }

  public function playCard() {
    self::setAjaxMode();
    $card_id = self::getArg("id", AT_posint, true);
    $this->game->playCard($card_id);
    self::ajaxResponse();
  }

  public function bid() {
    self::setAjaxMode();
    // TODO(sdspikes): figure out what getArg does
    $bid_value = self::getArg("bid", AT_posint, true);
    $this->game->playCard($bid_value);
    self::ajaxResponse();
  }

  public function pass() {
    self::setAjaxMode();
    $this->game->pass();
    self::ajaxResponse();
  }

  public function chooseBidSuit() {
    self::setAjaxMode();
    $bid_suit = self::getArg("bid_suit", AT_posint, true);
    $this->game->chooseBidSuit();
    self::ajaxResponse();
  }
}
