<?php
/**
 * KDNA Forms Elementor Widget
 *
 * Comprehensive Elementor widget with full styling options for KDNA Forms.
 * Works with Elementor popups.
 *
 * @package KDNA_Forms
 * @subpackage Elementor
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KDNA_Forms_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'kdna-forms';
	}

	public function get_title() {
		return esc_html__( 'KDNA Form', 'kdnaforms' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'kdna-forms', 'general' );
	}

	public function get_keywords() {
		return array( 'form', 'contact', 'kdna', 'survey', 'quiz' );
	}

	/**
	 * Get available forms for the dropdown.
	 */
	private function get_forms_list() {
		global $wpdb;
		$forms = array( '' => esc_html__( '-- Select a Form --', 'kdnaforms' ) );
		$table = $wpdb->prefix . 'gf_form';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
			$results = $wpdb->get_results( "SELECT id, title FROM {$table} WHERE is_active = 1 AND is_trash = 0 ORDER BY title ASC" );
			if ( $results ) {
				foreach ( $results as $form ) {
					$forms[ $form->id ] = $form->title;
				}
			}
		}

		return $forms;
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_form_container();
		$this->register_style_title();
		$this->register_style_description();
		$this->register_style_labels();
		$this->register_style_sub_labels();
		$this->register_style_inputs();
		$this->register_style_textarea();
		$this->register_style_select();
		$this->register_style_checkbox_radio();
		$this->register_style_file_upload();
		$this->register_style_section_break();
		$this->register_style_submit_button();
		$this->register_style_prev_next_buttons();
		$this->register_style_validation();
		$this->register_style_progress_bar();
		$this->register_style_confirmation();
		$this->register_style_field_spacing();
	}

	// ==========================================
	// CONTENT TAB
	// ==========================================

	private function register_content_controls() {
		$this->start_controls_section( 'section_form', array(
			'label' => esc_html__( 'Form Settings', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		) );

		$this->add_control( 'form_id', array(
			'label'   => esc_html__( 'Select Form', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => $this->get_forms_list(),
			'default' => '',
		) );

		$this->add_control( 'show_title', array(
			'label'        => esc_html__( 'Show Title', 'kdnaforms' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'label_on'     => esc_html__( 'Yes', 'kdnaforms' ),
			'label_off'    => esc_html__( 'No', 'kdnaforms' ),
			'return_value' => 'yes',
		) );

		$this->add_control( 'show_description', array(
			'label'        => esc_html__( 'Show Description', 'kdnaforms' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'label_on'     => esc_html__( 'Yes', 'kdnaforms' ),
			'label_off'    => esc_html__( 'No', 'kdnaforms' ),
			'return_value' => 'yes',
		) );

		$this->add_control( 'use_ajax', array(
			'label'        => esc_html__( 'AJAX Submission', 'kdnaforms' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'label_on'     => esc_html__( 'Yes', 'kdnaforms' ),
			'label_off'    => esc_html__( 'No', 'kdnaforms' ),
			'return_value' => 'yes',
		) );

		$this->add_control( 'tab_index', array(
			'label'   => esc_html__( 'Tab Index Start', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		) );

		$this->add_control( 'form_theme', array(
			'label'   => esc_html__( 'Form Theme', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => array(
				''         => esc_html__( 'Default', 'kdnaforms' ),
				'orbital'  => esc_html__( 'Orbital', 'kdnaforms' ),
				'gravity'  => esc_html__( 'Classic', 'kdnaforms' ),
			),
			'default' => '',
		) );

		$this->add_control( 'custom_class', array(
			'label'       => esc_html__( 'Custom CSS Class', 'kdnaforms' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'description' => esc_html__( 'Add a custom CSS class to the form wrapper.', 'kdnaforms' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - FORM CONTAINER
	// ==========================================

	private function register_style_form_container() {
		$this->start_controls_section( 'section_style_container', array(
			'label' => esc_html__( 'Form Container', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
			'name'     => 'container_background',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .kdna-elementor-form-wrapper',
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'container_border',
			'selector' => '{{WRAPPER}} .kdna-elementor-form-wrapper',
		) );

		$this->add_responsive_control( 'container_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .kdna-elementor-form-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'container_box_shadow',
			'selector' => '{{WRAPPER}} .kdna-elementor-form-wrapper',
		) );

		$this->add_responsive_control( 'container_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .kdna-elementor-form-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'container_margin', array(
			'label'      => esc_html__( 'Margin', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .kdna-elementor-form-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'container_max_width', array(
			'label'      => esc_html__( 'Max Width', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%', 'vw' ),
			'range'      => array(
				'px' => array( 'min' => 200, 'max' => 1600 ),
				'%'  => array( 'min' => 10, 'max' => 100 ),
				'vw' => array( 'min' => 10, 'max' => 100 ),
			),
			'selectors' => array(
				'{{WRAPPER}} .kdna-elementor-form-wrapper' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - TITLE
	// ==========================================

	private function register_style_title() {
		$this->start_controls_section( 'section_style_title', array(
			'label'     => esc_html__( 'Title', 'kdnaforms' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_title' => 'yes' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'title_typography',
			'selector' => '{{WRAPPER}} .gform_heading .gform_title',
		) );

		$this->add_control( 'title_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_heading .gform_title' => 'color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'title_alignment', array(
			'label'   => esc_html__( 'Alignment', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::CHOOSE,
			'options' => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'kdnaforms' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'kdnaforms' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'kdnaforms' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array(
				'{{WRAPPER}} .gform_heading .gform_title' => 'text-align: {{VALUE}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Text_Shadow::get_type(), array(
			'name'     => 'title_text_shadow',
			'selector' => '{{WRAPPER}} .gform_heading .gform_title',
		) );

		$this->add_responsive_control( 'title_margin', array(
			'label'      => esc_html__( 'Margin', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_heading .gform_title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'title_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_heading .gform_title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - DESCRIPTION
	// ==========================================

	private function register_style_description() {
		$this->start_controls_section( 'section_style_description', array(
			'label'     => esc_html__( 'Description', 'kdnaforms' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_description' => 'yes' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'description_typography',
			'selector' => '{{WRAPPER}} .gform_heading .gform_description',
		) );

		$this->add_control( 'description_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_heading .gform_description' => 'color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'description_alignment', array(
			'label'   => esc_html__( 'Alignment', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::CHOOSE,
			'options' => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'kdnaforms' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'kdnaforms' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'kdnaforms' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array(
				'{{WRAPPER}} .gform_heading .gform_description' => 'text-align: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'description_margin', array(
			'label'      => esc_html__( 'Margin', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_heading .gform_description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - LABELS
	// ==========================================

	private function register_style_labels() {
		$this->start_controls_section( 'section_style_labels', array(
			'label' => esc_html__( 'Labels', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'label_typography',
			'selector' => '{{WRAPPER}} .gfield_label label, {{WRAPPER}} .gfield_label',
		) );

		$this->add_control( 'label_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gfield_label label, {{WRAPPER}} .gfield_label' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'required_color', array(
			'label'     => esc_html__( 'Required Indicator Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gfield_required' => 'color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'label_margin', array(
			'label'      => esc_html__( 'Margin', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gfield_label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'label_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gfield_label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - SUB-LABELS
	// ==========================================

	private function register_style_sub_labels() {
		$this->start_controls_section( 'section_style_sub_labels', array(
			'label' => esc_html__( 'Sub-Labels', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'sub_label_typography',
			'selector' => '{{WRAPPER}} .gform_body .ginput_complex label, {{WRAPPER}} .gform_body .gfield_description',
		) );

		$this->add_control( 'sub_label_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body .ginput_complex label, {{WRAPPER}} .gform_body .gfield_description' => 'color: {{VALUE}};',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - INPUT FIELDS
	// ==========================================

	private function register_style_inputs() {
		$this->start_controls_section( 'section_style_inputs', array(
			'label' => esc_html__( 'Input Fields', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'input_typography',
			'selector' => '{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])',
		) );

		$this->start_controls_tabs( 'input_style_tabs' );

		// Normal state
		$this->start_controls_tab( 'input_normal', array(
			'label' => esc_html__( 'Normal', 'kdnaforms' ),
		) );

		$this->add_control( 'input_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'input_background', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'input_border',
			'selector' => '{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])',
		) );

		$this->add_responsive_control( 'input_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'input_box_shadow',
			'selector' => '{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])',
		) );

		$this->end_controls_tab();

		// Focus state
		$this->start_controls_tab( 'input_focus', array(
			'label' => esc_html__( 'Focus', 'kdnaforms' ),
		) );

		$this->add_control( 'input_focus_border_color', array(
			'label'     => esc_html__( 'Border Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"]):focus' => 'border-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'input_focus_background', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"]):focus' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'input_focus_box_shadow',
			'selector' => '{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"]):focus',
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'input_placeholder_color', array(
			'label'     => esc_html__( 'Placeholder Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array(
				'{{WRAPPER}} .gform_body input::placeholder' => 'color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'input_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'input_height', array(
			'label'      => esc_html__( 'Height', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em' ),
			'range'      => array(
				'px' => array( 'min' => 20, 'max' => 100 ),
				'em' => array( 'min' => 1, 'max' => 6 ),
			),
			'selectors' => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])' => 'height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_control( 'input_transition', array(
			'label'      => esc_html__( 'Transition Duration (ms)', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 1000, 'step' => 50 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body input:not([type="radio"]):not([type="checkbox"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="file"]):not([type="hidden"])' => 'transition: all {{SIZE}}ms ease;',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - TEXTAREA
	// ==========================================

	private function register_style_textarea() {
		$this->start_controls_section( 'section_style_textarea', array(
			'label' => esc_html__( 'Textarea', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'textarea_typography',
			'selector' => '{{WRAPPER}} .gform_body textarea',
		) );

		$this->start_controls_tabs( 'textarea_style_tabs' );

		$this->start_controls_tab( 'textarea_normal', array( 'label' => esc_html__( 'Normal', 'kdnaforms' ) ) );

		$this->add_control( 'textarea_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body textarea' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'textarea_background', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body textarea' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'textarea_border',
			'selector' => '{{WRAPPER}} .gform_body textarea',
		) );

		$this->add_responsive_control( 'textarea_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .gform_body textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'textarea_box_shadow',
			'selector' => '{{WRAPPER}} .gform_body textarea',
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'textarea_focus', array( 'label' => esc_html__( 'Focus', 'kdnaforms' ) ) );

		$this->add_control( 'textarea_focus_border_color', array(
			'label'     => esc_html__( 'Border Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body textarea:focus' => 'border-color: {{VALUE}};' ),
		) );

		$this->add_control( 'textarea_focus_background', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body textarea:focus' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'textarea_focus_box_shadow',
			'selector' => '{{WRAPPER}} .gform_body textarea:focus',
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'textarea_placeholder_color', array(
			'label'     => esc_html__( 'Placeholder Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .gform_body textarea::placeholder' => 'color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'textarea_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .gform_body textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'textarea_min_height', array(
			'label'      => esc_html__( 'Min Height', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 50, 'max' => 500 ) ),
			'selectors'  => array( '{{WRAPPER}} .gform_body textarea' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - SELECT / DROPDOWN
	// ==========================================

	private function register_style_select() {
		$this->start_controls_section( 'section_style_select', array(
			'label' => esc_html__( 'Select / Dropdown', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'select_typography',
			'selector' => '{{WRAPPER}} .gform_body select',
		) );

		$this->add_control( 'select_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body select' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'select_background', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body select' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'select_border',
			'selector' => '{{WRAPPER}} .gform_body select',
		) );

		$this->add_responsive_control( 'select_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .gform_body select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'select_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .gform_body select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'select_height', array(
			'label'      => esc_html__( 'Height', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 20, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .gform_body select' => 'height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'select_focus_border_color', array(
			'label'     => esc_html__( 'Focus Border Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body select:focus' => 'border-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - CHECKBOX & RADIO
	// ==========================================

	private function register_style_checkbox_radio() {
		$this->start_controls_section( 'section_style_checkbox_radio', array(
			'label' => esc_html__( 'Checkbox & Radio', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'checkbox_size', array(
			'label'      => esc_html__( 'Size', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 40 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body input[type="checkbox"], {{WRAPPER}} .gform_body input[type="radio"]' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_control( 'checkbox_color', array(
			'label'     => esc_html__( 'Unchecked Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input[type="checkbox"], {{WRAPPER}} .gform_body input[type="radio"]' => 'accent-color: {{VALUE}}; border-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'checkbox_checked_color', array(
			'label'     => esc_html__( 'Checked Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input[type="checkbox"]:checked, {{WRAPPER}} .gform_body input[type="radio"]:checked' => 'accent-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'checkbox_border_color', array(
			'label'     => esc_html__( 'Border Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body input[type="checkbox"], {{WRAPPER}} .gform_body input[type="radio"]' => 'outline-color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'checkbox_border_radius', array(
			'label'      => esc_html__( 'Checkbox Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ), '%' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body input[type="checkbox"]' => 'border-radius: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'checkbox_label_typography',
			'label'    => esc_html__( 'Choice Label Typography', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gchoice_label, {{WRAPPER}} .gform_body .gchoice label',
		) );

		$this->add_control( 'checkbox_label_color', array(
			'label'     => esc_html__( 'Choice Label Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body .gchoice_label, {{WRAPPER}} .gform_body .gchoice label' => 'color: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'checkbox_spacing', array(
			'label'      => esc_html__( 'Spacing Between Options', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body .gchoice, {{WRAPPER}} .gform_body .kdnachoice' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - FILE UPLOAD
	// ==========================================

	private function register_style_file_upload() {
		$this->start_controls_section( 'section_style_file_upload', array(
			'label' => esc_html__( 'File Upload', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'file_button_typography',
			'label'    => esc_html__( 'Button Typography', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gform_fileupload_rules, {{WRAPPER}} .gform_body .button.gform_button_select_files',
		) );

		$this->start_controls_tabs( 'file_upload_tabs' );

		$this->start_controls_tab( 'file_upload_normal', array( 'label' => esc_html__( 'Normal', 'kdnaforms' ) ) );

		$this->add_control( 'file_button_color', array(
			'label'     => esc_html__( 'Button Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .button.gform_button_select_files' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'file_button_bg', array(
			'label'     => esc_html__( 'Button Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .button.gform_button_select_files' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'file_button_border',
			'selector' => '{{WRAPPER}} .gform_body .button.gform_button_select_files',
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'file_upload_hover', array( 'label' => esc_html__( 'Hover', 'kdnaforms' ) ) );

		$this->add_control( 'file_button_hover_color', array(
			'label'     => esc_html__( 'Button Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .button.gform_button_select_files:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'file_button_hover_bg', array(
			'label'     => esc_html__( 'Button Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .button.gform_button_select_files:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control( 'file_drop_area_bg', array(
			'label'     => esc_html__( 'Drop Area Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .gform_body .gform_drop_area' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'file_drop_area_border',
			'label'    => esc_html__( 'Drop Area Border', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gform_drop_area',
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - SECTION BREAK
	// ==========================================

	private function register_style_section_break() {
		$this->start_controls_section( 'section_style_section_break', array(
			'label' => esc_html__( 'Section Break', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'section_title_typography',
			'label'    => esc_html__( 'Title Typography', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gsection_title',
		) );

		$this->add_control( 'section_title_color', array(
			'label'     => esc_html__( 'Title Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gsection_title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'section_desc_typography',
			'label'    => esc_html__( 'Description Typography', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gsection_description',
		) );

		$this->add_control( 'section_desc_color', array(
			'label'     => esc_html__( 'Description Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gsection_description' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'section_break_border',
			'label'    => esc_html__( 'Bottom Border', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gsection',
		) );

		$this->add_responsive_control( 'section_break_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .gform_body .gsection' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'section_break_margin', array(
			'label'      => esc_html__( 'Margin', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .gform_body .gsection' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - SUBMIT BUTTON
	// ==========================================

	private function register_style_submit_button() {

		// Use a short variable for the high-specificity button selector
		$btn = '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer input[type="submit"], {{WRAPPER}} .gform_wrapper .gform_footer .gform_button, {{WRAPPER}} .gform_wrapper .gform_footer input[type="submit"]';
		$btn_hover = '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer input[type="submit"]:hover, {{WRAPPER}} .gform_wrapper .gform_footer .gform_button:hover, {{WRAPPER}} .gform_wrapper .gform_footer input[type="submit"]:hover';

		$this->start_controls_section( 'section_style_submit', array(
			'label' => esc_html__( 'Submit Button', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'submit_typography',
			'selector' => $btn,
		) );

		$this->start_controls_tabs( 'submit_tabs' );

		$this->start_controls_tab( 'submit_normal', array( 'label' => esc_html__( 'Normal', 'kdnaforms' ) ) );

		$this->add_control( 'submit_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( $btn => 'color: {{VALUE}} !important;' ),
		) );

		$this->add_control( 'submit_bg_color', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( $btn => 'background-color: {{VALUE}} !important; background-image: none !important;' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'submit_border',
			'selector' => $btn,
		) );

		$this->add_responsive_control( 'submit_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( $btn => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'submit_box_shadow',
			'selector' => $btn,
		) );

		$this->end_controls_tab();

		$this->start_controls_tab( 'submit_hover', array( 'label' => esc_html__( 'Hover', 'kdnaforms' ) ) );

		$this->add_control( 'submit_hover_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( $btn_hover => 'color: {{VALUE}} !important;' ),
		) );

		$this->add_control( 'submit_hover_bg_color', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( $btn_hover => 'background-color: {{VALUE}} !important; background-image: none !important;' ),
		) );

		$this->add_control( 'submit_hover_border_color', array(
			'label'     => esc_html__( 'Border Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( $btn_hover => 'border-color: {{VALUE}} !important;' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'submit_hover_box_shadow',
			'selector' => $btn_hover,
		) );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'submit_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'separator'  => 'before',
			'selectors'  => array( $btn => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ),
		) );

		$this->add_control( 'submit_width', array(
			'label'   => esc_html__( 'Width', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => array(
				'auto' => esc_html__( 'Auto', 'kdnaforms' ),
				'100%' => esc_html__( 'Full Width', 'kdnaforms' ),
			),
			'default'   => 'auto',
			'selectors' => array( $btn => 'width: {{VALUE}} !important;' ),
		) );

		$this->add_responsive_control( 'submit_alignment', array(
			'label'   => esc_html__( 'Alignment', 'kdnaforms' ),
			'type'    => \Elementor\Controls_Manager::CHOOSE,
			'options' => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'kdnaforms' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'kdnaforms' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'kdnaforms' ), 'icon' => 'eicon-text-align-right' ),
			),
			'selectors' => array( '{{WRAPPER}} .gform_wrapper .gform_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer' => 'text-align: {{VALUE}};' ),
		) );

		$this->add_control( 'submit_transition', array(
			'label'     => esc_html__( 'Transition Duration (ms)', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::SLIDER,
			'range'     => array( 'px' => array( 'min' => 0, 'max' => 1000, 'step' => 50 ) ),
			'selectors' => array( $btn => 'transition: all {{SIZE}}ms ease !important;' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - PREVIOUS / NEXT BUTTONS
	// ==========================================

	private function register_style_prev_next_buttons() {
		$this->start_controls_section( 'section_style_prev_next', array(
			'label' => esc_html__( 'Previous / Next Buttons', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'prev_next_heading_prev', array(
			'label' => esc_html__( 'Previous Button', 'kdnaforms' ),
			'type'  => \Elementor\Controls_Manager::HEADING,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'prev_typography',
			'selector' => '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button',
		) );

		$this->add_control( 'prev_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'prev_bg_color', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'prev_border',
			'selector' => '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button',
		) );

		$this->add_responsive_control( 'prev_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'prev_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'prev_hover_text_color', array(
			'label'     => esc_html__( 'Hover Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'prev_hover_bg', array(
			'label'     => esc_html__( 'Hover Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_previous_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_previous_button:hover' => 'background-color: {{VALUE}};' ),
		) );

		// Next button
		$this->add_control( 'prev_next_heading_next', array(
			'label'     => esc_html__( 'Next Button', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'next_typography',
			'selector' => '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button',
		) );

		$this->add_control( 'next_text_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'next_bg_color', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'next_border',
			'selector' => '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button',
		) );

		$this->add_responsive_control( 'next_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'next_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'next_hover_text_color', array(
			'label'     => esc_html__( 'Hover Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button:hover' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'next_hover_bg', array(
			'label'     => esc_html__( 'Hover Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer .gform_next_button, {{WRAPPER}} .gform_wrapper .gform_footer .gform_next_button:hover' => 'background-color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - VALIDATION ERRORS
	// ==========================================

	private function register_style_validation() {
		$this->start_controls_section( 'section_style_validation', array(
			'label' => esc_html__( 'Validation Errors', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'validation_msg_typography',
			'label'    => esc_html__( 'Message Typography', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gform_validation_errors, {{WRAPPER}} .gform_body .validation_message',
		) );

		$this->add_control( 'validation_msg_color', array(
			'label'     => esc_html__( 'Message Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body .gform_validation_errors, {{WRAPPER}} .gform_body .validation_message' => 'color: {{VALUE}};',
			),
		) );

		$this->add_control( 'validation_msg_bg', array(
			'label'     => esc_html__( 'Message Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body .gform_validation_errors' => 'background-color: {{VALUE}};',
			),
		) );

		$this->add_control( 'validation_field_border_color', array(
			'label'     => esc_html__( 'Error Field Border Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .gform_body .gfield_error input, {{WRAPPER}} .gform_body .gfield_error textarea, {{WRAPPER}} .gform_body .gfield_error select' => 'border-color: {{VALUE}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'validation_container_border',
			'label'    => esc_html__( 'Container Border', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gform_validation_errors',
		) );

		$this->add_responsive_control( 'validation_container_padding', array(
			'label'      => esc_html__( 'Container Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body .gform_validation_errors' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'validation_container_border_radius', array(
			'label'      => esc_html__( 'Container Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body .gform_validation_errors' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - PROGRESS BAR (Multi-Page)
	// ==========================================

	private function register_style_progress_bar() {
		$this->start_controls_section( 'section_style_progress_bar', array(
			'label' => esc_html__( 'Progress Bar (Multi-Page)', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'progress_bar_bg', array(
			'label'     => esc_html__( 'Bar Background', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gform_page_steps' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_control( 'progress_bar_fill', array(
			'label'     => esc_html__( 'Progress Fill Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gform_page_steps .gf_step_active, {{WRAPPER}} .gform_body .percentbar_blue' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'progress_bar_height', array(
			'label'      => esc_html__( 'Bar Height', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 2, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .gform_body .percentbar_blue' => 'height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'progress_bar_border_radius', array(
			'label'      => esc_html__( 'Bar Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_body .percentbar_blue, {{WRAPPER}} .gform_body .gform_percentage_bar' => 'border-radius: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'progress_step_typography',
			'label'    => esc_html__( 'Step Text Typography', 'kdnaforms' ),
			'selector' => '{{WRAPPER}} .gform_body .gf_step, {{WRAPPER}} .gform_body .gform_percentage_bar_text',
		) );

		$this->add_control( 'progress_step_color', array(
			'label'     => esc_html__( 'Step Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gf_step, {{WRAPPER}} .gform_body .gform_percentage_bar_text' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'progress_active_step_color', array(
			'label'     => esc_html__( 'Active Step Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gf_step_active' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'progress_completed_step_color', array(
			'label'     => esc_html__( 'Completed Step Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .gform_body .gf_step_completed' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - CONFIRMATION MESSAGE
	// ==========================================

	private function register_style_confirmation() {
		$this->start_controls_section( 'section_style_confirmation', array(
			'label' => esc_html__( 'Confirmation Message', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'confirmation_typography',
			'selector' => '{{WRAPPER}} .kdna-elementor-form-wrapper .gform_confirmation_message',
		) );

		$this->add_control( 'confirmation_color', array(
			'label'     => esc_html__( 'Text Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .kdna-elementor-form-wrapper .gform_confirmation_message' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'confirmation_bg', array(
			'label'     => esc_html__( 'Background Color', 'kdnaforms' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .kdna-elementor-form-wrapper .gform_confirmation_message' => 'background-color: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'confirmation_border',
			'selector' => '{{WRAPPER}} .kdna-elementor-form-wrapper .gform_confirmation_message',
		) );

		$this->add_responsive_control( 'confirmation_border_radius', array(
			'label'      => esc_html__( 'Border Radius', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .kdna-elementor-form-wrapper .gform_confirmation_message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'confirmation_padding', array(
			'label'      => esc_html__( 'Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .kdna-elementor-form-wrapper .gform_confirmation_message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	// ==========================================
	// STYLE TAB - FIELD SPACING
	// ==========================================

	private function register_style_field_spacing() {
		$this->start_controls_section( 'section_style_field_spacing', array(
			'label' => esc_html__( 'Field Spacing', 'kdnaforms' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'field_row_gap', array(
			'label'      => esc_html__( 'Row Gap', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em' ),
			'range'      => array(
				'px' => array( 'min' => 0, 'max' => 60 ),
				'em' => array( 'min' => 0, 'max' => 4 ),
			),
			'selectors' => array(
				'{{WRAPPER}} .gform_body .gfield' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'field_column_gap', array(
			'label'      => esc_html__( 'Column Gap (Multi-column)', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'em' ),
			'range'      => array(
				'px' => array( 'min' => 0, 'max' => 60 ),
				'em' => array( 'min' => 0, 'max' => 4 ),
			),
			'selectors' => array(
				'{{WRAPPER}} .gform_body .gform_fields' => 'column-gap: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .gform_body .gfield--width-half' => 'padding-right: {{SIZE}}{{UNIT}};',
			),
		) );

		$this->add_responsive_control( 'footer_margin', array(
			'label'      => esc_html__( 'Button Area Margin', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_wrapper .gform_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
			),
		) );

		$this->add_responsive_control( 'footer_padding', array(
			'label'      => esc_html__( 'Button Area Padding', 'kdnaforms' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .gform_wrapper .gform_footer, {{WRAPPER}} .gform_wrapper.gravity-theme .gform_footer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
			),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$form_id  = absint( $settings['form_id'] );

		if ( empty( $form_id ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="kdna-elementor-form-placeholder" style="padding:40px;text-align:center;background:#f5f5f5;border:2px dashed #ccc;border-radius:8px;">';
				echo '<p style="font-size:16px;color:#666;">' . esc_html__( 'Please select a KDNA Form from the widget settings.', 'kdnaforms' ) . '</p>';
				echo '</div>';
			}
			return;
		}

		$show_title       = $settings['show_title'] === 'yes';
		$show_description = $settings['show_description'] === 'yes';
		$use_ajax         = $settings['use_ajax'] === 'yes';
		$tab_index        = absint( $settings['tab_index'] );
		$form_theme       = sanitize_text_field( $settings['form_theme'] );
		$custom_class     = sanitize_html_class( $settings['custom_class'] );

		$wrapper_classes = array( 'kdna-elementor-form-wrapper' );
		if ( ! empty( $custom_class ) ) {
			$wrapper_classes[] = $custom_class;
		}

		echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';

		// Build shortcode attributes
		$shortcode_atts = array(
			'id'          => $form_id,
			'title'       => $show_title ? 'true' : 'false',
			'description' => $show_description ? 'true' : 'false',
			'ajax'        => $use_ajax ? 'true' : 'false',
			'tabindex'    => $tab_index,
		);

		if ( ! empty( $form_theme ) ) {
			$shortcode_atts['theme'] = $form_theme;
		}

		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
		}

		$shortcode = '[kdnaform ' . implode( ' ', $shortcode_parts ) . ']';

		// Render the form
		echo do_shortcode( $shortcode );

		echo '</div>';
	}

	// No content_template() - forces Elementor to always use server-side render()
	// which properly executes the shortcode and renders the actual form.
	protected function content_template() {}
}
