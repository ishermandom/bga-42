{OVERALL_GAME_HEADER}

<!--
BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> &
                Emmanuel Colin <ecolin@boardgamearena.com>
Texas 42 implementation: © Stardust Spikes <sdspikes@cs.stanford.edu> &
                          Jason Turner-Maier <jasonptm@gmail.com> &
                          Ilya Sherman <ishermandom@gmail.com>
This code has been produced on the BGA studio platform for use on
http://boardgamearena.com. See http://en.boardgamearena.com/#!doc/Studio
for more information.

====

This file defines the layout for the game user interface.

****

BGA docs:
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks

    Please REMOVE this comment before publishing your game on BGA
-->

<div id="tabletop">
  <div id="playertables">

      <!-- BEGIN player -->
      <div class="playertable whiteblock playertable_{SEAT}">
          <div class="playertablename" style="color:#{PLAYER_COLOR}">
              {PLAYER_NAME}
          </div>
          <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
          </div>
      </div>
      <!-- END player -->

  </div>

  <div id="scores" class="whiteblock">
    <table>
      <thead>
        <th>Us</th>
        <th>Them</th>
      </thead>
      <tbody>
        <td>U marks</td>
        <td>T marks</td>
      </tbody>
    </table>
  </div>
</div>

<div id="hand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="hand">
    </div>
</div>


<script type="text/javascript">

// Javascript HTML templates

var jstpl_cardontable = '<div class="cardontable" id="cardontable_${player_id}" style="background-position:-${x}px -${y}px">\
                        </div>';

</script>

{OVERALL_GAME_FOOTER}
