<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Mfn_Elementor_Widget_Offer extends \Elementor\Widget_Base {

  /**
	 * Get script dependences
	 */

  public function get_script_depends() {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			wp_register_script( 'mfn-offer', get_theme_file_uri( '/functions/plugins/elementor/assets/widget-offer-preview.js' ), [ 'elementor-frontend' ], MFN_THEME_VERSION, true );
			return [ 'mfn-offer' ];
		}

		return [];
	}

	/**
	 * Get widget name
	 */

	public function get_name() {
		return 'mfn_offer';
	}

	/**
	 * Get widget title
	 */

	public function get_title() {
		return __( 'Be • Offer', 'mfn-opts' );
	}

	/**
	 * Get widget icon
	 */

	public function get_icon() {
		return 'eicon-slides';
	}

	/**
	 * Get widget categories
	 */

	public function get_categories() {
		return [ 'mfn_builder' ];
	}

	/**
	 * Register widget controls
	 */

	protected function _register_controls() {

		$this->start_controls_section(
			'options_section',
			[
				'label' => __( 'Options', 'mfn-opts' ),
			]
		);

		$this->add_control(
			'category',
			[
				'label' => __( 'Category', 'mfn-opts' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options'	=> mfn_get_categories('offer-types'),
				'default' => "",
			]
		);

		$this->add_control(
			'align',
			[
				'label' => __( 'Text align', 'mfn-opts' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options'	=> [
          'left' => __('Left', 'mfn-opts'),
          'right' => __('Right', 'mfn-opts'),
          'center' => __('Center', 'mfn-opts'),
          'justify' => __('Justify', 'mfn-opts'),
        ],
				'default' => "left",
			]
		);

		$this->end_controls_section();

	}

	/**
	 * Render widget output on the frontend
	 */

	protected function render() {

		$settings = $this->get_settings_for_display();

		echo sc_offer( $settings );

	}

}
