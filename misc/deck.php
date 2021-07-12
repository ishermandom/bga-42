<?php

const NUM_SUITS = (6 - 0) + 1;  // All suits between 0 and 6.
const NUM_PLAYERS = 4;
const NO_TRUMP = -1;
const DOUBLES_AS_TRUMP = -2;
$DECK = buildDeck();
$HAND_SIZE = count($DECK) / NUM_PLAYERS;

class DominoPlayground {
  public function __construct(int $suit, int $rank) {
    $this->suit = $suit;
    $this->rank = $rank;
  }

  public function __destruct() {
    //printf("destroying [%d, %d]\n", $this->suit, $this->rank);
  }

  public function isDouble(): bool {
    return $this->suit === $this->rank;
  }

  public int $suit;
  public int $rank;
}

/** @return array<DominoPlayground> */
function buildDeck(): array {
  $deck = [];
  for ($i = 0; $i < NUM_SUITS; ++$i) {
    for ($j = 0; $j <= $i; ++$j) {
      array_push($deck, new DominoPlayground($i, $j));
    }
  }
  return $deck;
}

function compareDominoes(DominoPlayground $a, DominoPlayground $b): int {
  if ($a->suit === $b->suit) {
    return $a->rank <=> $b->rank;
  }
  return $a->suit <=> $b->suit;
}

// Note: Assumes that the suit vs. rank have already been
// disambiguated based on the play!
function beats(DominoPlayground $a, DominoPlayground $b, int $trump): bool {
  if ($trump === DOUBLES_AS_TRUMP) {
    if ($a->isDouble() && $b->isDouble()) {
      return $a->suit > $b->suit;
    }
    if ($a->isDouble()) {
      return true;
    }
    if ($b->isDouble()) {
      return false;
    }
  }

  if ($a->suit === $b->suit) {
    // Doubles are high.
    if ($a->isDouble()) {
      return true;
    }
    if ($b->isDouble()) {
      return false;
    }
    return $a->rank > $b->rank;
  }
  if ($b->suit === $trump) {
    return false;
  }
  return true;
}

function printBeats(DominoPlayground $a, DominoPlayground $b, int $trump): void {
  printf(
    "[%d, %d] beats [%d, %d] with trump=%d ? %s\n",
    $a->suit,
    $a->rank,
    $b->suit,
    $b->rank,
    $trump,
    beats($a, $b, $trump) ? "true" : "false"
  );
}

for ($suit1 = 0; $suit1 < NUM_SUITS; ++$suit1) {
  for ($rank1 = 0; $rank1 <= $suit1; ++$rank1) {
    for ($suit2 = 0; $suit2 < NUM_SUITS; ++$suit2) {
      for ($rank2 = 0; $rank2 <= $suit2; ++$rank2) {
        for ($trump = DOUBLES_AS_TRUMP; $trump < NUM_SUITS; ++$trump) {
          if ($suit1 === $suit2 && $rank1 === $rank2) {
            continue;
          }
          printBeats(
            new DominoPlayground($suit1, $rank1),
            new DominoPlayground($suit2, $rank2),
            $trump
          );
        }
      }
    }
  }
}

//print_r($deck);

shuffle($DECK);
//print_r($deck[0]);

$hands = [];
for ($i = 0; $i < NUM_PLAYERS; ++$i) {
  $hand = array_slice($DECK, $i * $HAND_SIZE, $HAND_SIZE);
  usort($hand, "compareDominoes");
  array_push($hands, $hand);
}

//print_r($hands);
