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
 * This file defines the logic for the game user interface.
 */

// TODO(isherman): The docs recommend an SVG for us rather than a PNG:
// https://en.doc.boardgamearena.com/Game_art:_img_directory#Images_format
// The sprites file contains images representing all of the dominoes.
const SPRITES_FILE = 'img/dominoes.svg';
const SPRITES_PER_ROW = 7;
// Dimensions of a single domino in the sprites file, in pixels.
// Note: Whenever updating these values, also update them in the CSS.
const DOMINO_WIDTH = 194;
const DOMINO_HEIGHT = 104;

// The number of suits: blanks through sixes.
const NUM_SUITS = 7;

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
  'ebg/stock'
], function(dojo, declare) {
  return declare('bgagame.texasfortytwo', ebg.core.gamegui, {
    constructor: function() {},

    // Initializes the UI based on the current game state.
    // Called each time the game interface is displayed to a player, ie:
    //   * when the game starts
    //   * when a player refreshes the game page (F5)
    // `this.gamedatas` contains the state returned by `getAllDatas` in PHP.
    setup: function() {
      console.log('Starting game setup(): ', this.gamedatas);

      // Player hand
      this.hand = new ebg.stock();
      this.hand.create(this, $('hand'), DOMINO_WIDTH, DOMINO_HEIGHT);
      this.hand.image_items_per_row = SPRITES_PER_ROW;

      dojo.connect(
        this.hand, 'onChangeSelection', this, 'onHandSelectionChanged');

      // Define the available domino types.
      for (let high = 0; high < NUM_SUITS; high++) {
        for (let low = 0; low <= high; low++) {
          const sprite_index = this.getSpriteIndex(high, low);
          // Note: The `id` here is a type id for use with the `Stock` class.
          // It is *not* in the same id space as the ids returned from PHP.
          // However, adding 1 to the sprite index results in ids that are
          // semantically identical in both id spaces – i.e. the domino with
          // Stock type id 1 is the double blank, which is also the domino with
          // `card_id` 1 in the SQL database.
          const id = sprite_index + 1;
          // TODO(isherman): This causes the hand to always be sorted from low
          // to high, which is not always the most useful sorting. Wizard
          // re-sorts nicely in response to game actions.
          const sort_id = id;
          this.hand.addItemType(
            id, sort_id, g_gamethemeurl + SPRITES_FILE, sprite_index);
        }
      }

      // Display dominoes in the player's hand.
      for (const domino of this.gamedatas.hand) {
        const id = domino.id;
        this.hand.addToStockWithId(id, id);
      }

      // Display dominoes in play on the table.
      for (const domino of this.gamedatas.table) {
        const player_id = domino.location_arg;
        this.playDomino(player_id, domino.id);
      }

      this.setUpNotifications();

      console.log('Ending game setup');
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    onEnteringState: function(stateName, args) {
      console.log('Entering state: ' + stateName);

      switch (stateName) {
        case 'playerTurn':
          this.updatePossibleMoves(args.args.trickSuit);
          break;
      }
    },

    updatePossibleMoves: function(trickSuit) {
      const handItems = this.hand.getAllItems();
      for (const handItem of handItems) {
        const id = handItem.id;
        const domino = this.getDominoFromId(id);
        if (domino.high === trickSuit || domino.low === trickSuit) {
          const div = this.hand.getItemDivId(id);
          /*div.style.border = "solid 2px hsl(58deg 100% 50%)";
          div.style.borderRadius = "22px";
          div.style.boxShadow = "0 0 22px hsl(58deg 100% 50%)";*/
        }
      }
    },

    chooseBidSuit: function(e, bidSuit) {
      console.log('bidSuit: ' + bidSuit);
      console.log(e);
      this.ajaxcall(
        "/texasfortytwo/texasfortytwo/chooseBidSuit.html", {
          lock: true,
          bid_suit: bidSuit
        },
        this,
        function(result) {
          console.log(result)
        },
        function(is_error) {
          console.log(is_error)
        });

    },


    bid: function(e, bidValue) {
      console.log('bidded: ' + bidValue);
      console.log(e);
      this.ajaxcall(
        "/texasfortytwo/texasfortytwo/bid.html", {
          lock: true,
          bid: bidValue
        },
        this,
        function(result) {
          console.log(result)
        },
        function(is_error) {
          console.log(is_error)
        });

    },


    pass: function(e) {
      console.log('passed: ');
      console.log(e);
      this.ajaxcall(
        "/texasfortytwo/texasfortytwo/pass.html", {
          lock: true
        },
        this,
        function(result) {
          console.log(result)
        },
        function(is_error) {
          console.log(is_error)
        });

    },
    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function(stateName) {
      console.log('Leaving state: ' + stateName);

      switch (stateName) {
        /* Example:

            case 'myGameState':

                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
           */

        case 'dummmy':
          break;
      }
    },

    // onUpdateActionButtons: in this method you can manage 'action buttons' that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function(stateName, args) {
      console.log('onUpdateActionButtons: ' + stateName);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case 'playerBid':
            if (!args) {
              // error message?
              return;
            }

            bidFunction = (bidVal) => (e => this.bid(e, bidVal));
            for (const [key, value] of Object.entries(args)) {
              console.log(key);
              console.log(value);
              if (value === 'splash' || value === 'plunge') {
                this.addActionButton('bid' + key, _(value), bidFunction(key), null, false, 'green');
              } else {
                this.addActionButton('bid' + key, _(value), bidFunction(key));
              }
            }
            this.addActionButton('pass', _('pass'), e => this.pass(e), null, false, 'red');
            break;

          case 'chooseBidSuit':
            if (!args) {
              return;
            }

            chooseBidSuitFunction = (bidSuit) => (e => this.chooseBidSuit(e, bidSuit));
            args.forEach((bidSuit, index) => {
              this.addActionButton(bidSuit, _(bidSuit), chooseBidSuitFunction(index));
            });
            break;
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    // Returns the index into the sprites image file for the domino with big end
    // having `high` pips and little end having `low` pips.
    getSpriteIndex: function(high, low) {
      // The sprites file is organized as [0:0], [0:1], [1:1], [0:2], etc.
      // Thus compute a triangle number for the number of dominoes to skip in
      // order to land in the `high` suit, and then index into the suit to get
      // the `low`th rank card.
      return (high * (high + 1)) / 2 + low;
    },

    // TODO(isherman): Docs
    getDominoFromId: function(id) {
      // TODO(isherman): Implement for realz
      const sprite_index = id - 1;
      for (let high = 0; high <= 6; high++) {
        for (let low = 0; low <= high; low++) {
          if (this.getSpriteIndex(high, low) === sprite_index) {
            return {
              high: high,
              low: low,
            };
          }
        }
      }
      return {
        high: -1,
        low: -1,
      };
    },

    // Animates a domino being played on the table.
    playDomino: function(player_id, domino_id) {
      // Note: Sprites are 0-indexed, whereas dominoes are 1-index.
      const sprite_index = domino_id - 1;
      const sprite_x_index = sprite_index % 7;
      const sprite_y_index = Math.floor(sprite_index / 7);
      dojo.place(
        this.format_block('jstpl_cardontable', {
          x: sprite_x_index * DOMINO_WIDTH,
          y: sprite_y_index * DOMINO_HEIGHT,
          player_id: player_id,
        }),
        'playertablecard_' + player_id);

      const destination = 'cardontable_' + player_id;
      if (player_id != this.player_id) {
        // An opponent played a domino. Animate the domino as originating from
        // the player panel.
        this.placeOnObject(destination, 'overall_player_board_' + player_id);
      } else {
        // TODO(isherman): Could it fail to exist in our hand? Why is that not
        // an invariant? The if-stmt below is adapted from the Hearts example
        // game.
        // You played a domino. If it exists in your hand, animate it moving.
        if ($('hand_item_' + domino_id)) {
          this.placeOnObject(destination, 'hand_item_' + domino_id);
          this.hand.removeFromStockById(domino_id);
        }
      }

      // In any case: move it to its final destination
      this.slideToObject(destination, 'playertablecard_' + player_id).play();
    },

    // /////////////////////////////////////////////////
    // // Player's action

    /*
     *
     * Here, you are defining methods to handle player's action (ex: results of mouse click on game objects).
     *
     * Most of the time, these methods: _ check the action is possible at this game state. _ make a call to the game server
     *
     */

    onHandSelectionChanged: function() {
      var items = this.hand.getSelectedItems();

      if (items.length > 0) {
        var action = 'playCard';
        if (this.checkAction(action, true)) {
          // Can play a card
          var card_id = items[0].id;
          this.ajaxcall(
            '/' +
            this.game_name +
            '/' +
            this.game_name +
            '/' +
            action +
            '.html', {
              id: card_id,
              lock: true
            },
            this,
            function(result) {},
            function(is_error) {}
          )

          this.hand.unselectAll()
        } else if (this.checkAction('giveCards')) {
          // Can give cards => let the player select some cards
        } else {
          this.hand.unselectAll();
        }
      }
    },

    /*
     * Example:
     *
     * onMyMethodToCall1: function( evt ) { console.log( 'onMyMethodToCall1' ); // Preventing default browser reaction dojo.stopEvent(
     * evt ); // Check that this action is possible (see 'possibleactions' in states.inc.php) if( ! this.checkAction( 'myAction' ) ) {
     * return; }
     *
     * this.ajaxcall( '/heartsla/heartsla/myAction.html', { lock: true, myArgument1: arg1, myArgument2: arg2, ... }, this, function(
     * result ) { // What to do after the server call if it succeeded // (most of the time: nothing) }, function( is_error) { // What to
     * do after the server call in anyway (success or failure) // (most of the time: nothing) } ); },
     *
     */

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
        setupNotifications:

        In this method, you associate each of your game notifications with your local method to handle it.

        Note: game notification names correspond to 'notifyAllPlayers' and 'notifyPlayer' calls in
              your template.game.php file.

    */
    setUpNotifications: function() {
      console.log('notifications subscriptions setup');

      dojo.subscribe('newHand', this, 'onNewHand');
      dojo.subscribe('bid', this, 'onBid');
      dojo.subscribe('bidWin', this, 'onBidWin');
      dojo.subscribe('playCard', this, 'onPlayDomino');

      dojo.subscribe('trickWin', this, 'notif_trickWin');
      this.notifqueue.setSynchronous('trickWin', 1000);
      dojo.subscribe(
        'giveAllCardsToPlayer',
        this,
        'notif_giveAllCardsToPlayer'
      );
      dojo.subscribe('newScores', this, 'notif_newScores');
    },

    onNewHand: function(data) {
      // We received a new full hand of dominoes.
      this.hand.removeAll();
      for (const domino of data.args.hand) {
        const id = domino.id;
        this.hand.addToStockWithId(id, id);
      }
    },

    onPlayDomino: function(data) {
      this.playDomino(data.args.player_id, data.args.card_id);
    },

    onBid: function(data) {
      console.log('in onBid with: ');
      console.log(data);
      // do whatever we need to do when someone bids?
      // update player card thingy with bid?
    },

    onBidWin: function(data) {
      // TODO(sdspikes): update display with winning bid
    },

    notif_trickWin: function(notif) {
      // We do nothing here (just wait in order players can view the 4 cards played before they're gone.
    },
    notif_giveAllCardsToPlayer: function(notif) {
      // Move all cards on table to given table, then destroy them
      var winner_id = notif.args.player_id;
      for (var player_id in this.gamedatas.players) {
        var anim = this.slideToObject(
          'cardontable_' + player_id,
          'overall_player_board_' + winner_id
        );
        dojo.connect(anim, 'onEnd', function(node) {
          dojo.destroy(node)
        });
        anim.play();
      }
    },
    notif_newScores: function(notif) {
      // Update players' scores
      for (var player_id in notif.args.newScores) {
        this.scoreCtrl[player_id].toValue(
          notif.args.newScores[player_id]
        );
      }
    }
  })
})
