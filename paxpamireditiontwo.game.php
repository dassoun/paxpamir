<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * PaxPamirEditionTwo implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * paxpamireditiontwo.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
require_once('modules/tokens.php');

class PaxPamirEditionTwo extends Table
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
            "setup" => 10,
            "remaining_actions" => 11,
            // "favored_suit" => 12,
            // "dominance_checks" => 13,
            "ruler_transcaspia" => 14,
            "ruler_kabul" => 15,
            "ruler_persia" => 16,
            "ruler_herat" => 17,
            "ruler_kandahar" => 18,
            "ruler_punjab" => 19,
            // "bribe_card_id" =>20,
            // "bribe_amount" =>21,
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );

        $this->tokens = new Tokens();
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "paxpamireditiontwo";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
        $number_of_players = count($players);

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, loyalty, rupees) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $loyalty = "null";
            $rupees = 4;
            // $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."','$loyalty','$rupees')";
            // $this->tokens->createTokensPack("token_".$player_id."_{INDEX}", "tokens_".$player_id, 10);
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        $this->tokens->createTokensPack("card_{INDEX}", "court_cards", 100);
        $this->tokens->createTokensPack("card_{INDEX}", "dom_cards", 4, 101);
        $this->tokens->createTokensPack("card_{INDEX}", "event_cards", 12, 105);
        $this->tokens->shuffle("court_cards");
        $this->tokens->shuffle("event_cards");

        // Init global values with their initial values
        self::setGameStateInitialValue( 'setup', 1 );
        self::setGameStateInitialValue( 'remaining_actions', 2 );
        // self::setGameStateInitialValue( 'favored_suit', 0 );
        // self::setGameStateInitialValue( 'dominance_checks', 0 );

        // TODO (Frans): set to null initially
        self::setGameStateInitialValue( 'ruler_transcaspia', 2371053 );
        self::setGameStateInitialValue( 'ruler_kabul', 2371053 );
        self::setGameStateInitialValue( 'ruler_persia', 2371053 );
        self::setGameStateInitialValue( 'ruler_herat', 2371053 );
        self::setGameStateInitialValue( 'ruler_kandahar', 2371053 );
        self::setGameStateInitialValue( 'ruler_punjab', 2371053 );

        // self::setGameStateInitialValue( 'bribe_card_id', 0 );
        // self::setGameStateInitialValue( 'bribe_amount', -1 );

        // build market deck based on number of players
        for ($i = 6; $i >=1; $i--) {
            $this->tokens->pickTokensForLocation($number_of_players+5, 'court_cards', 'pile');
            if ($i == 2) {
                $this->tokens->pickTokensForLocation(2, 'event_cards', 'pile');
            } elseif ($i > 2) {
                $this->tokens->pickTokensForLocation(1, 'event_cards', 'pile');
                $this->tokens->pickTokensForLocation(1, 'dom_cards', 'pile');
            }
            $this->tokens->shuffle('pile');
            $pile = $this->tokens->getTokensInLocation('pile');
            $n_cards = $this->tokens->countTokensInLocation('deck');
            foreach ( $pile as $id => $info) {
                // $this->tokens->insertTokenOnExtremePosition($id, 'deck', true);
                $this->tokens->moveToken($id, 'deck', $info['state'] + $n_cards);
            }
        }

        // Assign initial cards to
        for ($i = 0; $i < 6; $i++) {
            $this->tokens->pickTokensForLocation(1, 'deck', 'market_0_'.$i);
            $this->tokens->pickTokensForLocation(1, 'deck', 'market_1_'.$i);
        }

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, loyalty, rupees FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
  
        $result['deck'] = $this->tokens->getTokensOfTypeInLocation(null, 'deck', null, 'state');

        for ($i = 0; $i < 6; $i++) {
            // $result['market'][0][$i] = $this->tokens->getTokenOnTop('market_0_'.$i);
            $result['market'][0][$i] = $this->tokens->getTokenOnLocation('market_0_'.$i);
            $result['market'][1][$i] = $this->tokens->getTokenOnLocation('market_1_'.$i);
        }

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
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function checkDiscards( $player_id )
    {
        //
        // check for extra cards in hand and court
        //
        $result = array();
        $suits = $this->getPlayerSuits($player_id);
        $court_cards = $this->tokens->getTokensOfTypeInLocation('card', 'court_'.$player_id, null, 'state');
        $hand = $this->tokens->getTokensOfTypeInLocation('card', 'hand_'.$player_id, null, 'state');
        
        $result['court'] = count($court_cards) - $suits['political'] - 3;
        $result['court'] = max($result['court'], 0);

        $result['hand'] = count($hand) - $suits['intelligence'] - 2;
        $result['hand'] = max($result['hand'], 0);

        return $result;

    }

    function getAllRegionRulers() {
        
        $result = array();

        $result['ruler_transcaspia'] = $this->getGameStateValue( 'ruler_transcaspia' );
        $result['ruler_kabul'] = $this->getGameStateValue( 'ruler_kabul' );
        $result['ruler_persia'] = $this->getGameStateValue( 'ruler_persia' );
        $result['ruler_herat'] = $this->getGameStateValue( 'ruler_herat' );
        $result['ruler_kandahar'] = $this->getGameStateValue( 'ruler_kandahar' );
        $result['ruler_punjab'] = $this->getGameStateValue( 'ruler_punjab' );

        return $result;

    }

    // TODO (Frans): this probably needs prizes and gifts added?
    function getPlayerInfluence($player_id) {
        $influence = 1;
        $court_cards = $this->tokens->getTokensOfTypeInLocation('card', 'court_'.$player_id, null, 'state');
        for ($i = 0; $i < count($court_cards); $i++) {
            // $card_info = $this->token_types[$court_cards[$i]['key']];
            // $card_info['suit'] += $card_info['rank'];
        }
        return $influence;
    }

    function getPlayerLoyalty($player_id) {
        $sql = "SELECT loyalty FROM player WHERE  player_id='$player_id' ";
        return $this->getUniqueValueFromDB($sql);
    }

    function getPlayerRupees($player_id) {
        $sql = "SELECT rupees FROM player WHERE  player_id='$player_id' ";
        return $this->getUniqueValueFromDB($sql);
    }

    function getPlayerSuits($player_id) {
        $suits = array (
            'political' => 0,
            'military' => 0,
            'economic' => 0,
            'intelligence' => 0
        );
        $court_cards = $this->tokens->getTokensOfTypeInLocation('card', 'court_'.$player_id, null, 'state');
        for ($i = 0; $i < count($court_cards); $i++) {
            $card_info = $this->token_types[$court_cards[$i]['key']];
            $suits[$card_info['suit']] += $card_info['rank'];
        }
        return $suits;
    }

    function getUnavailableCards() {

        $result = array();
        
        for ($i = 0; $i < 2; $i++) {
            for ($j = 0; $j < 6; $j++) {
                $res = $this->tokens->getTokensOfTypeInLocation('card', 'market_'.$i.'_'.$j, 1 );
                $card = array_shift( $res );
                if (($card !== NULL) and ($card['state'] == 1)) {
                    $result[] = $card['key'];
                }
            }
        }

        return $result;

    }

    function incPlayerRupees($player_id, $value) {
        $rupees = $this->getPlayerRupees($player_id);
        $rupees += $value;
        $sql = "UPDATE player SET coins='$rupees' 
                WHERE  player_id='$player_id' ";
        self::DbQuery( $sql );
    }

    function setPlayerLoyalty($player_id, $coalition) {
        $sql = "UPDATE player SET loyalty='$coalition' 
        WHERE  player_id='$player_id' ";
        self::DbQuery( $sql );
    }

    function updatePlayerCounts() {
        $counts = array();
        $players = $this->loadPlayersBasicInfos();
        // $sql = "SELECT player_id id, player_score score, loyalty, coins FROM player ";
        // $result['players'] = self::getCollectionFromDb( $sql );
        foreach ( $players as $player_id => $player_info ) {
            $counts[$player_id] = array();
            $counts[$player_id]['rupees'] = $this->getPlayerRupees($player_id );
            $counts[$player_id]['tokens'] = count($this->tokens->getTokensOfTypeInLocation('token', 'tokens_'.$player_id ));
            $counts[$player_id]['cards'] = count($this->tokens->getTokensOfTypeInLocation('card', 'hand_'.$player_id ));
            $counts[$player_id]['suits'] = $this->getPlayerSuits($player_id);
            $counts[$player_id]['influence'] = $this->getPlayerInfluence($player_id);
        }

        self::notifyAllPlayers( "updatePlayerCounts", '', array(
            'counts' => $counts
        ) );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in paxpamireditiontwo.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
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

    function chooseLoyalty( $coalition )
    {
        //
        // select starting loyalty during game setup
        //

        self::checkAction( 'choose_loyalty' );

        $player_id = self::getActivePlayerId();
        $coalition_name = $this->loyalty[$coalition]['name'];

        $this->setPlayerLoyalty($player_id, $coalition);

        // Notify
        self::notifyAllPlayers( "chooseLoyalty", clienttranslate( '${player_name} selected ${coalition_name}.' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'coalition' => $coalition,
            'coalition_name' => $coalition_name
        ) );

        $this->gamestate->nextState( 'next' );

    }

    function cleanup( )
    {
        //
        // go to the next state for cleanup:  either discard court, discard hand or refresh market
        //

        $player_id = self::getActivePlayerId();
        $discards = $this->checkDiscards($player_id);

        if ($discards['court'] > 0) {
            $this->gamestate->nextState( 'discard_court' );
        } elseif ($discards['hand'] > 0) {
            $this->gamestate->nextState( 'discard_hand' );
        } else {
            $this->gamestate->nextState( 'refresh_market' );
        }

    }

    function purchaseCard( $card_id )
    {
        self::checkAction( 'purchase' );

        $player_id = self::getActivePlayerId();
        $card = $this->tokens->getTokenInfo($card_id);
        $card_name = $this->token_types[$card_id]['name'];
        $market_location = $card['location'];
        $row = explode("_", $market_location)[1];
        $row_alt = ($row == 0) ? 1 : 0;
        $col = $cost = explode("_", $market_location)[2];

        if ($this->getGameStateValue("remaining_actions") > 0) {

            if ($cost > $this->getPlayerRupees($player_id)) {
                throw new feException( "Not enough rupees" );
            } else {
                $this->incPlayerRupees($player_id, -$cost);
            };

            $this->tokens->moveToken($card_id, 'hand_'.$player_id);
            $this->incGameStateValue("remaining_actions", -1);

            $rupees = $this->tokens->getTokensOfTypeInLocation('rupee', $card_id);
            // $this->tokens->moveTokens($coins, 'pool');
            $this->incPlayerRupees($player_id, count($rupees));
            $this->tokens->moveAllTokensInLocation($card_id, 'pool');

            // self::notifyAllPlayers( "log", "purchaseCard", array(
            //     'card' => $card,
            //     'col' => $col,
            //     'row' => $row,
            //     'market_location' => $market_location,
            //     'coins' => $coins
            // ) );

            $updated_cards = array();

            for ($i = $col-1; $i >= 0; $i--) {
                $location = 'market_'.$row.'_'.$i;
                $m_card = $this->tokens->getTokenOnLocation($location);
                if ($m_card == NULL) {
                    $location = 'market_'.$row_alt.'_'.$i;
                    $m_card = $this->tokens->getTokenOnLocation($location);
                }
                if ($m_card !== NULL) {
                    $c = $this->tokens->getTokenOnTop('pool');
                    $this->tokens->moveToken($c['key'], $m_card["key"]); 
                    $this->tokens->setTokenState($m_card["key"], 1);
                    $updated_cards[] = array(
                        'location' => $location,
                        'card_id' => $m_card["key"],
                        'coin_id' => $c['key']
                    );
                }
            }

            self::notifyAllPlayers( "purchaseCard", clienttranslate( '${player_name} purchased ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card' => $card,
                'card_name' => $card_name,
                'market_location' => $market_location,
                'updated_cards' => $updated_cards,
                'i18n' => array( 'card_name' ),
            ) );

            $this->updatePlayerCounts();

        }

        if ($this->getGameStateValue("remaining_actions") > 0) {
            $this->gamestate->nextState( 'action' );
        } else {
            $this->cleanup();
        }

    }


    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerActions()
    {
        $player_id = self::getActivePlayerId();

        return array(
            'remaining_actions' => $this->getGameStateValue("remaining_actions"),
            'unavailable_cards' => $this->getUnavailableCards(),
            'hand' => $this->tokens->getTokensInLocation('hand_'.$player_id),
            'court' => $this->tokens->getTokensOfTypeInLocation('card', 'court_'.$player_id, null, 'state'),
            'suits' => $this->getPlayerSuits($player_id),
            'rulers' => $this->getAllRegionRulers()
        );
    }

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
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

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stNextPlayer()
    {
        $setup = $this->getGameStateValue("setup");
        self::dump( "setup in stNextPlayer", $setup );
        // Active next player
        if ($setup == 1) {
            // setup
            $player_id = self::activeNextPlayer();
            $loyalty = $this->getPlayerLoyalty($player_id);
            self::dump( "loyalty in stNextPlayer", $loyalty == "null" );
            if ($this->getPlayerLoyalty($player_id) == "null") {
                // choose next loyalty
                $this->giveExtraTime($player_id);

                $this->gamestate->nextState( 'setup' );
            } else {
                // setup complete, go to player actions
                $player_id = self::activePrevPlayer();
                $this->giveExtraTime($player_id);

                $this->setGameStateValue("setup", 0);
                $this->setGameStateValue("remaining_actions", 2);

                $this->gamestate->nextState( 'next_turn' );
            }

        } else {
            // player turn
            $player_id = self::activeNextPlayer();

            $this->setGameStateValue("remaining_actions", 2);
            $this->giveExtraTime($player_id);

            $this->gamestate->nextState( 'next_turn' );
        }

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

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
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
    
    function upgradeTableDb( $from_version )
    {
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
