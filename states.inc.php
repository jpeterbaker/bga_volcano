<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Volcano implementation : © Jonathan Baker <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * states.inc.php
 *
 * Volcano game states description
 *
 */

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => array( '' => 2 )
    ),
    
    2 => array(
        'name' => 'player_turn',
        'description' => clienttranslate('${actplayer} must move a cap'),
        'descriptionmyturn' => clienttranslate('${you} must move a cap'),
        'type' => 'activeplayer',
        'possibleactions' => array( 'act_move_cap', 'act_power_play' ),
        'transitions' => array('trans_end_turn' => 3)
    ),
    
    3 => array(
        'name' => 'after_eruption',
        'type' => 'game',
        'args' => 'args_after_eruption',
        'action' => 'st_after_eruption',
        'transitions' => array('trans_player_turn' => 2, 'trans_end_game' => 99)
    ),
    
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd'
    )
);

