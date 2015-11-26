<?php

/**
 *
 * Class EE_Quantity_Modifying_Line_Item_Filter
 *
 * AbstractLine item filter parent that modifies ticket quantities
 *
 * @package         Event Espresso
 * @subpackage    
 * @author				Mike Nelson
 * @since		 	   $VID:$
 *
 */
if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

abstract class EE_Quantity_Modifying_Line_Item_Filter extends EE_Line_Item_Filter_Base{
	/**
	 * Creates a duplicate of the line item tree, except only includes billable items
	 * and the portion of line items attributed to billable things
	 * @param EEI_Line_Item      $line_item
	 * @return \EEI_Line_Item
	 */
	public function process( EEI_Line_Item $line_item ) {
		$this->_adjust_line_item_quantity( $line_item );
		if( ! $line_item->children() ) {
			return $line_item;
		}
		//the original running total (taking ALL tickets into account)
		$running_total_of_children = 0;
		//the new running total (only taking the specified ticket quantities into account)
		$runnign_total_of_children_under_consideration = 0;
		foreach ( $line_item->children() as $child_line_item ) {
			if( $child_line_item->is_percent() ) {
				$original_li_total = $running_total_of_children * $child_line_item->percent() / 100;
			}else{
				$original_li_total = $child_line_item->unit_price() * $child_line_item->quantity();
			}

			$this->process( $child_line_item );
			/*
			 * If this line item is a normal line item that isn't for a ticket
			 * we want to modify its total (and unit price if not a percentage line item)
			 * so it reflects only that portion of the surcharge/discount shared by these
			 * registrations
			 */
			if( $child_line_item->type() === EEM_Line_Item::type_line_item &&
					$child_line_item->OBJ_type() !== 'Ticket' ) {
				if( $running_total_of_children ) {
					$percent_of_running_total = $original_li_total / $running_total_of_children;
				} else {
					$percent_of_running_total = 0;
				}

				$child_line_item->set_total( $runnign_total_of_children_under_consideration * $percent_of_running_total );
				if( ! $child_line_item->is_percent() ) {
					$child_line_item->set_unit_price( $child_line_item->total() / $child_line_item->quantity() );
				}
			}elseif( $line_item->type() === EEM_Line_Item::type_line_item &&
					$line_item->OBJ_type() === 'Ticket' ) {
				//make sure this item's quantity matches its parent
				if( ! $child_line_item->is_percent() ) {
					$child_line_item->set_quantity( $line_item->quantity() );
					$child_line_item->set_total( $child_line_item->unit_price() * $child_line_item->quantity() );
				}
			}
			$running_total_of_children += $original_li_total;
			$runnign_total_of_children_under_consideration += $child_line_item->total();
		}
		$line_item->set_total( $runnign_total_of_children_under_consideration );
		if( $line_item->quantity() ) {
			$line_item->set_unit_price( $runnign_total_of_children_under_consideration / $line_item->quantity() );
		} else {
			$line_item->set_unit_price( 0 );
		}
		return $line_item;
	}
	
	/**
	 * Updates the line item's quantity according to whatever logic
	 * @return EE_Line_Item
	 */
	abstract protected function _adjust_line_item_quantity( EEI_Line_Item $line_item );
}
