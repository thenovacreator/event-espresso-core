<?php

if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version		4.1
 *
 * ------------------------------------------------------------------------
 *
 * EE_Pending_Approval_message_type
 *
 * Handles frontend registration message types. 
 *
 * @package		Event Espresso
 * @subpackage	core/libraries/messages/message_type/EE_Pending_Approval_message_type.class.php
 * @author		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */

class EE_Pending_Approval_message_type extends EE_message_type {

	public function __construct() {
		$this->name = 'pending_approval';
		$this->description = __('This message type is used for recipients who have Pending Payment registration status.', 'event_espresso');
		$this->label = array(
			'singular' => __('registration pending payment', 'event_espresso'),
			'plural' => __('registrations pending payment', 'event_espresso')
			);

		parent::__construct();
	}



	protected function _set_admin_pages() {
		$this->admin_registered_pages = array(
			'events_edit' => TRUE
			);
	}

	/**
	 * This message type doesn't need any settings so we are just setting to empty array.
	 */
	protected function _set_admin_settings_fields() {
		$this->_admin_settings_fields = array();
	}


	protected function _get_admin_content_events_edit_for_messenger( EE_Messenger $messenger ) {
		//this is just a test
		return $this->label['singular'] . ' Message Type for ' . $messenger->name . ' Messenger ';
	}




	protected function _set_data_handler() {
		$this->_data_handler = $this->_data instanceof EE_Registration ? 'REG' : 'Gateways';
		$this->_single_message = $this->_data instanceof EE_Registration ? TRUE : FALSE;
	}



	protected function _set_default_field_content() {

		$this->_default_field_content = array(
			'subject' => $this->_default_template_field_subject(),
			'content' => $this->_default_template_field_content(),
		);
	}






	protected function _default_template_field_subject() {
		foreach ( $this->_contexts as $context => $details ) {
			$content[$context] = 'Registration Pending Payment';
		};
		return $content;
	}






	protected function _default_template_field_content() {
		$content = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/pending_approval-message-type-content.template.php', TRUE );
		
		foreach ( $this->_contexts as $context => $details ) {
			$tcontent[$context]['main'] = $content;
			$tcontent[$context]['attendee_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/pending_approval-message-type-attendee-list.template.php', TRUE );
			$tcontent[$context]['event_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/pending_approval-message-type-event-list.template.php', TRUE );
			$tcontent[$context]['ticket_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/pending_approval-message-type-ticket-list.template.php', TRUE );
			$tcontent[$context]['datetime_list'] = file_get_contents( EE_LIBRARIES . 'messages/message_type/assets/defaults/pending_approval-message-type-datetime-list.template.php', TRUE );
		}


		return $tcontent;
	}






	/**
	 * _set_contexts
	 * This sets up the contexts associated with the message_type
	 * 
	 * @access  protected
	 * @return  void
	 */
	protected function _set_contexts() {
		$this->_context_label = array(
			'label' => __('recipient', 'event_espresso'),
			'plural' => __('recipients', 'event_espresso'),
			'description' => __('Recipient\'s are who will recieve the template.  You may want different pending approval details sent out depending on who the recipient is.  To "turn off" a recipient from receiving message, simply remove any content from the "to" field in the template.', 'event_espresso')
			);

		$this->_contexts = array(
			'admin' => array(
				'label' => __('Event Admin', 'event_espresso'),
				'description' => __('This template is what event administrators will receive when a message is sent to registrants with the pending payment registration status.', 'event_espresso')
				),
			'primary_attendee' => array(
				'label' => __('Primary Registrant', 'event_espresso'),
				'description' => __('This template is what the primary registrant (the person who completed the initial transaction) will receive on when their registration status is pending payment.', 'event_espresso')
				)
			);

		$this->_contexts = apply_filters('FHEE_set_contexts_'. $this->name, $this->_contexts);
		$this->_contexts = apply_filters('FHEE_set_contexts_all', $this->_contexts);
	}


	/**
	 * see abstract declaration in parent class for details
	 */
	protected function _set_valid_shortcodes() {
		$this->_valid_shortcodes = array(
			'admin' => array('event','venue','organization', 'attendee', 'registration', 'attendee_list', 'event_list', 'ticket_list', 'datetime_list'),
			'primary_attendee' => array('event','venue','organization', 'attendee', 'registration', 'attendee_list', 'event_list', 'ticket_list','datetime_list')
			);
	}





	/**
	 * returns an array of addressee objects for event_admins
	 *
	 * @access protected
	 * @return array array of EE_Messages_Addressee objects
	 */
	protected function _admin_addressees() {
		if ( $this->_single_message )
			return array();

		$admin_ids = array();
		$admin_events = array();
		$admin_attendees = array();
		$addresees = array();

		//first we need to get the event admin user id for all the events and setup an addressee object for each unique admin user.
		foreach ( $this->_data->events as $line_ref => $event ) {
			$admin_id = $this->_get_event_admin_id($event['ID']);
			//get the user_id for the event
			$admin_ids[] = $admin_id;
			//make sure we are just including the events that belong to this admin!
			$admin_events[$admin_id][$line_ref] = $event;
		}

		//make sure we've got unique event_admins!
		$admin_ids = array_unique($admin_ids);

		//k now we can loop through the event_admins and setup the addressee data.
		foreach ( $admin_ids as $event_admin ) {
			$aee = array(
				'user_id' => $event_admin,
				'events' => $admin_events[$event_admin],
				'attendees' => $this->_data->attendees
				);
			$aee = array_merge( $this->_default_addressee_data, $aee );
			$addressees[] = new EE_Messages_Addressee( $aee );
		}

		return $addressees;
	}


	/**
	 * Takes care of setting up the addressee object(s) for the primary attendee.
	 *
	 * @access protected
	 * @return array of EE_Addressee objects
	 */
	protected function _primary_attendee_addressees() {
		$aee = $this->_default_addressee_data;
		$aee['events'] = $this->_data->events;
		$aee['attendees'] = $this->_data->attendees;
		$aee['att_obj'] = $this->_data->primary_attendee_data['att_obj'];

		//great now we can instantiate the $addressee object and return (as an array);
		$add[] = new EE_Messages_Addressee( $aee );
		return $add;
	}

} //end EE_Pending_Approval_message_type class