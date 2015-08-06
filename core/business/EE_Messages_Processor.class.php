<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }

/**
 * This class contains all business logic related to generating, queuing, and scheduling of
 * messages in the EE_messages system.
 *
 * @package    Event Espresso
 * @subpackage messages
 * @author     Darren Ethier
 * @since      4.9.0
 */
class EE_Messages_Processor {


	/**
	 * This is set on instantiation.  Is an instance of the EE_messages object.
	 * @type EE_messages
	 */
	protected $_EEMSG;


	/**
	 * @type EE_Messages_Queue
	 */
	protected $_queue;





	/**
	 * @type  EE_Messages_Generator
	 */
	protected $_generator;




	/**
	 * constructor
	 * @param EE_messages $ee_messages
	 */
	public function __construct( EE_messages $ee_messages ) {
		$this->_EEMSG = $ee_messages;
		$this->_queue = new EE_Messages_Queue( $ee_messages );
		$this->_generator = new EE_Messages_Generator( $this->_queue, $ee_messages );
	}




	/**
	 * This returns the current set queue.
	 * @return EE_Messages_Queue
	 */
	public function get_queue() {
		return $this->_queue;
	}




	/**
	 *  Calls the EE_Messages_Queue::get_batch_to_generate() method and sends to EE_Messages_Generator.
	 * @return EE_Messages_Queue | bool  return false if nothing generated.  This returns a new EE_Message_Queue with
	 *                                   generated messages.
	 */
	public function batch_generate_from_queue() {
		if ( $this->_queue->get_batch_to_generate() ) {
			$new_queue = $this->_generator->generate();
			if ( $new_queue instanceof EE_Messages_Queue ) {
				//unlock queue
				$this->_queue->unlock_queue();
				$this->_queue->initiate_request_by_priority('send');
				return $new_queue;
			}
		} else {
			$this->_queue->unlock_queue();
			return false;
		}
	}




	/**
	 * Calls the EE_Message_Queue::get_to_send_batch_and_send() method and then immediately just calls EE_Message_Queue::execute()
	 * to iterate and send unsent messages.
	 * @return EE_Messages_Queue
	 */
	public function batch_send_from_queue() {
		//get messages to send and execute.
		$this->_queue->get_to_send_batch_and_send();
		//note: callers can use the EE_Messages_Queue::count_STS_in_queue() method to find out if there were any failed
		//messages in the queue and decide how to handle at that point.
		return $this->_queue;
	}






	/**
	 * This immediately generates messages using the given array of EE_Message_To_Generate objects and returns the
	 * EE_Message_Queue with the generated messages for the caller to work with.  Note, this does NOT save the generated
	 * messages in the queue, leaving it up to the caller to do so.
	 *
	 * @param EE_Message_To_Generate[] $messages_to_generate
	 * @return EE_Messages_Queue
	 */
	public function generate_and_return(  $messages_to_generate ) {
		$this->_queue_for_generation_loop( $messages_to_generate );
		return $this->_generator->generate( false );
	}




	/**
	 * Executes the generator generate method on the current internal queue, and returns the generated queue.
	 * @param  bool     $persist    Indicate whether to instruct the generator to persist the generated queue (true) or not (false).
	 * @return EE_Messages_Queue
	 */
	public function generate_queue( $persist = true ) {
		return $this->_generator->generate( $persist );
	}




	/**
	 * Queue for generation.  Note this does NOT persist to the db.  Client code should call get_queue()->save() if desire
	 * to persist.  This method is provided to client code to decide what it wants to do with queued messages for generation.
	 * @param EE_Message_To_Generate $mtg
	 * @return  EE_Messages_Queue
	 */
	public function queue_for_generation( EE_Message_To_Generate $mtg ) {
		if ( $mtg->valid() ) {
			$this->_generator->create_and_add_message_to_queue( $mtg );
		}
	}







	/**
	 * This receives an array of EE_Message_To_Generate objects, converts them to EE_Message adds them to the generation queue
	 * and then persists to storage.
	 *
	 * @param EE_Message_To_Generate[] $messages_to_generate
	 */
	public function batch_queue_for_generation_and_persist( $messages_to_generate ) {
		$this->_queue_for_generation_loop( $messages_to_generate );
		$this->_queue->save();
	}






	/**
	 * This receives an array of EE_Message_To_Generate objects, converts them to EE_Message and adds them to the generation
	 * queue.  Does NOT persist to storage (unless there is an error.
	 * Client code can retrieve the generated queue by calling EEM_Messages_Processor::get_queue()
	 *
	 * @param EE_Message_To_Generate[]  $messages_to_generate
	 */
	public function batch_queue_for_generation_no_persist( $messages_to_generate ) {
		$this->_queue_for_generation_loop( $messages_to_generate );
	}




	/**
	 * Simply loops through the given array of EE_Message_To_Generate objects and adds them to the _queue as EE_Message
	 * objects.
	 *
	 * @param $messages_to_generate
	 */
	protected function _queue_for_generation_loop( $messages_to_generate ) {
		//make sure is in an array.
		if ( ! is_array( $messages_to_generate ) ) {
			$messages_to_generate = array( $messages_to_generate );
		}

		foreach ( $messages_to_generate as $mtg ) {
			if ( $mtg instanceof EE_Message_To_Generate && $mtg->valid() ) {
				$this->queue_for_generation( $mtg );
			}
		}
	}





	/**
	 * Receives an array of EE_Message_To_Generate objects and generates the EE_Message objects, then persists (so its
	 * queued for sending).
	 * @param  EE_Message_To_Generate[]
	 * @return EE_Messages_Queue
	 */
	public function generate_and_queue_for_sending( $messages_to_generate ) {
		$this->_queue_for_generation_loop( $messages_to_generate );
		return $this->_generator->generate( true );
	}





	/**
	 * Generate for preview and execute right away.
	 * @param   EE_Message_To_Generate $mtg
	 * @return  EE_Messages_Qeueue | bool   false if unable to generate otherwise the generated queue.
	 */
	public function generate_for_preview( EE_Message_To_Generate $mtg ) {
		if ( ! $mtg->valid() ) {
			EE_Error::add_error(
				__( 'Unable to generate preview because of invalid data', 'event_espresso' ),
				__FILE__,
				__FUNCTION__,
				__LINE__
			);
			return false;
		}
		//just make sure preview is set on the $mtg (in case client forgot)
		$mtg->preview = true;
		$generated_queue = $this->generate_and_return( array( $mtg ) );
		if ( $generated_queue->execute( false ) ) {
			//the first queue item should be the preview
			$generated_queue->get_queue()->rewind();
			if ( $generated_queue->get_queue()->valid() ) {
				if ( $generated_queue->get_queue()->is_test_send() ) {
					return true;
				} else {
					return $generated_queue;
				}
			}
		} else {
			return false;
		}
	}


	/**
	 * This queues for sending.
	 * The messenger send now method is also verified to see if sending immediately is requested.
	 * otherwise its just saved to the queue.
	 * @param EE_Message_To_Generate $mtg
	 * @return bool true or false for success.
	 */
	public function queue_for_sending( EE_Message_To_Generate $mtg ) {
		if ( ! $mtg->valid() ) {
			return false;
		}
		$this->_queue->add( $mtg->get_EE_Message() );
		if ( $mtg->send_now() ) {
			$this->_queue->execute( false );
		} else {
			$this->_queue->save();
		}
		return true;
	}


	/**
	 * This generates and sends from the given EE_Message_To_Generate class immediately.
	 * @param EE_Message_To_Generate $mtg
	 * @return bool
	 */
	public function generate_and_send_now( EE_Message_To_Generate $mtg ) {
		if ( ! $mtg->valid() ) {
			return false;
		}
		$sending_messenger = $mtg instanceof EEI_Has_Sending_Messenger ? $mtg->sending_messenger()->name : '';
		if ( $mtg->get_EE_Message()->STS_ID() === EEM_Message::status_idle ) {
			$this->_queue->add( $mtg->get_EE_Message() );
			$this->_queue->execute( false, $sending_messenger );
		} elseif ( $mtg->get_EE_Message()->STS_ID() === EEM_Message::status_incomplete ) {
			$generated_queue = $this->generate_and_return( $mtg );
			$generated_queue->execute( false, $sending_messenger );
		} else {
			return false;
		}
	}




	/**
	 * Creates mtg objects for all active messengers and queues for generation.
	 * This method also calls the execute by priority method on the queue which will optionally kick off a new non-blocking
	 * request to complete the action if the priority for the message requires immediate action.
	 * @param string $message_type
	 * @param mixed  $data   The data being used for generation.
	 * @param bool   $persist   Whether to persist the queued messages to the db or not.
	 */
	public function generate_for_all_active_messengers( $message_type, $data, $persist = true ) {
		$messages_to_generate = $this->setup_mtgs_for_all_active_messengers( $message_type, $data );
		if ( $persist ) {
			$this->batch_queue_for_generation_and_persist( $messages_to_generate );
			$this->_queue->initiate_request_by_priority();
		} else {
			$this->batch_queue_for_generation_no_persist( $messages_to_generate );
		}
	}




	/**
	 * This simply loops through all active messengers and takes care of setting up the
	 * EE_Message_To_Generate objects.
	 * @param $message_type
	 * @param $data
	 *
	 * @return EE_Message_To_Generate[]
	 */
	public function setup_mtgs_for_all_active_messengers( $message_type, $data ) {
		$messages_to_generate = array();
		foreach( $this->_EEMSG->get_active_messengers() as $messenger_slug => $messenger_object  ) {
			$mtg = new EE_Message_To_Generate(
				$messenger_slug,
				$message_type,
				$data,
				$this->_EEMSG
			);
			if ( $mtg->valid() ) {
				$messages_to_generate[] = $mtg;
			}
		}
		return $messages_to_generate;
	}




	/**
	 * This accepts an array of EE_Message::MSG_ID values and will use that to retrieve the objects from the database
	 * and send.
	 * @param array $message_ids
	 */
	public function setup_messages_from_ids_and_send( $message_ids ) {
		$messages = EEM_Message::instance()->get_all( array(
			array(
				'MSG_ID' => aray( 'IN', $message_ids )
			)
		));

		//set the Messages to resend (only if their status is sent).
		foreach ( $messages as $message ) {
			if ( $message instanceof EE_Message && in_array( $message->STS_ID(), EEM_Message::instance()->stati_indicating_sent() ) ) {
				$message->set_STS_ID( EEM_Message::status_resend );
				$this->_queue->add( $message );
			}
		}

		$this->_queue->initiate_request_by_priority( 'send' );
	}



	/**
	 * This method checks for registration IDs in the request via the given key and creates the messages to generate
	 * objects from them, then returns the array of messages to generate objects.
	 * Note, this sets up registrations for the registration family of message types.
	 *
	 * @param string    $key    The key for the request var holding a/the registration IDs.
	 * @return EE_Message_To_Generate[]
	 */
	public function setup_messages_to_generate_from_registration_ids_in_request( $key = '_REG_ID' ) {
		EE_Registry::instance()->load_core( 'Request_Handler' );
		EE_Registry::instance()->load_helper( 'MSG_Template' );
		$regs_to_send = array();
		$regIDs = EE_Registry::instance()->REQ->get( '_REG_ID' );
		if ( empty( $regIDs ) ) {
			EE_Error::add_error( __('Something went wrong because we\'re missing the registration ID', 'event_espresso'), __FILE__, __FUNCTION__, __LINE__ );
			return false;
		}

		//make sure is an array
		$regIDs = is_array( $regIDs ) ? $regIDs : array( $regIDs );

		foreach( $regIDs as $regID ) {
			$reg = EEM_Registration::instance()->get_one_by_ID( $regID );
			if ( ! $reg instanceof EE_Registration ) {
				EE_Error::add_error( sprintf( __('Unable to retrieve a registration object for the given reg id (%s)', 'event_espresso'), $regID ) );
				return false;
			}
			$regs_to_send[$reg->transaction_ID()][$reg->status_ID()][] = $reg;
		}

		$messages_to_generate = array();

		foreach( $regs_to_send as $status_group ) {
			foreach( $status_group as $status_id => $registrations ) {
				$messages_to_generate = $messages_to_generate + $this->setup_mtgs_for_all_active_messengers(
						EEH_MSG_Template::convert_reg_status_to_message_type( $status_id ),
						array( $registrations, $status_id )
					);
			}
		}

		return $messages_to_generate;
	}



} //end class EE_Messages_Processor