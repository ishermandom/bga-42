<?php

// Meta-information for Texas 42 implementation on BoardGameArena.
//
// After modifying this file, click on "Reload game informations" from the BGA
// Control Panel in order for changes to take effect.

// Docs: http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
$gameinfos = array(

// Game designer (or game designers, separated by commas)
'designer' => 'W. A. Thomas',

// Game artist (or game artists, separated by commas)
'artist' => 'Unknown',

// Year of first publication of this game.
'year' => 1885,

// Game publisher.
'publisher' => 'Public Domain',

// Url of game publisher website.
'publisher_website' => '',

// Board Game Geek ID of the publisher.
// Public domain: https://boardgamegeek.com/boardgamepublisher/171/public-domain
'publisher_bgg_id' => 171,

// Board game geek ID of the game: https://boardgamegeek.com/boardgame/12131/42
'bgg_id' => 12131,

// Number of players.
'players' => array( 4 ),

// Suggested number of players. `null` because there is only one possible
// player configuration.
'suggest_player_number' => null,

// Discourage players to play with these numbers of players. `null` because
// there is only one possible player configuration.
'not_recommend_player_number' => null,

// Estimated game duration, in minutes (used only for the launch, afterward the
// real duration is computed).
'estimated_duration' => 30,

// Time in seconds given to a player when "giveExtraTime" is called (speed
// profile = fast)
'fast_additional_time' => 30,

// Time in seconds given to a player when "giveExtraTime" is called (speed
// profile = medium)
'medium_additional_time' => 40,

// Time in seconds given to a player when "giveExtraTime" is called (speed
// profile = slow)
'slow_additional_time' => 50,

// TODO(isherman): Do we want to implement a tie-breaker? Probably not, and
// instead implement win-by-one or win-by-two – right?
// If you are using a tie breaker in your game (using "player_score_aux"), you
// must describe here the formula used to compute "player_score_aux". This
// description will be used as a tooltip to explain the tie breaker to the
// players.
// Note: if you are NOT using any tie breaker, leave the empty string.
//
// Example: 'tie_breaker_description' => totranslate( "Number of remaining cards in hand" ),
'tie_breaker_description' => "",

// Losers are not ranked relative to each other in this game, since you win or
// lose as a team.
'losers_not_ranked' => true,

// Not a solo-only game.
'solo_mode_ranked' => false,

// Game is "beta". A game MUST set is_beta=1 when published on BGA for the
// first time, and must remains like this until all bugs are fixed.
'is_beta' => 1,

// Not a cooperative game.
'is_coop' => 0,

// Players don't have to all speak the same language.
'language_dependency' => false,

// TODO(isherman): Configure each of complexity, luck, strategy, and diplomacy.
// Complexity of the game, from 0 (extremely simple) to 5 (extremely complex).
'complexity' => 2,

// Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally
// luck driven).
'luck' => 2,

// Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based
// on strategy).
'strategy' => 3,

// Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally
// based on interaction and discussion between players)
'diplomacy' => 3,

// Colors attributed to players.
'player_colors' => array( "ff0000", "008000", "0000ff", "ffa500", "773300" ),

// Favorite colors support: if set to "true", support attribution of favorite
// colors based on player's preferences (see
// reattributeColorsBasedOnPreferences PHP method)
'favorite_colors_support' => true,

// TODO(isherman): I'm not actually sure that BGA APIs will let us chose teams
// as part of gameplay, so we might want to flip this to 'true' instead.
// Rotate the starting player on every rematch. Note that teams are chosen
// during game play.
'disable_player_order_swap_on_rematch' => false,

// TODO(isherman): Configure the min width.
// Game interface width range, in pixels.
// The "game interface" refers to space on the left side, without the column on
// the right.
'game_interface_width' => array(
    // Minimum width
    //  default: 740
    //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
    //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
    'min' => 740,

    // Maximum width
    //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
    //  maximum possible value: unlimited
    //  minimum possible value: 740
    'max' => null
),

// TODO(isherman): Fill this out.
// Game presentation
// Short game presentation text that will appear on the game description page, structured as an array of paragraphs.
// Each paragraph must be wrapped with totranslate() for translation and should not contain html (plain text without formatting).
// A good length for this text is between 100 and 150 words (about 6 to 9 lines on a standard display)
'presentation' => array(
//    totranslate("This wonderful game is about geometric shapes!"),
//    totranslate("It was awarded best triangle game of the year in 2005 and nominated for the Spiel des Jahres."),
//    ...
),

// Games categories, ordered by relevance.
// Docs: https://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php#Tags
// 1: Abstract game
//    Most card games seem to fit here, though some are categorized as
//    "2 - casual game" instead
// 11: Medium-length, 11 to 30 minutes
// 23: Classic game from public domain
// 200: Card game (a bit of a stretch...)
'tags' => array( 1, 23, 11, 200 ),


//////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)

// simple : A plays, B plays, C plays, A plays, B plays, ...
// circuit : A plays and choose the next player C, C plays and choose the next player D, ...
// complex : A+B+C plays and says that the next player is A+B
'is_sandbox' => false,
'turnControl' => 'simple'

////////
);
