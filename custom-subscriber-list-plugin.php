<?php 
/**
 * Plugin Name:Snappy List Builder
 * Plugin URI:https://weblinks.cc
 * Author:Wayne Sen
 * Description:This is for subscriber collect plugin on backend
 * Version:1.0.1
 * Text Domain:snappy
 */

//Hooks
add_action('init','slb_register_shortcodes');
add_action('init','slb_register_post_type');

//custom post type
function slb_register_post_type() {
  register_post_type( 'slb_subscriber', array(
    'labels'  => array(
      'name'  => 'Subscriber',
      'edit_item'  => 'Edit Subscriber',
      'add_new'  => 'Add new subscriber',
      'add_new_item'  => 'Add new subscriber',
      'view_item'  => 'View Subscriber',
      'all_items'  => 'All Subscribers',
      'not_found'  => 'No subscriber found'

    ),
    'public'  => false,
    'show_ui'  => true,
    'has_archive'  => false,
    'exclude_from_search'  => true,
    'show_in_menu'  => true,
    'supports'  => false
    

  ) );

  register_post_type( 'slb_list',array(
    'labels'  => array(
      'name'  => 'Lists',
      'edit_item'  => 'Edit Lists',
      'add_new'  => 'Add new Lists',
      'add_new_item'  => 'Add new Lists',
      'view_item'  => 'View Lists',
      'all_items'  => 'All Listss',
      'not_found'  => 'No Lists found'

    ),
    'public'  => false,
    'show_ui'  => true,
    'has_archive'  => false,
    'exclude_from_search'  => true,
    'show_in_menu'  => true,
    'supports'  => ['title']
    ));
}



/**
 * Shortcode
 */
function slb_register_shortcodes() {
  add_shortcode('slb_form','slb_form_shortcode');
}
function slb_form_shortcode($args,$content = ""){
  //setup variable
  $output = '
  <div class="slb">
    <form id="slb_form" name="slb_form" class="slb_form" method="post">
      <p class="slb-input-container">
        <label>Your Name</label>
        <input type="text" name="slb_fname" placeholder="First Name">
        <input type="text" name="slb_lname" placeholder="Last Name">
      </p>
      <p class="slb-input-container">
        <label>Your Email</label>
        <input type="email" name="slb_email" placeholder="xxx@email.com">
      </p>';
      if (strlen($content)) {
        $output .= '<div class="slb_content">' . wpautop($content) . "</div>";
      }

      $output .= '
       <p class="slb-input-container">
        <input type="submit" name="slb_submit" placeholder="Sign me up!">
      </p>
    </form>
  </div>
  ';
  return $output;
}

//metabox
function slb_add_subscriber_metaboxes($post) {
  add_meta_box('sub-subscriber-details','Subscriber Details','slb_subscriber_metabox','slb_subscriber','normal','default');
}
add_action('add_meta_boxes','slb_add_subscriber_metaboxes');


function slb_subscriber_metabox(){
  global $post;
  wp_nonce_field( basename(__FILE__),'slb_subscriber_nonce' );
  ?>
  <div class="slb-field-row">
    <div class="slb-field-container">
      <label for="">First Name <span>*</span></label>
      <input type="text" name="slb_first_name" required="required" value="<?php echo get_post_meta($post->ID,'slb_first_name',true) ?>">
      <label for="">last Name <span>*</span></label>
      <input type="text" name="slb_last_name" required="required" value="<?php echo get_post_meta($post->ID,'slb_last_name',true); ?>">
    </div>
  </div>

  <div class="slb-field-row">
    <div class="slb-field-container">
      <label for="">Email</label>
      <input type="email" name="slb_email" required="required" value="<?php echo get_post_meta($post->ID,'slb_email',true) ?>">
    </div>
  </div>

  <div class="slb-field-row">
    <div class="slb-field-container">
      <label for="">Lists</label>
      <ul>
        <?php 
        $lists = get_post_meta( $post->ID, 'slb_list',false );
        global $wpdb;
        $list_query = $wpdb->get_results(
          "SELECT ID,post_title FROM {$wpdb->posts} WHERE post_type = 'slb_list'"
        );
        if (!is_null($list_query)) :
          foreach($list_query as $list) : 
            $checked = (in_array($list->ID,$lists)) ? 'checked="checked"' : '';
        
         ?>
          
          <li><label><input type="checkbox" name="slb_list[]" value="<?php echo $list->ID; ?>" <?php echo $checked; ?>><?php echo $list->post_title; ?></label></li>

          <?php 
        endforeach;endif;
            ?>
      </ul>
    </div>
  </div>

  <?php 
}

//save metabox
function slb_save_metabox($post_id,$post) {
  //nonce field generate unique idjust for the function you know its not been posted
  if (!isset($_POST['slb_subscriber_nonce']) || !wp_verify_nonce($_POST['slb_subscriber_nonce'],basename(__FILE__))) {
    return;
  }

  //get post type object
  
  $post_type = get_post_type_object( $post->post_type );


  //check if current user has permission to edit the post
  if (!current_user_can( $post_type->cap->edit_post,$post_id )) {
    return;
  }

  //get the posted data and sanitize
  
  $first_name = (isset($_POST['slb_first_name'])) ? sanitize_text_field( $_POST['slb_first_name']) : '';
  $last_name = (isset($_POST['slb_last_name'])) ? sanitize_text_field( $_POST['slb_last_name']) : '';
  $email = (isset($_POST['slb_email'])) ? sanitize_text_field( $_POST['slb_email']) : '';
  $lists = (isset($_POST['slb_list']) && is_array($_POST['slb_list'])) ? (array) $_POST['slb_list'] : [];

  //update post meta
  update_post_meta($post_id,'slb_first_name',$first_name);  //metakey is the name of the field
  update_post_meta($post_id,'slb_last_name',$last_name);  //metakey is the name of the field
  update_post_meta($post_id,'slb_email',$email);  //metakey is the name of the field
  //delete the exsting list meta
  
  delete_post_meta( $post_id, 'slb_list' );  //如果之前有slb_list这个field的话就先删除

  //add new list meta
  if(!empty($lists)){
  foreach($lists as $index=>$list_id) {
    //add list relational meta value
    add_post_meta($post_id,'slb_list',$list_id,false); //not unique
  }}


}
add_action('save_post','slb_save_metabox',10,2);  //这里传递的是两个参数，所以必须要添加10,2 

//change the title column title
function slb_edit_change_post_title() {
  global $post;
  if ($post->post_type == 'slb_subscriber') {
    add_filter('the_title','slb_subscriber_title',100,2);
  }
}
add_action('admin_head-edit.php','slb_edit_change_post_title');

function slb_subscriber_title($title,$post_id) {
  $new_title = get_post_meta($post_id,'slb_first_name',true) . ' ' . get_post_meta( $post_id, 'slb_last_name',true );
  return $new_title;
}


//custom columns 添加自定义列标题
add_filter("manage_edit-slb_subscriber_columns", "slb_scubscriber_column_headers");
function slb_scubscriber_column_headers($columns){
  $columns = array(
    "cb" => "<input type=\"checkbox\" />",  //就是title旁边可以选择的 checkbox 
    "title" => __( 'Subscriber Name', 'tie' ),  // 第二列是title 
    "email"  => __('Email','tie'),
    "id"  => __('ID','tie')
  );

  return $columns;
}

//设置自定义列内容
add_action("manage_slb_subscriber_posts_custom_column",  "slb_subscriber_custom_columns");
function slb_subscriber_custom_columns($column){
  global $post;

  $original_post = $post;

  switch ($column) {
    case "email":
    echo get_post_meta($post->ID,'slb_email',true);
    case "id":
      echo $post->ID;
    break;
  }
}

add_filter("manage_edit-slb_list_columns", "slb_list_column_headers");
function slb_list_column_headers($columns){
  $columns = array(
    "cb" => "<input type=\"checkbox\" />",  //就是title旁边可以选择的 checkbox 
    "title" => __( 'List Name', 'tie' ),  // 第二列是title 
   
  );

  return $columns;
}