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
class EE_Specific_Registrations_Line_Item_Filter extends EE_Quantity_Modifying_Line_Item_Filter {
/**
	 * array of line item codes and their corresponding quantities for
	 * registrations that owe money and can pay at this moment
	 * @type array $_counts_per_line_item_code
	 */
	protected $_counts_per_line_item_code = array();

	/**
	 * Just kept in case we want it someday. Currently unused
	 * @var EE_Registration[]
	 */
	protected $_registrations = array();



	/**
	 * EE_Billable_Line_Item_Filter constructor.
	 * @param EE_Registration[] $registrations
	 */
	public function __construct( $registrations ) {
		$this->_registrations = $registrations;
		$this->_calculate_counts_per_line_item_code( $registrations );
	}

	/**
	 * sets the _counts_per_line_item_code from the provided registrations
	 * @param EE_Registration[] $registrations
	 * @return void
	 */
	protected function _calculate_counts_per_line_item_code( $registrations ) {
		foreach( $registrations as $registration ) {
			$line_item_code = EEM_Line_Item::instance()->get_var( EEM_Line_Item::instance()->line_item_for_registration_query_params( $registration, array( 'limit' => 1 ) ), 'LIN_code' );
			if( $line_item_code ) {
				if( ! isset( $this->_counts_per_line_item_code[ $line_item_code ] ) ) {
					$this->_counts_per_line_item_code[ $line_item_code ] = 1;
				}else{
					$this->_counts_per_line_item_code[ $line_item_code ]++;
				}
			}
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
			if ( isset( $this->_counts_per_line_item_code[ $line_item->code() ] )) {
				// set quantity based on number of billable registrations for this ticket
				$quantity = $this->_counts_per_line_item_code[ $line_item->code() ];
			} else {
				$quantity = 0;
			}
			$line_item->set_quantity( $quantity );
			$line_item->set_total( $line_item->unit_price() * $line_item->quantity() );
		}
		return $line_item;
	}
}

// End of file EE_Specific_Registrations_Line_Item_Filter.class.php