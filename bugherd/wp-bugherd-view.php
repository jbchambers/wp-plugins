<?php
/**
 * Display administration form (to connect to bugherd)
 *
 *
 * @package WordPress
 * @subpackage wp-bugherd
 * @since 1.0.0.0
 */

class Bugherd_View
{

	/**
	 * Init view
	 * Add basic callbacks
	 * 
	 * @access public
	 * @since 1.0.0.0
	 * @return void
	 */	
	public static function init()
	{
		//Setup menu
		add_action( 'admin_menu', array('Bugherd_View','admin_menu') );
		
		//Administration view setup
		add_action('admin_init',array('Bugherd_View','admin_init'));
		
		//Integration
		add_action('wp_head',array('Bugherd_View','integration'));
		add_action('admin_print_footer_scripts', array('Bugherd_View','integration'));
	}
	
	/**
	 * Administration tasks
	 *
	 * @access public
	 * @since 1.0.0.0
	 * @return void
	 */
	public static function admin_init()
	{
		//Setup help
		add_meta_box('help',__('What is BugHerd','wp-bugherd'), array('Bugherd_View','metabox_help'), 'settings_page_wp-bugherd', 'side');
		add_meta_box('consider',__('Consider','wp-bugherd'), array('Bugherd_View','metabox_consider'), 'settings_page_wp-bugherd', 'side');
		
		//Setup assets
		add_action('admin_print_styles-settings_page_wp-bugherd', array('Bugherd_View','css'));
		add_action('admin_footer-settings_page_wp-bugherd', array('Bugherd_View','js'));

		//Ajax
		add_action( 'wp_ajax_bugherd_ajax', array('Bugherd_View','ajax'));
		add_action( 'admin_action_bugherd_reset', array('Bugherd_View','reset'));
		
		//Inform user to finish integration
		if(current_user_can('manage_options'))
		{
				$bugherd_integration_code = get_option( 'bugherd_integration_code' );
				if(empty($bugherd_integration_code))
				{
					add_action( 'admin_notices', array('Bugherd_View','site_admin_notice') );
					//add_action( 'network_admin_notices', array('Bugherd_View','site_admin_notice') );		
				}	
		}
	}	
	
		
	
	/**
	 * If user has not provided integration code, display a nice message to ask them to do so.
	 *
	 * @access public
	 * @since 1.0.0.0
	 * @return void
	 */	
	public static function site_admin_notice()
	{
		global $page_hook, $wp_version;

		if($page_hook != "settings_page_wp-bugherd")
		{
			$integration_code = get_option('bugherd_options', array());
			
			if (version_compare( $wp_version, '3.0', '>=' ) ) :
					if(empty($integration_code) && is_multisite())
						$integration_code = get_site_option('bugherd_options', array());
			endif;	

			//User has not provided any integration code, inform him/her
			if(empty($integration_code)) :
		?>
			<div class="updated fade">
				<p><?php printf( __('<b>BugHerd is almost ready.</b> You must enter an <a href="%1$s">integration key</a> for it to work.'), esc_url( admin_url( 'options-general.php?page=wp-bugherd' ) ) ); ?></p>
			</div>		
		<?php
			endif;
		}
		
		if(isset($_REQUEST['settings-reset']))
		{
		?>
			<div class="updated fade">
				<p><?php printf( __('<b>Your BugHerd settings have been reset successfully.</b>') ); ?></p>
			</div>		
		<?php			
		}
	}
	
	/**
	 * Add menu into options page
	 *
	 * @access public
	 * @since 1.0.0.0
	 * @return void
	 */	
	public static function admin_menu()
	{
		add_options_page(__('BugHerd','wp-bugherd'),__('BugHerd','wp-bugherd'),'manage_options','wp-bugherd', array('Bugherd_View','admin'));
	}
	
	/**
	 * Administration view
	 *
	 * @access public
	 * @since 1.0.0.0
	 * @return void
	 */
	public static function admin()
	{
			global $wp_version;
			
			$bugherd_options = get_option('bugherd_options',array());
			
			//Reset URl
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'settings-reset' ), $_SERVER['REQUEST_URI'] );
		?>
			<div class="wrap">
				<h2><?php _e('BugHerd Configuration','wp-bugherd'); ?></h2>
				<div class="updated below-h2" style="display:none" id="message"></div>
				
				<form action="" method="post" id="bugherd_settings_form">
				<input type="hidden" name="action" id="action" value="bugherd_ajax" />
				<input type="hidden" name="task" id="task" value="save_settings" />
				
				<?php wp_nonce_field( 'save-bugherd', '_wpnonce_bugherd', false ); ?>				
				<div id="bugherd_settings">
						<div id="poststuff">
								<div id="post-body" class="metabox-holder columns-2">
											<div id="post-body-content">
													
												<ul id="steps">
													<?php if(empty($bugherd_options)) : ?>
													<li id="step_login" class="step_container">
														<h2><?php _e('Step 1 :: Obtaining integration key','wp-bugherd'); ?></h2>
														<div class="step_body">
																		<div class="instruction">
																			<ul>
																					<li><p><?php _e('Please enter your username/password to allow plugin to retrieve your project\'s integration key.','wp-bugherd'); ?></p></li>
																					<li><p><?php _e(sprintf('We do not store these credentials and only use them to retrieve project information through the BugHerd <a href="%1$s" target="_blank">API</a>.','http://www.bugherd.com/api'),'wp-bugherd'); ?></p></li>
																					<li><p><?php _e(sprintf('If you do not have an account with BugHerd, please <a href="%1$s" target="_blank">click here to create one</a>. It is a simple two step process.',"https://www.bugherd.com/"),'wp-bugherd'); ?></p></li>
																			</ul>
																		</div>
																		
																		<p>
																			<label for="bugherd_username"><?php _e('Email address','wp-bugherd'); ?></label><br />
																			<input type="text" value="" class="regular-text" id="bugherd_username" name="bugherd_username">
																		</p>
																		<p>
																			<label for="bugherd_password"><?php _e('Password','wp-bugherd'); ?></label><br />
																			<input type="password" value="" class="regular-text" id="bugherd_password" name="bugherd_password">
																		</p>
																		<p class="submit">
																			<input type="button" class="button-primary" value="<?php _e('Login to retrieve projects','wp-bugherd'); ?>" accesskey="l" id="check_login" />
																			&#8212; <a href="#" id="show_integration_form"><?php _e('I know my integration key','wp-bugherd'); ?></a>
																		</p>	
														</div>													
													</li>
													<li id="step_integration_key" class="step_container">
														<h2><?php _e('Step 2 :: Selecting project or providing integration key','wp-bugherd'); ?></h2>
														<div class="step_body">
																		
																		<div class="grid">
																			<div class="column column-1">
																				<div class="column_body">
																						<p>
																							<?php _e('Pick one of the following existing project or define new <a href="#" id="add_new_project_link">one</a>.','wp-bugherd'); ?>
																							<ul id="bugherd_projects">
																								<li><a data-key="xtuairrhvro6zslx5exqaa" href="#">My Project</a></li><li><a data-key="izqgcnrmrntueltmqg4l0w" href="#">Social Test</a></li><li><a data-key="fxxbzp6neti9pnjjdjsaoa" href="#">Social Test</a></li>
																							</ul>
																						</p>
																																					
																						<p>
																							<div id="new_project_textbox">
																										<input type="text" value="" class="regular-text" id="bugherd_new_project" name="bugherd_new_project" placeholder="<?php _e('name of new project','wp-bugherd'); ?>" /> 
																										<a href="#" id="add_new_project"><?php _e('Add new project','wp-bugherd'); ?></a>
																							</div>
																						</p>			
																				</div>																
																			</div>
																			<div class="column column-2">
																						<p>
																								<label for="bugherd_integration_code"><?php _e('If you already know your integration key please paste it here.','wp-bugherd'); ?></label><br />
																								<textarea class="regular-text" id="bugherd_integration_code" name="options[bugherd_integration_code]"><?php echo $bugherd_options['bugherd_integration_code']; ?></textarea>																						
																						</p>
																						<p class="submit">
																							<input type="button" class="button-primary" value="<?php _e('Save','wp-bugherd'); ?>" accesskey="i" id="check_keys" />
																						</p>																				
																			</div>
																		</div>
													
															
														</div>
													</li>
													<?php endif; ?>
													<li id="step_wp_configuration" class="step_container<?php if(!empty($bugherd_options)) :?> active<?php endif;?>">
														<h2>
														
																<?php 
																		if(empty($bugherd_options)) :
																			_e('Step 3 :: Configure WordPress','wp-bugherd'); 
																		else:
																			_e('Configure WordPress','wp-bugherd');
																		endif;
																?>		
														</h2>
														
														
														<?php 
																$bugherd_options_default = array(
																									'user_levels' => array('administrator','editor'),
																									'enable_admin' => 'no',
																									'sites' => array()
																								);
																$bugherd_options = wp_parse_args($bugherd_options, $bugherd_options_default);
																
														?>
														<div class="step_body">
																		
																		<p>
																			<label for="bugherd_capabilities" class="main_label"><?php _e('Enable BugHerd sidebar for following user levels.','wp-bugherd'); ?></label><br />
																			
																			<?php 
																							$editable_roles = get_editable_roles();
																							
																							foreach ( $editable_roles as $role => $details ) 
																							{
																								$name = translate_user_role($details['name'] );
																								if ( in_array($role,  $bugherd_options['user_levels']) )
																									echo "\n\t<label><input type='checkbox' name='options[user_levels][]' checked='checked' value='" . esc_attr($role) . "' /> $name</label><br />";
																								else
																									echo "\n\t<label><input type='checkbox'  name='options[user_levels][]' value='" . esc_attr($role) . "' /> $name</label><br />";
																							}	

																							
																			?>
																			<label><input type="checkbox"  name="options[user_levels][]" value="unregistered" <?php if ( in_array('unregistered',  $bugherd_options['user_levels'])) :?> checked="checked" <?php endif; ?> /> <?php _e('Unregistered (always enabled)','wp-bugherd'); ?></label>
																		</p>
													
																		<p>
																			<label for="options_enable_admin_no" class="main_label"><?php _e('Do you want to enable BugHerd sidebar for WordPress Administration as well?','wp-bugherd'); ?></label><br />
																			
																			<label><input type="radio" name="options[enable_admin]" id="options_enable_admin_yes" value="yes" <?php checked($bugherd_options['enable_admin'], 'yes') ?> /> <?php _e('Yes','wp-bugherd'); ?></label> 
																			<label><input type="radio" name="options[enable_admin]" id="options_enable_admin_no" value="no" <?php checked($bugherd_options['enable_admin'], 'no') ?> /> <?php _e('No','wp-bugherd'); ?></label>
																		</p>
																		
																		<?php if (version_compare( $wp_version, '3.0', '>=' ) ) : ?>
																					<?php if (is_multisite() && is_main_site() && current_user_can( 'manage_sites' )) : ?>
																					<?php 
																						$all_blogs = get_blogs_of_user( get_current_user_id() );
																						if ( count( $all_blogs ) > 1 ) 
																						{
																							?>
																							<p>
																									<label for="bugherd_sites" class="main_label"><?php _e('Copy above settings to the following WordPress sub-sites as well.','wp-bugherd'); ?></label><br />
																									<?php
																									foreach ( $all_blogs as $b )
																									{
																										if ( in_array($b->userblog_id,  $bugherd_options['sites']) )
																											echo "\n\t<label><input type='checkbox' name='options[sites][]' checked='checked' value='" . esc_attr($b->userblog_id) . "' /> $b->blogname </label><br />";
																										else
																											echo "\n\t<label><input type='checkbox'  name='options[sites][]' value='" . esc_attr($b->userblog_id) . "' /> $b->blogname</label><br />";
																									}
																									?>
																							</p>
																							<?php																				
																						}
																					?>
																					<?php endif; ?>
																		<?php endif; ?>
														
																		<p class="submit">
																			<input type="button" class="button-primary" value="<?php _e('Save settings','wp-bugherd'); ?>" accesskey="s" id="save_bugherd_settings" />
																			 &#8212; <a href="<?php echo admin_url('admin.php?action=bugherd_reset'); ?>"><?php _e('Reset BugHerd settings','wp-bugherd'); ?></a>
																		</p>																		
															
														</div>
													</li>													
													
													
												</ul>	
													
											</div><!-- /post-body-content -->
											
											<div id="postbox-container-1" class="postbox-container">
													<?php 
														do_meta_boxes('settings_page_wp-bugherd', 'side', null);
													?>
													
											</div><!-- /post-body-content -->				
											
								</div><!-- /post-body -->
								<br class="clear" />
						</div><!-- /poststuff -->
				</div>	
				</form>			
				
			</div>		
		<?php
	}	
	
	/**
	 * Stylesheet for the form
	 *
	 * @access public
	 * @since 1.0.0.0
	 */	
	public static function css()
	{
		global $wp_version;
		?>
		<style type="text/css">
				
				#poststuff #steps li h2
				{
					
					
					margin:0;
    				background:#f0f0f0;					
					padding:6px 20px;
					text-shadow:none;
					font-size:1.3em;
					border: 1px solid #CCCCCC;
					border-top:none;
					
				  -webkit-transition: background-color linear 0.4s;
				     -moz-transition: background-color linear 0.4s;
				      -ms-transition: background-color linear 0.4s;
				       -o-transition: background-color linear 0.4s;
				          transition: background-color linear 0.4s;					
				}
				
				#poststuff #steps li.active h2
				{
					background:#CCCCCC;			
				}
				
				#poststuff #steps
				{
					padding:0;
					margin:0;	
				}
				
				#poststuff #steps li.step_container .step_body
				{
					display:none;
				}
				
				#poststuff #steps li.active .step_body
				{
					display:block;
				}				
				
				#poststuff #steps .grid
				{
					width:100%;
					clear:both;
					zoom:1;
					overflow:auto;	
				}
				
				#poststuff #steps .grid .column
				{
					float:left;
					width:48%;
				}		

				#poststuff #steps .grid .column_body
				{
					margin-right:20px;
				}

				
				#poststuff #steps p
				{
					margin:0 0 1em 0;
					padding:0;
				}
				
				#poststuff #steps > li
				{
					margin-bottom:0;	
				}
				
				.step_body
				{
					border:1px solid #cccccc;
					padding:20px;
				}
				
				#poststuff #steps .instruction p,
				#poststuff #steps #step_login p.submit
				{
					padding:0;
					margin:0 0 0 3px;
				}
		
				
				#poststuff #steps .invalid
				{
					border-color:#ff0000;
				}
				
				#poststuff #steps .instruction
				{
					border:1px solid #cccccc;
					margin-bottom:10px;	
					font-family:Georgia, "Times New Roman", Times, serif;
										
					-webkit-box-shadow: 0 1px 4px rgba(0, 0, 0, 0.27), 0 0 40px rgba(0, 0, 0, 0.06) inset;
					-moz-box-shadow: 0 1px 4px rgba(0, 0, 0, 0.27), 0 0 40px rgba(0, 0, 0, 0.06) inset;
					box-shadow: 0 1px 4px rgba(0, 0, 0, 0.27), 0 0 40px rgba(0, 0, 0, 0.06) inset; 					
				}
				
				#poststuff #steps .instruction ul
				{
					padding:10px 0;					
					background:#fff;		
				}
				
				#poststuff #steps .instruction ul li
				{
					border-bottom:1px solid #e4f5f6;
					padding: 0 0 2px 0;
					margin: 0 0 0 0;
				}
				
				#poststuff #steps .instruction ul li p
				{
					display:block;
					border-left:1px solid #e4f5f6;
					padding: 0 0 0 15px;
					margin: 0 0 0 15px;
				}
				
				#poststuff #steps .instruction ul li:last-child
				{
					border-bottom:none;	
				}
				
				#bugherd_integration_code
				{
					width:100%;
					height:120px;	
				}
				
				#bugherd_projects
				{
					font-family:Georgia, "Times New Roman", Times, serif;					
				}
				
				#bugherd_projects li
				{
					border-bottom:1px solid #e4f5f6;
					margin-bottom:2px;
					padding-bottom:2pxl
				}
				
				#new_project_textbox
				{
					position:relative;
				}
				
				#new_project_textbox input
				{
					width:100%;
					border:none;
					border-bottom:2px solid #DFDFDF;
				}			

				#new_project_textbox a
				{
					display:block;
					position:absolute;
					right:0;
					top:0;
				}
				
				.main_label
				{
					font-weight:bold;	
				}
				
				#consider .inside
				{
					margin-left:0;
					margin-right:0;
					padding-left:0;
					padding-right:0;
				}

				#consider .inside ul li
				{
					padding:4px 8px;
					border-bottom:1px solid #DFDFDF;
				}		
				
				#consider .inside ul li:last-child
				{
					border-bottom:0;	
				}
				
				#consider .inside ul li a
				{
					text-decoration:none;	
				}
				
				
				<?php if (!version_compare( $wp_version, '3.4', '>=' ) ) :  ?>
							#poststuff 
							{
							    padding-top: 10px;
							}
							
							#poststuff #post-body.columns-2 
							{
							    margin-right: 300px;
								padding:0;
							}
												
							#post-body-content 
							{
							    float: left;
							    width: 100%;
							}
												
							#post-body.columns-2 #postbox-container-1 
							{
							    float: right;
							    margin-right: -300px;
							    width: 280px;
							}				  					
				<?php endif; ?>
		</style>
		<?php
	}
	
	/**
	 * Ajax/javascript
	 *
	 * @access public
	 * @since 1.0.0.0
	 */
	public static function js()
	{
		global $wp_version;
		?>
			<script type="text/javascript">

					jQuery(document).ready(function($)
					{
							//Selecting the form
							setTimeout(function()
							{
										$('#step_login .step_body').slideDown('fast',function()
										{
											$('#step_login').addClass('active');
										});
							}, 300);
						
							//Login functionality
							$('#check_login').click(function()
							{
								$('#message').fadeOut();
								$('#step_login .invalid').removeClass('invalid');
								
								var bugherd_username = $('#bugherd_username').val();
								if(!bugherd_username)
									$('#bugherd_username').addClass('invalid');

								var bugherd_password = $('#bugherd_password').val();
								if(!bugherd_password)
									$('#bugherd_password').addClass('invalid');

								//In case we have error
								if($('#step_login .invalid').length > 0)
								{
									$('#step_login .invalid').focus();
									return false;
								}
								
								//Making an ajax request to validate username password
								var formData = [];
								formData.push({'name' : 'action', 'value' : $('#action').val() });
								formData.push({'name' : '_wpnonce_bugherd', 'value': $('#_wpnonce_bugherd').val() });
								formData.push({'name' : 'task', 'value' : 'check_login' });		
								formData.push({'name' : 'username', 'value' : bugherd_username });	
								formData.push({'name' : 'password', 'value' : bugherd_password });								

								$('#check_login').attr('disabled','disabled');
								$.post( ajaxurl, formData, function(response)
								{
									$('#check_login').removeAttr('disabled');
									var errors = "";
									
									if(response.success === false)
									{
										for(k in response.data.errors)
										{
											errors += response.data.errors[k].join("<br />") + "<br />";
										}
									}

									//if status is true but data is null
									if((response.data == null) && (response.success === true))
									{
										errors = '<?php _e(sprintf('<b>Please enable Rest API access in your BugHerd account <a href="%1$s" target="_blank">settings</a> page and try again.</b>','http://www.bugherd.com/settings'),'wp-bugherd'); ?>';
									}

									if(errors.length > 0)
									{
										$('#message').html(errors);
										$('#message').removeClass('updated').addClass('error').appendTo('#step_login .step_body');
										$('#message').fadeIn();										
									}else{

										//Request was successful lets move user to next step
										if(response.data.project !== undefined)
										{
											var project_options = [];
											
											for (i = 0; i< response.data.project.length; i++)
											{
												project_options.push('<li><a href="#" data-key="' + response.data.project[i]['api-key'] + '">' + response.data.project[i]['name'] + '</a></li>');
											}
											
											$('#bugherd_projects').html(project_options.join(''));		

											//Move user to project selection window
											$('#step_login .step_body').slideUp('fast',function()
											{
																$('#step_login').removeClass('active');
																$('#step_integration_key .step_body').slideDown('fast',function()
																{
																	$('#step_integration_key').addClass('active');
																	$('#bugherd_integration_code').focus();
																});												
											});	
										}
										
									}
									
									
								}, "json");
								
								return false;
							});


							//Project integration
							$('#add_new_project_link').click(function(e)
							{
								e.preventDefault();

								$('#bugherd_new_project').focus();
								
								return false;
							});

							$('#show_integration_form').click(function(e)
							{
								e.preventDefault();

								$('#step_integration_key .column-1').hide();
								$('#step_integration_key .column-2').css('width','99%');

								$('#step_login .step_body').slideUp('fast',function()
								{
											$('#step_login').removeClass('active');
											$('#step_integration_key .step_body').slideDown('fast',function()
											{
												$('#step_integration_key').addClass('active');
												$('#bugherd_integration_code').focus();
											});												
								});								
								
								
								return false;
							});							
							

							//Selecting API key :: providing support for legacy jQuery LIVE and new ON support
							<?php if (version_compare( $wp_version, '3.4', '<=' ) ) :  ?>							
							$("#bugherd_projects a").live("click",function(e)						
							<?php else: ?>
							$("#bugherd_projects").on('click.api_key','a',function(e)							
							<?php endif; ?>					
							{
								var key = $(this).data('key');
								
								//Wordpress 3.0 or earlier supports
								if(!key)
									key = $(this).attr('data-key');
									
								$('#bugherd_integration_code').val(key);
								return false;
							});		


							$('#add_new_project').click(function()
							{
										$('#message').fadeOut();
										$('#step_integration_key.invalid').removeClass('invalid');

										//@todo: do we need to have verification again?
										var bugherd_username = $('#bugherd_username').val();
										var bugherd_password = $('#bugherd_password').val();										
										
										var bugherd_new_project = $('#bugherd_new_project').val();
										if(!bugherd_new_project)
											$('#bugherd_new_project').addClass('invalid');

										//In case we have error
										if($('#step_integration_key .invalid').length > 0)
										{
											$('#step_integration_key .invalid').focus();
											return false;
										}
										
										//Making an ajax request to validate username password
										var formData = [];
										formData.push({'name' : 'action', 'value' : $('#action').val() });
										formData.push({'name' : '_wpnonce_bugherd', 'value': $('#_wpnonce_bugherd').val() });
										formData.push({'name' : 'task', 'value' : 'add_new_project' });		
										formData.push({'name' : 'username', 'value' : bugherd_username });	
										formData.push({'name' : 'password', 'value' : bugherd_password });	
										formData.push({'name' : 'bugherd_new_project', 'value' : bugherd_new_project });								

										$('#add_new_project').hide();
										$.post( ajaxurl, formData, function(response)
										{
											$('#add_new_project').show();
											var errors = "";
											
											if(response.success === false)
											{
												for(k in response.data.errors)
												{
													errors += response.data.errors[k].join("<br />") + "<br />";
												}
											}

											//if status is true but data is null
											if((response.data == null) && (response.success === true))
											{
												errors = '<?php _e(sprintf('<b>Please enable Rest API access in your BugHerd account <a href="%1$s" target="_blank">settings</a> page and try again.</b>','http://www.bugherd.com/settings'),'wp-bugherd'); ?>';
											}

											if(errors.length > 0)
											{
												$('#message').html(errors);
												$('#message').removeClass('updated').addClass('error').appendTo('#step_integration_key .step_body');
												$('#message').fadeIn();										
											}else{

												//Request was successful add new project and move to next step
												$("#bugherd_projects").append('<li><a data-key="' + response.data['api-key'] + '" href="#">' +  response.data['name']  + '</a></li>');
												$('#bugherd_new_project').val('');											
												
											}
											
										}, "json");
										
										return false;
							});							

							$('#check_keys').click(function(e)
							{
								var bugherd_integration_code = $('#bugherd_integration_code').val();
								if(!bugherd_integration_code)
								{
									$('#bugherd_integration_code').addClass('invalid').focus(); 
									return false;
								}
																
								$('#step_integration_key .step_body').slideUp('fast',function()
								{
											$('#step_integration_key').removeClass('active');
											
											$('#step_wp_configuration .step_body').slideDown('fast',function()
											{
												$('#step_wp_configuration').addClass('active');
											});												
								});								
								
								
								return false;
							});		
							
							
							//In case we have integration key, or got it from API



							//Saving settings
							$('#save_bugherd_settings').click(function()
							{
										$('#message').fadeOut();
										$.post( ajaxurl, $('#bugherd_settings_form').serialize(), function(response)
										{
												if(response.success === true)
												{
													$('#message').html(response.data).removeClass('error').addClass('updated').appendTo('#step_wp_configuration .step_body');
													$('#message').fadeIn();			
												}
										}, "json");										
							});							
												
					});
			
			</script>
			<?php
		}	
	
	/**
	 * Default metabox to demonstrate bugherd
	 *
	 * @access public
	 * @since 1.0.0.0
	 */
	public static function metabox_help()
	{
		?>
			<iframe src="http://player.vimeo.com/video/39621566?title=0&amp;byline=0&amp;portrait=0&amp;color=000000" width="260" height="146" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
		<?php
	}	
	
	/**
	 * Credits & other userful information
	 *
	 * @access public
	 * @since 1.0.0.0
	 */	
	public static function metabox_consider()
	{
		?>
		<ul>
			<li><a href="http://simpleux.co.uk/plugins/wordpress/bugherd" target="_blank"><?php _e('Visit plugin site','wp-bugherd'); ?></a></li>
			<li><a href="http://wordpress.org/extend/plugins/bugherd/" target="_blank"><?php _e('Rate this plugin','wp-bugherd'); ?></a></li>
			<li><a href="https://twitter.com/simple_ux" target="_blank"><?php _e('follow @simple_ux','wp-bugherd'); ?></a></li>
			<li><a href="https://twitter.com/bugherd" target="_blank"><?php _e('follow @bugherd','wp-bugherd'); ?></a></li>
		</ul>
		<?php
	}
	
	/**
	 * Ajax functionality
	 *
	 * @access public
	 * @since 1.0.0.0
	 */	
	public static function ajax()
	{
		global $wp_version;
		
		if(!defined("DOING_AJAX")) return false;
		check_ajax_referer(  'save-bugherd', '_wpnonce_bugherd' );
		
		$json_result = array();
		$task = $_REQUEST['task'];
		if($task == "check_login")
		{
				$login = Bugherd_Client::login($_POST['username'],$_POST['password']);
				
				//if user is successfully logged in attach project information to the response
				if(!is_wp_error($login))
				{
					$json_result['success'] = true;
					$json_result['data'] = Bugherd_Client::get_projects();
				}else{
					$json_result['success'] = false;
					$json_result['data'] = $login;				
				}

		}elseif($task == "add_new_project")
		{
				//if(false === ($json_result = get_transient('project_add')))
				//set_transient('project_add', $json_result);
				
				$login = Bugherd_Client::login($_POST['username'],$_POST['password']);
				if(!is_wp_error($login))
				{
					$project_information = Bugherd_Client::add_project(stripslashes($_POST['bugherd_new_project']));
					$json_result['success'] = true;
					$json_result['data'] = $project_information;
					
				}else{
					$json_result['success'] = false;
					$json_result['data'] = $login;				
				}
			
		}elseif($task == "save_settings")
		{
			$existing_settings = get_option('bugherd_options', array());
			
			if (version_compare( $wp_version, '3.0', '>=' ) ) : 
				if(empty($existing_settings) && is_multisite())
					$existing_settings = get_site_option('bugherd_options', array());
			endif;
							
			$data = stripslashes_deep($_POST['options']);
			
			//In case of multisite if user has not selected any site, then checkbox do not appear in form, which cause trouble with wp_parse_args below, so make sure to provide some value
			if(!array_key_exists("sites",$data)) $data['sites'] = array();
			
			//Preserve existing settings (if posted information missing some (like integration code)	
			$data = wp_parse_args($data, $existing_settings);
			
			update_option('bugherd_options', $data);
			
			//copy across multisites
			if (version_compare( $wp_version, '3.0', '>=' ) ) : 
					if (is_multisite() && is_main_site() && current_user_can( 'manage_sites' )) :
						
						update_site_option('bugherd_options', $data);
					
					endif;
			endif;
			//$bugherd_options = get_option('bugherd_options',array());
			
			$json_result['success'] = true;
			$json_result['data'] = __(sprintf('Successfully updated BugHerd settings. <a href="%1$s" target="_blank">click here to preview</a>', get_bloginfo('wpurl')),'wp-bugherd');
		}
		
		//Response
		header('Content-type: application/json');
		echo json_encode($json_result);
		exit;
	}
	
	/**
	 * Reset Bugherd settings and Redirect back to the settings page that was submitted
	 *
	 * @access public
	 * @since 1.0.0.0
	 */
	public static function reset()
	{
		global $wp_version;
		
		if(current_user_can("manage_options"))
		{
			delete_option('bugherd_options');
			
			if (version_compare( $wp_version, '3.0', '>=' ) ) : 
					if (is_multisite() && is_main_site() && current_user_can( 'manage_sites' )) 
					{
						delete_site_option('bugherd_options');
					}
			endif; 
		}
		
		$goback = add_query_arg( 'settings-reset', 'true',  wp_get_referer() );
		wp_redirect( $goback );
		exit;		
	}	
	
	/**
	 * Add BugHerd integration code
	 *
	 * @access public
	 * @since 1.0.0.0
	 */	
	public static function integration()
	{
		global $blog_id, $wp_version;
		
		$allow_integration = false;

		//First see if we have any settings available for current site
		$integration_code = get_option('bugherd_options', array());
		
		//WP 3.0 Multisite support check
		if (version_compare( $wp_version, '3.0', '>=' ) ) :
					if(empty($integration_code) && is_multisite())
					{
						//Do we have any settings specified by Main Site?
						$integration_code = get_site_option('bugherd_options', array());
						if(!empty($integration_code) && is_array($integration_code['sites']))
						{
							$allow_integration = in_array($blog_id, $integration_code['sites']);
						}
						
					}else{
						$allow_integration = true;
					}
		else:
					$allow_integration = true; //older 2.9
		endif;
		
		//check user has entered key
		if(empty($integration_code['bugherd_integration_code']))
		{
			$allow_integration = false;
		}
		
		//Did user selected to enable it for wp admin?
		if(is_admin())
		{
			if(isset($integration_code['enable_admin']) && $integration_code['enable_admin'] != "yes")
			{
				$allow_integration = false;
			}
		}

		//Second check Current user level is allowed
		if($allow_integration === true)
		{
				$allow_integration = false; //Test again
				if(in_array('unregistered', $integration_code['user_levels']))
				{
					$allow_integration = true;
				}else{
					$current_user = wp_get_current_user();
					if(!empty($current_user) && !empty($integration_code['user_levels']))
					{
						foreach($integration_code['user_levels'] as $role)
						{
							if(in_array($role, $current_user->roles))
							{
								$allow_integration = true;
								break;
							}
						}
					}
				}
		}

		if($allow_integration)
		{
						extract($integration_code);

						//User manually pasted javascript
						if(strpos($bugherd_integration_code,'<script') !== false)
						{
							echo $bugherd_integration_code;
						}else{
							
							//User used API so format the javascript
							?>
							<script type='text/javascript'>
									(function (d, t) {
									  var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
									  bh.type = 'text/javascript';
									  bh.src = '//www.bugherd.com/sidebarv2.js?apikey=<?php echo $bugherd_integration_code; ?>';
									  s.parentNode.insertBefore(bh, s);
									  })(document, 'script');
							</script>
							<?php
						}
		}
	}
}

Bugherd_View::init();


?>
