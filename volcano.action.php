<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Volcano implementation : © Jonathan Baker <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * volcano.action.php
 *
 * Volcano main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/volcano/volcano/myAction.html", ...)
 *
 */


class action_volcano extends APP_GameAction{
    // Constructor: please do not modify
    public function __default(){
        if( self::isArg( 'notifwindow') ){
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else{
            $this->view = "volcano_volcano";
            self::trace( "Complete reinitialization of board game" );
        }
    }

    public function act_move_cap(){
        self::setAjaxMode();

        // Piece ID of the cap
        $cap_id = self::getArg('cap_id',AT_posint,true);
        // Destination space
        $x = self::getArg('x',AT_posint,true);
        $y = self::getArg('y',AT_posint,true);

        $this->game->move_cap($cap_id,$x,$y);

        self::ajaxResponse();
    }

    public function act_power_play(){
        self::setAjaxMode();

        // Piece ID of the cap
        $piece_id = self::getArg('piece_id',AT_posint,true);
        // Destination space
        $x = self::getArg('x',AT_posint,true);
        $y = self::getArg('y',AT_posint,true);

        $this->game->power_play($piece_id,$x,$y);

        self::ajaxResponse();
    }
}

