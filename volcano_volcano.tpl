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
        <td id='VOLcell_0_0' class='VOLspace'></td>
        <td id='VOLcell_1_0' class='VOLspace'></td>
        <td id='VOLcell_2_0' class='VOLspace'></td>
        <td id='VOLcell_3_0' class='VOLspace'></td>
        <td id='VOLcell_4_0' class='VOLspace'></td>
    </tr>                
    <tr>                 
        <td id='VOLcell_0_1' class='VOLspace'></td>
        <td id='VOLcell_1_1' class='VOLspace'></td>
        <td id='VOLcell_2_1' class='VOLspace'></td>
        <td id='VOLcell_3_1' class='VOLspace'></td>
        <td id='VOLcell_4_1' class='VOLspace'></td>
    </tr>                
    <tr>                 
        <td id='VOLcell_0_2' class='VOLspace'></td>
        <td id='VOLcell_1_2' class='VOLspace'></td>
        <td id='VOLcell_2_2' class='VOLspace'></td>
        <td id='VOLcell_3_2' class='VOLspace'></td>
        <td id='VOLcell_4_2' class='VOLspace'></td>
    </tr>                
    <tr>                 
        <td id='VOLcell_0_3' class='VOLspace'></td>
        <td id='VOLcell_1_3' class='VOLspace'></td>
        <td id='VOLcell_2_3' class='VOLspace'></td>
        <td id='VOLcell_3_3' class='VOLspace'></td>
        <td id='VOLcell_4_3' class='VOLspace'></td>
    </tr>                
    <tr>                 
        <td id='VOLcell_0_4' class='VOLspace'></td>
        <td id='VOLcell_1_4' class='VOLspace'></td>
        <td id='VOLcell_2_4' class='VOLspace'></td>
        <td id='VOLcell_3_4' class='VOLspace'></td>
        <td id='VOLcell_4_4' class='VOLspace'></td>
    </tr>
</table>

<script type="text/javascript">

// Javascript HTML templates
var jstpl_piece = "<div class='VOLpiece' id='VOLpiece_${piece_id}' ptype='${colornum}_${pipsnum}' VOLz='${z}'>color ${colornum} size ${pipsnum}</div>";

</script>  

{OVERALL_GAME_FOOTER}
