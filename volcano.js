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

	setup: function(gamedatas) {
        ////////////////////////
        // Make capture areas //
        ////////////////////////
//        console.log(gamedatas);
        var board = document.getElementById('VOLtemp_display');
        var player_id,player,name,html;
        for(player_id in gamedatas.players){
            player = gamedatas.players[player_id];
            name = player.name;
			html = this.format_block(
				'jstpl_captures',
				{
					player_id: player_id,
					player_name: name
				}
			);
            dojo.place(html,board,'after');
        }
        //////////////////////
        // Make score areas //
        //////////////////////
        for(player_id in gamedatas.players){
            player = gamedatas.players[player_id];
            board = document.getElementById('player_board_'+player_id);
			html = this.format_block(
				'jstpl_scores',
				{
					player_id: player_id
				}
			);
            var x=dojo.place(html,board,'last');
            console.log('board',board);
            console.log('x',x);
            this.update_scores(player_id,player.trees,player.pures);
        }

		//////////////////////////////////
		// Put pieces on correct spaces //
		//////////////////////////////////
		var piece_id,cell;
		for(piece_id in gamedatas.pieces) {
			piece = gamedatas.pieces[piece_id];
            if(piece.z == null)
                piece.z = 'none';
			html = this.format_block(
				'jstpl_piece',
				{
					piece_id: piece_id,
					colornum: piece.color,
					pipsnum:  piece.pips,
					z      :  piece.z
				}
			);
            if(piece.owner_id != null){
                // Put it on the board for starters
                // It needs to exist for stow to work
                dojo.place(html,board,'after');
                this.stow(piece.piece_id,piece.owner_id);
                continue;
            }
			cell = this.get_space(piece.x,piece.y);
			dojo.place(html,cell);
		}
        //////////////////////
        // Order the pieces //
        //////////////////////
        var x,y,children,child;
        for(cell of dojo.query('.VOLcell')){
                // Make an array of the children
                children = [...cell.children];
                children.sort(this.compare_z);
                // Place the children in order
                for(child of children){
                    dojo.place(child,cell,'first');
                }
        }

		this.connectClass('VOLcell','onclick','cell_clicked');

		this.selected=null;

		// Setup game notifications to handle (see "setupNotifications" method below)
		this.setupNotifications();
	},

    // piece is an object provided by server
    // Place corresponding node in owner's collection
	stow: function(piece_id,owner_id) {
        var node = this.get_piece(piece_id);
        var pips = this.get_size(node);
        var collection = document.getElementById('VOLcaptures_'+owner_id);
        var row = dojo.query('.VOLrow_'+pips,collection)[0];
        dojo.place(node,row);
    },
    

	///////////////////////////////////////////////////
	//// Game & client states

	onEnteringState: function(stateName,args){
        // Things added by args function on client side are in args.args
		switch(stateName){
            case 'after_eruption':
                console.log(args);
                this.update_scores(args.active_player,args.args.trees,args.args.pures);
                break;
		}
	},

	onLeavingState: function( stateName ) {
		switch( stateName ) {
		case 'dummmy':
			break;
		}
	},

	// onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
	//                        action status bar (ie: the HTML links in the status bar).
	//
	onUpdateActionButtons: function( stateName, args ) {
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
    get_piece: function(piece_id){
        return document.getElementById('VOLpiece_'+piece_id);
    },

	get_resting_space: function(node){
		// Get the space this piece is on
		var par = node;
		while(!dojo.hasClass(par,'VOLcell')){
			par = par.parentNode;
			if(par === undefined || par.id === undefined){
				return null;
			}
		}
		return par;
	},
    get_space: function(x,y){
        return document.getElementById('VOLcell_'+x+'_'+y);
    },

	get_xy: function(space){
		return space.id.split('_').slice(1);
	},
	get_color: function(piecenode){
		return parseInt(piecenode.getAttribute('VOLcolor'));
	},
	get_size: function(piecenode){
		return parseInt(piecenode.getAttribute('VOLpips'));
	},

    update_scores(player_id,trees,pures){
        var span = document.getElementById('VOLtrees_'+player_id);
        console.log('id and span');
        console.log('VOLtrees_'+player_id);
        console.log(span);
        span.innerText = trees;
        span = document.getElementById('VOLpures_'+player_id);
        span.innerText = pures;
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

    // Comparison function for ordering piece nodes by z value
    compare_z: function(a,b){
        return Math.sign(a.getAttribute('VOLz')-b.getAttribute('VOLz'));
//        var result = Math.floor(Math.random()*2)*2 - 1;
//        return result;
    },

	///////////////////////////////////////////////////
	//// Player's action

	cell_clicked: function(evt){
		evt.preventDefault();
		var space = evt.currentTarget;
		if(this.selected==null){
            // Cell has just been selected
            // But ignore selection if cell is empty
            if(space.children.length>0){
                this.selected=space;
                dojo.addClass(this.selected,'VOLselected');
            }
			return;
		}
        // Selected piece should be moved to this square
		var xy = this.get_xy(space);
        var piece = this.selected.children[0];
		var id = this.get_piece_id(piece);
        var oldxy = this.get_xy(this.get_resting_space(piece));
        this.ajaxcallwrapper(
            'act_move_cap',
            {
                oldx: oldxy[0],
                oldy: oldxy[1],
                x: xy[0],
                y: xy[1]
            }
        );
        dojo.removeClass(this.selected,'VOLselected');
        this.selected=null;
	},

	///////////////////////////////////////////////////
	//// Reaction to cometD notifications

	setupNotifications: function() {

		// Example 2: standard notification handling + tell the
		// user interface to wait 3 seconds after the method before next notif
		// dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
		// this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
		//
		dojo.subscribe('notif_debug',this,'ignore_notif');

		dojo.subscribe('notif_move_cap',this,'move_from_notif');
		dojo.subscribe('notif_flow',this,'move_from_notif');
		dojo.subscribe('notif_capture',this,'capture_from_notif');

		dojo.subscribe('notif_power_play',this,'power_play_from_notif');

		dojo.subscribe('notif_end_game',this,'ignore_notif');
	},

	// Ignore the notification. The text will simply appear in the log
	ignore_notif: function(notif){},

	move_from_notif: function(notif){
        var piece_id = notif.args.piece_id;
        var x = notif.args.x;
        var y = notif.args.y;
        var z = notif.args.z;

        var piece = this.get_piece(piece_id);
        var space = this.get_space(x,y);
        dojo.place(piece,space,'first');
        piece.setAttribute('VOLz',z);
    },

    capture_from_notif: function(notif){
        var piece_id = notif.args.piece_id;
        var player_id = notif.args.player_id;
        this.stow(piece_id,player_id);
    },

    // Put a piece back on the board
	power_play_from_notif: function(notif){
        var piece_id = notif.args.piece_id;
        var x = notif.args.x;
        var y = notif.args.y;
    },

});
});
