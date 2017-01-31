<?php
/*
Plugin Name: GFMailgun
Plugin URI: http://marcquarles.com/
Description: Uses Mailgun to send notification emails after a Gravity Form is submitted. Requires Gravity Forms 1.9.5 or greater.
Version: 1.2
Author: Marc Quarles
Author URI: http://marcquarles.com
License: Proprietary
*/

//error_reporting(E_ALL);
require 'vendor/autoload.php';
use Mailgun\Mailgun;
if (class_exists("GFForms")) {
    GFForms::include_addon_framework();
    class GFSimpleAddOn extends GFAddOn {
        protected $_version = "1.1";
        protected $_min_gravityforms_version = "1.9.5";
        protected $_slug = "gfmailgun";
        protected $_path = "gfmailgun/gfmailgun.php";
        protected $_full_path = __FILE__;
        protected $_title = "Gravity Forms to Mailgun";
        protected $_short_title = "GFMailgun";
        public function init(){
            parent::init();
            //add_filter("gform_submit_button", array($this, "form_submit_button"), 10, 2);
            add_action( 'gform_after_submission', array($this,'mailgun'), 10, 2 );
        }
        
        function htmlrow($label,$text,$rowcount){
            $bgcolor="#fff";
            if($rowcount % 2 == 0){
                $bgcolor="#e5e5e5";
            }
            return '<tr>
                        <td align="left" valign="top" bgcolor="'.$bgcolor.'" font style="font-family: arial;font-size:16px;">
                            '.$label.': '.$text.'
                        </td>
                    </tr>';
        }
        
        function mailgun ($entry, $form) {
            $settings=$this->get_plugin_settings();
            $formsettings=$this->get_form_settings($form);
            //send by mailgun
            //echo "<p>Settings:";
            //print_r($settings);
            //echo "<p>Form Settings:";
            //print_r($formsettings);
            //echo "<p>Entry:";
            //print_r($entry);
            //echo "<p>Form:";
            //print_r($form);
            if(isset($settings['mg_apikey']) && $settings['mg_apikey']!='' && isset($settings['mg_domain']) && $settings['mg_domain']!=''){
                if(isset($formsettings['fromemail']) && $formsettings['fromemail']!='' && isset($formsettings['toemail']) && $formsettings['toemail']!=''){
                    $htmlblock="";
                    $rowcount=0;
                    $mgClient = new Mailgun($settings['mg_apikey']);
                    $domain = $settings['mg_domain'];
                    $fields=array();
                    $body=(isset($formsettings['body'])?$formsettings['body']:'');
                    $replyto=$formsettings['fromemail'];
                    if(count($form['fields'])>0){
                        foreach ( $form['fields'] as $field ) {
                            if($field['adminLabel']!=''){
                                $label=$field['adminLabel'];
                            } else {
                                $label=$field['label'];
                            }
                            if($field['type']!='honeypot'){
                                $fields[]=array("label"=>$label,"value"=>$entry[$field['id']],"action"=>$form['gfmailgun']['id_'.$field['id']]);
                            }
                        }
                    }
                    if(trim($body)==''){
                        //Just output all fields
                        if(count($fields)){
                            foreach($fields as $value){
                                //print_r($value);
                                //echo("Getting form[gfmailgun][id_".$value['id']": ".$form['gfmailgun']['id_'.$value['id']]);
                                if($value['action'] != 'exclude'){
                                    if($value['action'] == 'replyto'){
                                        $replyto=$value['value'];
                                    }
                                    $body.= $value['label']. ': ' . $value['value']."\n\n";
                                    $rowcount++;
                                    $htmlblock.=$this->htmlrow($value['label'],$value['value'],$rowcount);
                                }
                            }
                        }
                    } else {
                        if(count($fields)){
                            foreach($fields as $key=>$value){
                                $search = '{'.$key.'}';
                                $body=str_replace($search,$value,$body);
                            }
                        }
                    }
                    if(isset($_COOKIE['__highrank_ch_campaign'])){
                        $rowcount++;
                        $htmlblock.=$this->htmlrow('__highrank_ch_campaign',$_COOKIE['__highrank_ch_campaign'],$rowcount);
                        $body.='__highrank_ch_campaign: '.$_COOKIE['__highrank_ch_campaign']."\n\n";
                    }
                    if(isset($_COOKIE['__highrank_ch_ad'])){
                        $rowcount++;
                        $htmlblock.=$this->htmlrow('__highrank_ch_ad',$_COOKIE['__highrank_ch_ad'],$rowcount);
                        $body.='__highrank_ch_ad: '.$_COOKIE['__highrank_ch_ad']."\n\n";
                    }
                    if(isset($_COOKIE['__highrank_ch_target'])){
                        $rowcount++;
                        $htmlblock.=$this->htmlrow('__highrank_ch_target',$_COOKIE['__highrank_ch_target'],$rowcount);
                        $body.='__highrank_ch_target: '.$_COOKIE['__highrank_ch_target']."\n\n";
                    }
                    if(isset($_COOKIE['__highrank_ch_contact_phone'])){
                        $rowcount++;
                        $htmlblock.=$this->htmlrow('__highrank_ch_contact_phone',$_COOKIE['__highrank_ch_contact_phone'],$rowcount);
                        $body.='__highrank_ch_contact_phone: '.$_COOKIE['__highrank_ch_contact_phone']."\n\n";
                    }
                                    // HTML version
        
                    $html='<table border="0" cellpadding="0" cellspacing="0" width="100%" id="bodyTable">
                            <td align="center" bgcolor="#fff" style="padding: 40px 0 30px 0;">
                             <img src="http://www.ilawyermarketing.com/images/ilawyerlogo.jpg" alt="iLawyerLead"  style="display: block;" />
                            </td>
                            </table>
                            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                                <tr>
                                    <td align="center" valign="top">
                                        <table border="0" cellpadding="0" cellspacing="0" width="800" id="emailContainer">
                                            <tr>
                                                <td align="center" valign="top">
                                                    <table border="0" cellpadding="10" cellspacing="0" width="100%" id="emailHeader" bgcolor="#e5e5e5">
                                                        <tr>
                                                            <td align="center" valign="top" font style="font-family: arial; font-size:25px; color="#fff">
                                                                Website Contact                                </td>

                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" valign="top">
                                                    <table border="1" bordercolor="#e5e5e5"   cellpadding="10" cellspacing="0" width="100%" id="emailBody">
                            ';
                    $html.=$htmlblock;
                    $html.='                </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" valign="top">
                                            <table border="0" cellpadding="5" cellspacing="0" width="100%" id="emailFooter" font style="font-family: arial;">
                                                <tr>
                                                    <td align="center" valign="top">
                                                        Form submitted on '.get_bloginfo().'
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>';
        
        
                    $messagevars = array('from'    => $formsettings['fromemail'],
                                        'to'      => $formsettings['toemail'],
                                        'subject' => (isset($formsettings['subjectline'])?$formsettings['subjectline']:''),
                                        'text'    => $body,
                                        'html'    => $html,
                                        'o:tracking' => 'no',
                                        'o:tracking-clicks' => 'no',
                                        'o:tracking-opens' => 'no',
                                        'h:reply-to' => $replyto);
                    if(isset($formsettings['bccemail']) && trim($formsettings['bccemail'])!=''){
                        $messagevars['bcc']=$formsettings['bccemail'];
                    };
                    try{
                        $result = $mgClient->sendMessage("$domain",$messagevars);
                    }catch (Exception $e)  {
                        $site=site_url();
                        if(isset($options['test_email'])){
                            $messagevars = array('from' => $options['from_email'],
                                                 'to' => $options['test_email'],
                                                 'subject' => 'Mailgun API Call Failed for '.$site,
                                                 'text' => print_r($e,true),
                                                 'o:tracking' => 'no');
                            $result = $mgClient->sendMessage("$domain",$messagevars);
                        }
                    } 
                
            
                }
            }
        }
        
        function form_submit_button($button, $form){
            $settings = $this->get_form_settings($form);
            if(isset($settings["enabled"]) && true == $settings["enabled"]){
                $text = $this->get_plugin_setting("mytextbox");
                $button = "<div>{$text}</div>" . $button;
            }
            return $button;
        }
       //  public function plugin_page() {
//             
//             echo ('This page appears in the Forms menu');
//         
//         }
        public function form_settings_fields($form) {
            $settings=$this->get_plugin_settings();
            // print_r($settings);
//             print_r($form);
            
            
            //$qb=new trinityQB(array('token'=>$settings['qb_apptoken'],'realm'=>$settings['qb_realm']));
            //$qb=new trinityQB($settings['qb_user'],$settings['qb_password'],$settings['qb_realm'],$settings['qb_app_id'],$settings['qb_apptoken']);
            //$qb->authenticate($settings['qb_user'],$settings['qb_password']);
            

            
            
                $mychoices=array();
                $mychoices[]=array(
                    "label" => "Include",
                    "value" => "include"
                );
                $mychoices[]=array(
                    "label" => "Exclude",
                    "value" => "exclude"
                );
                $mychoices[]=array(
                    "label" => "Reply To",
                    "value" => "replyto"
                );
            
            $myfields=array();
            foreach($form['fields'] as $field){
                $tempfield=array(
                            "label"   => ($field['adminLabel']?$field['adminLabel']:$field['label']),
                            "type"    => "select",
                            "name"    => "id_".$field['id'],
                            "tooltip" => "Select Include to include this field in the notification, or Exclude to exclude this field from the notification. Selecting Reply To will make the contents of this field be the REPLYTO: address on your notification. If you select Reply To for multiple fields, only the last will be used.",
                            "class"   => "small qbselect",
                            "choices" => $mychoices
                        );
                $myfields[]=$tempfield;
            }
            return array(
                array(
                    "title" => "GFMailGun Form Settings",
                    "description" => "Fill out this information to connect this form to MailGun",
                    "fields" => array(array(
                                    "type"=>"hidden",
                                    "name"=>"hidden"
                                ))
                ),
                array(
                    "title"  => "Email Settings",
                    "description" => "Email settings for this form",
                    "fields" => array(array(
                                    "label" => "From Email Address",
                                    "type" => "text",
                                    "name" => "fromemail",
                                    "tooltip" => "The FROM: address for your notifications. The domain must match the domain you specified for MailGun.",
                                    "class" => "medium",
                                    "placeholder" => "from@email.com"
                                ),
                                array(
                                    "label" => "To Email Address",
                                    "type" => "text",
                                    "name" => "toemail",
                                    "tooltip" => "Email address(es) to which this notification will be sent. Separate multiple addresses with commas.",
                                    "class" => "medium",
                                    "placeholder" => "from@email.com"
                                ),
                                array(
                                    "label" => "BCC Email Address",
                                    "type" => "text",
                                    "name" => "bccemail",
                                    "tooltip" => "Hidden email address(es) to which this notification will be sent. Separate multiple address with commas.",
                                    "class" => "medium",
                                    "placeholder" => "from@email.com"
                                ),
                                array(
                                    "label" => "Subject Line",
                                    "type" => "text",
                                    "name" => "subjectline",
                                    "tooltip" => "The subject line for this notification.",
                                    "class" => "large"
                                ),
                                )
                ),
                array(
                    "title" => "Notification Field Settings",
                    "description" => 'Select which fields should be included in the notification. Setting a field to "Reply To" will make the contents of that field be the REPLYTO: address for the notification. Reply To fields are always included in the notification.',
                    "fields" => $myfields
                )
            );
        }
        public function settings_my_custom_field_type(){
            ?>
            <div>
                My custom field contains a few settings:
            </div>
            <?php
                $this->settings_text(
                    array(
                        "label" => "A textbox sub-field",
                        "name" => "subtext",
                        "default_value" => "change me"
                    )
                );
                $this->settings_checkbox(
                    array(
                        "label" => "A checkbox sub-field",
                        "choices" => array(
                            array(
                                "label" => "Activate",
                                "name" => "subcheck",
                                "default_value" => true
                            )
                        )
                    )
                );
        }
        public function plugin_settings_fields() {
            return array(
                            array(
                                'title'       => 'Mailgun Account Information',
                                'description' => 'Needed to access your MailGun account',
                                'fields'      => array(
                                
                                                        array(
                                                            "name"        => "mg_apikey",
                                                            "tooltip"     => __("Ensure this is your API Key and not your Public API Key.", "gfmailgun"),
                                                            "label"       => __("Mailgun API Key", "gfmailgun"),
                                                            "type"        => "text",
                                                            "class"       => "medium",
                                                        ),
                                                        array(
                                                            "name"    => "mg_domain",
                                                            "tooltip" => __("Your domain must be marked verified at Mailgun.", "gfmailgun"),
                                                            "label"   => __("Mailgun Domain", "gfmailgun"),
                                                            "class"       => "medium",
                                                            "type"    => "text",
                                                            "placeholder" => "example.com"
                                                        ),
                                                        array(
                                                            "name"    => "mg_testemail",
                                                            "tooltip" => __("Enter the email address to which test emails should be sent. If you leave this blank, no test email will be sent and your Mailgun settings may be incorrect.", "gfmailgun"),
                                                            "label"   => __("Test Email Address", "gfmailgun"),
                                                            "class"       => "medium",
                                                            "type"    => "text",
                                                            "placeholder" => "email@example.com"
                                                        )
                                
                                )
                            )
                        );
        }
        public function is_valid_setting($value){
            //return strlen($value) < 10;
            return true;
        }
        public function scripts() {
            $scripts = array(
                array("handle"  => "my_script_js",
                      "src"     => $this->get_base_url() . "/js/main.js",
                      "version" => $this->_version,
                      "deps"    => array("jquery"),
                      "enqueue" => array(
                          array(
                              "admin_page" => array("form_settings"),
                              "tab"        => "gfmailgun"
                          ),
                          array(
                              "admin_page" => array("plugin_settings"),
                              "tab"        => "gfmailgun"
                          )
                      )
                ),
            );
            return array_merge(parent::scripts(), $scripts);
        }
        public function styles() {
            $styles = array(
                array("handle"  => "my_styles_css",
                      "src"     => $this->get_base_url() . "/css/my_styles.css",
                      "version" => $this->_version,
                      "enqueue" => array(
                          array("field_types" => array("poll"))
                      )
                )
            );
            return array_merge(parent::styles(), $styles);
        }
    }
    new GFSimpleAddOn();
}
 
?>