<?php

class Brizy_Editor_API {

	const nonce = 'brizy-api';
	const AJAX_PING = 'brizy_editor_ping';
	const AJAX_GET = 'brizy_editor_get_items';
	const AJAX_UPDATE = 'brizy_update_item';
	const AJAX_GET_GLOBALS = 'brizy_get_gb';
	const AJAX_SET_GLOBALS = 'brizy_set_gb';
	const AJAX_MEDIA = 'Brizy_Editor_Asset_Media';
	const AJAX_SIDEBARS = 'brizy_sidebars';
	const AJAX_BUILD = 'brizy_build';
	const AJAX_SIDEBAR_CONTENT = 'brizy_sidebar_content';
	const AJAX_SHORTCODE_CONTENT = 'brizy_shortcode_content';
	const AJAX_SHORTCODE_LIST = 'brizy_shortcode_list';
	const AJAX_GET_TEMPLATES = 'brizy_get_templates';
	const AJAX_GET_INTERNAL_LINKS = 'brizy_get_internal_links';
	const AJAX_GET_MENU_LIST = 'brizy_get_menu_list';
	const AJAX_SAVE_TRIGGER = 'brizy_update_post';
	const AJAX_GET_TERMS = 'brizy_get_terms';
	const AJAX_JWT_TOKEN = 'brizy_multipass_create';

	const AJAX_GET_DEFAULT_FORM = 'brizy_default_form';
	const AJAX_GET_FORM = 'brizy_get_form';
	const AJAX_CREATE_FORM = 'brizy_create_form';
	const AJAX_DELETE_FORM = 'brizy_delete_form';
	const AJAX_FORM_INTEGRATION_STATUS = 'brizy_form_integration_status';
	const AJAX_SUBMIT_FORM = 'brizy_submit_form';


	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	/**
	 * @var Brizy_Editor_Post
	 */
	private $post;

	/**
	 * @return Brizy_Editor_Project
	 */
	public function get_project() {
		return $this->project;
	}

	/**
	 * @return Brizy_Editor_Post
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Brizy_Editor_API constructor.
	 *
	 * @param Brizy_Editor_Project $project
	 * @param Brizy_Editor_Post $post
	 */
	public function __construct( $project, $post ) {

		$this->project = $project;
		$this->post    = $post;

		$this->initialize();
	}

	private function initialize() {

		if ( Brizy_Editor::is_user_allowed() ) {

			add_action( 'wp_ajax_' . self::AJAX_PING, array( $this, 'ping' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET, array( $this, 'get_item' ) );
			add_action( 'wp_ajax_' . self::AJAX_UPDATE, array( $this, 'update_item' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_GLOBALS, array( $this, 'get_globals' ) );
			add_action( 'wp_ajax_' . self::AJAX_SET_GLOBALS, array( $this, 'set_globals' ) );
			add_action( 'wp_ajax_' . self::AJAX_MEDIA, array( $this, 'media' ) );
			add_action( 'wp_ajax_' . self::AJAX_SIDEBARS, array( $this, 'get_sidebars' ) );
			//add_action( 'wp_ajax_' . self::AJAX_BUILD, array( $this, 'build_content' ) );
			add_action( 'wp_ajax_' . self::AJAX_SIDEBAR_CONTENT, array( $this, 'sidebar_content' ) );
			add_action( 'wp_ajax_' . self::AJAX_SHORTCODE_CONTENT, array( $this, 'shortcode_content' ) );
			add_action( 'wp_ajax_' . self::AJAX_SHORTCODE_LIST, array( $this, 'shortcode_list' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_TEMPLATES, array( $this, 'template_list' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_INTERNAL_LINKS, array( $this, 'get_internal_links' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_MENU_LIST, array( $this, 'get_menu_list' ) );
			add_action( 'wp_ajax_' . self::AJAX_SAVE_TRIGGER, array( $this, 'save_trigger' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_TERMS, array( $this, 'get_terms' ) );
			add_action( 'wp_ajax_' . self::AJAX_JWT_TOKEN, array( $this, 'multipass_create' ) );

			add_action( 'wp_ajax_' . self::AJAX_GET_DEFAULT_FORM, array( $this, 'default_form' ) );
			add_action( 'wp_ajax_' . self::AJAX_GET_FORM, array( $this, 'get_form' ) );
			add_action( 'wp_ajax_' . self::AJAX_CREATE_FORM, array( $this, 'create_form' ) );
			add_action( 'wp_ajax_' . self::AJAX_FORM_INTEGRATION_STATUS, array(
				$this,
				'update_form_integrations_status'
			) );
			add_action( 'wp_ajax_' . self::AJAX_DELETE_FORM, array( $this, 'delete_form' ) );

		}

		add_action( 'wp_ajax_' . self::AJAX_SUBMIT_FORM, array( $this, 'submit_form' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_SUBMIT_FORM, array( $this, 'submit_form' ) );
	}

	public function default_form() {
		try {

			$current_user = wp_get_current_user();
			$form         = new Brizy_Editor_Forms_Form();
			$form->setEmailTo( $current_user->user_email );

			$this->success( $form );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function get_form() {
		try {
			//$this->authorize();

			$manager = new Brizy_Editor_Forms_Manager( Brizy_Editor_Storage_Common::instance() );

			$form = $manager->getForm( $_REQUEST['form_id'] );

			if ( $form ) {
				$this->success( $form );
			}

			$this->error( 404, 'Form not found' );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function create_form() {
		try {
			//$this->authorize();
			$manager           = new Brizy_Editor_Forms_Manager( Brizy_Editor_Storage_Common::instance() );
			$instance          = Brizy_Editor_Forms_Form::create_from_post();
			$validation_result = $instance->validate();

			if ( $validation_result === true ) {
				$manager->addForm( $instance );
				$this->success( $instance );
			}

			$this->error( 400, $validation_result );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function delete_form() {
		try {
			//$this->authorize();
			$manager = new Brizy_Editor_Forms_Manager( Brizy_Editor_Storage_Common::instance() );
			$manager->deleteFormById( $_REQUEST['form_id'] );
			$this->success( array() );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function submit_form() {
		try {
			$manager = new Brizy_Editor_Forms_Manager( Brizy_Editor_Storage_Common::instance() );
			/**
			 * @var Brizy_Editor_Forms_Form $form ;
			 */

			$form = $manager->getForm( $_REQUEST['form_id'] );

			if ( $form->hasIntegrations() ) {
				// notify platform
				$platform = new Brizy_Editor_API_Platform();
				$platform->notifyFormSubmit( array(
					'data'             => $_REQUEST['data'],
					'project_language' => $_REQUEST['project_language'],
					'form_id'          => $form->getId(),
				) );

			}

			if ( ! $form ) {
				$this->error( 400, "Invalid form id" );
			}

			$fields = json_decode( stripslashes( $_REQUEST['data'] ) );

			if ( ! $fields ) {
				$this->error( 400, "Invalid form data" );
			}

			$form   = apply_filters( 'brizy_form', $form );
			$fields = apply_filters( 'brizy_form_submit_data', $fields, $form );

			// send email
			$headers   = array();
			$headers[] = 'Content-type: text/html; charset=utf-8';

			$field_string = array();
			foreach ( $fields as $field ) {
				$field_string[] = "{$field->label}: " . esc_html( $field->value );
			}

			$email_body = implode( '<br>', $field_string );

			$headers    = apply_filters( 'brizy_form_email_headers', $headers, $form, $fields );
			$email_body = apply_filters( 'brizy_form_email_body', $email_body, $form, $fields );

			$result = wp_mail(
				$form->getEmailTo(),
				$form->getSubject(),
				$email_body,
				$headers
			);

			if ( $result ) {
				$this->success( array() );
			}

			$this->error( 500, "Unable to send the email" );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	public function multipass_create() {

		try {
			$client_id = $_REQUEST['client_id'];

			if ( ! $client_id ) {
				throw new Exception( 'Bad request' );
			}

//			$platform = new Brizy_Editor_API_Platform();
//			if ( $platform->isUserCreatedLocally() ) {
//				$platform->createUser( null, false );
//			}

			$user = Brizy_Editor_User::get();

			if ( ! $user ) {
				throw new Exception( "Unable to create user" );
			}

			$email                = $user->getPlatformUserEmail();
			$secret               = $user->getPlatformUserSignature();
			$platform_credentials = Brizy_Editor_API_Platform::getCredentials();

			$platform_client_id = $platform_credentials->client_id;

			date_default_timezone_set( "UTC" );

			$date = new \DateTime();

			$user_data = array(
				"user_id"    => $user->getPlatformUserId(),
				"created_at" => $date->format( DateTime::ISO8601 ),
				'client_id'  => $client_id
			);

			$multipass = new Brizy_Editor_Multipass( $secret );

			$token = $multipass->encode( $user_data );

			$redirect_uri = sprintf( Brizy_Config::BRIZY_PLATFORM_MULTIPASS_LOGIN, $platform_client_id, $token, $email );

			wp_redirect( $redirect_uri );
			exit;
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( 500, "Bad request" );
			exit;
		}
	}

	public function update_form_integration_status() {

		try {

			$manager = new Brizy_Editor_Forms_Manager( Brizy_Editor_Storage_Common::instance() );
			$form    = $manager->getForm( $_REQUEST['form_id'] );

			if ( $form ) {

				$form->setHasIntegrations( (int) $_REQUEST['has_integrations'] );

				$manager->addForm( $form );

				$this->success( $form );
			}

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( 500, "Invalid post id" );
			exit;
		}

	}

	public function save_trigger() {
		try {
			$this->authorize();
			$post_id = $this->param( 'post' );
			$post    = Brizy_Editor_Post::get( $post_id );

			if ( ! $post->uses_editor() ) {
				return;
			}

			wp_update_post( array( 'ID' => $post_id, 'post_content' => $post->get_compiled_html_body() ) );

			$post_type        = $post->get_wp_post()->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$can_publish      = current_user_can( $post_type_object->cap->publish_posts );

			if ( $can_publish ) {
				wp_publish_post( $post_id );
			} else {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'pending' ) );
			}

			// get latest version of post
			$post                 = Brizy_Editor_Post::get( $post_id );
			$post_arr             = self::create_post_arr( $post );
			$post_arr['is_index'] = true; // this is for the case when the page we return is not an index page.. but the editor wants one.
			$this->success( array( $post_arr ) );

		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( 500, "Invalid post id" );
			exit;
		}
	}

	/**
	 * @internal
	 **/
	public function ping() {
		try {
			$this->authorize();
			$this->success( array() );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	/**
	 * @internal
	 **/
	public function get_globals() {
		try {
			$this->authorize();
			$data = $this->create_post_globals();

			$this->success( $data );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	/**
	 * @internal
	 */
	public function set_globals() {
		try {
			$this->authorize();

			// update project globas
			$data    = $this->param( 'gb' );
			$post_id = (int) $this->param( 'post' );
			$this->project->set_globals_as_json( stripslashes( $data ) );
			$this->project->save();

			// mark all brizy post to be compiled on next view
			$posts = Brizy_Editor_Post::get_all_brizy_posts();

			// we need to trigger a post update action to make sure the cache plugins will update clear the cache
			remove_action( 'save_post', array( Brizy_Admin_Main::_init(), 'compile_post_action' ) );
			// mark all post to be compiled on next view
			foreach ( $posts as $bpost ) {
				$bpost->set_needs_compile( true );
				$bpost->save();

				wp_update_post( array( 'ID' => $bpost->get_id() ) );
			}

//			$platform = new Brizy_Editor_API_Platform();
//			if ( ! $platform->isUserCreatedLocally() ) {
//				Brizy_Editor_User::get()->update_project( $this->project->get_api_project() );
//			}

			$this->success( $this->create_post_globals() );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
			exit;
		}
	}

	/**
	 * @internal
	 **/
	public function get_item() {
		try {
			$this->authorize();

			$post_arr = self::create_post_arr( $this->post );

			$post_arr['is_index'] = true; // this is for the case when the page we return is not an index page.. but the editor wants one.

			$this->success( array( $post_arr ) );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( 500, $exception->getMessage() );
			exit;
		}
	}

	/**
	 * @internal
	 **/
	public function update_item() {
		try {
			//$_POST = array_map( 'stripslashes_deep', $_POST );
			$data      = stripslashes( $this->param( 'data' ) );
			$atemplate = $this->param( 'template' );

			if ( $atemplate ) {
				$this->post->set_template( $atemplate );
			}

			if ( $data ) {
				$this->post->set_editor_data( $data );
			}

			$this->post->save();

			$this->success( self::create_post_arr( $this->post ) );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
		}
	}

	public function sidebar_content() {
		try {

			if ( isset( $_REQUEST['sidebarId'] ) ) {
				$sidebar_id = $_REQUEST['sidebarId'];
			} else {
				throw new Exception( 'Invalid sidebar id provided', 500 );
			}

			ob_start();

			dynamic_sidebar( $sidebar_id );

			$sidebar_html = ob_get_clean();

			$this->success( array(
				'sidebarId'      => $sidebar_id,
				'sidebarContent' => $sidebar_html
			) );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
		}
	}

	public function shortcode_content() {
		try {

			if ( isset( $_REQUEST['shortcode'] ) ) {
				$shortcode = stripslashes( $_REQUEST['shortcode'] );
			} else {
				throw new Exception( 'Shortcode string not provided.', 500 );
			}
			$shortcode_content = do_shortcode( $shortcode );

			$this->success( array(
				'shortcode' => $shortcode_content
			) );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			http://brizy.local/wp-admin/admin-ajax.php?post=13
			$this->error( $exception->getCode(), $exception->getMessage() );
		}
	}

	public function shortcode_list() {
		try {
			global $shortcode_tags;
			$this->success( array_keys( $shortcode_tags ) );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
		}
	}

	public function template_list() {
		try {
			$templates = get_page_templates();

			$response = array(
				(object) array( "name" => 'Default', 'value' => 'default' )
			);

			foreach ( $templates as $name => $path ) {
				$response[] = (object) array( "name" => $name, 'value' => $path );
			}

			$this->success( $response );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
		}
	}

	public function get_internal_links() {

		$search_term = $this->param( 'filter_term' );

		$links = array();
		$links = array_merge( $links, $this->get_post_link_list( $search_term ) );
		$links = array_merge( $links, $this->get_term_link_list( $search_term ) );

		wp_send_json( array( 'filter_term' => $search_term, 'links' => $links ), 200 );
	}

	public function get_sidebars() {
		global $wp_registered_sidebars;

		$items = array();

		foreach ( $wp_registered_sidebars as $sidebar ) {
			$item    = array(
				'id'    => $sidebar['id'],
				'title' => $sidebar['name'],
			);
			$items[] = $item;
		}

		$this->success( $items );
	}

	/**
	 * @internal
	 **/
	public function media() {
		try {
			$this->authorize();

			$attachment_id = $this->param( 'attachmentId' );

			$brizy_editor_user = Brizy_Editor_User::get();
			$this->success( $brizy_editor_user->get_media_id(
				$this->project,
				$attachment_id
			) );
		} catch ( Exception $exception ) {
			Brizy_Logger::instance()->exception( $exception );
			$this->error( $exception->getCode(), $exception->getMessage() );
		}
	}

	protected function param( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			return $_REQUEST[ $name ];
		}

		return null;
	}

	protected function error( $code, $message ) {
		wp_send_json_error( array( 'code' => $code, 'message' => $message ), $code );
	}

	protected function success( $data ) {
		wp_send_json( $data );
	}

	protected function static_url() {
		return brizy()->get_url( '/includes/editor/static' );
	}

	private function authorize() {
		if ( ! wp_verify_nonce( $_REQUEST['hash'], self::nonce ) ) {
			throw new Brizy_Editor_Exceptions_AccessDenied();
		}
	}

	public static function create_post_arr( Brizy_Editor_Post $post ) {

		$p_id      = (int) $post->get_id();
		$the_title = get_the_title( $p_id );

		return array(
			'title'    => $the_title,
			'slug'     => sanitize_title( $the_title ),
			'data'     => $post->get_editor_data(),
			'id'       => $p_id,
			'is_index' => false,
			'template' => get_page_template_slug( $p_id ),
			'status'   => get_post_status( $p_id ),
			'url'      => get_the_permalink( $p_id )
		);
	}

	/**
	 * @return array
	 */
	public function create_post_globals() {
		$wp_post = $this->post->get_wp_post();

		return array(
			'id'        => $this->project->get_id(),
			'gb'        => $this->project->get_globals(),
			'name'      => $wp_post->post_name,
			'createdAt' => $wp_post->post_date,
			'updatedAt' => $wp_post->post_date,
			'user'      => array(
				'email' => null,
				'id'    => null,
			),
		);
	}

	/**
	 * Return an array of terms
	 *
	 * Ex: ['label'=>'Term name',
	 *      'url'=>'term url',
	 *      'taxonomy'=>'taxonomy name']
	 *
	 * @return array
	 */
	private function get_term_link_list( $search_term ) {

		$links = array();

		$args = array();

		if ( $search_term ) {
			$args['name__like'] = $search_term;
		}

		$terms = get_terms( $args );

		foreach ( $terms as $term ) {
			$links[] = (object) array(
				'label'    => $term->name,
				'url'      => get_term_link( $term ),
				'taxonomy' => $term->taxonomy
			);
		}

		return $links;
	}

	/**
	 * @param $search_term
	 *
	 * @return array
	 */
	private function get_post_link_list( $search_term ) {

		add_filter( 'posts_where', array( $this, 'brizy_post_title_filter' ), 10, 2 );

		$post_query = array(
			'post_type'      => brizy()->supported_post_types(),
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'orderby'        => 'post_title',
			'order'          => 'ASC'
		);

		if ( $search_term ) {
			$post_query['post_title_term'] = $search_term;
		}

		$posts = new WP_Query( $post_query );

		$links = array();

		foreach ( $posts->posts as $post ) {
			$permalink = null;
			switch ( $post->post_type ) {
				case 'revision':
				case 'nav_menu_item':
					continue;
				case 'page':
					$permalink = get_page_link( $post->ID );
					break;
				case 'post':
					$permalink = get_permalink( $post->ID );
					break;
				case 'attachment':
					$permalink = get_attachment_link( $post->ID );
					break;
				default:
					$permalink = get_post_permalink( $post->ID );
					break;
			}

			$label = get_the_title( $post );

			$links[] = (object) array( 'label' => $label, 'url' => $permalink, 'post_type' => $post->post_type );
		}

		remove_filter( 'posts_where', 'brizy_post_title_filter', 10, 2 );

		return $links;
	}

	public function brizy_post_title_filter( $where, &$wp_query ) {

		global $wpdb;

		if ( $term = $wp_query->get( 'post_title_term' ) ) {
			$search_term = $wpdb->esc_like( $term );
			$search_term = ' \'%' . $search_term . '%\'';

			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
		}

		return $where;
	}


	public function get_menu_list() {
		wp_send_json( wp_get_nav_menus( array( 'hide_empty' => true ) ), 200 );
	}

	public function get_terms() {

		$taxonomy = $this->param( 'taxonomy' );

		$terms = (array) get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

		wp_send_json( array_values( $terms ) );
	}

}