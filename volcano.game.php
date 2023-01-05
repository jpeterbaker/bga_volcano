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

function zeros($i,$j){
    // Return a 2D array full of zeros with i,j as indices
    $m = count($i);
    $n = count($j);
    $x = [];
    foreach($i as $a){
        $x[$a] = [];
        foreach($j as $b){
            $x[$a][$b] = 0;
        }
    }
    return $x;
}

function on_board($x,$y){
    return $x>=0 && $x<5 && $y>=0 && $y<5;
}

// Check the up-to-four spaces orthogonally adjacent to x,y
// for any that are of the given color
function borders_same($board,$x,$y,$color){
    for($d=-1 ; $d<2 ; $d+=2){
        $x1 = $x+$d;
        if(on_board($x1,$y) && $board[$x1][$y]==$color)
            return true;
        $y1 = $y+$d;
        if(on_board($x,$y1) && $board[$x][$y1]==$color)
            return true;
    }
    return false;
}

class Volcano extends Table {
	function __construct( ) {
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
        foreach($players as $player_id => $player){
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        //self::setGameStateInitialValue('my_global_variable',0);

        // Give players a few pieces to speed up testing
        if(1){
            $i=0;
            $sql = 'INSERT INTO Pieces (color,pips,owner_id) VALUES (';
            foreach($players as $player_id => $player){
                for($pips=1;$pips<3;$pips++){
                    for($color=2*$i+1;$color<2*$i+3;$color++){
                        for($j=0;$j<3;$j++)
                            $sql .= implode(',',[$color,$pips,$player_id]).'),(';
                    }
                }
                $i++;
            }
            // Remove the final ',('
            $sql=substr($sql,0,-2);
            self::DbQuery($sql);
        }

        $this->fiesta_caldera_setup();

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

    protected function getAllDatas(){
        $result = array();
    
        // result['players'] seems to get populated automatically with abbreviated column names
        // but you can add things yourself here, too
        $sql = 'SELECT player_id,trees,pures FROM player';
        $result['players'] = self::getCollectionFromDb($sql);
  
        $sql = "SELECT * FROM Pieces";
        $result['pieces'] = self::getCollectionFromDb($sql);
  
        return $result;
    }

    function getGameProgression(){
        return 50;
    }

    function fiesta_caldera_setup(){
        /*
        Put pieces on board with Pyramid Arcade rules:
        5x5 board, center square empty
        3 trios in 8 colors
        6 caps start on the orange and red nests
        */

        // 1D indices of spaces that need to be filled
        $tofill = range(0,24);
        // Mark the center square as used
        $tofill[12] = NULL;
        // Randomize the order
        shuffle($tofill);
        // Will be filled with color numbers where nests should be placed
        $board = zeros(range(0,4),range(0,4));

        // Board space to try next
        $fi = 0;
        // Iterate over pieces
        for($i=0;$i<24;$i++){
            $color = $i%8 + 1;
            $placed = false;
            // Be picky about placement the first time, then loosen up
            for($picky=1 ; $picky>=0 ; $picky--){
                // Iterate over candidate spaces
                for($j=0;$j<25;$j++){
                    $fi++;
                    $fi %= 25;
                    $k = $tofill[$fi];
                    // Check if it's filled already
                    if(is_null($k))
                        continue;
                    $x = intdiv($k,5);
                    $y = $k%5;
                    if($picky && borders_same($board,$x,$y,$color)){
                        // We are still checking every square for the first time
                        // (while being picky)
                        // and it borders a same-colored nest
                        continue;
                    }
                    // x,y is a good place for this nest
                    $board[$x][$y] = $color;
                    $tofill[$fi] = NULL;
                    $placed = true;
                    break;
                }
                if($placed){
                    // Nest was placed while still being picky
                    break;
                }
            }
            if(!$placed){
                throw new BgaVisibleSystemException(
                    self::_('Could not place all pieces.')
                );
            }
        }
        /*
        $board = [
            [1,1,3,2,2],
            [1,3,3,4,2],
            [8,8,0,4,4],
            [7,8,6,6,5],
            [7,7,6,5,5]
        ];
        */

		///////////////////////////////////
		// Build up one big SQL command  //
        // for all nested pieces and caps//
		///////////////////////////////////
		$sql = 'INSERT INTO Pieces (color,pips,x,y,z) VALUES (';
		for($x=0;$x<5;$x++){
			for($y=0;$y<5;$y++){
                $color = $board[$x][$y];
                if($color==0)
                    continue;
				for($pips=1;$pips<=3;$pips++){
					$z = $pips-1;
                    $sql .= implode(',',[$color,$pips,$x,$y,$z]).'),(';
				}
                if($color == 1 || $color == 2){
                    // This is a red or orange nest, so add a cap
                    $sql .= implode(',',[0,1,$x,$y,3]).'),(';
                }
			}
		}
		// Remove the final ',('
		$sql=substr($sql,0,-2);
		self::DbQuery($sql);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    function say($s){
        // For JSON like things, use json_encode($x)
        self::notifyAllPlayers('notif_debug',$s,[]);
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
        return $result[array_key_first($result)];
    }

    function update_score($player_id){
        $trees = $this->count_trees($player_id);
        $sql = 'UPDATE player
            SET trees='.$trees['all'].',
                pures='.$trees['pure'].'
            WHERE player_id='.$player_id;
        self::DbQuery($sql);
    }

    // Count the mixed and pure trees of active player
    function count_trees($player_id){
        $sql = "SELECT piece_id,color,pips FROM Pieces
            WHERE owner_id=${player_id}";
        $pieces = self::getCollectionFromDb($sql);

        // An array of size counts
        $counts = [1=>0,2=>0,3=>0];
        // A 2D array of piece counts
        $subcounts = zeros(range(1,9),range(1,3));
        foreach($pieces as $piece_id => $piece){
            $subcounts[$piece['color']][$piece['pips']] += 1;
            $counts[$piece['pips']] += 1;
        }
        // The total number of trees
        $tree_count = min($counts);
        // For each color, the number of pure trees is the row min
        $pure_count = 0;
        for($i=1;$i<10;$i++){
            $pure_count += min($subcounts[$i]);
        }
        return ['all' => $tree_count, 'pure' => $pure_count];
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
        if(!on_board($x1,$y1)){
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

        $cap_id = array_key_first($result);

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
        $erupted = false;
        //Flow lava while it makes sense
        while($source_height>0){
            $destx += $dx;
            $desty += $dy;

            // Off the board?
            if(!on_board($destx,$desty)){
                break;
            }
            // Capped?
            if($this->capped($destx,$desty)){
                break;
            }
            // An eruption occurs this turn
            if(!$erupted){
                // This is the first flowing piece
                $this->say(clienttranslate('An eruption occurs!'));
                $erupted = true;
            }
            $this->flow($x0,$y0,$source_height-1,$destx,$desty);
            $source_height--;
        }
        if(!$erupted){
            // There was no eruption, so we can stay in the same game state
            return;
        }
        // Score update needs to happen before args and st functions
        $this->update_score($this->getActivePlayerId());
        $this->gamestate->nextState('trans_end_turn');
    }

    function power_play($piece_id,$x,$y){
        /*
        Verify player has the piece
        Ensure that space isn't capped
        Put piece onto that space
        Notify players
        */
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////
    // args to send to clients' onenetering function

    function args_after_eruption(){
        $player_id = $this->getActivePlayerId();
        $sql = 'SELECT player_id,trees,pures FROM player
            WHERE player_id='.$player_id;
        $result = self::getCollectionFromDb($sql);
        return [
            'trees' => $result[$player_id]['trees'],
            'pures' => $result[$player_id]['pures']
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    function st_after_eruption(){
        /*
        Check for victory
        Activate next player
        Change state to player_turn
        */
        // Score was updated by eruption function
        $player_id = $this->getActivePlayerId();
        $sql = 'SELECT player_id,trees,pures FROM player
            WHERE player_id='.$player_id;
        $result = self::getCollectionFromDb($sql);
        $trees = $result[$player_id]['trees'];
        $pures = $result[$player_id]['pures'];

        if($trees < 5 && $pures < 3){
            // Nobody has won yet
            self::activeNextPlayer();
            $this->gamestate->nextState('trans_player_turn');
            return;
        }
        // This player wins
        $player_id = $this->getActivePlayerId();
        $sql = "UPDATE player
            SET player_score=1
            WHERE player_id=${player_id}";
        self::DbQuery($sql);

        if($trees >= 5){
            $this->notifyAllPlayers(
                'notif_end_game',
                clienttranslate('${player_name} has collected ${trees} mixed-color trios'),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'trees' => $trees
                ]
            );
        }
        else{
            $this->notifyAllPlayers(
                'notif_end_game',
                clienttranslate('${player_name} has collected ${trees} monochrome trios'),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'trees' => $pures
                ]
            );
        }

        $this->gamestate->nextState('trans_end_game');
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
