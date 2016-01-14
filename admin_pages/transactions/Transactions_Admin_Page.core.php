<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
 * @ link					{@link http://www.eventespresso.com}
 * @ since		 		4.0
 *
 * ------------------------------------------------------------------------
 *
 * EE_Admin_Transactions class
 *
 * @package			Event Espresso
 * @subpackage	includes/core/admin/transactions/Transactions_Admin_Page.core.php
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class Transactions_Admin_Page extends EE_Admin_Page {

	/**
	 * @var EE_Transaction
	 */
	private $_transaction;

	/**
	 * @var EE_Session
	 */
	private $_session;

	/**
	 * @var array $_txn_status
	 */
	private static $_txn_status;

	/**
	 * @var array $_pay_status
	 */
	private static $_pay_status;

	/**
	 * @var array $_existing_reg_payment_REG_IDs
	 */
	protected $_existing_reg_payment_REG_IDs = null;



	/**
	 * @Constructor
	 * @access public
	 * @param bool $routing
	 * @return Transactions_Admin_Page
	 */
	public function __construct( $routing = TRUE ) {
		parent::__construct( $routing );
	}



	/**
	 * 	_init_page_props
	 * @return void
	 */
	protected function _init_page_props() {
		$this->page_slug = TXN_PG_SLUG;
		$this->page_label = __('Transactions', 'event_espresso');
		$this->_admin_base_url = TXN_ADMIN_URL;
		$this->_admin_base_path = TXN_ADMIN;
	}



	/**
	 * 	_ajax_hooks
	 * @return void
	 */
	protected function _ajax_hooks() {
		add_action('wp_ajax_espresso_apply_payment', array( $this, 'apply_payments_or_refunds'));
		add_action('wp_ajax_espresso_apply_refund', array( $this, 'apply_payments_or_refunds'));
		add_action('wp_ajax_espresso_delete_payment', array( $this, 'delete_payment'));
	}



	/**
	 * 	_define_page_props
	 * @return void
	 */
	protected function  _define_page_props() {
		$this->_admin_page_title = $this->page_label;
		$this->_labels = array(
			'buttons' => array(
				'add' => __('Add New Transaction', 'event_espresso'),
				'edit' => __('Edit Transaction', 'event_espresso'),
				'delete' => __('Delete Transaction','event_espresso')
				)
			);
	}



	/**
	 * 		grab url requests and route them
	*		@access private
	*		@return void
	*/
	public function _set_page_routes() {

		$this->_set_transaction_status_array();

		$txn_id = ! empty( $this->_req_data['TXN_ID'] ) && ! is_array( $this->_req_data['TXN_ID'] ) ? $this->_req_data['TXN_ID'] : 0;

		$this->_page_routes = array(

				'default' => array(
					'func' => '_transactions_overview_list_table',
					'capability' => 'ee_read_transactions'
					),

				'view_transaction' => array(
					'func' => '_transaction_details',
					'capability' => 'ee_read_transaction',
					'obj_id' => $txn_id
					),

				'send_payment_reminder'	=> array(
					'func' => '_send_payment_reminder',
					'noheader' => TRUE,
					'capability' => 'ee_send_message'
					),

				'espresso_apply_payment' => array(
				 	'func' => 'apply_payments_or_refunds',
				 	'noheader' => TRUE,
				 	'capability' => 'ee_edit_payments'
				 	),

				'espresso_apply_refund'	=> array(
					'func' => 'apply_payments_or_refunds',
					'noheader' => TRUE,
					'capability' => 'ee_edit_payments'
					),

				'espresso_delete_payment' => array(
					'func' => 'delete_payment',
					'noheader' => TRUE,
					'capability' => 'ee_delete_payments'
					),

		);

	}








	protected function _set_page_config() {
		$this->_page_config = array(
			'default' => array(
				'nav' => array(
					'label' => __('Overview', 'event_espresso'),
					'order' => 10
					),
				'list_table' => 'EE_Admin_Transactions_List_Table',
				'help_tabs' => array(
					'transactions_overview_help_tab' => array(
						'title' => __('Transactions Overview', 'event_espresso'),
						'filename' => 'transactions_overview'
					),
					'transactions_overview_table_column_headings_help_tab' => array(
						'title' => __('Transactions Table Column Headings', 'event_espresso'),
						'filename' => 'transactions_overview_table_column_headings'
					),
					'transactions_overview_views_filters_help_tab' => array(
						'title' => __('Transaction Views & Filters & Search', 'event_espresso'),
						'filename' => 'transactions_overview_views_filters_search'
					),
				),
				'help_tour' => array( 'Transactions_Overview_Help_Tour' ),
				/**
				 * commented out because currently we are not displaying tips for transaction list table status but this
				 * may change in a later iteration so want to keep the code for then.
				 */
				//'qtips' => array( 'Transactions_List_Table_Tips' ),
				'require_nonce' => FALSE
				),
			'view_transaction' => array(
				'nav' => array(
					'label' => __('View Transaction', 'event_espresso'),
					'order' => 5,
					'url' => isset($this->_req_data['TXN_ID']) ? add_query_arg(array('TXN_ID' => $this->_req_data['TXN_ID'] ), $this->_current_page_view_url )  : $this->_admin_base_url,
					'persistent' => FALSE
					),
				'help_tabs' => array(
					'transactions_view_transaction_help_tab' => array(
						'title' => __('View Transaction', 'event_espresso'),
						'filename' => 'transactions_view_transaction'
					),
					'transactions_view_transaction_transaction_details_table_help_tab' => array(
						'title' => __('Transaction Details Table', 'event_espresso'),
						'filename' => 'transactions_view_transaction_transaction_details_table'
					),
					'transactions_view_transaction_attendees_registered_help_tab' => array(
						'title' => __('Attendees Registered', 'event_espresso'),
						'filename' => 'transactions_view_transaction_attendees_registered'
					),
					'transactions_view_transaction_views_primary_registrant_billing_information_help_tab' => array(
						'title' => __('Primary Registrant & Billing Information', 'event_espresso'),
						'filename' => 'transactions_view_transaction_primary_registrant_billing_information'
					),
				),
				'qtips' => array( 'Transaction_Details_Tips' ),
				'help_tour' => array( 'Transaction_Details_Help_Tour' ),
				'metaboxes' => array('_transaction_details_metaboxes'),

				'require_nonce' => FALSE
				)
		);
	}


	/**
	 * The below methods aren't used by this class currently
	 */
	protected function _add_screen_options() {}
	protected function _add_feature_pointers() {}
	public function admin_init() {
		EE_Registry::$i18n_js_strings[ 'invalid_server_response' ] = __( 'An error occurred! Your request may have been processed, but a valid response from the server was not received. Please refresh the page and try again.', 'event_espresso' );
		EE_Registry::$i18n_js_strings[ 'error_occurred' ] = __( 'An error occurred! Please refresh the page and try again.', 'event_espresso' );
		EE_Registry::$i18n_js_strings[ 'txn_status_array' ] = self::$_txn_status;
		EE_Registry::$i18n_js_strings[ 'pay_status_array' ] = self::$_pay_status;
	}
	public function admin_notices() {}
	public function admin_footer_scripts() {}



	/**
	 * _set_transaction_status_array
	 * sets list of transaction statuses
	*
	 * @access private
	*	@return void
	*/
	private function _set_transaction_status_array() {
		self::$_txn_status = EEM_Transaction::instance()->status_array(TRUE);
	}



	/**
	 * get_transaction_status_array
	 * return the transaction status array for wp_list_table
	 *
	 * @access public
	 * @return array
	 */
	public function get_transaction_status_array() {
		return self::$_txn_status;
	}



	/**
	 * 	get list of payment statuses
	*
	 * @access private
	*	@return void
	*/
	private function _get_payment_status_array() {
		self::$_pay_status = EEM_Payment::instance()->status_array(TRUE);
		$this->_template_args['payment_status'] = self::$_pay_status;

	}



	/**
	 * 	_add_screen_options_default
	 *
	 * 	@access protected
	 *	@return void
	 */
	protected function _add_screen_options_default() {
		$this->_per_page_screen_option();
	}



	/**
	 * load_scripts_styles
	 *
	 * @access public
	 *	@return void
	 */
	public function load_scripts_styles() {
		//enqueue style
		wp_register_style( 'espresso_txn', TXN_ASSETS_URL . 'espresso_transactions_admin.css', array(), EVENT_ESPRESSO_VERSION );
		wp_enqueue_style('espresso_txn');

		//scripts
		add_filter('FHEE_load_accounting_js', '__return_true');

		//scripts
		wp_register_script('espresso_txn', TXN_ASSETS_URL . 'espresso_transactions_admin.js', array('ee_admin_js', 'ee-datepicker', 'jquery-ui-datepicker', 'jquery-ui-draggable', 'ee-dialog', 'ee-accounting', 'ee-serialize-full-array'), EVENT_ESPRESSO_VERSION, TRUE);
		wp_enqueue_script('espresso_txn');

	}



	/**
	 * 	load_scripts_styles_view_transaction
	 *
	 *	@access public
	 *	@return void
	 */
	public function load_scripts_styles_view_transaction() {
		//styles
		wp_enqueue_style('espresso-ui-theme');
	}



	/**
	 * 	load_scripts_styles_default
	 *
	 * @access public
	 *	@return void
	 */
	public function load_scripts_styles_default() {
		//styles
		wp_enqueue_style('espresso-ui-theme');
	}



	/**
	 * 	_set_list_table_views_default
	 *
	 *	@access protected
	 *	@return void
	 */
	protected function _set_list_table_views_default() {
		$this->_views = array (
			'all' => array (
				'slug' 		=> 'all',
				'label' 		=> __('View All Transactions', 'event_espresso'),
				'count' 	=> 0
				),
			'abandoned' => array(
				'slug' 		=> 'abandoned',
				'label' 		=> __('Abandoned Transactions', 'event_espresso'),
				'count' 	=> 0
			),
			'failed' => array(
				'slug' 		=> 'failed',
				'label' 		=> __('Failed Transactions', 'event_espresso'),
				'count' 	=> 0
			)
		);
	}



	/**
	 * _set_transaction_object
	 * This sets the _transaction property for the transaction details screen
	 *
	 *	@access private
	 *	@return void
	 */
	private function _set_transaction_object() {
		if ( is_object( $this->_transaction) )
			return; //get out we've already set the object

	    $TXN = EEM_Transaction::instance();

	    $TXN_ID = ( ! empty( $this->_req_data['TXN_ID'] )) ? absint( $this->_req_data['TXN_ID'] ) : FALSE;

	    //get transaction object
	    $this->_transaction = $TXN->get_one_by_ID($TXN_ID);
	    $this->_session = !empty( $this->_transaction ) ? $this->_transaction->get('TXN_session_data') : NULL;

	 	if ( empty( $this->_transaction ) ) {
	    	$error_msg = __('An error occurred and the details for Transaction ID #', 'event_espresso') . $TXN_ID .  __(' could not be retrieved.', 'event_espresso');
			EE_Error::add_error( $error_msg, __FILE__, __FUNCTION__, __LINE__ );
	    }
	}



	/**
	 * 	_transaction_legend_items
	 *
	 *	@access protected
	 *	@return array
	 */
	protected function _transaction_legend_items() {
		$items = apply_filters(
			'FHEE__Transactions_Admin_Page___transaction_legend_items__items',
			array(
				'view_details' => array(
					'class' => 'dashicons dashicons-cart',
					'desc' => __('View Transaction Details', 'event_espresso')
				),
				'view_invoice' => array(
					'class' => 'dashicons dashicons-media-spreadsheet',
					'desc' => __('View Transaction Invoice', 'event_espresso')
				),
				'view_receipt' => array(
					'class' => 'dashicons dashicons-media-default',
					'desc' => __('View Transaction Receipt', 'event_espresso' )
				),
				'view_registration' => array(
					'class' => 'dashicons dashicons-clipboard',
					'desc' => __('View Registration Details', 'event_espresso')
				)
			)
		);

		if ( EE_Registry::instance()->CAP->current_user_can( 'ee_send_message', 'espresso_transactions_send_payment_reminder' ) ) {

			EE_Registry::instance()->load_helper( 'MSG_Template' );
			if ( EEH_MSG_Template::is_mt_active( 'payment_reminder' ) ) {
				$items['send_payment_reminder'] = array(
					'class' => 'dashicons dashicons-email-alt',
					'desc' => __('Send Payment Reminder', 'event_espresso')
					);
			} else {
				$items['blank*'] = array(
					'class'=> '',
					'desc' => ''
					);
			}
		} else {
			$items['blank*'] = array(
				'class'=> '',
				'desc' => ''
				);
		}
		$more_items = apply_filters(
			'FHEE__Transactions_Admin_Page___transaction_legend_items__more_items',
			array(
				'overpaid'   => array(
					'class' => 'ee-status-legend ee-status-legend-' . EEM_Transaction::overpaid_status_code,
					'desc'  => EEH_Template::pretty_status( EEM_Transaction::overpaid_status_code, FALSE, 'sentence' )
				),
				'complete'   => array(
					'class' => 'ee-status-legend ee-status-legend-' . EEM_Transaction::complete_status_code,
					'desc'  => EEH_Template::pretty_status( EEM_Transaction::complete_status_code, FALSE, 'sentence' )
				),
				'incomplete' => array(
					'class' => 'ee-status-legend ee-status-legend-' . EEM_Transaction::incomplete_status_code,
					'desc'  => EEH_Template::pretty_status( EEM_Transaction::incomplete_status_code, FALSE, 'sentence' )
				),
				'abandoned'  => array(
					'class' => 'ee-status-legend ee-status-legend-' . EEM_Transaction::abandoned_status_code,
					'desc'  => EEH_Template::pretty_status( EEM_Transaction::abandoned_status_code, FALSE, 'sentence' )
				),
				'failed'     => array(
					'class' => 'ee-status-legend ee-status-legend-' . EEM_Transaction::failed_status_code,
					'desc'  => EEH_Template::pretty_status( EEM_Transaction::failed_status_code, FALSE, 'sentence' )
				)
			)
		);

		return array_merge( $items, $more_items);
	}



	/**
	 * 	_transactions_overview_list_table
	 *
	 * @access protected
	 *	@return void
	 */
	protected function _transactions_overview_list_table() {
		$this->_admin_page_title = __('Transactions', 'event_espresso');
		$event = isset($this->_req_data['EVT_ID']) ? EEM_Event::instance()->get_one_by_ID($this->_req_data['EVT_ID'] ) : NULL;
		$this->_template_args['admin_page_header'] = $event instanceof EE_Event ? sprintf( __('%sViewing Transactions for the Event: %s%s', 'event_espresso'), '<h3>', '<a href="' . EE_Admin_Page::add_query_args_and_nonce(array('action' => 'edit', 'post' => $event->ID()), EVENTS_ADMIN_URL ) . '" title="' . esc_attr__('Click to Edit event', 'event_espresso') . '">' . $event->get('EVT_name') . '</a>', '</h3>' ) : '';
		$this->_template_args['after_list_table'] = $this->_display_legend( $this->_transaction_legend_items() );
		$this->display_admin_list_table_page_with_no_sidebar();
	}



	/**
	* 	_transaction_details
	 * generates HTML for the View Transaction Details Admin page
	*
	 * @access protected
	*	@return void
	*/
	protected function _transaction_details() {
		do_action( 'AHEE__Transactions_Admin_Page__transaction_details__start', $this->_transaction );
		EE_Registry::instance()->load_helper( 'MSG_Template' );

		$this->_set_transaction_status_array();

		$this->_template_args = array();
		$this->_template_args['transactions_page'] = $this->_wp_page_slug;

		$this->_set_transaction_object();

		$primary_registration = $this->_transaction->primary_registration();
		$attendee = $primary_registration instanceof EE_Registration ? $primary_registration->attendee() : NULL;

		$this->_template_args['txn_nmbr']['value'] = $this->_transaction->ID();
		$this->_template_args['txn_nmbr']['label'] = __( 'Transaction Number', 'event_espresso' );

		$this->_template_args['txn_datetime']['value'] = $this->_transaction->get_datetime('TXN_timestamp', 'l F j, Y', 'g:i:s a' );
		$this->_template_args['txn_datetime']['label'] = __( 'Date', 'event_espresso' );

		$this->_template_args['txn_status']['value'] = self::$_txn_status[ $this->_transaction->get('STS_ID') ];
		$this->_template_args['txn_status']['label'] = __( 'Transaction Status', 'event_espresso' );
		$this->_template_args['txn_status']['class'] = 'status-' . $this->_transaction->get('STS_ID');

		$this->_template_args['grand_total'] = $this->_transaction->get('TXN_total');
		$this->_template_args['total_paid'] = $this->_transaction->get('TXN_paid');

		if ( $attendee instanceof EE_Attendee && EE_Registry::instance()->CAP->current_user_can( 'ee_send_message', 'espresso_transactions_send_payment_reminder' ) ) {
			EE_Registry::instance()->load_helper( 'MSG_Template' );
			$this->_template_args['send_payment_reminder_button'] = EEH_MSG_Template::is_mt_active( 'payment_reminder' )
				 && $this->_transaction->get('STS_ID') != EEM_Transaction::complete_status_code
				 && $this->_transaction->get('STS_ID') != EEM_Transaction::overpaid_status_code
				 ? EEH_Template::get_button_or_link( EE_Admin_Page::add_query_args_and_nonce( array( 'action'=>'send_payment_reminder', 'TXN_ID'=>$this->_transaction->ID(), 'redirect_to' => 'view_transaction' ), TXN_ADMIN_URL ), __(' Send Payment Reminder'), 'button secondary-button right',  'dashicons dashicons-email-alt' )
				 : '';
		} else {
			$this->_template_args['send_payment_reminder_button'] = '';
		}

		$amount_due = $this->_transaction->get('TXN_total') - $this->_transaction->get('TXN_paid');
		$this->_template_args['amount_due'] = EEH_Template::format_currency( $amount_due, TRUE );
		if ( EE_Registry::instance()->CFG->currency->sign_b4 ) {
			$this->_template_args['amount_due'] = EE_Registry::instance()->CFG->currency->sign . $this->_template_args['amount_due'];
		} else {
			$this->_template_args['amount_due'] = $this->_template_args['amount_due'] . EE_Registry::instance()->CFG->currency->sign;
		}
		$this->_template_args['amount_due_class'] =  '';

		if ( $this->_transaction->get('TXN_paid') == $this->_transaction->get('TXN_total') ) {
			// paid in full
			$this->_template_args['amount_due'] =  FALSE;
		} elseif ( $this->_transaction->get('TXN_paid') > $this->_transaction->get('TXN_total') ) {
			// overpaid
			$this->_template_args['amount_due_class'] =  'txn-overview-no-payment-spn';
		} elseif (( $this->_transaction->get('TXN_total') > 0 ) && ( $this->_transaction->get('TXN_paid') > 0 )) {
			// monies owing
			$this->_template_args['amount_due_class'] =  'txn-overview-part-payment-spn';
		} elseif (( $this->_transaction->get('TXN_total') > 0 ) && ( $this->_transaction->get('TXN_paid') == 0 )) {
			// no payments made yet
			$this->_template_args['amount_due_class'] =  'txn-overview-no-payment-spn';
		} elseif ( $this->_transaction->get('TXN_total') == 0 ) {
			// free event
			$this->_template_args['amount_due'] =  FALSE;
		}

		$payment_method = $this->_transaction->payment_method();

		$this->_template_args['method_of_payment_name'] = $payment_method instanceof EE_Payment_Method ? $payment_method->admin_name() : __( 'Unknown', 'event_espresso' );
		$this->_template_args['currency_sign'] = EE_Registry::instance()->CFG->currency->sign;
		// link back to overview
		$this->_template_args['txn_overview_url'] = ! empty ( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : TXN_ADMIN_URL;


		//next and previous links
		$next_txn = $this->_transaction->next(null, array( array( 'STS_ID' => array( '!=', EEM_Transaction::failed_status_code ) ) ), 'TXN_ID' );
		$this->_template_args['next_transaction'] = $next_txn ? $this->_next_link( EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'view_transaction', 'TXN_ID' => $next_txn['TXN_ID'] ), TXN_ADMIN_URL ), 'dashicons dashicons-arrow-right ee-icon-size-22' ) : '';
		$previous_txn = $this->_transaction->previous( null, array( array( 'STS_ID' => array( '!=', EEM_Transaction::failed_status_code ) ) ), 'TXN_ID' );
		$this->_template_args['previous_transaction'] = $previous_txn ? $this->_previous_link( EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'view_transaction', 'TXN_ID' => $previous_txn['TXN_ID'] ), TXN_ADMIN_URL ), 'dashicons dashicons-arrow-left ee-icon-size-22' ) : '';


		// grab messages at the last second
		$this->_template_args['notices'] = EE_Error::get_notices();
		// path to template
		$template_path = TXN_TEMPLATE_PATH . 'txn_admin_details_header.template.php';
		$this->_template_args['admin_page_header'] = EEH_Template::display_template( $template_path, $this->_template_args, TRUE );

		// the details template wrapper
		$this->display_admin_page_with_sidebar();

	}



	/**
	 * 		_transaction_details_metaboxes
	 *
	 *		@access protected
	 *		@return void
	 */
	protected function _transaction_details_metaboxes() {

		$this->_set_transaction_object();

		add_meta_box( 'edit-txn-details-mbox', __( 'Transaction Details', 'event_espresso' ), array( $this, 'txn_details_meta_box' ), $this->_wp_page_slug, 'normal', 'high' );
		add_meta_box(
			'edit-txn-attendees-mbox',
			__( 'Attendees Registered in this Transaction', 'event_espresso' ),
			array( $this, 'txn_attendees_meta_box' ),
			$this->_wp_page_slug,
			'normal',
			'high',
			array( 'TXN_ID' => $this->_transaction->ID() )
		);
		add_meta_box( 'edit-txn-registrant-mbox', __( 'Primary Contact', 'event_espresso' ), array( $this, 'txn_registrant_side_meta_box' ), $this->_wp_page_slug, 'side', 'high' );
		add_meta_box( 'edit-txn-billing-info-mbox', __( 'Billing Information', 'event_espresso' ), array( $this, 'txn_billing_info_side_meta_box' ), $this->_wp_page_slug, 'side', 'high' );

	}



	/**
	 * txn_details_meta_box
	 * generates HTML for the Transaction main meta box
	*
	 * @access public
	*	@return void
	*/
	public function txn_details_meta_box() {

		$this->_set_transaction_object();
		$this->_template_args['TXN_ID'] = $this->_transaction->ID();
		$this->_template_args['attendee'] = $this->_transaction->primary_registration() instanceof EE_Registration ? $this->_transaction->primary_registration()->attendee() : null;

		//get line table
		EEH_Autoloader::register_line_item_display_autoloaders();
		$Line_Item_Display = new EE_Line_Item_Display( 'admin_table', 'EE_Admin_Table_Line_Item_Display_Strategy' );
		$this->_template_args['line_item_table'] = $Line_Item_Display->display_line_item( $this->_transaction->total_line_item() );
		$this->_template_args['REG_code'] = $this->_transaction->get_first_related('Registration')->get('REG_code');

		// process taxes
		$taxes = $this->_transaction->get_many_related( 'Line_Item', array( array( 'LIN_type' => EEM_Line_Item::type_tax )));
		$this->_template_args['taxes'] = ! empty( $taxes ) ? $taxes : FALSE;

		$this->_template_args['grand_total'] = EEH_Template::format_currency($this->_transaction->get('TXN_total'), FALSE, FALSE );
		$this->_template_args['grand_raw_total'] = $this->_transaction->get('TXN_total');
		$this->_template_args['TXN_status'] = $this->_transaction->get('STS_ID');

//		$txn_status_class = 'status-' . $this->_transaction->get('STS_ID');

		// process payment details
		$payments = $this->_transaction->get_many_related('Payment');
		if( ! empty(  $payments ) ) {
			$this->_template_args[ 'payments' ] = $payments;
			$this->_template_args[ 'existing_reg_payments' ] = $this->_get_registration_payment_IDs( $payments );
		} else {
			$this->_template_args[ 'payments' ] = false;
			$this->_template_args[ 'existing_reg_payments' ] = array();
		}

		$this->_template_args['edit_payment_url'] = add_query_arg( array( 'action' => 'edit_payment'  ), TXN_ADMIN_URL );
		$this->_template_args['delete_payment_url'] = add_query_arg( array( 'action' => 'espresso_delete_payment' ), TXN_ADMIN_URL );

		if ( isset( $txn_details['invoice_number'] )) {
			$this->_template_args['txn_details']['invoice_number']['value'] = $this->_template_args['REG_code'];
			$this->_template_args['txn_details']['invoice_number']['label'] = __( 'Invoice Number', 'event_espresso' );
		}

		$this->_template_args['txn_details']['registration_session']['value'] = $this->_transaction->get_first_related('Registration')->get('REG_session');
		$this->_template_args['txn_details']['registration_session']['label'] = __( 'Registration Session', 'event_espresso' );

		$this->_template_args['txn_details']['ip_address']['value'] = isset( $this->_session['ip_address'] ) ? $this->_session['ip_address'] : '';
		$this->_template_args['txn_details']['ip_address']['label'] = __( 'Transaction placed from IP', 'event_espresso' );

		$this->_template_args['txn_details']['user_agent']['value'] = isset( $this->_session['user_agent'] ) ? $this->_session['user_agent'] : '';
		$this->_template_args['txn_details']['user_agent']['label'] = __( 'Registrant User Agent', 'event_espresso' );

		$reg_steps = '<ul>';
		foreach ( $this->_transaction->reg_steps() as $reg_step => $reg_step_status ) {
			if ( $reg_step_status === true ) {
				$reg_steps .= '<li style="color:#70cc50">' . sprintf( __( '%1$s : Completed', 'event_espresso' ), ucwords( str_replace( '_', ' ', $reg_step ) ) ) . '</li>';
			} else if ( is_numeric( $reg_step_status ) && $reg_step_status !== false ) {
					$reg_steps .= '<li style="color:#2EA2CC">' . sprintf(
							__( '%1$s : Initiated %2$s', 'event_espresso' ),
							ucwords( str_replace( '_', ' ', $reg_step ) ),
							gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), ( $reg_step_status + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) )
						) . '</li>';
				} else {
				$reg_steps .= '<li style="color:#E76700">' . sprintf( __( '%1$s : Never Initiated', 'event_espresso' ), ucwords( str_replace( '_', ' ', $reg_step ) ) ) . '</li>';
			}
		}
		$reg_steps .= '</ul>';
		$this->_template_args['txn_details']['reg_steps']['value'] = $reg_steps;
		$this->_template_args['txn_details']['reg_steps']['label'] = __( 'Registration Step Progress', 'event_espresso' );


		$this->_get_registrations_to_apply_payment_to();
		$this->_get_payment_methods( $payments );
		$this->_get_payment_status_array();
		$this->_get_reg_status_selection(); //sets up the template args for the reg status array for the transaction.

		$this->_template_args['transaction_form_url'] = add_query_arg( array( 'action' => 'edit_transaction', 'process' => 'transaction'  ), TXN_ADMIN_URL );
		$this->_template_args['apply_payment_form_url'] = add_query_arg( array( 'page' => 'espresso_transactions', 'action' => 'espresso_apply_payment' ), WP_AJAX_URL );
		$this->_template_args['delete_payment_form_url'] = add_query_arg( array( 'page' => 'espresso_transactions', 'action' => 'espresso_delete_payment' ), WP_AJAX_URL );

		// 'espresso_delete_payment_nonce'

		$template_path = TXN_TEMPLATE_PATH . 'txn_admin_details_main_meta_box_txn_details.template.php';
		echo EEH_Template::display_template( $template_path, $this->_template_args, TRUE );

	}



	/**
	 * _get_registration_payment_IDs
	 *
	 *    generates an array of Payment IDs and their corresponding Registration IDs
	 *
	 * @access protected
	 * @param EE_Payment[] $payments
	 * @return array
	 */
	protected function _get_registration_payment_IDs( $payments = array() ) {
		$existing_reg_payments = array();
		// get all reg payments for these payments
		$reg_payments = EEM_Registration_Payment::instance()->get_all( array(
			array(
				'PAY_ID' => array(
					'IN',
					array_keys( $payments )
				)
			)
		) );
		if ( ! empty( $reg_payments ) ) {
			foreach ( $payments as $payment ) {
				if ( ! $payment instanceof EE_Payment ) {
					continue;
				} else if ( ! isset( $existing_reg_payments[ $payment->ID() ] ) ) {
					$existing_reg_payments[ $payment->ID() ] = array();
				}
				foreach ( $reg_payments as $reg_payment ) {
					if ( $reg_payment instanceof EE_Registration_Payment && $reg_payment->payment_ID() === $payment->ID() ) {
						$existing_reg_payments[ $payment->ID() ][ ] = $reg_payment->registration_ID();
					}
				}
			}
		}
		return $existing_reg_payments;
	}



	/**
	 * _get_registrations_to_apply_payment_to
	 *
	 * 	generates HTML for displaying a series of checkboxes in the admin payment modal window
	 * which allows the admin to only apply the payment to the specific registrations
	 *
	 *	@access protected
	 * @return void
	 */
	protected function _get_registrations_to_apply_payment_to() {
		// we want any registration with an active status (ie: not deleted or cancelled)
		$query_params = array(
			array(
				'STS_ID' => array(
					'IN',
					array(
						EEM_Registration::status_id_approved,
						EEM_Registration::status_id_pending_payment,
						EEM_Registration::status_id_not_approved,
					)
				)
			)
		);
		$registrations_to_apply_payment_to = '<br /><div id="txn-admin-apply-payment-to-registrations-dv"  style="clear: both; margin: 1.5em 0 0; display: none;">';
		$registrations_to_apply_payment_to .= '<br /><div class="admin-primary-mbox-tbl-wrap">';
		$registrations_to_apply_payment_to .= '<table class="admin-primary-mbox-tbl">';
		$registrations_to_apply_payment_to .= '<thead><tr>';
		$registrations_to_apply_payment_to .= '<td>' . __( 'ID', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '<td>' . __( 'Registrant', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '<td>' . __( 'Ticket', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '<td>' . __( 'Event', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '<td class="txn-admin-payment-paid-td jst-cntr">' . __( 'Paid', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '<td class="txn-admin-payment-owing-td jst-cntr">' . __( 'Owing', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '<td class="jst-cntr">' . __( 'Apply', 'event_espresso' ) . '</td>';
		$registrations_to_apply_payment_to .= '</tr></thead><tbody>';
		// get registrations for TXN
		$registrations = $this->_transaction->registrations( $query_params );
		foreach ( $registrations as $registration ) {
			if ( $registration instanceof EE_Registration ) {
				$owing = $registration->final_price() - $registration->paid();
				$taxable = $registration->ticket()->taxable() ? ' <span class="smaller-text lt-grey-text"> ' . __( '+ tax', 'event_espresso' ) . '</span>' : '';
				$checked = empty( $existing_reg_payments ) || in_array( $registration->ID(), $existing_reg_payments ) ? ' checked="checked"' : '';
				$registrations_to_apply_payment_to .= '<tr id="apply-payment-registration-row-' . $registration->ID() . '">';
				// add html for checkbox input and label
				$registrations_to_apply_payment_to .= '<td>' . $registration->ID() . '</td>';
				$registrations_to_apply_payment_to .= '<td>' . $registration->attendee() instanceof EE_Attendee ? $registration->attendee()->full_name() : __( 'Unknown Attendee', 'event_espresso' ) . '</td>';
				$registrations_to_apply_payment_to .= '<td>' . $registration->ticket()->name() . ' : ' . $registration->ticket()->pretty_price() . $taxable . '</td>';
				$registrations_to_apply_payment_to .= '<td>' . $registration->event_name() . '</td>';
				$registrations_to_apply_payment_to .= '<td class="txn-admin-payment-paid-td jst-rght">' . $registration->pretty_paid() . '</td>';
				$registrations_to_apply_payment_to .= '<td class="txn-admin-payment-owing-td jst-rght">' . EEH_Template::format_currency( $owing ) . '</td>';
				$registrations_to_apply_payment_to .= '<td class="jst-cntr">';
				$disabled = $registration->final_price() > 0 ? '' : ' disabled';
				$registrations_to_apply_payment_to .= '<input type="checkbox" value="' . $registration->ID() . '" name="txn_admin_payment[registrations]"' . $checked . $disabled . '>';
				$registrations_to_apply_payment_to .= '</td>';
				$registrations_to_apply_payment_to .= '</tr>';
			}
		}
		$registrations_to_apply_payment_to .= '</tbody></table></div>';
		$registrations_to_apply_payment_to .= '<p class="clear description">' . __( 'The payment will only be applied to the registrations that have a check mark in their corresponding check box. Checkboxes for free registrations have been disabled.', 'event_espresso' ) . '</p></div>';
		$this->_template_args[ 'registrations_to_apply_payment_to' ] = $registrations_to_apply_payment_to;
	}



	/**
	 * _get_reg_status_selection
	 *
	 * @todo this will need to be adjusted either once MER comes along OR we move default reg status to tickets instead of events.
	 *	@access protected
	 * @return void
	 */
	protected function _get_reg_status_selection() {
		//first get all possible statuses
		$statuses = EEM_Registration::reg_status_array(array(), TRUE);
		//let's add a "don't change" option.
		$status_array['NAN'] = __('Leave the Same', 'event_espresso');
		$status_array = array_merge( $status_array, $statuses );
		$this->_template_args['status_change_select'] = EEH_Form_Fields::select_input( 'txn_reg_status_change[reg_status]', $status_array, 'NAN', 'id="txn-admin-payment-reg-status-inp"', 'txn-reg-status-change-reg-status' );
		$this->_template_args['delete_status_change_select'] = EEH_Form_Fields::select_input( 'delete_txn_reg_status_change[reg_status]', $status_array, 'NAN', 'delete-txn-admin-payment-reg-status-inp', 'delete-txn-reg-status-change-reg-status' );

	}



	/**
	 * 	_get_payment_methods
	 * Gets all the payment methods available generally, or the ones that are already
	 * selected on these payments (in case their payment methods are no longer active).
	 * Has the side-effect of updating the template args' payment_methods item
	 *	@access private
	 * @param EE_Payment[] to show on this page
	 *	@return void
	 */
	private function _get_payment_methods( $payments = array() ) {
		$payment_methods_of_payments = array();
		foreach( $payments as $payment ){
			if( $payment instanceof EE_Payment ){
				$payment_methods_of_payments[] = $payment->get( 'PMD_ID' );
			}
		}
		if( $payment_methods_of_payments ){
			$query_args = array( array( 'OR*payment_method_for_payment' => array(
					'PMD_ID' => array( 'IN', $payment_methods_of_payments ),
					'PMD_scope' => array( 'LIKE', '%' . EEM_Payment_Method::scope_admin . '%' ) ) ) );
		}else{
			$query_args = array( array( 'PMD_scope' => array( 'LIKE', '%' . EEM_Payment_Method::scope_admin . '%' ) ) );
		}
		$this->_template_args['payment_methods'] = EEM_Payment_Method::instance()->get_all( $query_args );
	}



	/**
	 * txn_attendees_meta_box
	 *    generates HTML for the Attendees Transaction main meta box
	 *
	 * @access public
	 * @param WP_Post $post
	 * @param array $metabox
	 * @return void
	 */
	public function txn_attendees_meta_box( $post, $metabox = array( 'args' => array() )) {

		extract( $metabox['args'] );
		$this->_template_args['post'] = $post;
		$this->_template_args['event_attendees'] = array();
		// process items in cart
		$line_items = $this->_transaction->get_many_related('Line_Item', array( array( 'LIN_type' => 'line-item' ) ) );
		if ( ! empty( $line_items )) {
			foreach ( $line_items as $item ) {
				if ( $item instanceof EE_Line_Item ) {
					switch( $item->OBJ_type() ) {

						case 'Event' :
							break;

						case 'Ticket' :
							$ticket = $item->ticket();
							if ( empty( $ticket )) {
								continue; //right now we're only handling tickets here.  Cause its expected that only tickets will have attendees right?
							}
							$ticket_price = EEH_Template::format_currency( $item->get( 'LIN_unit_price' ));
							$event = $ticket->get_first_related('Registration')->get_first_related('Event');
							$event_name = $event instanceof EE_Event ? $event->get('EVT_name') . ' - ' . $item->get('LIN_name') : '';

							$registrations = $ticket->get_many_related('Registration', array( array('TXN_ID' => $this->_transaction->ID() )));
							foreach( $registrations as $registration ) {
								$this->_template_args['event_attendees'][$registration->ID()]['att_num'] 						= $registration->get('REG_count');
								$this->_template_args['event_attendees'][$registration->ID()]['event_ticket_name'] 	= $event_name;
								$this->_template_args['event_attendees'][$registration->ID()]['ticket_price'] 				= $ticket_price;
								// attendee info
								$attendee = $registration->get_first_related('Attendee');
								if ( $attendee instanceof EE_Attendee ) {
									$this->_template_args['event_attendees'][$registration->ID()]['att_id'] 			= $attendee->ID();
									$this->_template_args['event_attendees'][$registration->ID()]['attendee'] 	= $attendee->full_name();
									$this->_template_args['event_attendees'][$registration->ID()]['email'] 			= '<a href="mailto:' . $attendee->email() . '?subject=' . $event->get('EVT_name') . __(' Event', 'event_espresso') . '">' . $attendee->email() . '</a>';
									$this->_template_args['event_attendees'][$registration->ID()]['address'] 		=  implode(',<br>', $attendee->full_address_as_array() );
								} else {
									$this->_template_args['event_attendees'][$registration->ID()]['att_id'] 			= '';
									$this->_template_args['event_attendees'][$registration->ID()]['attendee'] 	= '';
									$this->_template_args['event_attendees'][$registration->ID()]['email'] 			= '';
									$this->_template_args['event_attendees'][$registration->ID()]['address'] 		= '';
								}
							}
							break;

					}
				}
			}

			$this->_template_args['transaction_form_url'] = add_query_arg( array( 'action' => 'edit_transaction', 'process' => 'attendees'  ), TXN_ADMIN_URL );
			echo EEH_Template::display_template( TXN_TEMPLATE_PATH . 'txn_admin_details_main_meta_box_attendees.template.php', $this->_template_args, TRUE );

		} else {
			echo sprintf(
				__( '%1$sFor some reason, there are no attendees registered for this transaction. Likely the registration was abandoned in process.%2$s', 'event_espresso' ),
				'<p class="important-notice">',
				'</p>'
			);
		}
	}



	/**
	 * txn_registrant_side_meta_box
	 * generates HTML for the Edit Transaction side meta box
	 *
	 * @access public
	 * @throws \EE_Error
	 * @return void
	 */
	public function txn_registrant_side_meta_box() {
		$primary_att = $this->_transaction->primary_registration() instanceof EE_Registration ? $this->_transaction->primary_registration()->get_first_related('Attendee') : null;
		if ( ! $primary_att instanceof EE_Attendee ) {
			$this->_template_args['no_attendee_message'] = __('There is no attached contact for this transaction.  The transaction either failed due to an error or was abandoned.', 'event_espresso');
			$primary_att = EEM_Attendee::instance()->create_default_object();
		}
		$this->_template_args['ATT_ID'] 						= $primary_att->ID();
		$this->_template_args['prime_reg_fname']		= $primary_att->fname();
		$this->_template_args['prime_reg_lname']		= $primary_att->lname();
		$this->_template_args['prime_reg_email'] 		= $primary_att->email();
		$this->_template_args['prime_reg_phone'] 	= $primary_att->phone();
		$this->_template_args['edit_attendee_url'] 	= EE_Admin_Page::add_query_args_and_nonce( array( 'action' => 'edit_attendee', 'post' => $primary_att->ID()  ), REG_ADMIN_URL );
		// get formatted address for registrant
		EE_Registry::instance()->load_helper( 'Formatter' );
		$this->_template_args[ 'formatted_address' ] = EEH_Address::format( $primary_att );
		echo EEH_Template::display_template( TXN_TEMPLATE_PATH . 'txn_admin_details_side_meta_box_registrant.template.php', $this->_template_args, TRUE );
	}



	/**
	 * txn_billing_info_side_meta_box
	 * 	generates HTML for the Edit Transaction side meta box
	*
	 * @access public
	*	@return void
	*/
	public function txn_billing_info_side_meta_box() {

		$this->_template_args['billing_form'] = $this->_transaction->billing_info();
		$this->_template_args['billing_form_url'] = add_query_arg(
			array( 'action' => 'edit_transaction', 'process' => 'billing'  ),
			TXN_ADMIN_URL
		);

		$template_path = TXN_TEMPLATE_PATH . 'txn_admin_details_side_meta_box_billing_info.template.php';
		echo EEH_Template::display_template( $template_path, $this->_template_args, TRUE );/**/
	}



	/**
	 * apply_payments_or_refunds
	 * 	registers a payment or refund made towards a transaction
	*
	 * @access public
	*	@return void
	*/
	public function apply_payments_or_refunds() {
		$json_response_data = array( 'return_data' => FALSE );
		$valid_data = $this->_validate_payment_request_data();
		if ( ! empty( $valid_data ) ) {
			$PAY_ID = $valid_data[ 'PAY_ID' ];
			//save  the new payment
			$payment = $this->_create_payment_from_request_data( $valid_data );
			// get the TXN for this payment
			$transaction = $payment->transaction();
			// verify transaction
			if ( $transaction instanceof EE_Transaction ) {
				// calculate_total_payments_and_update_status
				$this->_process_transaction_payments( $transaction );
				$REG_IDs = $this->_get_REG_IDs_to_apply_payment_to( $payment );
				$this->_remove_existing_registration_payments( $payment, $PAY_ID );
				// apply payment to registrations (if applicable)
				if ( ! empty( $REG_IDs ) ) {
					$this->_update_registration_payments( $transaction, $payment, $REG_IDs );
					$this->_maybe_send_notifications();
					// now process status changes for the same registrations
					$this->_process_registration_status_change( $transaction, $REG_IDs );
				}
				$this->_maybe_send_notifications( $payment );
				//prepare to render page
				$json_response_data[ 'return_data' ] = $this->_build_payment_json_response( $payment, $REG_IDs );
				do_action( 'AHEE__Transactions_Admin_Page__apply_payments_or_refund__after_recording', $transaction, $payment );
			} else {
				EE_Error::add_error(
					__( 'A valid Transaction for this payment could not be retrieved.', 'event_espresso' ),
					__FILE__, __FUNCTION__, __LINE__
				);
			}
		} else {
			EE_Error::add_error( __( 'The payment form data could not be processed. Please try again.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		}

		$notices = EE_Error::get_notices( FALSE, FALSE, FALSE );
		echo json_encode( array_merge( $json_response_data, $notices ));
		die();
	}



	/**
	 * _validate_payment_request_data
	 *
	 * @return array
	 */
	protected function _validate_payment_request_data() {
		if ( ! isset( $this->_req_data[ 'txn_admin_payment' ] ) ) {
			return false;
		}
		$payment_form = $this->_generate_payment_form_section();
		try {
			if ( $payment_form->was_submitted() ) {
				$payment_form->receive_form_submission();
				if ( ! $payment_form->is_valid() ) {
					$submission_error_messages = array();
					foreach ( $payment_form->get_validation_errors_accumulated() as $validation_error ) {
						if ( $validation_error instanceof EE_Validation_Error ) {
							$submission_error_messages[] = sprintf(
								_x( '%s : %s', 'Form Section Name : Form Validation Error', 'event_espresso' ),
								$validation_error->get_form_section()->html_label_text(),
								$validation_error->getMessage()
							);
						}
					}
					EE_Error::add_error( join( '<br />', $submission_error_messages ), __FILE__, __FUNCTION__, __LINE__ );
					return array();
				}
			}
		} catch ( EE_Error $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__ );
			return array();
		}
		return $payment_form->valid_data();
	}



	/**
	 * _generate_payment_form_section
	 *
	 * @return EE_Form_Section_Proper
	 */
	protected function _generate_payment_form_section() {
		return new EE_Form_Section_Proper(
			array(
				'name' => 'txn_admin_payment',
				'subsections'     => array(
					'PAY_ID' => new EE_Text_Input(
						array(
							'default' => 0,
							'required' => false,
							'html_label_text' => __( 'Payment ID', 'event_espresso' ),
							'validation_strategies' => array( new EE_Int_Normalization() )
						)
					),
					'TXN_ID' => new EE_Text_Input(
						array(
							'default' => 0,
							'required' => true,
							'html_label_text' => __( 'Transaction ID', 'event_espresso' ),
							'validation_strategies' => array( new EE_Int_Normalization() )
						)
					),
					'type' => new EE_Text_Input(
						array(
							'default' => 1,
							'required' => true,
							'html_label_text' => __( 'Payment or Refund', 'event_espresso' ),
							'validation_strategies' => array( new EE_Int_Normalization() )
						)
					),
					'amount' => new EE_Text_Input(
						array(
							'default' => 0,
							'required' => true,
							'html_label_text' => __( 'Payment amount', 'event_espresso' ),
							'validation_strategies' => array( new EE_Float_Normalization() )
						)
					),
					'status' => new EE_Text_Input(
						array(
							'default' => EEM_Payment::status_id_approved,
							'required' => true,
							'html_label_text' => __( 'Payment status', 'event_espresso' ),
						)
					),
					'PMD_ID' => new EE_Text_Input(
						array(
							'default' => 2,
							'required' => true,
							'html_label_text' => __( 'Payment Method', 'event_espresso' ),
							'validation_strategies' => array( new EE_Int_Normalization() )
						)
					),
					'date' => new EE_Text_Input(
						array(
							'default' => time(),
							'required' => true,
							'html_label_text' => __( 'Payment date', 'event_espresso' ),
						)
					),
					'txn_id_chq_nmbr' => new EE_Text_Input(
						array(
							'default' => '',
							'required' => false,
							'html_label_text' => __( 'Transaction or Cheque Number', 'event_espresso' ),
                                                        'validation_strategies' => array(
                                                            new EE_Max_Length_Validation_Strategy( __('Input too long', 'event_espresso'), 100 ),
                                                        )
						)
					),
					'po_number' => new EE_Text_Input(
						array(
							'default' => '',
							'required' => false,
							'html_label_text' => __( 'Purchase Order Number', 'event_espresso' ),
                                                        'validation_strategies' => array(
                                                            new EE_Max_Length_Validation_Strategy( __('Input too long', 'event_espresso'), 100 ),
                                                        )
						)
					),
					'accounting' => new EE_Text_Input(
						array(
							'default' => '',
							'required' => false,
							'html_label_text' => __( 'Extra Field for Accounting', 'event_espresso' ),
                                                        'validation_strategies' => array(
                                                            new EE_Max_Length_Validation_Strategy( __('Input too long', 'event_espresso'), 100 ),
                                                        )
						)
					),
				)
			)
		);
	}



	/**
	 * _create_payment_from_request_data
	 *
	 * @param array $valid_data
	 * @return EE_Payment
	 */
	protected function _create_payment_from_request_data( $valid_data ) {
		$PAY_ID = $valid_data[ 'PAY_ID' ];
		// get payment amount
		$amount = $valid_data[ 'amount' ] ? abs( $valid_data[ 'amount' ] ) : 0;
		// payments have a type value of 1 and refunds have a type value of -1
		// so multiplying amount by type will give a positive value for payments, and negative values for refunds
		$amount = $valid_data[ 'type' ] < 0 ? $amount * -1 : $amount;
		$payment = EE_Payment::new_instance(
			array(
				'TXN_ID' 								=> $valid_data[ 'TXN_ID' ],
				'STS_ID' 								=> $valid_data[ 'status' ],
				'PAY_timestamp' 				=> $valid_data[ 'date' ],
				'PAY_source'           			=> EEM_Payment_Method::scope_admin,
				'PMD_ID'               				=> $valid_data[ 'PMD_ID' ],
				'PAY_amount'           			=> $amount,
				'PAY_txn_id_chq_nmbr'  	=> $valid_data[ 'txn_id_chq_nmbr' ],
				'PAY_po_number'        		=> $valid_data[ 'po_number' ],
				'PAY_extra_accntng'    		=> $valid_data[ 'accounting' ],
				'PAY_details'          				=> $valid_data,
				'PAY_ID'               				=> $PAY_ID
			),
			'',
			array( 'Y-m-d', 'H:i a' )
		);
		if ( ! $payment->save() ) {
			EE_Error::add_error(
				sprintf(
					__( 'Payment %1$d has not been successfully saved to the database.', 'event_espresso' ),
					$payment->ID()
				),
				__FILE__, __FUNCTION__, __LINE__
			);
		}
		return $payment;
	}



	/**
	 * _process_transaction_payments
	 *
	 * @param \EE_Transaction $transaction
	 * @return array
	 */
	protected function _process_transaction_payments( EE_Transaction $transaction ) {
		/** @type EE_Transaction_Payments $transaction_payments */
		$transaction_payments = EE_Registry::instance()->load_class( 'Transaction_Payments' );
		//update the transaction with this payment
		if ( $transaction_payments->calculate_total_payments_and_update_status( $transaction ) ) {
			EE_Error::add_success( __( 'The payment has been processed successfully.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		} else {
			EE_Error::add_error(
				__( 'The payment was processed successfully but the amount paid for the transaction was not updated.', 'event_espresso' )
				, __FILE__, __FUNCTION__, __LINE__
			);
		}
	}



	/**
	 * _get_REG_IDs_to_apply_payment_to
	 *
	 * returns a list of registration IDs that the payment will apply to
	 *
	 * @param \EE_Payment $payment
	 * @return array
	 */
	protected function _get_REG_IDs_to_apply_payment_to( EE_Payment $payment ) {
		$REG_IDs = array();
		// grab array of IDs for specific registrations to apply changes to
		if ( isset( $this->_req_data[ 'txn_admin_payment' ][ 'registrations' ] ) ) {
			$REG_IDs = (array)$this->_req_data[ 'txn_admin_payment' ][ 'registrations' ];
		}
		//nothing specified ? then get all reg IDs
		if ( empty( $REG_IDs ) ) {
			$registrations = $payment->transaction()->registrations();
			$REG_IDs = ! empty( $registrations ) ? array_keys( $registrations ) : $this->_get_existing_reg_payment_REG_IDs( $payment );
		}
		// ensure that REG_IDs are integers and NOT strings
		return array_map( 'intval', $REG_IDs );
	}



	/**
	 * @return array
	 */
	public function existing_reg_payment_REG_IDs() {
		return $this->_existing_reg_payment_REG_IDs;
	}



	/**
	 * @param array $existing_reg_payment_REG_IDs
	 */
	public function set_existing_reg_payment_REG_IDs( $existing_reg_payment_REG_IDs = null ) {
		$this->_existing_reg_payment_REG_IDs = $existing_reg_payment_REG_IDs;
	}



	/**
	 * _get_existing_reg_payment_REG_IDs
	 *
	 * returns a list of registration IDs that the payment is currently related to
	 * as recorded in the database
	 *
	 * @param \EE_Payment $payment
	 * @return array
	 */
	protected function _get_existing_reg_payment_REG_IDs( EE_Payment $payment ) {
		if ( $this->existing_reg_payment_REG_IDs() === null ) {
			// let's get any existing reg payment records for this payment
			$existing_reg_payment_REG_IDs = $payment->get_many_related( 'Registration' );
			// but we only want the REG IDs, so grab the array keys
			$existing_reg_payment_REG_IDs = ! empty( $existing_reg_payment_REG_IDs ) ? array_keys( $existing_reg_payment_REG_IDs ) : array();
			$this->set_existing_reg_payment_REG_IDs( $existing_reg_payment_REG_IDs );
		}
		return $this->existing_reg_payment_REG_IDs();
	}



	/**
	 * _remove_existing_registration_payments
	 *
	 * this calculates the difference between existing relations
	 * to the supplied payment and the new list registration IDs,
	 * removes any related registrations that no longer apply,
	 * and then updates the registration paid fields
	 *
	 * @param \EE_Payment $payment
	 * @param int         $PAY_ID
	 * @return bool;
	 */
	protected function _remove_existing_registration_payments( EE_Payment $payment, $PAY_ID = 0 ) {
		// newly created payments will have nothing recorded for $PAY_ID
		if ( $PAY_ID == 0 ) {
			return false;
		}
		$existing_reg_payment_REG_IDs = $this->_get_existing_reg_payment_REG_IDs( $payment );
		if ( empty( $existing_reg_payment_REG_IDs )) {
			return false;
		}
		/** @type EE_Transaction_Payments $transaction_payments */
		$transaction_payments = EE_Registry::instance()->load_class( 'Transaction_Payments' );
		return $transaction_payments->delete_registration_payments_and_update_registrations(
			$payment,
			array(
				array(
					'PAY_ID' => $payment->ID(),
					'REG_ID' => array( 'IN', $existing_reg_payment_REG_IDs ),
				)
			)
		);
	}



	/**
	 * _update_registration_payments
	 *
	 * this applies the payments to the selected registrations
	 * but only if they have not already been paid for
	 *
	 * @param  EE_Transaction $transaction
	 * @param \EE_Payment $payment
	 * @param array $REG_IDs
	 * @return bool
	 */
	protected function _update_registration_payments( EE_Transaction $transaction, EE_Payment $payment, $REG_IDs = array() ) {
		// we can pass our own custom set of registrations to EE_Payment_Processor::process_registration_payments()
		// so let's do that using our set of REG_IDs from the form
		$registration_query_where_params = array(
			'REG_ID' => array( 'IN', $REG_IDs )
		);
		// but add in some conditions regarding payment,
		// so that we don't apply payments to registrations that are free or have already been paid for
		// but ONLY if the payment is NOT a refund ( ie: the payment amount is not negative )
		if ( ! $payment->is_a_refund() ) {
			$registration_query_where_params[ 'REG_final_price' ]  = array( '!=', 0 );
			$registration_query_where_params[ 'REG_final_price*' ]  = array( '!=', 'REG_paid', true );
		}
		//EEH_Debug_Tools::printr( $registration_query_where_params, '$registration_query_where_params', __FILE__, __LINE__ );
		$registrations = $transaction->registrations( array( $registration_query_where_params ) );
		if ( ! empty( $registrations ) ) {
			/** @type EE_Payment_Processor $payment_processor */
			$payment_processor = EE_Registry::instance()->load_core( 'Payment_Processor' );
			$payment_processor->process_registration_payments( $transaction, $payment, $registrations );
		}
	}



	/**
	 * _process_registration_status_change
	 *
	 * This processes requested registration status changes for all the registrations
	 * on a given transaction and (optionally) sends out notifications for the changes.
	 *
	 * @param  EE_Transaction $transaction
	 * @param array $REG_IDs
	 * @return bool
	 */
	protected function _process_registration_status_change( EE_Transaction $transaction, $REG_IDs = array() ) {
		// first if there is no change in status then we get out.
		if (
			! isset( $this->_req_data['txn_reg_status_change'], $this->_req_data[ 'txn_reg_status_change' ][ 'reg_status' ] )
			|| $this->_req_data['txn_reg_status_change']['reg_status'] == 'NAN'
		) {
			//no error message, no change requested, just nothing to do man.
			return FALSE;
		}
		/** @type EE_Transaction_Processor $transaction_processor */
		$transaction_processor = EE_Registry::instance()->load_class( 'Transaction_Processor' );
		// made it here dude?  Oh WOW.  K, let's take care of changing the statuses
		return $transaction_processor->manually_update_registration_statuses(
			$transaction,
			sanitize_text_field( $this->_req_data[ 'txn_reg_status_change' ][ 'reg_status' ] ),
			array( array( 'REG_ID' => array( 'IN', $REG_IDs ) ) )
		);
	}



	/**
	 * _build_payment_json_response
	 *
	 * @access public
	 * @param \EE_Payment $payment
	 * @param array       $REG_IDs
	 * @param bool | null        $delete_txn_reg_status_change
	 * @return array
	 */
	protected function _build_payment_json_response( EE_Payment $payment, $REG_IDs = array(), $delete_txn_reg_status_change = null ) {
		// was the payment deleted ?
		if ( is_bool( $delete_txn_reg_status_change )) {
			return array(
				'PAY_ID' 				=> $payment->ID(),
				'amount' 			=> $payment->amount(),
				'total_paid' 			=> $payment->transaction()->paid(),
				'txn_status' 			=> $payment->transaction()->status_ID(),
				'pay_status' 		=> $payment->STS_ID(),
				'registrations' 	=> $this->_registration_payment_data_array( $REG_IDs ),
				'delete_txn_reg_status_change' => $delete_txn_reg_status_change,
			);
		} else {
			$this->_get_payment_status_array();
			return array(
				'amount' 		=> $payment->amount(),
				'total_paid' 		=> $payment->transaction()->paid(),
				'txn_status' 		=> $payment->transaction()->status_ID(),
				'pay_status' 	=> $payment->STS_ID(),
				'PAY_ID'           => $payment->ID(),
				'STS_ID' 			=> $payment->STS_ID(),
				'status' 			=> self::$_pay_status[ $payment->STS_ID() ],
				'date' 				=> $payment->timestamp( 'Y-m-d', 'h:i a' ),
				'method' 		=> strtoupper( $payment->source() ),
				'PM_ID' 			=> $payment->payment_method() ? $payment->payment_method()->ID() : 1,
				'gateway' 		=> $payment->payment_method() ? $payment->payment_method()->admin_name() : __( "Unknown", 'event_espresso' ),
				'gateway_response' 	=> $payment->gateway_response(),
				'txn_id_chq_nmbr'  	=> $payment->txn_id_chq_nmbr(),
				'po_number'        		=> $payment->po_number(),
				'extra_accntng'    		=> $payment->extra_accntng(),
				'registrations'    			=> $this->_registration_payment_data_array( $REG_IDs ),
			);
		}
	}



	/**
	 * delete_payment
	 * 	delete a payment or refund made towards a transaction
	*
	 * @access public
	*	@return void
	*/
	public function delete_payment() {
		$json_response_data = array( 'return_data' => FALSE );
		$PAY_ID = isset( $this->_req_data['delete_txn_admin_payment'], $this->_req_data['delete_txn_admin_payment']['PAY_ID'] ) ? absint( $this->_req_data['delete_txn_admin_payment']['PAY_ID'] ) : 0;
		if ( $PAY_ID ) {
			$delete_txn_reg_status_change = isset( $this->_req_data[ 'delete_txn_reg_status_change' ] ) ? $this->_req_data[ 'delete_txn_reg_status_change' ] : false;
			$payment = EEM_Payment::instance()->get_one_by_ID( $PAY_ID );
			if ( $payment instanceof EE_Payment ) {
				$REG_IDs = $this->_get_existing_reg_payment_REG_IDs( $payment );
				/** @type EE_Transaction_Payments $transaction_payments */
				$transaction_payments = EE_Registry::instance()->load_class( 'Transaction_Payments' );
				if ( $transaction_payments->delete_payment_and_update_transaction( $payment )) {
					EE_Error::add_success( __( 'The Payment was successfully deleted.', 'event_espresso' ) );
					$json_response_data['return_data'] = $this->_build_payment_json_response( $payment, $REG_IDs, $delete_txn_reg_status_change );
					if ( $delete_txn_reg_status_change ) {
						$this->_req_data['txn_reg_status_change'] = $delete_txn_reg_status_change;
						//MAKE sure we also add the delete_txn_req_status_change to the
						//$_REQUEST global because that's how messages will be looking for it.
						$_REQUEST['txn_reg_status_change'] = $delete_txn_reg_status_change;
						$this->_maybe_send_notifications();
						$this->_process_registration_status_change( $payment->transaction(), $REG_IDs );
						//$this->_maybe_send_notifications( $payment );
					}
				}
			} else {
				EE_Error::add_error(
					__( 'Valid Payment data could not be retrieved from the database.', 'event_espresso' ),
					__FILE__, __FUNCTION__, __LINE__
				);
			}
		} else {
			EE_Error::add_error(
				__( 'A valid Payment ID was not received, therefore payment form data could not be loaded.', 'event_espresso' ),
				__FILE__, __FUNCTION__, __LINE__
			);
		}
		$notices = EE_Error::get_notices( FALSE, FALSE, FALSE );
		echo json_encode( array_merge( $json_response_data, $notices ));
		die();
	}



	/**
	 * _registration_payment_data_array
	 * adds info for 'owing' and 'paid' for each registration to the json response
	 *
	 * @access protected
	 * @param array $REG_IDs
	 * @return array
	 */
	protected function _registration_payment_data_array( $REG_IDs ) {
		$registration_payment_data = array();
		//if non empty reg_ids lets get an array of registrations and update the values for the apply_payment/refund rows.
		if ( ! empty( $REG_IDs ) ) {
			EE_Registry::instance()->load_helper( 'Template' );
			$registrations = EEM_Registration::instance()->get_all( array( array( 'REG_ID' => array( 'IN', $REG_IDs ) ) ) );
			foreach ( $registrations as $registration ) {
				if ( $registration instanceof EE_Registration ) {
					$registration_payment_data[ $registration->ID() ] = array(
						'paid' => $registration->pretty_paid(),
						'owing' => EEH_Template::format_currency( $registration->final_price() - $registration->paid() ),
					);
				}
			}
		}
		return $registration_payment_data;
	}



	/**
	 * _maybe_send_notifications
	 *
	 * determines whether or not the admin has indicated that notifications should be sent.
	 * If so, will toggle a filter switch for delivering registration notices.
	 * If passed an EE_Payment object, then it will trigger payment notifications instead.
	 *
	 * @access protected
	 * @param \EE_Payment | null $payment
	 */
	protected function _maybe_send_notifications( $payment = null ) {
		switch ( $payment instanceof EE_Payment ) {
			// payment notifications
			case true :
				if (
					isset(
						$this->_req_data[ 'txn_payments' ],
						$this->_req_data[ 'txn_payments' ][ 'send_notifications' ]
					) &&
					filter_var( $this->_req_data[ 'txn_payments' ][ 'send_notifications' ], FILTER_VALIDATE_BOOLEAN )
				) {
					$this->_process_payment_notification( $payment );
				}
				break;
			// registration notifications
			case false :
				if (
					isset(
						$this->_req_data[ 'txn_reg_status_change' ],
						$this->_req_data[ 'txn_reg_status_change' ][ 'send_notifications' ]
					) &&
					filter_var( $this->_req_data[ 'txn_reg_status_change' ][ 'send_notifications' ], FILTER_VALIDATE_BOOLEAN )
				) {
					add_filter( 'FHEE__EED_Messages___maybe_registration__deliver_notifications', '__return_true' );
				}
				break;
		}
	}



	/**
	 * _send_payment_reminder
	 * 	generates HTML for the View Transaction Details Admin page
	*
	 * @access protected
	*	@return void
	*/
	protected function _send_payment_reminder() {
	    $TXN_ID = ( ! empty( $this->_req_data['TXN_ID'] )) ? absint( $this->_req_data['TXN_ID'] ) : FALSE;
		$transaction = EEM_Transaction::instance()->get_one_by_ID( $TXN_ID );
		$query_args = isset($this->_req_data['redirect_to'] ) ? array('action' => $this->_req_data['redirect_to'], 'TXN_ID' => $this->_req_data['TXN_ID'] ) : array();
		do_action( 'AHEE__Transactions_Admin_Page___send_payment_reminder__process_admin_payment_reminder', $transaction );
		$this->_redirect_after_action( FALSE, __('payment reminder', 'event_espresso'), __('sent', 'event_espresso'), $query_args, TRUE );
	}



	/**
	 *  get_transactions
	 *    get transactions for given parameters (used by list table)
	 *
	 * @param  int     $perpage how many transactions displayed per page
	 * @param  boolean $count return the count or objects
	 * @param string   $view
	 * @return mixed int = count || array of transaction objects
	 */
	public function get_transactions( $perpage, $count = FALSE, $view = '' ) {

		$TXN = EEM_Transaction::instance();

	    $start_date = isset( $this->_req_data['txn-filter-start-date'] ) ? wp_strip_all_tags( $this->_req_data['txn-filter-start-date'] ) : date( 'm/d/Y', strtotime( '-10 year' ));
	    $end_date = isset( $this->_req_data['txn-filter-end-date'] ) ? wp_strip_all_tags( $this->_req_data['txn-filter-end-date'] ) : date( 'm/d/Y' );

	    //make sure our timestamps start and end right at the boundaries for each day
	    $start_date = date( 'Y-m-d', strtotime( $start_date ) ) . ' 00:00:00';
	    $end_date = date( 'Y-m-d', strtotime( $end_date ) ) . ' 23:59:59';


	    //convert to timestamps
	    $start_date = strtotime( $start_date );
	    $end_date = strtotime( $end_date );

	    //makes sure start date is the lowest value and vice versa
	    $start_date = min( $start_date, $end_date );
	    $end_date = max( $start_date, $end_date );

	    //convert to correct format for query
	$start_date = EEM_Transaction::instance()->convert_datetime_for_query( 'TXN_timestamp', date( 'Y-m-d H:i:s', $start_date ), 'Y-m-d H:i:s' );
	$end_date = EEM_Transaction::instance()->convert_datetime_for_query( 'TXN_timestamp', date( 'Y-m-d H:i:s', $end_date ), 'Y-m-d H:i:s' );



	    //set orderby
		$this->_req_data['orderby'] = ! empty($this->_req_data['orderby']) ? $this->_req_data['orderby'] : '';

		switch ( $this->_req_data['orderby'] ) {
			case 'TXN_ID':
				$orderby = 'TXN_ID';
				break;
			case 'ATT_fname':
				$orderby = 'Registration.Attendee.ATT_fname';
				break;
			case 'event_name':
				$orderby = 'Registration.Event.EVT_name';
				break;
			default: //'TXN_timestamp'
				$orderby = 'TXN_timestamp';
		}

		$sort = ( isset( $this->_req_data['order'] ) && ! empty( $this->_req_data['order'] )) ? $this->_req_data['order'] : 'DESC';
		$current_page = isset( $this->_req_data['paged'] ) && !empty( $this->_req_data['paged'] ) ? $this->_req_data['paged'] : 1;
		$per_page = isset( $perpage ) && !empty( $perpage ) ? $perpage : 10;
		$per_page = isset( $this->_req_data['perpage'] ) && !empty( $this->_req_data['perpage'] ) ? $this->_req_data['perpage'] : $per_page;

		$offset = ($current_page-1)*$per_page;
		$limit = array( $offset, $per_page );

		$_where = array(
			'TXN_timestamp' => array('BETWEEN', array($start_date, $end_date) ),
			'Registration.REG_count' => 1
		);

		if ( isset( $this->_req_data['EVT_ID'] ) ) {
			$_where['Registration.EVT_ID'] = $this->_req_data['EVT_ID'];
		}

		if ( isset( $this->_req_data['s'] ) ) {
			$search_string = '%' . $this->_req_data['s'] . '%';
			$_where['OR'] = array(
				'Registration.Event.EVT_name' => array( 'LIKE', $search_string ),
				'Registration.Event.EVT_desc' => array( 'LIKE', $search_string ),
				'Registration.Event.EVT_short_desc' => array( 'LIKE' , $search_string ),
				'Registration.Attendee.ATT_full_name' => array( 'LIKE', $search_string ),
				'Registration.Attendee.ATT_fname' => array( 'LIKE', $search_string ),
				'Registration.Attendee.ATT_lname' => array( 'LIKE', $search_string ),
				'Registration.Attendee.ATT_short_bio' => array( 'LIKE', $search_string ),
				'Registration.Attendee.ATT_email' => array('LIKE', $search_string ),
				'Registration.Attendee.ATT_address' => array( 'LIKE', $search_string ),
				'Registration.Attendee.ATT_address2' => array( 'LIKE', $search_string ),
				'Registration.Attendee.ATT_city' => array( 'LIKE', $search_string ),
				'Registration.REG_final_price' => array( 'LIKE', $search_string ),
				'Registration.REG_code' => array( 'LIKE', $search_string ),
				'Registration.REG_count' => array( 'LIKE' , $search_string ),
				'Registration.REG_group_size' => array( 'LIKE' , $search_string ),
				'Registration.Ticket.TKT_name' => array( 'LIKE', $search_string ),
				'Registration.Ticket.TKT_description' => array( 'LIKE', $search_string ),
				'Payment.PAY_source' => array('LIKE', $search_string ),
				'Payment.Payment_Method.PMD_name' => array('LIKE', $search_string ),
				'TXN_session_data' => array( 'LIKE', $search_string ),
				'Payment.PAY_txn_id_chq_nmbr' => array( 'LIKE', $search_string )
				);
		}

		//failed transactions
		$failed = ( ! empty( $this->_req_data['status'] ) && $this->_req_data['status'] == 'failed' && ! $count ) || ( $count && $view == 'failed' ) ? TRUE: FALSE;
		$abandoned = ( ! empty( $this->_req_data['status'] ) && $this->_req_data['status'] == 'abandoned' && ! $count ) || ( $count && $view == 'abandoned' ) ? TRUE: FALSE;

		if ( $failed ) {
			$_where[ 'STS_ID' ] = EEM_Transaction::failed_status_code;
		} else if ( $abandoned ) {
				$_where['STS_ID'] = EEM_Transaction::abandoned_status_code;
		} else {
				$_where['STS_ID'] = array( '!=', EEM_Transaction::failed_status_code );
				$_where['STS_ID*'] = array( '!=', EEM_Transaction::abandoned_status_code );
		}

		$query_params = array( $_where, 'order_by' => array( $orderby => $sort ), 'limit' => $limit );

		$transactions = $count ? $TXN->count( array($_where), 'TXN_ID', TRUE ) : $TXN->get_all($query_params);


		return $transactions;

	}



}
