<?php

/**
 * Setting custom variable htdocs root path
 */
variable_set('htdocs_root', str_replace(strrchr(DRUPAL_ROOT, "/"), "/htdocs", DRUPAL_ROOT));

/**
 *  Implements template_preprocess_page()
 *
 *  The code below handles additional page template/CSS suggestions in Drupal
 *  and other various and sundry activities
 */
function ncsulib_foundation_preprocess_page(&$variables) {
  // Add page--node_type.tpl.php suggestions
  if (!empty($variables['node'])) {
    $variables['theme_hook_suggestions'][] = 'page__' . $variables['node']->type;
  }

  // Convenience variables
  if (!empty($variables['page']['sidebar_first'])){
    $left = $variables['page']['sidebar_first'];
  }

  if (!empty($variables['page']['sidebar_second'])) {
    $right = $variables['page']['sidebar_second'];
  }

  // Dynamic sidebars (this is critical)
  if (!empty($left) && !empty($right)) {
    $variables['main_grid'] = 'medium-6 push-3';
    $variables['sidebar_left'] = 'pull-6';
  } elseif (empty($left) && !empty($right)) {
    $variables['main_grid'] = 'medium-9';
    $variables['sidebar_left'] = '';
  } elseif (!empty($left) && empty($right)) {
    $variables['main_grid'] = 'medium-9 push-3';
    $variables['sidebar_left'] = 'pull-9';
  } else {
    $variables['main_grid'] = 'medium-12';
  }


  /**
   * End d.o Foundation code
   */

  // Begin CSS suggestions
  if (module_exists('path')) {
    $alias = drupal_get_path_alias(str_replace('/edit','',$_GET['q']));
    // If the alias is a clean URL
    if ($alias != $_GET['q'] || empty($variables['node'])) {
      // Break the alias into its parts and iterate through the alias part by
      // part
      $i=0;
      foreach (explode('/', $alias) as $path_part) {
        if ($i==0) {
          // If this is the first time through the loop, create the template
          // suggestion
          $template_suggestion = $path_part;
          $css_suggestion = $path_part;
        } elseif ($i>=1) {
          if ($i==1) {
            // If this is the second time through the loop, create a variable
            // to append each $path_part to
            $path_part_holder = $css_suggestion . '--' . $path_part;
          } elseif ($i>=2) {
            $path_part_holder .= '--' . $path_part;
          }

          // If this is the second time or more through the loop, continue to
          // append the alias path to the template suggestion
          $template_suggestion = $template_suggestion . '__' . $path_part;
          $css_suggestions[] = $path_part_holder;
        }
        $i++;
      }

      $template_suggestion = 'page__' . $template_suggestion;
      // Add the template suggestion to the template suggestions hook
      $variables['theme_hook_suggestions'][] = $template_suggestion;
    }

    // Create the CSS suggestion(s)
    if (isset($css_suggestion)) {
      $css_suggestion = path_to_theme() .'/styles/core/custom/'. $css_suggestion .'.css';
      // CSS suggestion for the top level alias path
      $include_style[] = $css_suggestion;
      // If the page resides at a deep level and more specific CSS is desired,
      // add more specific page CSS suggestions
      if (isset($css_suggestions)) {
        foreach ($css_suggestions as $suggestion) {
          $include_style[] = path_to_theme() .'/styles/core/custom/'. $suggestion .'.css';
        }
      }
    }

    // If there are CSS suggestions to include
    if (isset($include_style)) {
      foreach ($include_style as $included_styles) {
        // Add the CSS suggestion to Drupal, add it after the 100 group which
        // contains the global theme-level css
        drupal_add_css($included_styles, array('group' => 101));
      }
    }
    // End CSS suggestions
  }


  // Creating a single template suggestion for all pages that begin with /scrc
  // allowing for overrides by more specific path-based or node id templates
  // e.g. page--scrc--zoologicalhealth.tpl.php or page--node--9999.tpl.php
  if (preg_match('/^scrc/', $alias)){
    $arr_length = count($variables['theme_hook_suggestions']);
    array_splice($variables['theme_hook_suggestions'], ($arr_length-2), 0, 'page__scrc__subpage');
  }

  // Add sidebard detector to the node object
  if(!empty($variables['page']['sidebar_first'])){
    $node_id = key($variables['page']['content']['system_main']['nodes']);
    $variables['page']['content']['system_main']['nodes'][$node_id]['#node']->sidebar_first = TRUE;
  }

  // Add custom JS/CSS
  $url_comp = explode('/', request_uri());

  // for entire directory (ex: /find)
  if (isset($url_comp[1])) {
    switch ($url_comp[1]) {
      case 'styleguide':
        drupal_add_js(path_to_theme() . '/scripts/styleguide.js', array('type' => 'file', 'group' => 101, 'weight' => 1));
        break;
    }

    if (isset($url_comp[1]) && isset($url_comp[2])) {
      // for two dirs deep (ex: find/books)
      switch ($url_comp[1] . '/' . $url_comp[2]) {
        case 'borrow/privileges':
          drupal_add_js(path_to_theme() . '/scripts/borrow-privileges.js', array('type' => 'file', 'group' => 101, 'weight' => 1));
          break;
      }
    }
  }

  // target a specific single page
  $url_comp = explode('/', request_path());
  $url_comp = implode('--', $url_comp);
  switch ($url_comp) {
    case 'techlending':
      drupal_add_js(path_to_theme() . '/scripts/vendor/foundation/foundation.equalizer.js', array('type' => 'file', 'group' => 101, 'weight' => 1));
      break;
    case 'huntlibrary--namingopportunities':
      drupal_add_css(path_to_theme() . '/styles/blitzer/jquery-ui-1.10.4.custom.min.css', 'file');
      drupal_add_js(path_to_theme() . '/scripts/vendor/jquery-ui-1.10.4.custom.min.js', 'file');
      drupal_add_js(path_to_theme() . '/scripts/vendor/jquery.imagemapster.min.js', 'file');
      drupal_add_js(path_to_theme() . '/scripts/namingopps.js', 'file');
      drupal_add_js(path_to_theme() . '/scripts/vendor/jquery.tablesorter.min.js', 'file');
      break;
  }


  // hide default 'no content' text for taxonomy terms
  if(isset($variables['page']['content']['system_main']['no_content'])) {
    unset($variables['page']['content']['system_main']['no_content']);
  }

  // overwrite 'user activity' taxonomy template page
  if (arg(0) == 'taxonomy' && arg(1) == 'term' && is_numeric(arg(2))) {
    $term = taxonomy_term_load(arg(2));
    // $variables['theme_hook_suggestions'][] = 'page__taxonomy_' . $term->vocabulary_machine_name;

    // unset all content from user activity taxonomy page
    if($term->vocabulary_machine_name == 'user_activities' || $term->vocabulary_machine_name == 'services'){
      unset($variables['page']['content']['system_main']['nodes']);
      unset($variables['page']['content']['system_main']['term_heading']['term']);
    };
  }


} // End tremendous template_preprocess_page function


/**
 * Impelments template_preprocess_node()
 *
 */
function ncsulib_foundation_preprocess_node(&$variables) {
  switch ($variables['type']) {
    case 'space':
      drupal_add_css(path_to_theme() . '/styles/core/custom/space.css', array('group' => 101));
      break;

    case 'report':
      drupal_add_css(path_to_theme() . '/styles/core/custom/report.css', array('group' => 101));
      break;

    case 'project':
      drupal_add_css(path_to_theme() . '/styles/core/custom/projects.css', array('group' => 101));
      break;

  }

  // Make "node--NODETYPE--VIEWMODE.tpl.php" templates available for nodes
  if($variables['view_mode'] == 'teaser') {
    $variables['theme_hook_suggestions'][] = 'node__' . $variables['node']->type . '__teaser';
    $variables['theme_hook_suggestions'][] = 'node__' . $variables['node']->nid . '__teaser';
  }
}


/**
 *  Blocks preprocessor
 *
 *  Handles adding additional classes to the blocks on the "/upcomingevents"
 *  page. Adds classes to blocks on the scrc page.
 */
function ncsulib_foundation_preprocess_block(&$variables) {
  if ($variables['block_html_id'] == 'block-views-upcoming-events-block-3') {
    $variables['classes_array'][] = 'medium-8';
    $variables['classes_array'][] = 'columns';
  }
  // adding classes to blocks on /scrc
  if ($variables['block_html_id']  == "block-aggregator-feed-8") {
    $variables['classes_array'][] = 'medium-8';
    $variables['classes_array'][] = 'columns';
  }
  if ($variables['block_html_id']  == "block-block-78"){
    $variables['classes_array'][] = 'medium-3';
    $variables['classes_array'][] = 'columns';
  }
}

/**
 * Implements hook_process_HOOK()
 *
 * Making our resource references (css and js) themeless
 */
function ncsulib_foundation_process_html(&$vars){
    foreach (array('head', 'styles', 'scripts', 'page_bottom') as $replace) {
        if (!isset($vars[$replace])) {
            continue;
        }

        $vars[$replace] = preg_replace('/(src|href|@import )(url\(|=)(")http(s?):/', '$1$2$3', $vars[$replace]);
    }
}

/**
 * Implements theme_menu_link()
 *
 * Adding Foundation 5 class for side navigation
 */
function ncsulib_foundation_menu_tree($variables) {
  return '<ul class="side-nav">' . $variables['tree'] . '</ul>';
}

/**
 * Implements hook_js_alter()
 */
function ncsulib_foundation_js_alter(&$javascript) {
  // Remove old jQuery
  unset($javascript['misc/jquery.js']);

  // Unset jQuery from the jQuery Update module
  unset($javascript['https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js']);
  unset($javascript['https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js']);
  unset($javascript['sites/all/modules/panels/js/panels.js']);
  unset($javascript[0]);
}


/**
 * Implements theme_breadrumb().
 *
 * Print breadcrumbs as a list, with separators.
 */
function ncsulib_foundation_breadcrumb($variables) {
  $links = array();
  $path = '';
  // Get URL arguments
  $arguments = explode('/', request_uri());
  // Remove empty values
  foreach ($arguments as $key => $value) {
    if (empty($value)) {
      unset($arguments[$key]);
    }
  }
  $arguments = array_values($arguments);
  // Add 'Home' link
  $links[] = l(t('Home'), '<front>');
  // Add other links
  if (!empty($arguments)) {
    foreach ($arguments as $key => $value) {
      // Don't make last breadcrumb a link
      if ($key == (count($arguments) - 1)) {
        $links[] = drupal_get_title();
      } else {
        if (!empty($path)) {
          $path .= '/'. $value;
        } else {
          $path .= $value;
        }
        $links[] = l(drupal_ucfirst($value), $path);
      }
    }
  }
  // Set custom breadcrumbs
  drupal_set_breadcrumb($links);
  // Get custom breadcrumbs
  $breadcrumb = drupal_get_breadcrumb();

  if (!empty($breadcrumb)) {
    // Provide a navigational heading to give context for breadcrumb links to
    // screen-reader users. Make the heading invisible with .element-invisible.
    $breadcrumbs = '<h2 class="element-invisible">' . t('You are here') . '</h2>';

    $breadcrumbs .= '<ul class="breadcrumbs">';

    foreach ($breadcrumb as $key => $value) {
      $breadcrumbs .= '<li>' . $value . '</li>';
    }

    $title = strip_tags(drupal_get_title());
    $breadcrumbs .= '</ul>';

    return $breadcrumbs;
  }
}

/**
 * Implements hook_form_FORM_ID_alter()
 *
 */
function ncsulib_foundation_form_user_login_alter(&$form, &$form_state, $form_id) {
  // Alters the text on the user login form
  drupal_set_title(t('Website editing login'));
  $form['name']['#title'] = t('Enter your Unity ID:');
  $form['name']['#description'] = t('');  // Enter descriptive text here, if desired
  $form['pass']['#title'] = t('Enter your Unity password:');
  $form['pass']['#description'] = t('');  // Enter descriptive text here, if desired
}



/**
 * Do we still need this?
 *
 */
function ncsulib_foundation_more_link ($array) {
  if (stristr($array['url'], 'aggregator')) {
    return "";
  }
}

/**
 * Modify the output of views
 *
 */
function ncsulib_foundation_views_pre_render(&$view) {
  // The following two loops add month and day formatted dates to events
  if ($view->name == 'upcoming_events' && $view->current_display == 'block_3') {
    if (!empty($view->result)) {
      for ($i = 0; $i < count($view->result); $i++ ) {
        // Set event url
        $node = node_load($view->result[$i]->nid);
        $url_data = field_get_items('node', $node, 'field_event_url');
        $alias = drupal_get_path_alias('node/'.$node->nid);
        if(!empty($url_data[0]['url'])) {
          $view->result[$i]->event_url = $url_data[0]['url'];
        } else if ($alias){
          $view->result[$i]->event_url = $alias;
        } else {
          $view->result[$i]->event_url = '/node/'.$view->result[$i]->nid;
        }

        // Set event dates
        $timestamp = filter_xss($view->result[$i]->field_data_field_time_field_time_value);
        $timestamp2 = filter_xss($view->result[$i]->field_data_field_time_field_time_value2);
        $open = strtotime($timestamp)+ncsulib_foundation_adjust_for_timezone($timestamp);
        $close = strtotime($timestamp2)+ncsulib_foundation_adjust_for_timezone($timestamp2);
        $view->result[$i]->date_display = (date('m j Y', $open) == date('m j Y', $close)) ? date('M j, Y', $open) : date('M j, Y', $open) . ' - ' . date('M j, Y', $close);
      }
    }
  }

  // Add stylesheet to make the related devices on tech categories look right
  if ($view->name == 'devices_device' && $view->current_display == 'block_9') {
    drupal_add_css(path_to_theme() . '/styles/related_devices.css');
  }

  // Add stylesheet to cover all dataviews block displays
  if ($view->name == "dv_hours_open") {
    drupal_add_css(path_to_theme() . '/styles/dataviews.css');
  }


}

/**
 * Returns HTML for an individual feed item for display in the block.
 *
 * Author: Charlie Morris
 * For SCRC
 * TODO: Can this be deleted?
 */
function ncsulib_foundation_aggregator_block_item($variables) {
  if ($variables['item']->fid == '8') {
    // Display the external link to the item.
    return '<a href="' . check_url($variables['item']->link) . '">' . check_plain($variables['item']->title) . "</a>\n<br />" . filter_xss($variables['item']->description);
  } else {
    return '<a href="' . check_url($variables['item']->link) . '">' . check_plain($variables['item']->title) . "</a>\n";
  }
}


/**
 * Implements theme_field()
 *
 * Just to get rid of stupid colons
 */
function ncsulib_foundation_field($variables) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<div class="field-label"' . $variables['title_attributes'] . '>' . $variables['label'] . '&nbsp;</div>';
  }

  // Render the items.
  $output .= '<div class="field-items"' . $variables['content_attributes'] . '>';
  foreach ($variables['items'] as $delta => $item) {
    $classes = 'field-item ' . ($delta % 2 ? 'odd' : 'even');
    $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
  }
  $output .= '</div>';

  // Render the top-level DIV.
  $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';

  return $output;
}

/**
 * Implements theme_field()
 *
 * Using this to change the markup delivered to the Building field on Space
 * nodes
 */
function ncsulib_foundation_field__field_building_name__space($variables) {
  $output ='';
  foreach ($variables['items'] as $delta => $item) {
    $output = '<span class="building-name">at the '. $variables['items'][0]['#markup'] .'</span>';
  }
  return $output;
}

/**
 * Implements theme_field()
 *
 * Using this to change the markup delivered to the Field Request Form URL
 * field.  Turning it into a button.
 */
function ncsulib_foundation_field__field_request_form_url__device($variables) {
  $output ='';
  $device_nid = $variables['element']['#object']->nid;
  // default is Hill only
  $building = " (Hill only)";
  // NIDs that represent devices that can be lent anywhere
  $building_agnostic = array(23583, 23564, 22470);

  foreach ($variables['items'] as $delta => $item) {
    // Change value to "Hunt only" if it's a Hunt only lend device
    if ($device_nid == 24518) {
      // 24518 = Google Glass
      $building = " (Hunt only)";
    } else if (in_array($device_nid, $building_agnostic)) {
      // 23583 = projectors
      $building = '';
    }
    $output = '<div class="clear-left"><a href="'.drupal_render($item).'" class="button">Request'.$building.'</a></div>';
  }
  return $output;
}

/**
 * Implements theme_field()
 *
 * Using this to change the markup delivered to the Reservation Method
 * field.  Turning it into a button.
 */
function ncsulib_foundation_field__field_reservation_method__space($variables) {
  $output = '';

  // Create a button based on the method chosen
  $res_method   = $variables['items'][0]['#markup'];
  $nid          = $variables['element']['#object']->nid;
  $node         = node_load($nid);

  switch ($res_method) {
    case 'By Room Reservation System':
      $today        = date('m-d-Y');

      // Getting the phpScheduleIt id
      $room_res_id  = field_get_items('node', $node, 'field_room_res_id');
      $schedule_id_render_array  = field_view_value('node', $node, 'field_room_res_id', $room_res_id[0]);
      $schedule_id = $schedule_id_render_array['#markup'];

      // Check for exceptions on small-only button, issue discovered by Cory Lown
      $space_nid = $variables['element']['#object']->nid;
      $nodes_that_use_desktop_version = array(
        'Mini theater' => 1736,
        'Fishbowl' => 2092,
        'DML Studio' => 23924,
        'DML Workstations' => 24235
        );

      $mlib_option = '<a class="button show-for-small-only small" href="//m.lib.ncsu.edu/studyrooms/reserve.php?schedule='. $schedule_id .'">&rdsh; Reserve</a>';
      $mlib_option .= '<a class="button show-for-medium-up small" href="//www.lib.ncsu.edu/roomreservations/schedule.php?date='. $today .'&scheduleid='. $schedule_id .'">&rdsh; Reserve</a>';
      $desktop_only = '<a class="button" href="//www.lib.ncsu.edu/roomreservations/schedule.php?date='. $today .'&scheduleid='. $schedule_id .'">&rdsh; Reserve</a>';

      $output = in_array($space_nid, $nodes_that_use_desktop_version) ? $desktop_only : $mlib_option;
      break;

    case 'By Mediated Email Form':
      $request_form_url = field_get_items('node', $node, 'field_request_form_url');
      $form_url  = field_view_value('node', $node, 'field_request_form_url', $request_form_url[0]);
      $output = '<a class="button tiny" href="'. $form_url['#element']['url'] .'">&rdsh; Request this room</a>';
      break;

    case 'Not Reservable':
    default:
      $output = ' ';
      break;
  }
  return $output;
}

/**
 * Implements theme_field()
 *
 * Make room numbers a comma separated list
 */
function ncsulib_foundation_field__field_room_number__space($variables) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<h3' . $variables['title_attributes'] . '>' . $variables['label'] . '</h3>';
  }

  // Render the items as a comma separated inline list

  if (count($variables['items']) > 1) {
    $output .= '<span class="room-list">Room Numbers:&nbsp;</span>';
    $output .= '<ul class="room-numbers"' . $variables['content_attributes'] . '>';
    for ($i=0; $i < count($variables['items']); $i++) {
      $output .= '<li>'. drupal_render($variables['items'][$i]);
      $output .= ($i == count($variables['items'])-1) ? '</li>' : ', </li>';
    }
    $output .= '</ul>';
  } else {
    $output .= '<span class="room-list">Room Number:&nbsp;' . drupal_render($variables['items'][0]) . '</span>';
  }

  // Building map
  $building_value = $variables['element']['#object']->field_building_name['und'][0]['value'];
  switch ($building_value) {
    case 'hill':
      $output .= '<div class="building-map"><a href="/libmaps"><i class="fa fa-map-marker"></i> building map</a></div>';
      break;

    case 'hunt':
      $output .= '<div class="building-map"><a href="/sites/default/files/files/pdfs/HuntLibrary-detailed-map.pdf"><i class="fa fa-map-marker"></i> building map</a></div>';
      break;

    default:
      $output .= '';
      break;
  }


  return $output;
}

/**
 * Implements theme_field()
 *
 * Make an unordered list
 */
function ncsulib_foundation_field__field_policies__space($variables) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<h3' . $variables['title_attributes'] . '>' . $variables['label'] . '</h3>';
  }

  // Render the items as a comma separated inline list
  $output .= '<ul class="field-items"' . $variables['content_attributes'] . '>';

  foreach ($variables['items'] as $delta => $item) {
    $output .= '<li>' . drupal_render($item) . '</li>';
  }


  $output .= '</ul>';

  return $output;
}

/**
 * Implements theme_field()
 *
 * Make an unordered list
 */
function ncsulib_foundation_field__field_get_help__space($variables) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<h3' . $variables['title_attributes'] . '>' . $variables['label'] . '</h3>';
  }

  // Render the items as a comma separated inline list
  $output .= '<ul class="field-items"' . $variables['content_attributes'] . '>';

  foreach ($variables['items'] as $delta => $item) {
    $output .= '<li>' . drupal_render($item) . '</li>';
  }


  $output .= '</ul>';

  return $output;
}


/**
 * Implements theme_field()
 *
 * Turns field labels into heading3
 */
function ncsulib_foundation_field__space($variables) {
  $output = '';

  // Render the label, if it's not hidden and display it as a heading 2
  if (!$variables['label_hidden']) {
    $output .= '<h3' . $variables['title_attributes'] . '>' . $variables['label'] . '</h3>';
  }

  // Render the items.
  $output .= '<div class="field-items"' . $variables['content_attributes'] . '>';
  foreach ($variables['items'] as $delta => $item) {
    $classes = 'field-item ' . ($delta % 2 ? 'odd' : 'even');
    $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
  }
  $output .= '</div>';

  // Render the top-level DIV.
  $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';

  return $output;
}

/**
 * Implements theme_field()
 *
 * Print the building along with the room name
 */
function ncsulib_foundation_field__field_space__event($variables) {
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<h3' . $variables['title_attributes'] . '>' . $variables['label'] . '</h3>';
  }

  // Render the items as a comma separated inline list
  $output .= '<ul class="field-items"' . $variables['content_attributes'] . '>';

  foreach ($variables['items'] as $delta => $item) {
    // Print space name
    $output .= '<li>' . drupal_render($item);
    $space_nid = $variables['element']['#items'][$delta]['entity']->nid;
    $space_node = node_load($space_nid);

    // Load building info, if present, print it
    $building_field = field_get_items('node', $space_node, 'field_building_name');
    $building_yes = !empty($building_field);
    if (!empty($building_field)) {
      $building_out = field_view_value('node', $space_node, 'field_building_name', $building_field[0]);
      $output .= ' at the '. drupal_render($building_out) .'</li>';
    }
    $output .= '</li>';

  }
  $output .= '</ul>';

  return $output;
}

/**
 * Implements theme_field()
 *
 * Turns field labels into heading3
 */
function ncsulib_foundation_field__event($variables) {
  $output = '';

  // Render the label, if it's not hidden and display it as a heading 2
  if (!$variables['label_hidden']) {
    $output .= '<h3' . $variables['title_attributes'] . '>' . $variables['label'] . '</h3>';
  }

  // Render the items.
  $output .= '<div class="field-items"' . $variables['content_attributes'] . '>';
  foreach ($variables['items'] as $delta => $item) {
    $classes = 'field-item ' . ($delta % 2 ? 'odd' : 'even');
    $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
  }
  $output .= '</div>';

  // Render the top-level DIV.
  $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';

  return $output;
}

/**
 * Implements theme_username()
 */
function ncsulib_foundation_username($variables) {
  $author = user_load($variables['uid']);
  $photo = get_user_image($author);

  // Add user's title
  $field_title = field_get_items('user', $author, 'field_title');
  $title = field_view_value('user', $author, 'field_title', $field_title[0]);

  if (isset($variables['link_path'])) {
    // We have a link path, so we should generate a link using l().
    $output  = '<div class="user-photo">'. render($photo) .'</div>';
    $output .= '<div class="user-details">';
    $output .= '<span class="user-name">'. l($variables['name'] . $variables['extra'], $variables['link_path'], $variables['link_options']) .'</span>';
    $output .= '<span class="user-title">'. render($title) .'</span>';
    $output .= '</div>';
    $output  = '<div class="user-info">'. $output .'</div>';
  }
  else {
    // Modules may have added important attributes so they must be included
    // in the output. Additional classes may be added as array elements like
    // $variables['attributes_array']['class'][] = 'myclass';
    $output  = '<span' . drupal_attributes($variables['attributes_array']) . '>';
    $output .=  render($photo);
    $output .=  $variables['name'];
    $output .=  $variables['extra'];
    $output .= '</span>';
  }
  return $output;
}

/**
 * Implements theme_field()
 *
 * Make collaborators show up with name, title and thumbnail image
 */
function ncsulib_foundation_field__field_staff($variables) {
  $output = '';

  // Load all collaborators' fields
  $collaborators = array();
  foreach ($variables['element']['#items'] as $key => $value) {
    $collaborators[] = user_load($value['target_id']);

    // Add link to user's page
    $variables['items'][$key]['link'] = 'user/'. $collaborators[$key]->uid;

    // Add user's image
    $variables['items'][$key]['image'] = get_user_image($collaborators[$key]);

    // Add user's title
    $field = field_get_items('user', $collaborators[$key], 'field_title');
    $variables['items'][$key]['title'] = field_view_value('user', $collaborators[$key], 'field_title', $field[0]);

  }

  // The HTML template for user info
  foreach ($variables['items'] as $delta => $item) {
    $output .= '<div class="user-info">';
    $output .= '<div class="user-photo">'. render($item['image']) .'</div>';
    $output .= '<div class="user-details">';
    $output .= '<span class="user-name">'. l(render($item), $item['link']) .'</span>';
    $output .= '<span class="user-title">'. render($item['title']) .'</span>';
    $output .= '</div>';
    $output .= '</div>';

  }

  return $output;
}


/**
 * Helper function that adjusts date to current timezone. Especially for
 * daylight savings
 */
function ncsulib_foundation_adjust_for_timezone($time){
    $origin_dtz = new DateTimeZone(date_default_timezone_get());
    $origin_dt = new DateTime($time, $origin_dtz);
    return $origin_dtz->getOffset($origin_dt);
}


/**
 * Helper function for getting an image render array
 */
function get_user_image($user) {
  $image_array = '';

  if (field_get_items('user', $user, 'field_staff_photo')) {
      $image = field_get_items('user', $user, 'field_staff_photo');
      $image_array = field_view_value('user', $user, 'field_staff_photo', $image[0], array(
        'type' => 'image',
        'settings' => array(
          'image_style' => 'half-page-width',
          'image_link' => 'content',
        ),
      ));
    } else {
      $image_array  = '<img src="http://www.placecage.com/460/460">';
    }
  return $image_array;
}
