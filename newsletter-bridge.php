<?php

class FV_Feedburner_Replacement_Newsletter_Bridge {

  var $name;

  var $status;

  function __construct() {
  
  }
  
  
  public function activate() {
    /*
    Taken from the Newsletter plugin, NEWSLETTER_LIST_MAX and NEWSLETTER_PROFILE_MAX filled in with numbers, plugin db function replaced with $wpdb->query
    */
		global $wpdb, $charset_collate;
	  $wpdb->query("create table if not exists " . $wpdb->prefix . "newsletter (id int auto_increment, `email` varchar(100) not null default '', primary key (id), unique key email (email)) $charset_collate");

		// User personal data
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column name varchar(100) not null default ''");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column surname varchar(100) not null default ''");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column sex char(1) not null default ''");

		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column status char(1) not null default 'S'");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column created timestamp not null default current_timestamp");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column token varchar(50) not null default ''");

		// Follow up
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column followup_time bigint(20) not null default 0");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column followup_step tinyint(4) not null default 0");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column followup tinyint(4) not null default 0");

		// Feed by mail
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column feed tinyint(4) not null default 0");
		$wpdb->query("alter table " . $wpdb->prefix . "newsletter add column feed_time bigint(20) not null default 0");

		// List/Preferences
		for ($i = 1; $i <= 20; $i++) {
				$wpdb->query("alter table {$wpdb->prefix}newsletter add column list_$i tinyint(4) not null default 0");
		}

		// Profiles
		for ($i = 1; $i <= 20; $i++) {
				$wpdb->query("alter table {$wpdb->prefix}newsletter add column profile_$i varchar(255) not null default ''");
		}

		// TODO: Still makes sense the referrer?
		$wpdb->query("alter table {$wpdb->prefix}newsletter add column referrer varchar(50) not null default ''");
		$wpdb->query("alter table {$wpdb->prefix}newsletter add column http_referer varchar(255) not null default ''");
		$wpdb->query("alter table {$wpdb->prefix}newsletter add column wp_user_id int not null default 0");
		$wpdb->query("alter table {$wpdb->prefix}newsletter add column ip varchar(50) not null default ''");
		$wpdb->query("alter table {$wpdb->prefix}newsletter add column test tinyint(4) not null default 0");

		// TODO: Flow module should add that it self (?)
		$wpdb->query("alter table {$wpdb->prefix}newsletter add column flow tinyint(4) not null default 0");
    /*
    Taken from the Newsletter plugin
    */
  }
  
  
  public function confirm( ) {
    add_filter( 'fv_feedburner_replacement_the_content', array( $this, 'output' ) );
    
    global $wpdb;
    $token = $wpdb->escape( $_REQUEST['fvfr'] );
    
    if( $subscriber = $wpdb->get_row( "SELECT id, name FROM {$wpdb->prefix}newsletter WHERE token = '{$token}' " ) ) {
      $this->name = $subscriber->name;
      $wpdb->query( "UPDATE {$wpdb->prefix}newsletter SET status = 'C' WHERE token = '{$token}'" );
      if( !$wpdb->last_error ) {
        $this->status = 'subscr_conf';
        return;
      }
    }
    $this->status == 'error';
    return;
  }
  
  
  public function export() {
    global $wpdb;
    $subscribers = $wpdb->get_results( "SELECT name,email FROM {$wpdb->prefix}newsletter WHERE status = 'C'" );
    
    $output = 'Name,E-mail'."\r\n";
    foreach( $subscribers AS $subscribers_item ) {
      $output .= $subscribers_item->name.','.$subscribers_item->email."\r\n";
    }
    
    header('Content-Disposition: attachment; filename="subscribers.csv"');
    echo $output;    
    die();
  }
  
  
  public function get_count() {
    global $wpdb;
    $count = $wpdb->get_var( "SELECT count(id) FROM {$wpdb->prefix}newsletter WHERE status = 'C'" );
    return $count;  
  }
  
  
  public function output( $output ) {
    $options = get_option( 'fv_feedburner_replacement' );

    if( $this->status == 'error_email' ) {
      $output = '<p><strong>'.$options['message_error_email'].'</strong></p>'.$output;
    }    
    if( $this->status == 'error_name' ) {
      $output = '<p><strong>'.$options['message_error_name'].'</strong></p>'.$output;
    }
        
    if( $this->status == 'subscr_exists' ) {
      $output = '<strong>'.$options['message_subscr_exists'].'</strong>';
    }    
    if( $this->status == 'subscr_succ' ) {
      $output = wpautop( $this->parse_template( $options['message_subscr_succ'], $this->name ) );
      if( is_single() ) {
        $output .= '<p><a href="'.get_permalink().'">Continue to &#8216;'.get_the_title().'&#8217; &raquo;</a></p>';
      }      
    }
    if( $this->status == 'subscr_conf' ) {
      $output = wpautop( $this->parse_template( $options['message_subscr_conf'], $this->name ) );
    }    
    
    if( $this->status == 'error' ) {
      $output = 'Your subscription could not be processed. Please contact the site administrator.';
    }    
    return $output;
  }
  
  
  function parse_template( $template, $name, $token = false ) {
    global $FV_Feedburner_Replacement;
    
    if( stripos( $template, '%confirmation%' ) !== FALSE ) { 
      $template = str_ireplace( '%confirmation%', $FV_Feedburner_Replacement->get_subscription_url().'?fvfr='.$token, $template );
    } else if( $token )  {
      $template = $template."\n\n".$FV_Feedburner_Replacement->get_subscription_url().'?fvfr='.$token;
    }
    
    $template = str_ireplace( '%name%', $name, $template );
    
    return $template;
  }
  

  public function subscribe( ) {

    add_filter( 'fv_feedburner_replacement_the_content', array( $this, 'output' ) );
    
    if( !is_email( $_REQUEST['email'] ) ) {
      $this->status = 'error_email';   
      return;
    }
    if( !strlen( trim($_REQUEST['fvfr_name']) ) ) {
      $this->status = 'error_name';
      return;
    }
    
    global $wpdb;
    $email = $wpdb->escape( trim($_REQUEST['email']) );
    $name = $wpdb->escape( trim($_REQUEST['fvfr_name']) );
    $this->name = $name;
    $time = date('Y-m-d H:i:s');
    $token = $wpdb->escape( substr(md5(rand()), 0, 10) );

    if( $wpdb->get_var( "SELECT email FROM {$wpdb->prefix}newsletter WHERE email = '{$email}' " ) ) {
      $this->status = 'subscr_exists';
      return;
    }

    $aFields = array(
      'email'     => $email,
      'name'      => $name,
      'status'    => 'S',
      'created'   => $time,
      'token'     => $token,
      'feed'      => 1,
      'ip'        => $_SERVER['REMOTE_ADDR']
    );
    $aFormats = array(
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%d',
      '%s',
    );

   $aFieldNFormats = apply_filters(
      'fv_feedburner_replacement_insert_feed_subscriber',
      array( 'fields' => $aFields, 'formats' => $aFormats )
   );
   $aFields  = $aFieldNFormats[ 'fields' ];
   $aFormats = $aFieldNFormats[ 'formats' ];

    $wpdb->insert(
      "{$wpdb->prefix}newsletter",
      $aFields,
      $aFormats
    );

    if( $wpdb->last_error ) {
      $this->status = 'error';
      return;
    }    
        
    $options = get_option( 'fv_feedburner_replacement' );
    
    wp_mail( $email, $options['mail_confirmation_subject'], $this->parse_template( $options['mail_confirmation_mail'], $name, $token ) );
        
    $this->status = 'subscr_succ';
    
    return;
    /* You successfully subscribed to my newsletter. You'll receive in few minutes a confirmation email. Follow the link in it to confirm the subscription. If the email takes more than 15 minutes to appear in your mailbox, check the spam folder.
    */
    
  }

}

?>