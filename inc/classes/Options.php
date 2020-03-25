<?php

namespace WpeBannerNotification;

use \Wpextend\Package\AdminNotice;
use \Wpextend\Package\RenderAdminHtml;
use \Wpextend\Package\TypeField;
use \Wpextend\Package\Multilanguage;

/**
*
*/
class Options {

    private static $_instance,
        $name_admin_post_options_update = 'wpe_banner_notification_options_update';
    
    public static $prefix_name_database = 'wpe_banner_notification_';
    
    static public $admin_url = '';

    private $options;
    
    public $wordpress_default_locale,
        $wordpress_current_langage;

	/**
	*
	*
	*/
	public static function getInstance() {

        if (is_null(self::$_instance)) {
             self::$_instance = new Options();
        }

        return self::$_instance;
   }



   /**
	* The constructor.
	*
	* @return void
	*/
	private function __construct() {

		// Configure hooks
        $this->create_hooks();

        // Get database options
        $this->options = get_option( self::$prefix_name_database . 'options', true );

        // Mutlilanguages initialisation
		$this->multilanguages_initialisation();
    }


    /**
	 * Mutlilanguages initialisation
	 *
	 */
	public function multilanguages_initialisation(){

		// Get default locale
		$this->wordpress_default_locale = substr(Multilanguage::get_wplang(), 0, 2);

		// Get admin & front current language
		if( apply_filters( 'wpml_current_language', NULL ) ) {
			$this->wordpress_current_langage = apply_filters( 'wpml_current_language', NULL );
		}
		elseif( isset($_GET['lang']) && !empty($_GET['lang']) ) {
			$this->wordpress_current_langage = $_GET['lang'];
		}

        if( ! $this->wordpress_current_langage || $this->wordpress_current_langage == 'all') {
			$this->wordpress_current_langage = $this->wordpress_default_locale;
        }
	}



    /**
	* Register some Hooks
	*
	* @return void
	*/
	public function create_hooks() {

        add_action( 'admin_menu', array($this, 'define_admin_menu') );
        add_action( 'admin_post_' . self::$name_admin_post_options_update, array($this, 'update') ); 
    }
    


    /**
     * Add sub-menu page into WPExtend menu
     * 
     */
    public function define_admin_menu() {

        // Base 64 encoded SVG image.
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( PLUGIN_DIR_PATH . 'assets/img/icon.svg' ) );
        add_menu_page(PLUGIN_NAME, PLUGIN_NAME, 'manage_options', 'wpe_banner_notification', array( $this, 'render_admin_page' ), $icon_svg );
    }



   /**
	* Render HTML admin page
	*
	* @return string
	*/
	public function render_admin_page() {

        // Header page & open form
		$retour_html = RenderAdminHtml::header( PLUGIN_NAME . ' (' . strtoupper($this->wordpress_current_langage) . ')' );

        $retour_html .= '<div class="mt-1 white">';
        $retour_html .= RenderAdminHtml::form_open( admin_url( 'admin-post.php' ), self::$name_admin_post_options_update );

        $retour_html .= RenderAdminHtml::table_edit_open();
        $retour_html .= TypeField::render_input_text( 'Title', 'title', $this->get_options('title') );
        $retour_html .= TypeField::render_input_textarea( 'Content', 'content', $this->get_options('content') );
        $retour_html .= TypeField::render_input_hidden( 'lang', $this->wordpress_current_langage );
        $retour_html .= RenderAdminHtml::table_edit_close();

        $retour_html .= RenderAdminHtml::form_close( 'Save', true );
        $retour_html .= '</div>';

		// return
		echo $retour_html;
    }



    /**
     * Save options
     * 
     */
    public function update() {

        check_admin_referer($_POST['action']);

        // Allow new users
        if( isset($_POST['title'], $_POST['content'], $_POST['lang']) ) {
            
            $current_options = $this->get_options();
            if( ! $current_options || ! is_array() ) $current_options = [];
            
            if( $_POST['lang'] == $this->wordpress_default_locale ) {
                $current_options[ $this->wordpress_default_locale ] = [
                    'title' => $_POST['title'],
                    'content' => $_POST['content']
                ];
            }
            $current_options[ $_POST['lang'] ] = [
                'title' => $_POST['title'],
                'content' => $_POST['content']
            ];
            update_option( self::$prefix_name_database . 'options', $current_options );

            AdminNotice::add_notice( 'WpeBannerNotification-0', 'Options saved.', 'success', true, true, PLUGIN_NAME );
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }



    /**
     * Get options saved in database
     * 
     */
    public function get_options( $label = false ) {
    
        if( $label ) {

            if( is_array($this->options) && isset($this->options[$this->wordpress_current_langage][$label]) ) {
                return $this->options[$this->wordpress_current_langage][$label];
            }

            return false;
        }
        
        return $this->options;
    }



}

