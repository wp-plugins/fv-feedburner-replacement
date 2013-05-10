<?php
/*
Plugin Name: FV Feedburner Replacement
Description: Changes /feed to a newsletter subscription page, yet allows feed readers to read it as a feed. Eases up user subscription.
Author: Foliovision 
Version: 0.2.9
*/

require_once( dirname(__FILE__) . '/newsletter-bridge.php' );

class FV_Feedburner_Replacement {

  var $enabled;
  var $default_form_code;
  var $default_form_css;

  public function __construct() {
      
    add_action( 'init', array( $this, 'init' ) );
    add_action( 'generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ) );
    add_action( 'init', array( $this, 'check_form' ) );
    add_action( 'init', array( $this, 'export' ) );
       
    add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
    add_filter( 'template_redirect', array( $this, 'template_redirect' ) );
    add_filter( 'wp_headers', array( $this, 'wp_headers' ), 999, 2 );
    
    add_action( 'wp_head', array( $this, 'extra_css' ) );
    
    //  admin
    add_action( 'activate_' .plugin_basename(__FILE__), array( $this, 'activate' ) );
    add_action( 'admin_head', array($this, 'admin_head') );
    add_action( 'admin_menu', array($this, 'admin_menu') );   
    add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    
    //  Feedburner tweaks
    add_filter( 'the_content', array( $this, 'the_content' ), 999 );
    add_filter( 'the_excerpt_rss', array( $this, 'the_excerpt_rss' ), 999 );
    add_filter( 'post_link', array( $this, 'the_permalink_rss' ), 999 );  //  let's make sure any links to post have our subscription request added
    add_filter( 'post_comments_feed_link', array( $this, 'post_comments_feed_link' ), 999 );  //  post_link filter messes up the comment feed links in feed, so we need to fix these
    add_filter( 'the_permalink_rss', array( $this, 'the_permalink_rss' ), 999 );  
    add_filter( 'option_rss_use_excerpt', array( $this, 'option_rss_use_excerpt' ), 999 );
    
    //  additional init
    $this->default_form_code = "Welcome to the ".get_bloginfo('name')." subscription area. You can subscribe to ".get_bloginfo('name')." via newsletter or via RSS.

Just include your name and email here and I'll make sure you don't miss anything important.

<form id=\"fv_form\" method=\"post\">
<table>
<tr><td>Name:</td><td><input type=\"text\" name=\"fvfr_name\" /></td></tr>
<tr><td>Email:</td><td><input type=\"text\" name=\"email\" /></td></tr>
<tr><td></td><td><input name=\"fv_feedburner_replacement\" type=\"submit\" value=\"Subscribe\" /></td></tr>
</table>
</form>

Thanks for reading ".get_bloginfo('name')."!";

    $this->default_form_css = "#fv_form table, #fv_form table td { border: 0; }";    
  }
  
	
	function activate() {
	  if( !get_option('fv_feedburner_replacement') ) {
	    update_option( 'fv_feedburner_replacement', array(
	      'show_links' => true,
	      'title' => get_bloginfo('name')." feeds",
	      'description' => "Subscribe via email or RSS to ".get_bloginfo('name'),
	      'form_code' => $this->default_form_code,
        'feedburner_message' => "Please resubscribe on <a href=\"".get_bloginfo('rss2_url')."\">".get_bloginfo('name')." subscription page</a> to get the latest news!",
        'form_css' => $this->default_form_css,
        
        'message_error_email' => 'Please enter valid email address.',
        'message_error_name' => 'Please enter your name.',
        'message_subscr_exists' => 'You are already subscribed.',
        'message_subscr_succ' => '%name%, you were successfully subscribed.'."\n\n".'Please check your mailbox for the confirmation email. If you don\'t see it in 15 minutes, please check your spam folder.',
        'message_subscr_conf' => 'Hello %name%,'."\n\n".'your subscription was successfully verified.',
        'mail_confirmation_subject' => get_bloginfo('name').' - Confirm Your Subscription',
        'mail_confirmation_mail' => 'Hello %name%,'."\n\n".'Thank you for your subscription to '.get_bloginfo('name')."\n\n".'To confirm your subscription, please click the following link: %confirmation%'."\n" 
        
	    ) );
	  }
	  
	  update_option( 'fv_feedburner_replacement_deferred_notices', 'Site subscription page installed - check <a href="'.$this->get_admin_page_url().'">FV Feedburner Replacement settings</a> for details!<br /><br />'.$this->notice() );  
    
    $plugins = get_option('active_plugins');
    foreach( $plugins AS $plugins_item ) {
      if( strpos( $plugins_item, 'super-cache' ) ) {
        update_option( 'fv_feedburner_replacement_deferred_errors', 'It appears you use WP Super Cache. Make sure that caching of feeds is turned off (Settings => WP Super Cache => Advanced => Accepted Filenames & Rejected URIs => Feeds (is_feed)), otherwise there might be issues with the FV Feedburner Replacement plugin.' );
        return;
      }
    }      
	  
	  $this->init( true );

		$newsletter_bridge = new FV_Feedburner_Replacement_Newsletter_Bridge;
    $newsletter_bridge->activate();      

	}
	
	
	function admin_head() {
	  if( $_GET['page'] == 'fv_feedburner_replacement' ) {
      echo '<link rel="stylesheet" type="text/css" href="'.plugins_url( 'css/style.css', __FILE__).'" />'."\n";
    }
	}
  
  
  function admin_menu()
  {
    add_options_page( 'FV Feedburner Replacement', 'FV Feedburner Replacement', 'manage_options', 'fv_feedburner_replacement', array($this, 'options_panel') );
  }
  
  
  /*function feed_link( $link ) {
    return str_replace( home_url().'/feed', home_url().'/feed/rss2', $link );
  }*/
  
  
  function admin_notices() {
    if( $notices = get_option('fv_feedburner_replacement_deferred_notices') ) {
      echo '<div class="updated">
       <p>'.$notices.'</p>
    </div>';  
      delete_option('fv_feedburner_replacement_deferred_notices');
    }
  
    if( $notices = get_option('fv_feedburner_replacement_deferred_errors') ) {
      echo '<div class="error">
       <p>'.$notices.'</p>
    </div>';  
      delete_option('fv_feedburner_replacement_deferred_errors');
    }
  }
  

  function check_form() {
    if( is_admin() ) {
      return;
    }
    if( isset($_POST['fv_feedburner_replacement']) ) {
      $newsletter_bridge = new FV_Feedburner_Replacement_Newsletter_Bridge;
      $newsletter_bridge->subscribe();               
    }
    if( isset($_GET['fvfr']) ) {
      $newsletter_bridge = new FV_Feedburner_Replacement_Newsletter_Bridge;
      $newsletter_bridge->confirm();               
    }
    
  }
    
  
  function comments_open( $open, $post_id ) {
    global $post;
    global $wp_query;
    if( $post->ID == $post_id ) {
      return false;
    }
  }

    
  public function detect_feedburner() {
  	if (preg_match('/feedburner/i', $_SERVER['HTTP_USER_AGENT'])) return true;
  	return false;
  }  
  
  
  function export() {
    if( is_admin() && $_GET['page'] == 'fv_feedburner_replacement' && $_GET['export'] == true ) {
      $newsletter_bridge = new FV_Feedburner_Replacement_Newsletter_Bridge;
      $newsletter_bridge->export();   
    }   
  }
  
  
  function extra_css() {
	   $options = get_option( 'fv_feedburner_replacement' );
	   if( ($this->get_request_subscribe() || $this->enabled) && strlen( trim($options['form_css']) ) ) {
	     echo '<style type="text/css">'."\n".$options['form_css']."\n".'</style>'."\n";
	   }  
  }
  
  
  public function get_edit_post_link( $link ) {
    return $this->get_admin_page_url();
  }
  
  
  /*
  Checks if it's Feedburner accessing the feed and the option is enabled
  */
  public function is_feedburner() {
    $options = get_option( 'fv_feedburner_replacement' );
    
    if( !is_feed() ) {
      return false;
    }
    
    if( ($this->detect_feedburner() && $options['feedburner_include']) || ($options['feedburner_test'] && $options['feedburner_include']) ) {
      return true;
    }
    return false;  
  }
  
  
  function generate_page($_posts = '', $_query = '') {
    $_query->is_feed = false;
 
		$include_post_content = null;
			
    $options = get_option( 'fv_feedburner_replacement' );			
    
		if( $options['form_code'] ) {
		  $include_post_content .= apply_filters( 'the_content', $options['form_code'] );
		}    
    
		if( $options['show_links'] ) {
		  $include_post_content .= '<p>'.__('Pick the feed for subscription').':</p>';
		  $include_post_content .= '<ul>';
		  $include_post_content .= '<li><a href="'.$this->get_rss2_url().'">RSS feed</a></li>';
      //$include_post_content .= '<li><a href="'.$this->get_rss2_url().'">RSS 2.0 feed (default)</a></li>';
      //$include_post_content .= '<li><a href="'.get_bloginfo('comments_rss2_url').'">Comments RSS 2.0 feed</a></li>';
      //$include_post_content .= '<li><a href="'.get_bloginfo('rdf_url').'">RDF/RSS 1.0 feed </a></li>';
      //$include_post_content .= '<li><a href="'.get_bloginfo('rss_url').'">RSS 0.92 feed</a></li>';
      $include_post_content .= '<li><a href="'.get_bloginfo('atom_url').'">Atom feed</a></li>';      
      if( $options['show_links_comments'] ) {
        $include_post_content .= '<li><a href="'.get_bloginfo('comments_rss2_url').'">Comments feed</a></li>';
      }
      $include_post_content .= '</ul>'; 
		}
		
    if( $options['show_category_links'] ) {
      $categories = get_categories();
      if( $categories ) {
		    $include_post_content .= '<p>'.__('Category feeds').':</p>';
		    $include_post_content .= '<ul>';        
        foreach( $categories AS $item ) {
          $include_post_content .= '<li><a href="'.get_category_feed_link($item->term_id).'">'.$item->name.'</a></li>';
        }
      }
      $include_post_content .= '</ul>';
    }		
    

		
		$include_post_content = apply_filters( 'fv_feedburner_replacement_the_content', $include_post_content );
		
		global $wp_query;

		$manager_page_title = html_entity_decode( $options['title'], ENT_COMPAT, 'UTF-8');
		if(function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage'))
			$manager_page_title = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($manager_page_title);

    $posts = array();
		$posts[] =
			(object)array(
				'ID' => '9999999',
				'post_author' => '1',
				'post_date' => '2001-01-01 11:38:56',
				'post_date_gmt' => '2001-01-01 00:38:56',
				'post_content' => $include_post_content,
				'post_title' => $manager_page_title,
				'post_excerpt' => '',
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_password' => '',
				'to_ping' => '',
				'pinged' => '',
				'post_modified' => '2001-01-01 11:00:01',
				'post_modified_gmt' => '2001-01-01 00:00:01',
				'post_content_filtered' => '',
				'post_parent' => '0',
				'menu_order' => '0',
				'post_type' => 'page',
				'post_mime_type' => '',
				'post_category' => '0',
				'comment_count' => '0',
				'filter' => 'raw',
				'guid' => get_bloginfo('url').'/?page_id=9999999',
				'post_name' => get_bloginfo('url').'/?page_id=9999999',
				'ancestors' => array()
			);

		// Make WP believe this is a real page, with no comments attached
		$wp_query->is_page = true;
		$wp_query->is_single = false;
		$wp_query->is_home = false;
		$wp_query->comments = false;

		// Discard 404 errors thrown by other checks
		unset($wp_query->query["error"]);
		$wp_query->query_vars["error"]="";
		$wp_query->is_404=false;

		// Seems like WP adds its own HTML formatting code to the content, we don't need that here
		remove_filter('the_content','wpautop');
		add_filter('post_class', array( $this, 'post_class') ) ;

		remove_filter( 'the_posts', array( $this, 'generate_page' ), 10, 2 );

		return $posts;
	}
	
	
	function get_admin_page_url() {
	  return get_admin_url().'options-general.php?page=fv_feedburner_replacement';
	}
	
	
	function get_request_subscribe() {
	  if( $_GET['subscribe'] == 'yes' ) {
	    return true;
	  }
	  return false;
	}
	
	
	public function get_rss2_url() {
	  return preg_replace( '~feed~', 'feed/rss2', get_bloginfo('rss2_url') );
	}
	
	
  public function get_subscription_url() {
	  return preg_replace( '~feed~', 'feed/subscription', get_bloginfo('rss2_url') );
	}
	
	
	function generate_rewrite_rules($wp_rewrite) {
    $feed_rules = array(
    'feed/subscription' => 'index.php?feed=subscription'
    );
    $wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
  }
  
	
	function init( $flush = false ) {	  
	  global $wp_rewrite;
	  add_feed( 'subscription', array( $this, 'subscription' ) );
	  global $wp_query;

	  if( $flush ) {
	    $wp_rewrite->flush_rules( false );
	  }
	}
	
	
	function meta_description() {
	   $options = get_option( 'fv_feedburner_replacement' );
	   if( strlen($options['description']) ) {
	     echo '<meta name="description" content="'.esc_attr($options['description']).'" />'."\n";
	   }
	}
	
	
	function notice() {
	  $options = get_option('fv_feedburner_replacement');
	  return 'You should clear your browser cache and <a href="'.get_bloginfo('rss2_url').'" target="_blank">check if you see the "'.$options['title'].'" page in your browser</a> and also <a href="http://validator.w3.org/appc/check.cgi?url='.get_bloginfo('rss2_url').'" target="_blank">check if valid feed is served to RSS readers</a>.';
	}
	
	
  function options_panel() {

    if (!empty($_POST)) :
    
      check_admin_referer('fv_feedburner_replacement');
      
      if( isset($_POST['fv_feedburner_replacement_submit'] ) ) :
        $options = get_option( 'fv_feedburner_replacement', array() );
        $options['title'] = stripslashes( $_POST['title'] );
        $options['description'] = stripslashes( trim($_POST['description']) );
        $options['form_code'] = stripslashes( $_POST['form_code'] );
        $options['form_css'] = stripslashes( trim($_POST['form_css']) );             
        $options['show_links'] = ( $_POST['show_links'] ) ? true : false;
        $options['show_links_comments'] = ( $_POST['show_links_comments'] ) ? true : false;
        $options['show_category_links'] = ( $_POST['show_category_links'] ) ? true : false;
        $options['feedburner_message'] = stripslashes( $_POST['feedburner_message'] );
        $options['feedburner_include'] = ( $_POST['feedburner_include'] ) ? true : false;
        $options['feedburner_test'] = ( $_POST['feedburner_test'] ) ? true : false;
          
          //'message_error_email' = stripslashes( trim($_POST['message_error_email']) ),
          //'message_error_name' = stripslashes( trim($_POST['message_error_name']) ),
          //'message_subscr_exists' = stripslashes( trim($_POST['message_subscr_exists']) ),
        $options['message_subscr_succ'] = stripslashes( trim($_POST['message_subscr_succ']) );
        $options['message_subscr_conf'] = stripslashes( trim($_POST['message_subscr_conf']) );
        $options['mail_confirmation_subject'] = stripslashes( trim($_POST['mail_confirmation_subject']) );
        $options['mail_confirmation_mail'] = stripslashes( trim($_POST['mail_confirmation_mail']) );

      
        update_option( 'fv_feedburner_replacement', $options );
?>
    <div id="message" class="updated fade">
      <p>
        <strong>
          Settings saved
        </strong>
      </p>
      <p>
        <?php echo $this->notice(); ?>
      </p>
    </div>
<?php
      endif; // fv_feedburner_replacement_submit
      
      if( isset($_POST['fv_feedburner_replacement_submit_restore'] ) ) :
        $options = get_option( 'fv_feedburner_replacement' );
        
        $options['form_code'] = $this->default_form_code;
        $options['form_css'] = $this->default_form_css;
      
        update_option( 'fv_feedburner_replacement', $options );
?>
    <div id="message" class="updated fade">
      <p>
        <strong>
          Original form code and CSS code restored!
        </strong>
      </p>
      <p>
        <?php echo $this->notice(); ?>
      </p>
    </div>
<?php
      endif; // fv_feedburner_replacement_submit
            
    endif;
    
    $options = get_option( 'fv_feedburner_replacement' );
?>

<div class="wrap">
  <div style="position: absolute; right: 20px; margin-top: 5px">
  <a href="https://foliovision.com/seo-tools/wordpress/plugins/fv-feedburner-replacement" target="_blank" title="Documentation"><img alt="visit foliovision" src="http://foliovision.com/shared/fv-logo.png" /></a>
  </div>
  <div>
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2>FV Feedburner Replacement</h2>
  </div>
  <?php if( !get_option( 'fv_feedburner_replacement_ad_disable' ) ) : ?>
  <div id="fv_feedburner_ad">
		<div class="text-part">
			<h2>FV <strong>Feedburner</strong> Replacement</h2>
			<span class="red-text">Go Self-Hosted</span>
	  		<ul>
				<li>No Monthly Fees</li>
				<li>Mail on your own schedule</li>
				<li>Fully Automated</li>
  			</ul>
  			<center>
  				<a href="http://foliovision.com/services/newsletter-feedburner-migration" class="red-button">Setup your own newsletter</a><p>Newsletter Pro + Your own SMTP</p>
  			</center>
  		</div>
  		<div class="graphic-part">
  			<a href="http://foliovision.com/services/newsletter-feedburner-migration">
  			<img width="297" height="239" border="0" src="<?php echo plugins_url( 'images/led-monitor.png' , __FILE__ ) ?>"> </a>
  		</div>
  </div>
  <?php endif; ?>
  <form id="fv_feedburner_replacement_form" method="post" action="">
  <?php wp_nonce_field('fv_feedburner_replacement') ?>
    <div id="poststuff" class="ui-sortable">
      <div class="postbox">
        <h3>
        <?php _e('Subscription Page Settings') ?>
        </h3>
        <div class="inside">
          <table class="form-table">
            <tr>
              <td>
                <label for="title">
                  Title<br />
                  <input type="text" class="large-text code" name="title" id="title" value="<?php echo esc_attr( $options['title'] ); ?>" />                  
                </label>
              </td>
            </tr>         
            <tr>
              <td>
                <label for="description">
                  Meta Description<br />
                  <textarea class="large-text code" rows="1" name="description" id="description" onkeyup="fv_feedburner_replacement_countChars(document.getElementById('description'),document.getElementById('length_description'));"><?php echo $options['description']; ?></textarea>             
                  <input id="length_description" class="inputcounter" readonly="readonly" type="text" name="length_description" size="3" maxlength="3" value="<?php echo strlen($options['description']);?>" />
<small><?php _e(' characters. Most search engines use a maximum of 145 chars for the description.', 'fv_seo') ?></small>     
                </label>
              </td>
            </tr>                 
            <tr>
              <td>
                <label for="form_code">
                  Enter your newsletter/feed subscription code here. You can use HTML and also plugin shortcodes:<br />
                  <textarea class="large-text code" cols="50" rows="10" name="form_code" id="form_code"><?php echo ( $options['form_code'] ) ?></textarea>
                  <p class="description">If you don't have a newsletter service yet, we recommend Newsletter Pro (Wordpress plugin) or <a target="_blank" href="http://foliovision.com/apps/mailchimp">MailChimp</a> (provides full newsletter service).</p>                  
                </label>
              </td>
            </tr>
            <tr>
              <td>
                <a href="#" onclick="jQuery('#wrap-form_css').show(); jQuery(this).hide(); return false">Change CSS</a>
                <div id="wrap-form_css" style="display: none; "> 
                  <label for="form_css">
                    CSS Tweaks<br />
                    <textarea class="large-text code" cols="50" rows="5" name="form_css" id="form_css"><?php echo ( $options['form_css'] ) ?></textarea>        
                    <p class="description">These will be put into <tt>&lt;head&gt;</tt> section on the subscription page only.</p>
                  </label>
                </div>
              </td>
            </tr>            
            <tr>
              <td>
                <label for="show_links">
                  <input type="checkbox" name="show_links" id="show_links" value="1" <?php if( $options['show_links'] ) echo 'checked="checked"'; ?> />
                  Show a list of standard Wordpress feed URLs.
                </label>
              </td>
            </tr>
            <tr>
              <td>
                <label for="show_links_comments">
                  <input type="checkbox" name="show_links_comments" id="show_links_comments" value="1" <?php if( $options['show_links_comments'] ) echo 'checked="checked"'; ?> />
                  Show comments feed link in the list (see above option).
                </label>
              </td>
            </tr>               
            <tr>
              <td>
                <label for="show_category_links">
                  <input type="checkbox" name="show_category_links" id="show_category_links" value="1" <?php if( $options['show_category_links'] ) echo 'checked="checked"'; ?> />
                  Show a list of all category feed URLs.
                </label>
              </td>
            </tr>                          
          </table>
          <p>
            <input type="submit" name="fv_feedburner_replacement_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
          </p>
        </div>
      </div>
      <p><?php echo __('Are you having any problems or questions? Use our <a target="_blank" href="http://foliovision.com/support/fv-feedburner-replacement/">support forums</a>.'); ?></p>
    </div>
    <div id="poststuff" class="ui-sortable">
      <div class="postbox">
        <h3>
        <?php _e('Settings for Feedburner') ?>
        </h3>
        <div class="inside">
          <table class="form-table">
            <tr>
              <td>
                <label for="feedburner_include">
                  <input type="checkbox" name="feedburner_include" id="feedburner_include" value="1" <?php if( $options['feedburner_include'] ) echo 'checked="checked"'; ?> />
                  Enable Feedburner Tweaks (experimental, check Feedburner feed thoroughly)
                </label>
                <ol class="description">
                  <li>Makes sure Feedburner readers see your subscription form if they come in from Feedburner feed (the post URLs have ?subscription=yes appended)</li>
                  <li>Feedburner only gets the post excerpt, to make sure your readers click the link to read the full post</li>
                  <li>Adds the message entered below to feed content for Feedburner</li>                    
                </ol>
              </td>
            </tr>   
            <!--<tr>
              <td>
                <label for="feedburner_test">
                  <input type="checkbox" name="feedburner_test" id="feedburner_test" value="1" <?php if( $options['feedburner_test'] ) echo 'checked="checked"'; ?> />
                  <strong>Test mode</strong> - include the message for everybody. Clear your browser cache, <a target="_blank" href="<?php echo $this->get_rss2_url(); ?>">check your feed</a> to see what it looks like and then <strong>don't forget to turn this off!</strong>
                </label>
              </td>
            </tr>-->                         
            <tr>
              <td>
                <label for="feedburner_message">
                  Message for Feedburner subscribers:<br />
                  <textarea class="large-text code" cols="50" rows="2" name="feedburner_message" id="feedburner_message"><?php echo ( $options['feedburner_message'] ) ?></textarea>     
                  <p class="description">This can be used to notify your Feedburner subscribers to re-subscribe.</p>                
                </label>
              </td>
            </tr>          
          </table>
          <p>
            <input type="submit" name="fv_feedburner_replacement_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
          </p>          
        </div>
      </div>
    </div>   
    <div id="poststuff" class="ui-sortable">
      <div class="postbox">
        <h3>
        <?php _e('Default subscription form settings') ?>
        </h3>
        <div class="inside">
          <p>The default form uses the same subscribers list as the Newsletter plugin by Stefano Lissa, so you can start bulding your newsletter list right after this plugin is installed.</p>
<?php
$newsletter_bridge = new FV_Feedburner_Replacement_Newsletter_Bridge;
$count = $newsletter_bridge->get_count();  
?>          
<p>
  You currently have <strong><?php echo $count; ?></strong> confirmed subscribers in your subscription list.
  <?php if( $count > 0 ) : ?>
  <a target="_blank" href="<?php echo $this->get_admin_page_url() ?>&export=true">CSV export</a>
  <?php endif; ?>
</p>
          <table class="form-table">
            <tr>
              <td>
                <label for="message_subscr_succ">
                  Subscription successfull, please confirm your subscription by clicking the link in email<br />
                  <textarea class="large-text code" cols="50" rows="2" name="message_subscr_succ" id="message_subscr_succ"><?php echo ( $options['message_subscr_succ'] ) ?></textarea>     
                  <p class="description">Use <code>%name%</code> for subscriber name.</p>  
                </label>
              </td>
            </tr>    
            <tr>
              <td>
                <label for="message_subscr_conf">
                  Subscription was successfully verified<br />
                  <textarea class="large-text code" cols="50" rows="2" name="message_subscr_conf" id="message_subscr_conf"><?php echo ( $options['message_subscr_conf'] ) ?></textarea>     
                  <p class="description">Use <code>%name%</code> for subscriber name.</p>  
                </label>
              </td>
            </tr>                      
            <tr>
              <td>
                <label for="message_subscr_exists">
                  Mail - Subject for confrimation message<br />
                  <input type="text" class="large-text code" name="mail_confirmation_subject" id="mail_confirmation_subject" value="<?php echo esc_attr( $options['mail_confirmation_subject'] ); ?>" />                  
                </label>
              </td>
            </tr>
            <tr>
              <td>
                <label for="mail_confirmation_mail">
                  Mail - text for the subscription confirmation message<br />
                  <textarea class="large-text code" cols="50" rows="2" name="mail_confirmation_mail" id="mail_confirmation_mail"><?php echo ( $options['mail_confirmation_mail'] ) ?></textarea>    
                  <p class="description">Use <code>%confirmation%</code> for the confirmation link. And <code>%name%</code> for subscriber name.</p>     
                </label>
              </td>
            </tr>                            
            <!--<tr>
              <td>
                <label for="message_error_email">
                  Error - Missing Email Address<br />
                  <input type="text" class="large-text code" name="message_error_email" id="message_error_email" value="<?php echo esc_attr( $options['message_error_email'] ); ?>" />                  
                </label>
              </td>
            </tr>    
            <tr>
              <td>
                <label for="message_error_name">
                  Error - Missing Name<br />
                  <input type="text" class="large-text code" name="message_error_name" id="message_error_name" value="<?php echo esc_attr( $options['message_error_name'] ); ?>" />                  
                </label>
              </td>
            </tr>    
            <tr>
              <td>
                <label for="message_subscr_exists">
                  Error - Address already subscribed<br />
                  <input type="text" class="large-text code" name="message_subscr_exists" id="message_subscr_exists" value="<?php echo esc_attr( $options['message_subscr_exists'] ); ?>" />                  
                </label>
              </td>
            </tr>-->                                                        
          </table>
          <p>
            <input type="submit" name="fv_feedburner_replacement_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
          </p>                  
        </div>
      </div>
    </div>         
  </form>
  <form onsubmit="return confirm('Are you sure you want to reset the form and CSS codes?');" method="post" action="">
    <?php wp_nonce_field('fv_feedburner_replacement') ?>
    <p>
      <input type="submit" name="fv_feedburner_replacement_submit_restore" class="button-primary" value="<?php _e('Restore original form and CSS codes') ?>" />
    </p>  
  </form>
</div>
<script type="text/JavaScript">
function fv_feedburner_replacement_countChars(field, cntfield) {
  if( !field.value ) return;
  cntfield.value = field.value.length;
  
  if( field.name == 'description' ) {
    if( field.value.length > 145 ) {
      jQuery('#description').css('background', 'red').css('color', 'black');
    }
    else if( field.value.length > 134 ) {
      jQuery('#description').css('background', 'yellow').css('color', 'black');
    }
    else {
      jQuery('#description').css('background', 'white').css('color', 'black');
    }
  }
}
fv_feedburner_replacement_countChars(document.getElementById('description'),document.getElementById('length_description'));
</script>
<style>
#fv_feedburner_replacement_form .inputcounter {
  background: none repeat scroll 0 0 white;
  color: #000000;
  font-size: 85%;
  padding: 0;
  text-align: center;
}
</style>
<?php
  }
  
  
  public function option_rss_use_excerpt( $value ) {
    if( $this->is_feedburner() ) {
      return true;
    }
    return $value;
  }
  
  
  public function post_class( $classes ) {
    $classes[] = 'page-feedburner-replacement';
    return $classes;
  }
  
  
  public function post_comments_feed_link( $permalink ) {
    if( $this->is_feedburner() ) {
      $permalink = str_replace( '/?subscribe=yes/feed', '/feed', $permalink );
      $permalink = str_replace( '?subscribe=yes/feed', '/feed', $permalink );
    }
    return $permalink;
  }  
  
  
  /*
  Here's where we decide what to show on the /feed/subscription address
  */
  public function pre_get_posts() {
    global $wp_query;

    if( $wp_query->is_feed && $wp_query->query_vars['feed'] == 'rss2' ) {  
      remove_action('template_redirect', 'redirect_canonical');
    }

    if( $this->user_agents() && $wp_query->query_vars['feed'] != 'subscription' ) {
      return;
    }    
     
    if( ($wp_query->is_feed && !$wp_query->is_archive) && $wp_query->query_vars['feed'] == 'subscription' ) {
      $this->enabled = true;
      //  what hooks in if we are going to show the subscription page
      add_filter( 'the_posts', array( $this, 'generate_page' ), 10, 2 );	
      add_action( 'wp_head', array( $this, 'meta_description' ) );
      add_action( 'get_edit_post_link', array( $this, 'get_edit_post_link' ) );      
      if( current_user_can('manage_options') ) {
        add_action( 'wp_before_admin_bar_render', array( $this, 'wp_before_admin_bar_render' ) );
      }
    }
   
    //  Make sure /feed/rss and /feed/rss2 doesn't redirect to /feed
    
  }
  
  
  function subscription() {
    include( get_page_template() );
  }
  
  
  /*
  Activated when you come from feedburner (?subscribe=yes in the link)
  */
  public function the_content( $content ) {
    $options = get_option( 'fv_feedburner_replacement' );
    
    if( ( $this->get_request_subscribe() && $options['feedburner_include']) || ($options['feedburner_test'] && $this->get_request_subscribe() ) ) {
      remove_filter( 'the_content', array( $this, 'the_content' ), 999 );
      $content = '<div id="fvfr_notice"><p><strong>My Feedburner feed is deprecaded, please resubscribe to my new newsletter service:</strong></p>'."\n".apply_filters( 'the_content', $options['form_code'] )."\n".'<p><a href="'.get_permalink().'">Continue to &#8216;'.get_the_title().'&#8217; &raquo;</a></p></div>';
      $content = apply_filters( 'fv_feedburner_replacement_the_content', $content );
      add_filter( 'the_content', array( $this, 'the_content' ), 999 );
      add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );
    }
    else if( strpos( $_SERVER['HTTP_REFERER'], 'subscribe=yes' ) ) {      
    	$content .= '<p><a href="'.preg_replace( '~(\?.*$)~', '', $_SERVER['HTTP_REFERER'] ).'">Continue to the article &raquo;</a></p>';
    }
    //remove_filter( 'the_content', array( $this, 'the_content' ), 999 );
    return $content.'<!--fvfr-->';
  }
  
  
  public function the_excerpt_rss( $content ) {
    if( $this->is_feedburner() ) {
      $options = get_option( 'fv_feedburner_replacement' );
      $content = $options['feedburner_message'].' '.$content;
    }
    return $content;
  }
  
  
  public function the_permalink_rss( $permalink ) {
    if( $this->is_feedburner() ) {
      if( strpos( $permalink, 'subscribe=yes' ) !== FALSE ) {
        return $permalink;
      }
      if( strpos( $permalink, '?' ) !== FALSE ) {
        $permalink = $permalink.'&subscribe=yes';
      } else {
        $permalink = $permalink.'?subscribe=yes';
      }
    }
    return $permalink;
  }  
  
  
  public function template_redirect() {
  	global $feed, $withcomments, $wp, $wpdb, $wp_version, $wp_db_version, $wp_query;

    if( $wp_query->query_vars['feed'] == 'subscription' ) {
      //include( get_page_template() );
      //return;
    }

  	// Do nothing if not a feed
  	if (!is_feed()) return;

  	global $hyper_cache_stop;
  	//$hyper_cache_stop = true;  //not really needed
  	
    if( $this->user_agents() ) {
      return;
    }
	
    //  don't affect other feed types, just the default one 
    if( $wp_query->query_vars['feed'] != 'feed' || $wp_query->query_vars['withcomments'] == '1' || $wp_query->is_archive ) {
      return;
    }
    
    header( "Location: ".preg_replace( '~feed~', 'feed/subscription', get_bloginfo('rss2_url') ) );
    die();
    
    // Otherwise redirect to a generic page template
    include( get_page_template() );
    return;
  }
  
  
  function user_agents() {
    if( strlen( $_SERVER['HTTP_REFERER'] ) == 0 ) return true;
  
    // Do nothing if feedburner is the user-agent
    if( $this->detect_feedburner() ) return true;
  	
  	// Avoid redirecting Googlebot to avoid sitemap feeds issues
  	// http://www.google.com/support/feedburner/bin/answer.py?hl=en&answer=97090
  	if (preg_match('/googlebot/i', $_SERVER['HTTP_USER_AGENT'])) return true;
  	
  	// Apple Safari RSS reader
  	if (preg_match('/pubsub/i', $_SERVER['HTTP_USER_AGENT'])) return true;
  	
  	return false;
  }
  
  
  function wp_before_admin_bar_render() {
    global $wp_admin_bar;
  	$wp_admin_bar->add_node(
      array(
        'id' => 'fv_feedburner_replacement',
        'title' => __( 'Edit page' ),
        'href' => $this->get_admin_page_url()
      )
    );
  }
  
  
	function wp_headers( $headers, $wp ) {
	  // we need to make sure there is no 304 headers for /feed and /feed/subscription
	  if( ( $wp->query_vars['feed'] == 'feed' || $wp->query_vars['feed'] == 'subscription' ) ) {	  
	    unset( $headers['Last-Modified'] );
	    unset( $headers['ETag'] );
	  }
	  
	  return $headers;
	}   
  
}


$FV_Feedburner_Replacement = new FV_Feedburner_Replacement;

?>
