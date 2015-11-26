<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Specific_Registrations_Line_Item_Filter
 *
 * Modifies the line item quantities to reflect only those items for the specified registrations.
 * Also, modifies NON-ticket regular line items (eg flat discounts and percent surcharges, etc)
 * to only show the share for the specified ticket quantities
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Specific_Tickets_Line_Item_Filter extends EE_Quantity_Modifying_Line_Item_Filter {

	/**
	 * Just kept in case we want it someday. Currently unused
	 * @var EE_Ticket[]
	 */
	protected $_tickets = array();
	
	/**
	 * Ticket Ids
	 * @var array
	 */
	protected $_ticket_ids = array();



	/**
	 * EE_Billable_Line_Item_Filter constructor.
	 * @param EE_Ticket[] $tickets
	 */
	public function __construct( $tickets ) {
		$this->_tickets = $tickets;
		foreach( $this->_tickets as $ticket ) {
			$this->_ticket_ids[] = $ticket->ID();
		}
	}

	/**
	 * Adjusts quantities for line items for tickets according to the registrations provided
	 * in the constructor
	 * @param EEI_Line_Item $line_item
	 * @return EEI_Line_Item
	 */
	protected function _adjust_line_item_quantity( EEI_Line_Item $line_item ) {
		// is this a ticket ?
		if ( $line_item->type() === EEM_Line_Item::type_line_item && $line_item->OBJ_type() == 'Ticket' ) {
			// if this ticket is billable at this moment, then we should have a positive quantity
			if ( ! in_array( $line_item->OBJ_ID(), $this->_ticket_ids ) ) {
				$line_item->set_quantity( 0 );
				$line_item->set_total( $line_item->unit_price() * $line_item->quantity() );
			}
		}
		return $line_item;
	}
}

// End of file EE_Specific_Registrations_Line_Item_Filter.class.php