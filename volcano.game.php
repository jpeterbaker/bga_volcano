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
  * volcano.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Volcano extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName(){
		// Used for translations and stuff. Please do not modify.
        return "volcano";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() ){    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player ){
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

		////////////////////////////////////////////////////////
		// Build up one big SQL command for all nested pieces //
		////////////////////////////////////////////////////////
		$sql = 'INSERT INTO Pieces (color,pips,x,y,z) VALUES (';
		for($x=0;$x<5;$x++){
			for($y=0;$y<5;$y++){
                $color = bga_rand(1,9);
				for($pips=1;$pips<=3;$pips++){
					$z = $pips-1;
                    $sql .= implode(',',[$color,$pips,$x,$y,$z]).'),(';
				}
			}
		}
		// Remove the final ',('
		$sql=substr($sql,0,-2);
		self::DbQuery($sql);

        ////////////////
        // Setup caps //
        ////////////////
        // Shuffle the squares
        $squares = range(0,24);
        shuffle($squares);
		$sql = 'INSERT INTO Pieces (color,pips,x,y,z) VALUES (';
        // Put caps on the first 5
        for($i=0;$i<5;$i++){
            $k = $squares[$i];
            $x = $k%5;
            $y = intdiv($k,5);
            $sql .= implode(',',[0,1,$x,$y,3]).'),(';
        }
		// Remove the final ',('
		$sql=substr($sql,0,-2);
		self::DbQuery($sql);
          
        /////////////////
        // Setup stats //
        /////////////////
        self::initStat('player','turns_number',0);
        self::initStat('player','captures_large',0);
        self::initStat('player','captures_medium',0);
        self::initStat('player','captures_small',0);
        self::initStat('player','trees_mixed',0);
        self::initStat('player','trees_monochrome',0);
        self::initStat('player','power_plays',0);

        /************ End of the game initialization *****/
        $this->activeNextPlayer();
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(){
        $result = array();
    
        // result['players'] seems to get populated automatically with abbreviated column names
  
        $sql = "SELECT * FROM Pieces";
        $result['pieces'] = self::getCollectionFromDb( $sql );
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression(){
        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    function say($s){
        // For JSON like things, use json_encode($x)
        self::notifyAllPlayers('notif_debug',$s,[]);
    }
    function array_key_first($x){
        foreach($x as $key=>$value)
            return $key;
        return NULL;
    }
    function on_board($x,$y){
        return $x>=0 && $x<5 && $y>=0 && $y<5;
    }
    function capped($x,$y){
        $sql = "SELECT piece_id FROM Pieces
            WHERE x=${x} AND y=${y} AND color=0";
        $result = self::getCollectionFromDb($sql);
        return count($result) > 0;
    }
    // Count pieces in stack
    function get_height($x,$y){
        $sql = "SELECT piece_id FROM Pieces
            WHERE x=${x} AND y=${y}";
        $result = self::getCollectionFromDb($sql);
        return count($result);
    }

    function flow($x0,$y0,$z0,$x1,$y1){
        $z1 = $this->get_height($x1,$y1);
//        $this->say("flowing from ${x0} ${y0} ${z0} to ${x1} ${y1} ${z1}");
        // The piece that gets landed on
        $piece1 = $this->top($x1,$y1);
        $piece = $this->top($x0,$y0);

        $piece_id  = $piece['piece_id'];
        $sql = "UPDATE Pieces
            SET x=${x1},y=${y1},z=${z1}
            WHERE piece_id=${piece_id}";
        self::DbQuery($sql);
        self::notifyAllPlayers(
            'notif_flow',
            '',
			[
                'piece_id' => $piece_id,
                'x'      => $x1,
                'y'      => $y1,
                'z'      => $z1
			]
        );
        ///////////////////////
        // Check for capture //
        ///////////////////////
        if(!is_null($piece1) && $piece['pips'] == $piece1['pips'])
            $this->capture($piece);
    }

    function capture($piece){
        $piece_id = $piece['piece_id'];
//        $piece_str = $this->piece_str($piece);
        $x = $piece['x'];
        $y = $piece['y'];
        $player_id = $this->getActivePlayerId();
        $sql = "UPDATE Pieces
            SET x=NULL,y=NULL,z=NULL,owner_id=${player_id}
            WHERE piece_id=${piece_id}";
        self::DbQuery($sql);
        self::notifyAllPlayers(
            'notif_capture',
            clienttranslate('${player_name} captures a piece from (${x},${y})'),
			[
                'player_name' => $this->getActivePlayerName(),
                'player_id'   => $this->getActivePlayerId(),
                'piece_id'    => $piece_id,
                'x'           => $x,
                'y'           => $y
			]
        );
    }

    // DB row of top piece on space x,y (NULL if none)
    // To avoid an extra query, provide the z coordinate if known
    function top($x,$y,$z=NULL){
        if(is_null($z))
            $z = $this->get_height($x,$y) - 1;
        $sql = "SELECT * from Pieces
            WHERE x=${x} AND y=${y} AND z=${z}";
        $result = self::getCollectionFromDb($sql);
        if(count($result)==0){
            return NULL;
        }
        return $result[$this->array_key_first($result)];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in volcano.action.php)
    */

    function move_cap($x0,$y0,$x1,$y1){
        // Check that destination is on board
        if(!$this->on_board($x1,$y1)){
			throw new BgaVisibleSystemException(
				self::_('That space is not on the board.')
            );
        }

        // Check move distance
        $dx = $x1-$x0;
        $dy = $y1-$y0;
        $dist = max(abs($dx),abs($dy));
        if($dist!=1){
			throw new BgaUserException(
				self::_('Caps must move exactly 1 space.')
            );
        }

        // Get moving cap's id
        $sql = "SELECT piece_id FROM Pieces
            WHERE x=${x0} AND y=${y0} AND color=0";
        $result = self::getCollectionFromDb($sql);
        if(count($result)==0){
			throw new BgaUserException(
				self::_('There must be a cap on origin space.')
            );
        }

        $cap_id = $this->array_key_first($result);

        // Ensure desitation has no cap
        $sql = "SELECT piece_id FROM Pieces
            WHERE x=${x1} AND y=${y1} AND color=0";
        $result = self::getCollectionFromDb($sql);
        if(count($result)!=0){
			throw new BgaUserException(
				self::_('Destination space must not have cap.')
            );
        }

        $z1 = $this->get_height($x1,$y1);

        // Update DB with cap movement
        $sql = "UPDATE Pieces
            SET x=${x1},y=${y1},z=${z1}
            WHERE piece_id=${cap_id}";
        self::DbQuery($sql);
        
        self::notifyAllPlayers(
            'notif_move_cap',
            clienttranslate('${player_name} moves a cap from (${x0},${y0}) to (${x},${y})'),
			[
                'player_name' => $this->getActivePlayerName(),
                'piece_id'    => $cap_id,
                'x0'          => $x0,
                'y0'          => $y0,
                'x'          => $x1,
                'y'          => $y1,
                'z'          => $z1

			]
        );

        ////////////////////
        // Eruption check //
        ////////////////////
        $destx = $x1;
        $desty = $y1;
        $source_height = $this->get_height($x0,$y0);
        //Flow lava while it makes sense
        while($source_height>0){
            $destx += $dx;
            $desty += $dy;

            // Off the board?
            if(!$this->on_board($destx,$desty)){
                break;
            }
            // Capped?
            if($this->capped($destx,$desty)){
                break;
            }

            $this->flow($x0,$y0,$source_height-1,$destx,$desty);

            $source_height--;
        }
    }

    function act_power_play($piece_id,$x,$y){
        /*
        Verify player has the piece
        Ensure that space isn't capped
        Put piece onto that space
        Notify players
        */
    }

    /*
    
    Example:

    function playCard( $card_id ){
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function args_after_move_cap(){
    }
    function args_after_power_play(){
    }
    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState(){
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    function st_after_move_cap(){
    }
    function st_after_power_play(){
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player ){
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer"){
            switch ($statename){
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer"){
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version ){
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
