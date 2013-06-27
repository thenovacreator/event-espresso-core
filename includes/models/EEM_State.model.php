<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0.B
 *
 * ------------------------------------------------------------------------
 *
 * State Model
 *
 * @package			Event Espresso
 * @subpackage	includes/models/
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
require_once ( EE_MODELS . 'EEM_Base.model.php' );

class EEM_State extends EEM_Base {

  	// private instance of the Attendee object
	private static $_instance = NULL;
  	// array of all states
	private static $_all_states = FALSE;
  	// array of all active states
	private static $_active_states = FALSE;



	/**
	 *		This funtion is a singleton method used to instantiate the EEM_State object
	 *
	 *		@access public
	 *		@return EEM_State instance
	 */	
	public static function instance() {	
		// check if instance of EEM_State already exists
		if ( self::$_instance === NULL ) {
			// instantiate Espresso_model 
			self::$_instance = new self();
		}
		// EEM_State object
		return self::$_instance;
	}

	protected function __construct(){
		$this->singlular_item = __('State','event_espresso');
		$this->plural_item = __('States','event_espresso');

		$this->_tables = array(
			'State'=> new EE_Primary_Table('esp_state', 'STA_ID')
		);
				
		$this->_fields = array(
			'State'=>array(
				'STA_ID'=> new EE_Primary_Key_String_Field('STA_ID', __('State ID','event_espresso'), false,0),
				'CNT_ISO'=> new EE_Foreign_Key_String_Field('CNT_ISO', __('COuntry ISO Code','event_espresso'), false, 1, 'Country'),
				'STA_abbrev' => new EE_Plain_Text_Field('STA_abbrev', __('State Abbreviation','event_espresso'), false, ''),
				'STA_name' => new EE_Plain_Text_Field('STA_name', __('State Name','event_espresso'), false, ''),
				'STA_active'=> new EE_Boolean_Field('STA_active', __("State Active Flag", "event_espresso"), false, false)
				));
		$this->_model_relations = array(
			'Country' => new EE_Belongs_To_Relation(),
			'Venue'=>new EE_Has_Many_Relation()
		);
		parent::__construct();
		
	}



	/**
	 * defines  table name as a constant
	 * @access public
	 */
	public static function define_table_name() {
		global $wpdb;
		define( 'EE_STATE_TABLE', $wpdb->prefix . 'esp_state' );
	}



	/**
	*		_get_states
	* 
	* 		@access		private
	*		@return 		void
	*/	
	public function get_all_states() {
		if ( ! self::$_all_states ) {
			self::$_all_states = $this->get_all( NULL, 'ASC', array( 0,99999 ));
		}
		return self::$_all_states;
	}

	/**
	*		_get_states
	* 
	* 		@access		private
	*		@return 		void
	*/	
	public function get_all_active_states() {
		if ( ! self::$_active_states ) {
			self::$_active_states =  $this->get_all( 
					array(array( 'STA_active' => 1 ), 
					'limit'=>array(0,99999)));
		}
		return self::$_active_states;
	}




	/**
	*		delete  a single state from db via their ID
	* 
	* 		@access		public
	* 		@param		$STA_ID		
	*		@return 		mixed		array on success, FALSE on fail
	*/	
	public function delete_by_ID( $STA_ID = FALSE ) {

		if ( ! $STA_ID ) {
			return FALSE;
		}
				
		// retreive a particular transaction
		$where_cols_n_values = array( 'STA_ID' => $STA_ID );
		if ( $answer = $this->delete ( $where_cols_n_values )) {
			return TRUE;
		} else {
			return FALSE;
		}

	}
	




}
// End of file EEM_State.model.php
// Location: /includes/models/EEM_State.model.php
