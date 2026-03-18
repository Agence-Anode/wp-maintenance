<?php
/*
Plugin Name: Anode Warning
Plugin URI: https://agence-anode.fr/
Description: Affiche un bandeau de maintenance pour avertir les administrateurs (frontend et backend).
Version: 2.1.0
Author: Agence Anode
Author URI: https://agence-anode.fr/
License: GPLv2 or later
Text Domain: anode-warning
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Anode_Warning {

    private $has_aiwm = false;

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'warning_text' ) );
        add_action( 'wp_footer', array( $this, 'warning_text' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_head', array( $this, 'admin_menu_styles' ) );
        add_action( 'plugins_loaded', array( $this, 'detect_environment' ), 20 );
    }

    public function detect_environment() {
        $this->has_aiwm = defined( 'AI1WM_PLUGIN_NAME' );
        add_action( 'admin_menu', array( $this, 'add_menu_page' ), 20 );
    }

    public function add_menu_page() {
        $parent = $this->has_aiwm ? 'ai1wm_export' : 'tools.php';
        add_submenu_page(
            $parent,
            'Anode Warning',
            'Anode Warning',
            'manage_options',
            'anode_warning',
            array( $this, 'options_page' )
        );
    }

    public function admin_menu_styles() {
        ?>
        <style>
            #adminmenu a[href*="page=anode_warning"] {
                background: #6B54FF !important;
                color: #fff !important;
                border-radius: 4px;
            }
            #adminmenu a[href*="page=anode_warning"]:hover {
                background: #5a45e0 !important;
            }
        </style>
        <?php
    }

    public function enqueue_styles() {
        $options = get_option( 'anode_warning_options' );
        if ( empty( $options['warning_enabled'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_enqueue_style(
            'anode-warning-fonts',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap',
            array(),
            null
        );
        wp_enqueue_style(
            'anode-warning',
            plugin_dir_url( __FILE__ ) . 'anode-warning.css',
            array( 'anode-warning-fonts' ),
            '2.0'
        );
    }

    public function register_settings() {
        register_setting( 'anode_warning_options', 'anode_warning_options', array( $this, 'sanitize_options' ) );
        add_settings_section( 'anode_warning_section', 'Paramètres du bandeau', array( $this, 'section_info' ), 'anode_warning' );
        add_settings_field( 'warning_enabled', 'Activer le bandeau', array( $this, 'warning_enabled_field' ), 'anode_warning', 'anode_warning_section' );
        add_settings_field( 'warning_text', 'Texte du bandeau', array( $this, 'warning_text_field' ), 'anode_warning', 'anode_warning_section' );
    }

    public function sanitize_options( $input ) {
        $output = array();
        $output['warning_enabled'] = ! empty( $input['warning_enabled'] ) ? 1 : 0;
        $output['warning_text'] = ! empty( $input['warning_text'] ) ? wp_kses_post( $input['warning_text'] ) : '';
        return $output;
    }

    public function section_info() {
        echo '<p>Configurez le message de maintenance affiché aux administrateurs.</p>';
    }

    public function warning_enabled_field() {
        $options = get_option( 'anode_warning_options' );
        $checked = ! empty( $options['warning_enabled'] ) ? 'checked' : '';
        echo '<input type="checkbox" name="anode_warning_options[warning_enabled]" value="1" ' . $checked . '>';
    }

    public function warning_text_field() {
        $options = get_option( 'anode_warning_options' );
        $warning_text = ! empty( $options['warning_text'] ) ? $options['warning_text'] : '';
        wp_editor( $warning_text, 'warning_text', array(
            'textarea_name' => 'anode_warning_options[warning_text]',
        ) );
    }

    public function options_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'anode_warning_options' );
                do_settings_sections( 'anode_warning' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function warning_text() {
        $options = get_option( 'anode_warning_options' );
        if ( empty( $options['warning_enabled'] ) || empty( $options['warning_text'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $logo_url = esc_url( plugin_dir_url( __FILE__ ) . 'assets/logo.svg' );
        ?>
        <div class="notice-warning anode-warning">
            <div class="anode-warning-wrapper">
                <img src="<?php echo $logo_url; ?>" alt="Anode" class="anode-warning-logo" />
                <div class="anode-warning-text">
                    <?php echo wp_kses_post( $options['warning_text'] ); ?>
                </div>
                <a href="#" class="anode-warning-close" onclick="this.closest('.anode-warning').style.display='none'; return false;" aria-label="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M175 175C184.4 165.7 199.6 165.7 208.1 175L255.1 222.1L303 175C312.4 165.7 327.6 165.7 336.1 175C346.3 184.4 346.3 199.6 336.1 208.1L289.9 255.1L336.1 303C346.3 312.4 346.3 327.6 336.1 336.1C327.6 346.3 312.4 346.3 303 336.1L255.1 289.9L208.1 336.1C199.6 346.3 184.4 346.3 175 336.1C165.7 327.6 165.7 312.4 175 303L222.1 255.1L175 208.1C165.7 199.6 165.7 184.4 175 175V175zM512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/>
                    </svg>
                </a>
            </div>
        </div>
        <?php
    }
}

new Anode_Warning();

// Auto-updater via GitHub Releases.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-anode-updater.php';
new Anode_Updater( __FILE__ );
