<?php
/**
 * Plugin Name
 *
 * @package     aklamator-float-video-on-your-blog
 * @author      Aklamator
 * @copyright   2017 Aklamator.com
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Aklamator - Float Video on your blog
 * Plugin URI:  https://www.aklamator.com/wordpress
 * Description: Float Video widget will help you promote your YouTube video, channel or playlist (with e.g. new campaign). Additionally Aklamator service enables you to add your media branding and choose destination URL.
 * Version:     2.0.2
 * Author:      Aklamator
 * Author URI:  https://www.aklamator.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
Copyright 2017 Aklamator.com (email : info@aklamator.com)

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

/*
 * Add setting link on plugin page
 */

if( !function_exists("aklamatorFV_plugin_settings_link")){
    // Add settings link on plugin page
    function aklamatorFV_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=aklamator-float-video-on-your-blog">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), 'aklamatorFV_plugin_settings_link' );

/*
 * Add rate and review link in plugin section
 */
if( !function_exists("aklamatorFV_plugin_meta_links")) {
    function aklamatorFV_plugin_meta_links($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin) {
            return array_merge(
                $links,
                array('<a href="https://wordpress.org/support/plugin/aklamator-float-video-on-your-blog/reviews" target=_blank>Please rate and review</a>')
            );
        }
        return $links;
    }
}
add_filter( 'plugin_row_meta', 'aklamatorFV_plugin_meta_links', 10, 2);

/*
 * Activation Hook
 */

register_activation_hook( __FILE__, 'set_up_options_aklamator_FV' );

function set_up_options_aklamatorFV(){
    add_option('aklamatorFVChannelURL', '');
    add_option('aklamatorFVApplicationID', '');
    add_option('aklamatorFVPoweredBy', '');
    add_option('aklamatorFVSingleWidgetID', '');
    add_option('aklamatorFVPageWidgetID', '');
    add_option('aklamatorFVSingleWidgetTitle', '');
    add_option('aklamatorFVShowOrDontShow');
    add_option('aklamatorFVPhotoURL'); 
    add_option('aklamatorFVintroURL'); 
}

/*
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'aklamatorFV_uninstall');

function aklamatorFV_uninstall()
{
    delete_option('aklamatorFVChannelURL');
    delete_option('aklamatorFVApplicationID');
    delete_option('aklamatorFVPoweredBy');
    delete_option('aklamatorFVSingleWidgetID');
    delete_option('aklamatorFVPageWidgetID');
    delete_option('aklamatorFVSingleWidgetTitle');
    delete_option('aklamatorFVShowOrDontShow');
    delete_option('aklamatorFVPhotoURL'); 
    delete_option('aklamatorFVintroURL'); 

}




new AklamatorFloatVideoWidget();

class AklamatorFloatVideoWidget 
{

    public $aklamator_url;
    public $api_data;


    public $popular_channels = array(
        array(
            'name' => 'YouTube Spotlight',
            'url' => 'https://www.youtube.com/user/youtube'
        ),
        array(
            'name' => 'PewDiePie',
            'url' => 'https://www.youtube.com/user/PewDiePie/'
        ),
        array(
            'name' => 'EmiMusic',
            'url' => 'https://www.youtube.com/user/emimusic'
        ),
        array(
            'name' => 'FunToyzCollector',
            'url' => 'https://www.youtube.com/user/disneycollectorbr'
        )

    );


    public function __construct()
    {
//CHANGE
        $this->aklamator_url = "https://aklamator.com/";
//        $this->aklamator_url = "http://127.0.0.1/aklamator/www/";



        if (is_admin()) {
            add_action("admin_menu", array(
                &$this,
                "adminMenu"
            ));


            add_action('admin_init', array(
                &$this,
                "setOptions"
            ));

            if (isset($_GET['page']) && $_GET['page'] == 'aklamator-float-video-on-your-blog' ) {
                if (get_option('aklamatorFVApplicationID') !== '') {
                    $this->api_data = $this->addNewWebsiteApi_float();
                }
            }
        }



        if (get_option('aklamatorFVSingleWidgetID') == '') {
            if (isset($this->api_data->data[0])) {
                update_option('aklamatorFVSingleWidgetID', $this->api_data->data[0]->uniq_name);
            }

        }

        add_action('wp_footer', array($this, 'bottom_of_every_post_FV'));

//        }
    }

    function setOptions()
    {


        register_setting('aklamatorFV-options', 'aklamatorFVApplicationID');
        register_setting('aklamatorFV-options', 'aklamatorFVPoweredBy');
        register_setting('aklamatorFV-options', 'aklamatorFVSingleWidgetID');
        register_setting('aklamatorFV-options', 'aklamatorFVPageWidgetID');
        register_setting('aklamatorFV-options', 'aklamatorFVSingleWidgetTitle');
        register_setting('aklamatorFV-options', 'aklamatorFVShowOrDontShow');
        register_setting('aklamatorFV-options', 'aklamatorFVPhotoURL');
        register_setting('aklamatorFV-options', 'aklamatorFVintroURL');
        register_setting('aklamatorFV-options', 'aklamatorFVChannelURL');


    }

    public function adminMenu()
    {
        add_menu_page('Aklamator - Float Video on your blog', 'Aklamator FloatV', 'manage_options', 'aklamator-float-video-on-your-blog', array(
            $this,
            'createAdminPage'
        ), content_url() . '/plugins/aklamator-float-video-on-your-blog/images/aklamator-icon.png');

    }

    public function getSignupUrl()
    {
        $user_info =  wp_get_current_user();

        return $this->aklamator_url . 'login/application_id?utm_source=wordpress&utm_medium=wpfloat&e=' . urlencode(get_option('admin_email')) .
        '&pub=' .  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']).
        '&un=' . urlencode($user_info->user_login). '&fn=' . urlencode($user_info->user_firstname) . '&ln=' . urlencode($user_info->user_lastname) .
        '&pl=float_video&return_uri=' . admin_url("admin.php?page=aklamator-float-video-on-your-blog");

    }

    private function addNewWebsiteApi_float()
    {

        if (!is_callable('curl_init')) {
            return;
        }



        $service     = $this->aklamator_url."wp-authenticate/videofloat";

        $p['ip']     = $_SERVER['REMOTE_ADDR'];
        $p['domain'] = site_url();
        $p['source'] = "wordpress";

        $p['AklamatorApplicationID'] = get_option('aklamatorFVApplicationID');
        $p['AklamatorFVChannelURL'] = get_option('aklamatorFVChannelURL');
        $p['AklamatorFVPhotoURL'] = get_option('aklamatorFVPhotoURL');
        $p['aklamatorFVintroURL'] = get_option('aklamatorFVintroURL');


        $data = wp_remote_post( $service, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $p,
                'cookies' => array()
            )
        );

        $ret_info = new stdClass();
        if(is_wp_error($data))
        {
            $this->curlfailovao=1;
        }
        else
        {
            $this->curlfailovao=0;
            $ret_info = json_decode($data['body']);
        }

        return $ret_info;

    }


    function bottom_of_every_post_FV(){

        $widget_id = get_option('aklamatorFVSingleWidgetID');
        $return_content = "";
        if (strlen($widget_id) >= 7) {
            $return_content .= ' <!-- created 2014-11-25 16:22:10 -->';
            $return_content .= '<div id="akla' . $widget_id . '"></div>';
            $return_content .= '<script async>(function(d, s, id) {';
            $return_content .= 'var js, fjs = d.getElementsByTagName(s)[0];';
            $return_content .= 'if (d.getElementById(id)) return;';
            $return_content .= 'js = d.createElement(s); js.id = id;';
            $return_content .= 'js.src = "'.$this->aklamator_url.'widget2/videofloat/' . $widget_id . '";';
            $return_content .= 'fjs.parentNode.insertBefore(js, fjs);';
            $return_content .= '}(document, \'script\', \'aklamator-' . $widget_id . '\'));</script>';
            $return_content .= '<!-- end --><br>';
        }
        echo $return_content;
    }

    public function createAdminPage()
    {
        $code = get_option('aklamatorFVApplicationID');
        $channel_url = get_option('aklamatorFVChannelURL');
        $photo_url = get_option('aklamatorFVPhotoURL');
        $intro_url = get_option('aklamatorFVintroURL');

        ?>
        <style>
            #adminmenuback{ z-index: 0}
            #aklamatorFV-options ul { margin-left: 10px; }
            #aklamatorFV-options ul li { margin-left: 15px; list-style-type: disc;}
            #aklamatorFV-options h1 {margin-top: 5px; margin-bottom:10px; color: #00557f}
            .fz-span { margin-left: 23px;}


            .aklamator_button {
                vertical-align: top;
                width: auto;
                height: 30px;
                line-height: 30px;
                padding: 10px;
                font-size: 20px;
                color: white;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                background: #c0392b;
                border-radius: 5px;
                border-bottom: 2px solid #b53224;
                cursor: pointer;
                -webkit-box-shadow: inset 0 -2px #b53224;
                box-shadow: inset 0 -2px #b53224;
                text-decoration: none;
                margin-top: 3px;
                margin-bottom: 10px;
            }

            .aklamator-login-button {
                float: left;
            }


            .aklamator-login-button:hover {
                cursor: pointer;
                color: lightskyblue;
            }

            h3 {
                margin-bottom: 3px;
            }
            p {
                margin-top: 3px;
            }

            .alert_red{
                margin-bottom: 18px;
                margin-top: 10px;
                color: #c09853;
                text-shadow: 0 1px 0 rgba(255,255,255,0.5);
                background-color: #fcf8e3;
                border: 1px solid #fbeed5;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                padding: 8px 35px 8px 14px;
            }
            .alert-msg_red {
                color: #8f0100;
                background-color: #f6cbd2;
                border-color: #f68d89;
            }

            .btn { font-size: 13px; border-radius: 5px; background: transparent; text-transform: uppercase; font-weight: 700; padding: 4px 10px; min-width: 162px; max-width: 100%; text-decoration: none;}

            .btn-primary { background: #7BB32C; border:1px solid #fff; color: #fff; text-decoration: none}
            .btn-primary:hover, .btn-primary.hovered { background: #7BB32C;  border:1px solid #167AC6; opacity:0.9; color: #fff }
            .btn-primary:Active, .btn-primary.pressed { background: #7BB32C; border:1px solid #167AC6; color: #fff}

            .box{float: left; margin-left: 10px; width: 500px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}
            .right_sidebar{float: right; margin-left: 10px; width: 300px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}

            .alert{
                margin-bottom: 18px;
                color: #c09853;
                text-shadow: 0 1px 0 rgba(255,255,255,0.5);
                background-color: #fcf8e3;
                border: 1px solid #fbeed5;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                padding: 8px 35px 8px 14px;
            }
            .alert-msg {
                color: #3a87ad;
                background-color: #d9edf7;
                border-color: #bce8f1;
            }

            .aklamator_INlogin {
                padding: 10px;
                background-color: #000058;
                color: white;
                text-decoration: none;
                font-size: 15px;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                border-radius: 5px;
                cursor: pointer;
                -webkit-box-shadow:0 0 4px #909090;
                box-shadow:0 0 4px #909090;
            }

            .aklamator_INlogin:hover {
                color: lightskyblue;
            }


        </style>
        <!-- Load css libraries -->

        <link href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">

        <div id="aklamatorFV-options" style="width:1160px;margin-top:10px;">

            <div class="left" style="float: left;">
                <div style="float: left; width: 300px;">

                    <a target="_blank" href="<?php echo $this->aklamator_url; ?>?utm_source=wp-plugin">
                        <img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/logo.png', __FILE__);?>" /></a>
                    <?php
                    if ($code != '') : ?>
                        <a target="_blank" href="<?php echo $this->aklamator_url; ?>dashboard?utm_source=wp-plugin">
                            <img style="border:0px;margin-top:5px;border-radius:5px;" src="<?php echo plugins_url('images/dashboard.jpg', __FILE__); ?>" /></a>

                    <?php endif; ?>

                    <a target="_blank" href="<?php echo $this->aklamator_url;?>/contact?utm_source=wp-plugin-contact">
                        <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/support.jpg', __FILE__); ?>" /></a>

                    <a target="_blank" href="http://qr.rs/q/4649f">
                        <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/promo-300x200.png', __FILE__); ?>" /></a>

                </div>
                <div class="box">

                    <h1 style="margin-bottom: 40px">Aklamator Float Video</h1>

                    <form method="post" action="options.php">
                        <?php
                        settings_fields('aklamatorFV-options');

                        if ($intro_url == '') : ?>
                            <h3>Step 1: Paste your Youtube intro video URL</h3>
                        <?php else :?>
                            <h3>Your Youtube intro video URL</h3>
                        <?php endif;?>
                        <p>
                            <input type="text" style="width: 400px" name="aklamatorFVintroURL" id="aklamatorFVintroURL" value="<?php
                            echo $intro_url; ?>" maxlength="999" />
                        </p>

                        <?php
                        if ($channel_url == '') : ?>
                            <h3>Step 2: Paste your Youtube playlist or channel URL</h3>
                        <?php else :?>
                            <h3>Your Youtube playlist or channel URL</h3>
                        <?php endif;?>
                        <p>
                            <input type="text" style="width: 400px" name="aklamatorFVChannelURL" id="aklamatorFVChannelURL" value="<?php
                            echo $channel_url; ?>" maxlength="999" />

                        </p>
                        <p>
                            <select id="aklamatorFVPopular" name="aklamatorFVPopular">
                                <?php
                                foreach ( $this->popular_channels as $item ): ?>
                                    <option <?php echo (get_option('aklamatorFVChannelURL') == $item['url'])? 'selected="selected"' : '' ;?> value="<?php echo $item['url']; ?>"><?php echo $item['name']; ?></option>
                                <?php endforeach; ?>
                            </select> or choose from popular channel

                        </p>

                        <?php
                        if ($photo_url == '') : ?>
                            <h3>Step 3: Paste Your photo (logo) URL</h3>
                        <?php else :?>
                            <h3>Your photo (logo) URL</h3>
                        <?php endif;?>
                        <p>
                            <input type="text" style="width: 400px" name="aklamatorFVPhotoURL" id="aklamatorFVPhotoURL" value="<?php
                            echo $photo_url; ?>" maxlength="999" /><br>
                            *square 171x171px or <a href="https://www.aklamator.com/floatvideo/add_new" target='_blank' title='Click & Login to change'>use dashboard</a> to upload and crop <br>
                            *optional, if you leave blank default image will be shown
                        </p>

                        <?php

                        if (isset($this->api_data->error) || $code == '') : ?>
                            <h3 style="float: left">Step 4: Get your Aklamator Aplication ID</h3>
                            <a class='aklamator_button aklamator-login-button' id="aklamator_login_button" >Click here for FREE registration/login</a>
                            <div style="clear: both"></div>
                            <p>Or you can manually <a href="<?php echo $this->aklamator_url . 'registration/publisher'; ?>" target="_blank">register</a> or <a href="<?php echo $this->aklamator_url . 'login'; ?>" target="_blank">login</a> and copy paste your Application ID</p>
                            <script>var signup_url = '<?php echo $this->getSignupUrl(); ?>';</script>
                        <?php endif; ?>

                        <div style="clear: both"></div>
                        <?php if ($code == '') { ?>
                            <h3>Step 5: &nbsp;&nbsp;&nbsp;&nbsp; Paste your Aklamator Application ID</h3>
                        <?php }else{ ?>
                            <h3>Your Aklamator Application ID</h3>
                        <?php } ?>

                        <p>
                            <input type="text" style="width: 400px" name="aklamatorFVApplicationID" id="aklamatorFVApplicationID" value="<?php
                            echo (get_option("aklamatorFVApplicationID"));
                            ?>" maxlength="50" onchange="appIDChange(this.value)"/>

                        </p>
                        <p>
                            <input type="checkbox" id="aklamatorFVPoweredBy" name="aklamatorFVPoweredBy" <?php echo (get_option("aklamatorFVPoweredBy") == true ? 'checked="checked"' : ''); ?> Required="Required">
                            <strong>Required</strong> I acknowledge there is a 'powered by aklamator' link on the widget. <br />
                        </p>

                        <p>
                        <div class="alert alert-msg">
                            <strong>Note </strong><span style="color: red">*</span>: By default, Float Video will automatically parse and show your channel or playlist from youtube channel.
                        </div>
                        </p>

                        <?php if(isset($this->api_data->flag) && $this->api_data->flag === false): ?>
                            <p id="aklamator_error" class="alert_red alert-msg_red"><span style="color:red"><?php echo $this->api_data->error; ?></span></p>
                        <?php endif; ?>

                        <?php if(get_option('aklamatorFVApplicationID') !=="" && $this->api_data->flag == true): ?>

                            <p>
                            <h1>Options</h1>
                            <h4>Select widget to be shown as Float Video:</h4>

                            <?php

                            $widgets = $this->api_data->data;
                            /* Add new item to the end of array */
                            $item_add = new stdClass();
                            $item_add->uniq_name = 'none';
                            $item_add->title = 'Do not show';
                            $widgets[] = $item_add;
                            ?>

                            <label for="aklamatorFVSingleWidgetID">Choose widget: </label>
                            <select id="aklamatorFVSingleWidgetID" name="aklamatorFVSingleWidgetID">
                                <?php
                                foreach ( $widgets as $item ): ?>
                                    <option <?php echo (get_option('aklamatorFVSingleWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input style="margin-left: 5px;" type="button" id="preview_single" class="button primary big submit" onclick="myFunction($('#aklamatorFVSingleWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorFVSingleWidgetID')=="none"? "disabled" :"" ;?>>
                            </p>


                        <?php endif; ?>

                        <input name="aklamatorFV_save" id="aklamatorFV_save" class="aklamator_INlogin" style ="margin: 0; border: 0; float: left;" type="submit" value="<?php echo (_e("Save Changes")); ?>" />
                        <?php if(!isset($this->api_data->flag) || !$this->api_data->flag): ?>
                            <div style="float: left; padding: 7px 0 0 10px; color: red; font-weight: bold; font-size: 16px"> <-- In order to proceed save changes</div>
                        <?php endif ?>

                    </form>
                </div>


                <div style="clear:both"></div>
                <div style="margin-top: 20px; margin-left: 0px; width: 810px;" class="box">

<!--                    --><?php //if (get_option('aklamatorFVApplicationID') != ''): ?>
<!--                        <h2 style="color:red">Error communicating with Aklamator server, please refresh plugin page or try again later. </h2>-->
<!--                    --><?php //endif;?>
                    <?php if(!isset($this->api_data->flag) || !$this->api_data->flag): ?>
                        <a href="<?php echo $this->getSignupUrl(); ?>" target="_blank"><img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/teaser-810x262.png', __FILE__);?>" /></a>
                    <?php else : ?>
                    <!-- Start of dataTables -->
                    <div id="aklamatorFVPro-options">
                        <h1>Your Widgets</h1>
                        <div>In order to add new widgets or to select target devices, position of widget, target visitors from campaigns, include or exclude specific pages, please <a href="<?php echo $this->aklamator_url; ?>login" target="_blank">login to aklamator</a></div>
                    </div>
                    <br>
                    <table cellpadding="0" cellspacing="0" border="0"
                           class="responsive dynamicTable display table table-bordered" width="100%">
                        <thead>
                        <tr>

                            <th>Name</th>
                            <th>Domain</th>
                            <th>Settings</th>
                            <th>Image size</th>
                            <th>Column/row</th>
                            <th>Created At</th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->api_data->data as $item): ?>

                            <tr class="odd">
                                <td style="vertical-align: middle;" ><?php echo $item->title; ?></td>
                                <td style="vertical-align: middle;" >
                                    <?php foreach($item->domain_ids as $domain): ?>
                                        <a href="<?php echo $domain->url; ?>" target="_blank"><?php echo $domain->title; ?></a><br/>
                                    <?php endforeach; ?>
                                </td>
                                <td style="vertical-align: middle"><div style="float: left; margin-right: 10px" class="button-group">
                                        <input type="button" class="button primary big submit" onclick="myFunction('<?php echo $item->uniq_name; ?>')" value="Preview Widget">
                                </td>
                                <td style="vertical-align: middle;" ><?php echo "<a href = \"$this->aklamator_url"."floatvideo/add_new/$item->id\" target='_blank' title='Click & Login to change'>$item->img_size px</a>";  ?></td>
                                <td style="vertical-align: middle;" >
                                    <?php echo "<a href = \"$this->aklamator_url"."floatvideo/add_new/$item->id\" target='_blank' title='Click & Login to change'>".$item->column_number ." x ". $item->row_number."</a>"; ?>
                                    <div style="float: right;">
                                        <?php echo "<a class=\"btn btn-primary\" href = \"$this->aklamator_url"."floatvideo/add_new/$item->id\" target='_blank' title='Edit widget settings'>Edit</a>"; ?>
                                    </div>
                                </td>
                                <td style="vertical-align: middle;" ><?php echo $item->date_created; ?></td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Name</th>
                            <th>Domain</th>
                            <th>Settings</th>
                            <th>Image size</th>
                            <th>Column/row</th>
                            <th>Created At</th>
                        </tr>
                        </tfoot>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right" style="float: right;">
                <!-- Right sidebar -->
                <div class="right_sidebar">
                    <iframe id="akla_right_sidebar" width="330" height="1024" src="<?php echo $this->aklamator_url; ?>wp-sidebar/right?plugin=float-video" frameborder="0"></iframe>
                </div>
                <!-- End Right sidebar -->
            </div>
        </div>



        <!-- load js scripts -->

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo content_url(); ?>/plugins/aklamator-float-video-on-your-blog/assets/dataTables/jquery.dataTables.min.js"></script>


        <script type="text/javascript">

            function appIDChange(val) {

                $('#aklamatorFVSingleWidgetID option:first-child').val('');
                $('#aklamatorFVPageWidgetID option:first-child').val('');

            }

            function myFunction(widget_id) {

                var aklaurlpreview = '<?php echo urlencode(site_url()); ?>';
                var myWindow = window.open('https://www.aklamator.com/show/videofloat?widget='+ widget_id+'&domain='+aklaurlpreview);
                myWindow.focus();
            }


            $(document).ready(function(){

                jQuery('#aklamatorFVApplicationID').on('input', function ()
                {
                    jQuery('#aklamator_error').css('display', 'none');
                });

                jQuery('#aklamator_login_button').click(function () {
                    var akla_login_window = window.open(signup_url,'_blank');
                    var aklamator_interval = setInterval(function() {
                        var aklamator_hash = akla_login_window.location.hash;
                        var aklamator_api_id = "";
                        if (akla_login_window.location.href.indexOf('aklamator_wordpress_api_id') !== -1) {

                            aklamator_api_id = aklamator_hash.substring(28);
                            jQuery("#aklamatorFVApplicationID").val(aklamator_api_id);
                            akla_login_window.close();
                            clearInterval(aklamator_interval);
                            jQuery('#aklamator_error').css('display', 'none');
                        }
                    }, 1000);

                });

                $("#aklamatorFVSingleWidgetID").change(function(){

                    if($(this).val() == 'none'){
                        $('#preview_single').attr('disabled', true);
                    }else{
                        $('#preview_single').removeAttr('disabled');
                    }

                    $("#aklamatorFVSingleWidgetID option").each(function () {

                        if (this.selected) {
                           $(this).attr('selected', true);

                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $("#aklamatorFVPageWidgetID").change(function(){

                    if($(this).val() == 'none'){

                        $('#preview_page').attr('disabled', true);
                    }else{
                        $('#preview_page').removeAttr('disabled');
                    }

                    $("#aklamatorFVPageWidgetID option").each(function () {

                        if (this.selected) {
                            $(this).attr('selected', true);
                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $("#aklamatorFVPopular").change(function(){


                    $(this).find("option").each(function () {
                        if (this.selected) {
                            $('#aklamatorFVChannelURL').val(this.value);
                            $(this).attr('selected', true);

                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });



                if ($('table').hasClass('dynamicTable')) {
                    $('.dynamicTable').dataTable({
                        "iDisplayLength": 10,
                        "sPaginationType": "full_numbers",
                        "bJQueryUI": false,
                        "bAutoWidth": false

                    });
                }

                $('#aklamatorFV_save').click(function (event) {
                    var aklaFVaplicationID = $('#aklamatorFVApplicationID');
                    var FV_url = $('#aklamatorFVChannelURL');
                    var FV_intro = $('#aklamatorFVintroURL');

                    if (FV_intro.val() == "")
                    {
                        alert('Youtube intro video URL can\'t be empty.');
                        FV_intro.focus();
                        event.preventDefault();
                    }
                    else if (FV_url.val().indexOf("youtu") == -1)
                    {
                        alert('Please make sure that you entered valid Intro Youtube URL');
                        FV_intro.focus();
                        event.preventDefault();
                    }
                    else if (FV_url.val() == ""){
                        alert('Youtube video, playlist or channel URL can\'t be empty');
                        FV_url.focus();
                        event.preventDefault();

                    }
                    else if(FV_intro.val().indexOf("youtu") == -1){
                        alert('Please make sure that you entered valid Youtube URL');
                        FV_url.focus();
                        event.preventDefault();
                    }
                    else if (aklaFVaplicationID.val() == "")
                    {

                        alert("Paste your Aklamator Application ID");
                        aklaFVaplicationID.focus();
                        event.preventDefault();
                    }
                })
            });
        </script>
    <?php
    }
}


