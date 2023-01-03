/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Volcano implementation : © Jonathan Baker <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * volcano.js
 *
 * Volcano user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.volcano", ebg.core.gamegui, {
	constructor: function(){
		// Here, you can init the global variables of your user interface
		// Example:
		// this.myGlobalValue = 0;

	},

	setup: function( gamedatas ) {
		///////////////////////////////////////
		// Make interface reflect game state //
		///////////////////////////////////////
		var piece_id,cell,html;
		for(piece_id in gamedatas.pieces) {
			piece = gamedatas.pieces[piece_id];
			cell = document.getElementById('VOLcell_'+piece.x+'_'+piece.y);
			html = this.format_block(
				'jstpl_piece',
				{
					piece_id: piece.id,
					colornum: piece.color,
					pipsnum:  piece.pips,
					z      :  piece.z
				}
			);
			dojo.place(html,cell,piece.z);
		}
		this.connectClass('VOLpiece','onclick','piece_clicked');

		this.selected=null;

		// Setup game notifications to handle (see "setupNotifications" method below)
		this.setupNotifications();
	},


	///////////////////////////////////////////////////
	//// Game & client states

	// onEnteringState: this method is called each time we are entering into a new game state.
	//                  You can use this method to perform some user interface changes at this moment.
	//
	onEnteringState: function( stateName, args ) {
		console.log( 'Entering state: '+stateName );

		switch( stateName ) {

		/* Example:

		case 'myGameState':

			// Show some HTML block at this game state
			dojo.style( 'my_html_block_id', 'display', 'block' );

			break;
	   */


		case 'dummmy':
			break;
		}
	},

	// onLeavingState: this method is called each time we are leaving a game state.
	//                 You can use this method to perform some user interface changes at this moment.
	//
	onLeavingState: function( stateName ) {
		console.log( 'Leaving state: '+stateName );

		switch( stateName ) {

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

	// onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
	//                        action status bar (ie: the HTML links in the status bar).
	//
	onUpdateActionButtons: function( stateName, args ) {
		console.log( 'onUpdateActionButtons: '+stateName );

		if( this.isCurrentPlayerActive() ) {
			switch( stateName ) {
/*
			 Example:

			 case 'myGameState':

				// Add 3 action buttons in the action status bar:

				this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
				this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
				this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
				break;
*/
			}
		}
	},

	///////////////////////////////////////////////////
	//// Utility methods

	get_piece_id: function(node){
		return node.id.split('_')[1];
	},

	get_space: function(node){
		// Get the space this piece is on
		var par = node;
		while(!dojo.hasClass(par,'VOLspace')){
			par = par.parentNode;
			if(par === undefined || par.id === undefined){
				return null;
			}
		}
		return par;
	},

	get_xy: function(space){
		return space.id.split('_').slice(1);
	},
	get_color: function(piecenode){
		return parseInt(piecenode.getAttribute('ptype').split('_')[0]);
	},
	get_size: function(piecenode){
		return parseInt(piecenode.getAttribute('ptype').split('_')[1]);
	},
    get_piece: function(piece_id){
        return document.getElementById('VOLpiece_'+piece_id);
    },

    ajaxcallwrapper: function(action, args, err_handler) {
        // this allows to skip args parameter for action which do not require them
        if (!args)
            args = [];
        // Avoid rapid clicking problems
        args.lock = true;
        // Check that player is active and action is declared
        if (this.checkAction(action)) {
            // this is mandatory fluff
            this.ajaxcall(
                "/" + this.game_name + "/" + this.game_name + "/" + action + ".html",
                args,
                this,
                // Success result handler is mandatory argument but not needed
                // If everything goes as expected, notifications are used to update
                (result) => {},
                // The optional error handler param  is "seldom needed"
                err_handler
            );
        }
    },

	///////////////////////////////////////////////////
	//// Player's action

	piece_clicked: function(evt){
		evt.preventDefault();
		var node = evt.currentTarget;
		if(this.selected==null){
            // Piece has just been selected
			this.selected=node;
			console.log('Selected',node);
			return;
		}
        // Selected piece should be moved to this square
		var xy = this.get_xy(this.get_space(node));
		var id = this.get_piece_id(this.selected);
        this.ajaxcallwrapper(
            'act_move_cap',
            {
                cap_id: id,
                x: xy[0],
                y: xy[1]
            }
        );
        this.selected=null;
	},

	///////////////////////////////////////////////////
	//// Reaction to cometD notifications

	/*
		setupNotifications:

		In this method, you associate each of your game notifications with your local method to handle it.

		Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
			  your volcano.game.php file.

	*/
	setupNotifications: function() {

		// Example 2: standard notification handling + tell the
		// user interface to wait 3 seconds after the method before next notif
		// dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
		// this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
		//
		dojo.subscribe('notif_debug',this,'ignore_notif');

		dojo.subscribe('notif_move_cap',this,'move_cap_from_notif');
		dojo.subscribe('notif_power_play',this,'power_play_from_notif');
	},

	// Ignore the notification. The text will simply appear in the log
	ignore_notif: function(notif){},

	move_cap_from_notif: function(notif){
        var cap_id = notif.args.cap_id;
        var x = notif.args.x;
        var y = notif.args.y;
        var z = notif.args.z;
        var piece = this.get_piece(cap_id);
        var space = document.getElementById('VOLcell_'+x+'_'+y);
        dojo.place(piece,space);
        piece.Volz = z;
    },
	power_play_from_notif: function(notif){
        var piece_id = notif.args.piece_id;
        var x = notif.args.x;
        var y = notif.args.y;
    },

});
});
