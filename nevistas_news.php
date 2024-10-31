<?php
/*
Plugin Name: NevistasNews
Description: Displays a selectable News feeds from Nevistas News Network of web sites, inline, widget or in theme.
Version:     1.1
Author:      Hotel News Resource
Author URI:  http://www.hotelnewsresource.com/
Plugin URI:  http://www.hotelnewsresource.com/Info-wordpress.html
License:     GPL

Minor parts of WordPress-specific code from various other GPL plugins.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
include_once(ABSPATH . WPINC . '/rss.php');

global $nevistas_news_instance;

if ( ! class_exists('nevistas_news_plugin')) {
    class nevistas_news_plugin {

        // So we don't have to query database on every replacement
        var $settings;


        var $nevistasfeeds = array(
	       "All News" => "xml.php",
           "Airline Industry" => "xml.php?industry=10",
           "Cruise Industry" => "xml.php?industry=8",
           "Gaming Industry" => "xml.php?industry=2",
           "Hotel Industry" => "xml.php?industry=1",
           "Restaurant Industry" => "xml.php?industry=3",
           "Travel Industry" => "xml.php?industry=4",
           "Travel Consumer" => "xml.php?industry=11",
           "Yoga" => "xml_health.php?industry=11",

        );

        var $desctypes = array(
            'Short' => '',
            'Long' => 'l',
        );

        // Constructor
        function nevistas_news_plugin() {

            // Form POSTs dealt with elsewhere
            if ( is_array($_POST) ) {
                if ( $_POST['nevistas_news-widget-submit'] ) {
                    $tmp = $_POST['nevistas_news-widget-feed'];
                    $alloptions = get_option('nevistas_news');
                    if ( $alloptions['widget-1'] != $tmp ) {
                        if ( $tmp == '*DEFAULT*' ) {
                            $alloptions['widget-1'] = '';
                        } else {
                            $alloptions['widget-1'] = $tmp;
                        }
                        update_option('nevistas_news', $alloptions);
                    }
                } else if ( $_POST['nevistas_news-options-submit'] ) {
                    // noop
                } else if ( $_POST['nevistas_news-submit'] ) {
                    // noop
                }
            }

	    add_filter('the_content', array(&$this, 'insert_news')); 
            add_action('admin_menu', array(&$this, 'admin_menu'));
            add_action('plugins_loaded', array(&$this, 'widget_init'));

            // Hook for theme coders/hackers
            add_action('nevistas_news', array(&$this, 'display_feed'));

            // Makes it backwards compat pre-2.5 I hope
            if ( function_exists('add_shortcode') ) {
                add_shortcode('nevistas-news', array(&$this, 'my_shortcode_handler'));
             }

        }

        // *************** Admin interface ******************

        // Callback for admin menu
        function admin_menu() {
            add_options_page('Nevistas News Options', 'Nevistas News',
                             'administrator', __FILE__, 
                              array(&$this, 'plugin_options'));
            add_management_page('Nevistas News', 'Nevistas News', 
                                'administrator', __FILE__,
                                array(&$this, 'admin_manage'));
               
        }

        // Settings -> Nevistas News
        function plugin_options() {

           if (get_bloginfo('version') >= '2.7') {
               $manage_page = 'tools.php';
            } else {
               $manage_page = 'edit.php';
            }
            print <<<EOT
           <div class="wrap">
            <h2>Nevistas News</h2>
            <p>This plugin allows you to define a number of Nevistas News 
               feeds and have them displayed anywhere in content, in a widget
               or in a theme. Any number of inline replacements or theme
               inserts can be made, but only one widget instance is
               permitted in this release. To use the feeds insert one or more
               of the following special html comments or Shortcodes 
               anywhere in user content. Note that Shortcodes, i.e. the
               ones using square brackets, are only available in 
               WordPress 2.5 and above.</p>
               <p><b>Important:</b> Go to <a href="$manage_page?page=nevistas-news/nevistas_news.php">Manage -> Nevistas News</a> to set up your default feed.</p>
               <p><b>For usage as a widget</b>
               <ul><li>For widget use, simply use the widget as any other after selecting which feed it should display.</li></ul></p>
               <p><b>For usage in templates</b>
               <ul><li><b>&lt;--nevistas_news--&gt</b> (for default feed)</li>
               <li><b>&lt;--nevistas_news#feedname--&gt</b></li>
               <li><b>[nevistas_news]</b> (also for default feed)</li>
               <li><b>[nevistas_news name="feedname"]</b></li></ul><p>
               To insert in a theme call <b>do_action('nevistas_news');</b> or 
               alternatively <b>do_action('nevistas_news', 'feedname');</b><p>
               To manage feeds, go to <a href="$manage_page?page=nevistas-news/nevistas_news.php">Manage -> Nevistas News</a>, where you will also find more information.</p>
 
EOT;
        }

        // Manage -> Nevistas News
        function admin_manage() {
            // Edit/delete links
            $mode = trim($_GET['mode']);
            $id = trim($_GET['id']);

            $this->upgrade_options();

            $alloptions = get_option('nevistas_news');

            $flipnevistasfeeds     = array_flip($this->nevistasfeeds);
            $flipdesctypes   = array_flip($this->desctypes);

            if ( is_array($_POST) && $_POST['nevistas_news-submit'] ) {

                $newoptions = array();
                $id                       = $_POST['nevistas_news-id'];

                $newoptions['name']       = $_POST['nevistas_news-name'];
                $newoptions['title']      = $_POST['nevistas_news-title'];
                $newoptions['feedurl']     = $_POST['nevistas_news-feedurl'];
                $newoptions['numnews']    = $_POST['nevistas_news-numnews'];
                $newoptions['desctype']    = $_POST['nevistas_news-desctype'];
                $newoptions['feedtype']   = $flipnevistasfeeds[$newoptions['feedurl']];

                if ( $alloptions['feeds'][$id] == $newoptions ) {
                    $text = 'No change...';
                    $mode = 'main';
                } else {
                    $alloptions['feeds'][$id] = $newoptions;
                    update_option('nevistas_news', $alloptions);
 
                    $mode = 'save';
                }
            } else if ( is_array($_POST) && $_POST['nevistas_news-options-cachetime-submit'] ) {
                if ( $_POST['nevistas_news-options-cachetime'] != $alloptions['cachetime'] ) {
                    $alloptions['cachetime'] = $_POST['nevistas_news-options-cachetime'];
                    update_option('nevistas_news', $alloptions);
                    $text = "Cache time changed to {$alloptions[cachetime]} seconds.";
                } else {
                    $text = "No change in cache time...";
                }
                $mode = 'main';
            }

            if ( $mode == 'newfeed' ) {
                $newfeed = 0;
                foreach ($alloptions['feeds'] as $k => $v) {
                    if ( $k > $newfeed ) {
                        $newfeed = $k;
                    }
                }
                $newfeed += 1;

                $text = "Please configure new feed and press Save.";
                $mode = 'main';
            }

            if ( $mode == 'save' ) {
                $text = "Saved feed {$alloptions[feeds][$id][name]} [$id].";
                $mode = 'main';
            }

            if ( $mode == 'edit' ) {
                if ( ! empty($text) ) {
                     echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
                }
                $text = "Editing feed {$alloptions[feeds][$id][name]} [$id].";

                $edit_id = $id;
                $mode = 'main';
            }

            if ( $mode == 'delete' ) {

                $text = "Deleted feed {$alloptions[feeds][$id][name]} [$id].";
                
                unset($alloptions['feeds'][$id]);

                update_option('nevistas_news', $alloptions);
 
                $mode = 'main';
            }

            // main
            if ( empty($mode) or ($mode == 'main') ) {

                if ( ! empty($text) ) {
                     echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>';
                }
                print '<div class="wrap">';
                print ' <h2>';
                print _e('Manage Nevistas News Feeds','nevistas_news');
                print '</h2>';
                print ' <table id="the-list-x" width="100%" cellspacing="3" cellpadding="3">';
                print '  <thead>';
                print '   <tr>';
                print '    <th scope="col">';
                print _e('Key','nevistas_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Name','nevistas_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Admin-defined title','nevistas_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Feed','nevistas_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Item length','nevistas_news');
                print '</th>';
                print '    <th scope="col">';
                print _e('Max items','nevistas_news');
                print '</th>';
                print '    <th scope="col" colspan="3">';
                print _e('Action','nevistas_news');
                print '</th>';
                print '   </tr>';
                print '  </thead>';

                if (get_bloginfo('version') >= '2.7') {
                    $manage_page = 'tools.php';
                } else {
                    $manage_page = 'edit.php';
                }

                if ( $alloptions['feeds'] || $newfeed ) {
                    $i = 0;

                    foreach ($alloptions['feeds'] as $key => $val) {
                        if ( $i % 2 == 0 ) {
                            print '<tr class="alternate">';
                        } else {
                            print '<tr>';
                        }
                        if ( isset($edit_id) && $edit_id == $key ) {
                            print "<form name=\"nevistas_news_options\" action=\"".
                                  htmlspecialchars($_SERVER['REQUEST_URI']).
                                  "\" method=\"post\" id=\"nevistas_news_options\">";
                                    
                            print "<th scope=\"row\">".$key."</th>";
                            print '<td><input size="10" maxlength="20" id="nevistas_news-name" name="nevistas_news-name" type="text" value="'.$val['name'].'" /></td>';
                            print '<td><input size="20" maxlength="20" id="nevistas_news-title" name="nevistas_news-title" type="text" value="'.$val['title'].'" /></td>';
                            print '<td><select name="nevistas_news-feedurl">';
                            $feedurl = $val['feedurl'];
                            foreach ($this->nevistasfeeds as $k => $v) {
                                print '<option '.(strcmp($v,$feedurl)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                            }
                            print '</select></td>';
                            print '<td><select name="nevistas_news-desctype">';
                            $desctype = $val['desctype'];
                            foreach ($this->desctypes as $k => $v) {
                                print '<option '.(strcmp($v,$desctype)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                            }
                            print '</select></td>';
                            print '<td><input size="3" maxlength="3" id="nevistas_news-numnews" name="nevistas_news-numnews" type="text" value="'.$val['numnews'].'" /></td>';
                            print '<td><input type="submit" value="Save  &raquo;">';
                            print "</td>";
                            print "<input type=\"hidden\" id=\"nevistas_news-id\" name=\"nevistas_news-id\" value=\"$edit_id\" />";
                            print "<input type=\"hidden\" id=\"nevistas_news-submit\" name=\"nevistas_news-submit\" value=\"1\" />";
                            print "</form>";
                        } else {
                            print "<th scope=\"row\">".$key."</th>";
                            print "<td>".$val['name']."</td>";
                            print "<td>".$val['title']."</td>";
                            print "<td>".$flipnevistasfeeds[$val['feedurl']]."</td>";
                            print "<td>".$flipdesctypes[$val['desctype']]."</td>";
                            print "<td>".$val['numnews']."</td>";
                            print "<td><a href=\"$manage_page?page=nevistas-news/nevistas_news.php&amp;mode=edit&amp;id=$key\" class=\"edit\">";
                            print __('Edit','nevistas_news');
                            print "</a></td>\n";
                            print "<td><a href=\"$manage_page?page=nevistas-news/nevistas_news.php&amp;mode=delete&amp;id=$key\" class=\"delete\" onclick=\"javascript:check=confirm( '".__("This feed entry will be erased. Delete?",'nevistas_news')."');if(check==false) return false;\">";
                            print __('Delete', 'nevistas_news');
                            print "</a></td>\n";
                        }
                        print '</tr>';

                        $i++;
                    }
                    if ( $newfeed ) {

                        print "<form name=\"nevistas_news_options\" action=\"".
                              htmlspecialchars($_SERVER['REQUEST_URI']).
                              "\" method=\"post\" id=\"nevistas_news_options\">";
                                
                        print "<th scope=\"row\">".$newfeed."</th>";
                        print '<td><input size="10" maxlength="20" id="nevistas_news-name" name="nevistas_news-name" type="text" value="" /></td>';
                        print '<td><input size="20" maxlength="20" id="nevistas_news-title" name="nevistas_news-title" type="text" value="" /></td>';
                        print '<td><select name="nevistas_news-feedurl">';
                        $feedurl = 'xml.php';
                        foreach ($this->nevistasfeeds as $k => $v) {
                            print '<option '.(strcmp($v,$feedurl)?'':'selected').' value="'.$v.'" >'.$k.'</option>';
                        }
                        print '</select></td>';
                        print '</select></td>';
                        print '<td><select name="nevistas_news-desctype">';
                        foreach ($this->desctypes as $k => $v) {
                            print '<option value="'.$v.'" >'.$k.'</option>';
                        }
                        print '</select></td>';
                        print '<td><input size="3" maxlength="3" id="nevistas_news-numnews" name="nevistas_news-numnews" type="text" value="5" /></td>';
                        print '<td><input type="submit" value="Save  &raquo;">';
                        print "</td>";
                        print "<input type=\"hidden\" id=\"nevistas_news-id\" name=\"nevistas_news-id\" value=\"$newfeed\" />";
                        print "<input type=\"hidden\" id=\"nevistas_news-newfeed\" name=\"nevistas_news-newfeed\" value=\"1\" />";
                        print "<input type=\"hidden\" id=\"nevistas_news-submit\" name=\"nevistas_news-submit\" value=\"1\" />";
                        print "</form>";
                    } else {
                        print "</tr><tr><td colspan=\"12\"><a href=\"$manage_page?page=nevistas-news/nevistas_news.php&amp;mode=newfeed\" class=\"newfeed\">";
                        print __('Add extra feed','nevistas_news');
                        print "</a></td></tr>";

                    }
                } else {
                    print '<tr><td colspan="12" align="center"><b>';
                    print __('No feeds found(!)','nevistas_news');
                    print '</b></td></tr>';
                    print "</tr><tr><td colspan=\"12\"><a href=\"$manage_page?page=nevistas-news/nevistas_news.php&amp;mode=newfeed\" class=\"newfeed\">";
                    print __('Add feed','nevistas_news');
                    print "</a></td></tr>";
                }
                print ' </table>';
                print '<h2>';
                print _e('Global configuration parameters','nevistas_news');
                print '</h2>';
                print ' <form method="post">';
                print ' <table id="the-cachetime" cellspacing="3" cellpadding="3">';
                print '<tr><td><b>Cache time:</b></td>';
                print '<td><input size="6" maxlength="6" id="nevistas_news-options-cachetime" name="nevistas_news-options-cachetime" type="text" value="'.$alloptions['cachetime'].'" /> seconds</td>';
                print '<input type="hidden" id="nevistas_news-options-cachetime-submit" name="nevistas_news-options-cachetime-submit" value="1" />';
                print '<td><input type="submit" value="Save  &raquo;"></td></tr>';
                print ' </table>';
                print '</form>'; 

                print '<h2>';
                print _e('Information','nevistas_news');
                print '</h2>';
                print ' <table id="the-list-x" width="100%" cellspacing="3" cellpadding="3">';
                print '<tr><td valign=top><b>Default Feed</b></td><td valign=top>In order for the plugin to call content you need to
first setup feeds in the Settings -> Nevistas News administration
area. (<a href=http://www.nevistas.com/wordpress/screenshot_1.png>see screenshot 1</a> and <a href=http://www.nevistas.com/wordpress/screenshot_2.png>see screenshot 2</a>)<br /><br />
Leave the Key field blank, and select your default feed from the 
Feed pulldown menu and click save.<br /><br /></td></tr>';
                print '<tr><td valign=top><b>Key</b></td><td valign=top>Unique identifier used internally.<br /><br /></td></tr>';
                print '<tr><td valign=top><b>Name</b></td><td valign=top>Optional name to be able to reference a specific feed as e.g. ';
                print ' <b>&lt;!--nevistas_news#myname--&gt;</b>. ';
                print ' If more than one feed shares the same name, a random among these will be picked each time. ';
                print ' The one(s) without a name will be treated as the default feed(s), i.e. used for <b>&lt;!--nevistas_news--&gt;</b> ';
                print ' or widget feed type <b>*DEFAULT*</b>. If you have Wordpress 2.5 ';
                print ' or above, you can also use Shortcodes on the form <b>[nevistas-news]</b> ';
                print ' (for default feed) or <b>[nevistas-news name="feedname"]</b>. And finally ';
                print ' you can use <b>do_action(\'nevistas_news\');</b> or <b>do_action(\'nevistas_news\', \'feedname\');</b> ';
                print ' in themes.<br /><br /></td></tr>';
                print '<tr><td valign=top><b>Admin-defined title</b></td><td valign=top>Optional feed title. If not set, a reasonable title based on ';
                print 'source will be used.<br /><br />';
                print '</td></tr>';
                print '<tr><td valign=top><b>Feed</b></td><td valign=top>The actual feed to use.<br /><br /></td></tr>';
                print '<tr><td valign=top><b>Max items</b></td><td valign=topMaximum number of news items to show for this feed. If the feed contains ';
                print 'less than the requested items, only the number of items in the feed will obviously be displayed.<br /><br /></td></tr>';
                print '<tr><td valign=top><b>Cache time</b></td><td valign=top>Minimum number of seconds that WordPress should cache a Nevistas News feed before fetching it again.<br /><br /></td></tr>';
                print ' </table>';
                print '</div>';
            }
        }

        // ************* Output *****************

        // The function that gets called from themes
        function display_feed($data) {
            global $settings;
            $settings   = get_option('nevistas_news');
            print $this->random_feed($data);
            unset($settings);
        }

        // Callback for inline replacement
        function insert_news($data) {
            global $settings;

            // Allow for multi-feed sites
            $tag = '/<!--nevistas-news(|#.*?)-->/';

            // We may have old style options
            $this->upgrade_options();

            // Avoid getting this for each callback
            $settings   = get_option('nevistas_news');

            $result = preg_replace_callback($tag, 
                              array(&$this, 'inline_replace_callback'), $data);

            unset($settings);

            return $result;
        }


        // *********** Widget support **************
        function widget_init() {

            // Check for the required plugin functions. This will prevent fatal
            // errors occurring when you deactivate the dynamic-sidebar plugin.
            if ( !function_exists('register_sidebar_widget') )
                return;

            register_widget_control('Nevistas News', 
                                   array(&$this, 'widget_control'), 200, 100);

            // wp_* has more features, presumably fixed at a later date
            register_sidebar_widget('Nevistas News',
                                   array(&$this, 'widget_output'));

        }

        function widget_control() {

            // We may have old style options
            $this->upgrade_options();

            $alloptions = get_option('nevistas_news');
            $thisfeed = $alloptions['widget-1'];

            print '<p><label for="nevistas_news-feed">Select feed:</label>';
            print '<select style="vertical-align:middle;" name="nevistas_news-widget-feed">';

            $allfeeds = array();
            foreach ($alloptions['feeds'] as $k => $v) {
                $allfeeds[strlen($v['name'])?$v['name']:'*DEFAULT*'] = 1;
            } 
            foreach ($allfeeds as $k => $v) {
                print '<option '.($k==$thisfeed?'':'selected').' value="'.$k.'" >'.$k.'</option>';
            }
            print '</select><p>';
            print '<input type="hidden" id="nevistas_news-widget-submit" name="nevistas_news-widget-submit" value="1" />';


        }

        // Called every time we want to display ourselves as a sidebar widget
        function widget_output($args) {
            extract($args); // Gives us $before_ and $after_ I presume
                        
            // We may have old style options
            $this->upgrade_options();

            $alloptions = get_option('nevistas_news');
            $matching_feeds = array();
            foreach ($alloptions['feeds'] as $k => $v) {
                if ( (string)$v['name'] == $alloptions['widget-1'] ) { 
                    $matching_feeds[] = $k;
                } 
            }
            if ( ! count($matching_feeds) ) {
                if ( ! strlen($alloptions['widget-1']) ) {
                    $content = '<ul><b>No default feed available</b></ul>';
                } else {
                    $content = "<ul>Unknown feed name <b>{$alloptions[widget-1]}</b> used</ul>";
                }
                echo $before_widget;
                echo $before_title . __('Nevistas News<br>Error','nevistas_news') . $after_title . '<div>';
                echo $content;
                echo '</div>' . $after_widget;
                return;
            }
            $feed_id = $matching_feeds[rand(0, count($matching_feeds)-1)];
            $options = $alloptions['feeds'][$feed_id];

            $feedtype   = $options['feedtype'];
            $cachetime  = $alloptions['cachetime'];

            if ( strlen($options['title']) ) {
                $title = $options['title'];
            } else {
                $title = 'Nevistas News<br>'.$feedtype;
            }

            echo $before_widget;
            echo $before_title . $title . $after_title . '<div>';
            echo $this->get_feed($options, $cachetime);
            echo '</div>' . $after_widget;
        }

        // ************** The actual work ****************
        function get_feed(&$options, $cachetime) {

            if ( ! isset($options['feedurl']) ) {
                return 'Options not set, visit plugin configuation screen.'; 
            }

            $feedurl    = $options['feedurl'] ? $options['feedurl'] : 'xml.php';
            $numnews    = $options['numnews'] ? $options['numnews'] : 5;
            $desctype   = $options['desctype'];

            $result = '<ul>';

            $rssurl = 'http://www.nevistas.com/'.$feedurl;

            // Using the WP RSS fetcher (MagpieRSS). It has serious
            // GC problems though.
            define('MAGPIE_CACHE_AGE', $cachetime);
            define('MAGPIE_CACHE_ON', 1);
            define('MAGPIE_DEBUG', 1);

            $rss = fetch_rss($rssurl);

            if ( ! is_object($rss) ) {
                return 'Nevistas News unavailable</ul>';
            }

            $rss->items = array_slice($rss->items, 0, $numnews);
            foreach ( $rss->items as $item ) {
                $description = $this->html_decode($item['description']);

                // Bunch of useless links after first <p> in desc 
                $bloc = strpos($description, '<p>');
                if ( $bloc ) {
                    $description = substr($description, 0, $bloc);
                }

                // No markup in tooltips
                $tooltip = preg_replace('/<[^>]+>/','',$description);
                $tooltip = preg_replace('/"/','\'',$tooltip);

                $title = $this->html_decode($item['title']);
                $date = $item['pubdate'];
                $link = $item['link'];
                if ( strlen($desctype) ) {
                    $result .= "<li><a href=\"$link\" target=\"_blank\">$title</a><br>$description</li>";
                } else {
                    $result .= "<li><a href=\"$link\" target=\"_blank\" ".
                               "title=\"$tooltip\">$title</a></li>";
                }
            } 
            return $result.'</ul>';
        }

        // *********** Shortcode support **************
        function my_shortcode_handler($atts, $content=null) {
            global $settings;
            $settings = get_option('nevistas_news');
            return $this->random_feed($atts['name']);
            unset($settings);
        }

        
        // *********** inline replacement callback support **************
        function inline_replace_callback($matches) {

            if ( ! strlen($matches[1]) ) { // Default
                $feedname = 'All News';
            } else {
                $feedname = substr($matches[1], 1); // Skip #
            }
            return $this->random_feed($feedname);
        }

        // ************** Support functions ****************

        function random_feed($name) {
            global $settings;

            $matching_feeds = array();
            foreach ($settings['feeds'] as $k => $v) {
                if ( (string)$v['name'] == $name ) { 
                    $matching_feeds[] = $k;
                } 
            }
            if ( ! count($matching_feeds) ) {
                if ( ! strlen($name) ) {
                    return '<ul><b>No default feed available</b></ul>';
                } else {
                    return "<ul>Unknown feed name <b>$name</b> used</ul>";
                }
            }
            $feed_id = $matching_feeds[rand(0, count($matching_feeds)-1)];
            $feed = $settings['feeds'][$feed_id];

            if ( strlen($feed['title']) ) {
                $title = $feed['title'];
            } else {
                $title = 'Nevistas News : '.$feed['feedtype'];
            }

            $result = '<!-- Start Nevistas News code -->';
            $result .= "<div id=\"nevistas-news-inline\"><h3>$title</h4>";
            $result .= $this->get_feed($feed, $settings['cachetime']);
            $result .= '</div><!-- End Nevistas News code -->';
            return $result;
        }

        function html_decode($in) {
            $patterns = array(
                '/&amp;/',
                '/&quot;/',
                '/&lt;/',
                '/&gt;/',
            );
            $replacements = array(
                '&',
                '"',
                '<',
                '>',
            );
            $tmp = preg_replace($patterns, $replacements, $in);
            return preg_replace('/&#39;/','\'',$tmp);

        }

        // Unfortunately, we didn't finalize on a data structure
        // until version 2.1ish of the plugin so we need to upgrade
        // if needed
        function upgrade_options() {
            $options = get_option('nevistas_news');

            if ( !is_array($options) ) {

                // a:6:{s:5:"title";s:0:"";s:8:"feedname";s:13:"Nevistas.com: U.S.";s:7:"feedurl";s:33:"http://www.nevistas.com/xml.php";s:7:"numnews";s:1:"5";s:11:"usefeedname";b:1;s:8:"getfeeds";b:0;}

                // From 1.0
                $oldoptions = get_option('widget_nevistas_news_widget');
                if ( is_array($oldoptions) ) {

                    $flipnevistasfeeds     = array_flip($this->nevistasfeeds);

                    $tmpfeed = array();
                    $tmpfeed['title']      = $oldoptions['title'];
                    $tmpfeed['name']       = '';
                    $tmpfeed['numnews']    = $oldoptions['numnews'];
                    $tmpfeed['feedurl']    = $oldoptions['feedurl'];
                    $tmpfeed['feedtype']   = $flipnevistasfeeds[substr($tmpfeed['feedurl'], 23)];

                    $options = array();
                    $options['feeds']     = array( $tmpfeed );
                    $options['widget-1']  = 0;
                    $options['cachetime'] = 300;
                    
                    delete_option('widget_nevistas_news_widget');
                    update_option('nevistas_news', $options);
                } else {
                    // First time ever
                    $options = array();
                    $options['feeds']     = array( $this->default_feed() );
                    $options['widget-1']  = 0;
                    $options['cachetime'] = 300;
                    update_option('nevistas_news', $options);
                }
            }
        }

        function default_feed() {
            return array( 'numnews' => 5,'feedurl' => 'xml.php','name' => '','feedtype' => 'All News');
        }
    }

    // Instantiate
    $nevistas_news_instance &= new nevistas_news_plugin();

}
?>
