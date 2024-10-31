<?php
/**
 * Plugin Name: myCRED Retro
 * Plugin URI: http://mycred.me
 * Description: Allows you to give out points retroactively for past events.
 * Version: 1.2.5
 * Tags: mycred, points, retroactive
 * Author: myCRED
 * Author URI: http://www.mycred.me
 * Author Email: support@mycred.me
 * Requires at least: WP 4.8
 * Tested up to: WP 6.6.1
 * Text Domain: mycred_retro
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
if ( ! class_exists( 'myCRED_Retro_Plugin' ) ) :
	final class myCRED_Retro_Plugin {

		// Plugin Version
		public $version             = '1.2.5';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = NULL;
		public $plugin_name         = '';
		public $built_in_tools      = array();

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.2
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.2
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.2
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.2
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.2
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.2
		 */
		public function __construct() {

			$this->slug        = 'mycred-retro';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred_retro';
			$this->plugin_name = 'myCRED Retro';

			$this->built_in_tools = array(
				'mycred_retro_comments' => 'myCRED_Retro_Comments_Tool',
				'mycred_retro_content'  => 'myCRED_Retro_Content_Tool',
				'mycred_retro_users'    => 'myCRED_Retro_Users_Tool'
			);

			$this->define_constants();
			$this->includes();

			add_action( 'mycred_pre_init',            array( $this, 'setup_importers' ) );
			add_action( 'mycred_init',                array( $this, 'load_textdomain' ) );
			add_action( 'mycred_admin_init',          array( $this, 'admin_init' ) );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.2
		 */
		public function define_constants() {

			$this->define( 'MYCRED_RETRO_VERSION',      $this->version );
			$this->define( 'MYCRED_RETRO_SLUG',         $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY',   'mycred_default' );

			$this->define( 'MYCRED_RETRO_MAX',           150 );

			$this->define( 'MYCRED_RETRO',               __FILE__ );
			$this->define( 'MYCERD_RETRO_ROOT_DIR',      plugin_dir_path( MYCRED_RETRO ) );
			$this->define( 'MYCRED_RETRO_IMPORTERS_DIR', MYCERD_RETRO_ROOT_DIR . 'importers/' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.2
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.2
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );

		}

		/**
		 * Setup Importers
		 * @since 1.0
		 * @version 1.2
		 */
		public function setup_importers() {

			$this->built_in_tools = apply_filters( 'mycred_retro_tools', $this->built_in_tools );

			$this->file( MYCRED_RETRO_IMPORTERS_DIR . 'retro-comments.php' );
			$this->file( MYCRED_RETRO_IMPORTERS_DIR . 'retro-content.php' );
			$this->file( MYCRED_RETRO_IMPORTERS_DIR . 'retro-users.php' );

			foreach ( $this->built_in_tools as $tool => $import_class ) {

				if ( ! class_exists( $import_class ) ) continue;

				add_action( 'load-importer-' . $tool,         array( $import_class, 'header' ) );
				add_action( 'mycred_retro_register_importer', array( $import_class, 'register' ) );

			}

		}

		/**
		 * Admin Init
		 * @since 1.0
		 * @version 1.2
		 */
		public function admin_init() {

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				foreach ( $this->built_in_tools as $tool => $import_class ) {

					if ( ! class_exists( $import_class ) ) continue;

					add_action( 'wp_ajax_' . $tool, array( $import_class, 'ajax_handler' ) );

				}
			}

			if ( defined( 'WP_LOAD_IMPORTERS' ) ) {

				do_action( 'mycred_retro_register_importer' );

			}

		}

	}
endif;

function mycred_retroactive_plugin() {
	return myCRED_Retro_Plugin::instance();
}
mycred_retroactive_plugin();
