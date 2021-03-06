<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Radio_Button_Input_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Form_Input_With_Options_Test extends EE_UnitTestCase{
	function test_single_valued(){
		//create a form with radio button in it.
		$form = new EE_Form_Section_Proper(array(
			'name' => 'test1',
			'subsections' => array(
				'radio1' => new EE_Radio_Button_Input(array(
					'option1' => 'Option 1',
					'option2' => 'Option 2' )
				))
			));
		$radio_button = $form->get_input( 'radio1' );
		//that radio button's normalization strategy is correct
		$this->assertInstanceOf( 'EE_Radio_Button_Input', $radio_button );
		$this->assertInstanceOf( 'EE_Text_Normalization', $radio_button->get_normalization_strategy() );
		//and that when it receives input, it sets it correctly
		$form->receive_form_submission( array( 'radio1' => 'option1' ) );
		$this->assertTrue($form->is_valid() );
		$form->receive_form_submission( array( 'radio1' => 'option_nonexistent' ) );
		$this->assertFalse( $form->is_valid() );
	}

	/**
	 * Verifies we don't change these options into booleans
	 * @group 8006
	 */
	function test_double_valued_with_blank(){
		EEM_Country::instance()->update( array( 'CNT_active' => false ), array() );
		EEM_Country::instance()->update( array( 'CNT_active' => true), array( 'limit' => 1 ) );
		$active_countries =  EEM_Country::instance()->get_all( array( array( 'CNT_active' => true ) ) );
		$this->assertEquals( 1, count( $active_countries ) );
		$active_country = reset( $active_countries );
		$country_input = new EE_Country_Select_Input();
		$this->assertEquals(
		array(
			'' => '',
			$active_country->ID() => $active_country->name()
		),
		$country_input->options() );
		$this->assertInstanceOf( 'EE_Text_Normalization', $country_input->get_normalization_strategy() );
	}

	function test_many_valued(){
		//create a form with radio button in it.
		$form = new EE_Form_Section_Proper(array(
			'name' => 'test1',
			'subsections' => array(
				'checkbox1' => new EE_Checkbox_Multi_Input(array(
					'option1' => 'Option 1',
					'option2' => 'Option 2' )
				))
			));
		$checkbox = $form->get_input( 'checkbox1' );
		//that radio button's normalization strategy is correct
		$this->assertInstanceOf( 'EE_Checkbox_Multi_Input', $checkbox );
		$this->assertInstanceOf( 'EE_Many_Valued_Normalization', $checkbox->get_normalization_strategy() );
		//and that when it receives input, it sets it correctly
		$form->receive_form_submission( array( 'checkbox1' => array( 'option1' ) ) );
		$this->assertTrue($form->is_valid() );
		$form->receive_form_submission( array( 'checkbox1' => array( 'option_nonexistent' ) ) );
		$this->assertFalse( $form->is_valid() );
		$form->receive_form_submission( array( 'checkbox1' => 'option1' ) );
		$this->assertTrue( $form->is_valid() );
		$form->receive_form_submission( array( 'checkbox1' => 'option_nonexistent' ) );
		$this->assertFalse( $form->is_valid() );
	}

}

// End of file EE_Radio_Button_Input_Test.php