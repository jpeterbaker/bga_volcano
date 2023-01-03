{OVERALL_GAME_HEADER}

<!-- 
    BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
    Volcano implementation : © Jonathan Baker <babamots@gmail.com>
    
    This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
    See http://en.boardgamearena.com/#!doc/Studio for more information.

    volcano_volcano.tpl
-->

<!-- Placeholder interface for developing backend -->
<table id='VOLtemp_display'>
    <tr>
        <td class='VOLcell' id='VOLcell_0_0' x='0' y='0'></td>
        <td class='VOLcell' id='VOLcell_1_0' x='1' y='0'></td>
        <td class='VOLcell' id='VOLcell_2_0' x='2' y='0'></td>
        <td class='VOLcell' id='VOLcell_3_0' x='3' y='0'></td>
        <td class='VOLcell' id='VOLcell_4_0' x='4' y='0'></td>
    </tr>                                
    <tr>                                 
        <td class='VOLcell' id='VOLcell_0_1' x='0' y='1'></td>
        <td class='VOLcell' id='VOLcell_1_1' x='1' y='1'></td>
        <td class='VOLcell' id='VOLcell_2_1' x='2' y='1'></td>
        <td class='VOLcell' id='VOLcell_3_1' x='3' y='1'></td>
        <td class='VOLcell' id='VOLcell_4_1' x='4' y='1'></td>
    </tr>                                
    <tr>                                 
        <td class='VOLcell' id='VOLcell_0_2' x='0' y='2'></td>
        <td class='VOLcell' id='VOLcell_1_2' x='1' y='2'></td>
        <td class='VOLcell' id='VOLcell_2_2' x='2' y='2'></td>
        <td class='VOLcell' id='VOLcell_3_2' x='3' y='2'></td>
        <td class='VOLcell' id='VOLcell_4_2' x='4' y='2'></td>
    </tr>                                
    <tr>                                 
        <td class='VOLcell' id='VOLcell_0_3' x='0' y='3'></td>
        <td class='VOLcell' id='VOLcell_1_3' x='1' y='3'></td>
        <td class='VOLcell' id='VOLcell_2_3' x='2' y='3'></td>
        <td class='VOLcell' id='VOLcell_3_3' x='3' y='3'></td>
        <td class='VOLcell' id='VOLcell_4_3' x='4' y='3'></td>
    </tr>                                
    <tr>                                 
        <td class='VOLcell' id='VOLcell_0_4' x='0' y='4'></td>
        <td class='VOLcell' id='VOLcell_1_4' x='1' y='4'></td>
        <td class='VOLcell' id='VOLcell_2_4' x='2' y='4'></td>
        <td class='VOLcell' id='VOLcell_3_4' x='3' y='4'></td>
        <td class='VOLcell' id='VOLcell_4_4' x='4' y='4'></td>
    </tr>
</table>

<script type="text/javascript">

// Javascript HTML templates

// Piece
var jstpl_piece = "<div class='VOLpiece' id='VOLpiece_${piece_id}' VOLcolor='${colornum}' VOLpips='${pipsnum}' VOLz='${z}'>${pipsnum}</div>";
// Capture table
var jstpl_captures = "<div class='VOLcaptures' id='VOLcaptures_${player_id}'><div>Captures of ${player_name}</div><div class='VOLrow_1 VOLcap_row'></div><div class='VOLrow_2 VOLcap_row'></div><div class='VOLrow_3 VOLcap_row'></div></div>";

</script> 

{OVERALL_GAME_FOOTER}

