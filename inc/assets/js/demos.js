( function( $ ) {

	"use strict";



	$( document ).ready( function() {
		cdiDemoImport.init();
		


	} );

	var cdiDemoImport = {

		importData: {},
		importTexts: {},
		allowPopupClosing: true,

		init: function() {
			var that = this;
			

			$(document).on('click','.cdi-import',function(){
				$('.cp-importer-form-wrapper').removeClass('cp-active');
			});

			// Prevent the popup from showing when the live preview button
			$( '.cdi-demo-wrap .theme-actions a.button' ).on( 'click', function( e ) {
				e.stopPropagation();
			} );

			// Get demo data
			$( '.cdi-open-popup' ).click( function( e ) {
				e.preventDefault();
				$(this).addClass('updating-message');

				// Vars
				var $selected_demo 		= $( this ).data( 'demo-id' ),
				$loading_icon 		= $( '.preview-' + $selected_demo ),
				$disable_preview 	= $( '.preview-all-' + $selected_demo );

				$loading_icon.show();
				$disable_preview.show();
				
				that.getDemoData( $selected_demo );
				

			} );

			$( document ).on( 'click'						, '.demo-content-wrapper .preview-btn, .demo-content-wrapper .cdi-preview-url',this.demoIframeLoad);
			$( document ).on( 'click'						, '.cdi-popup-preview .close-popup',this.demoIframeClose);
			$( document ).on( 'click' 						, '.install-now', this.installNow );
			$( document ).on( 'click'						, '.cdi-reset-database-wrapper a.cdi-db-reset,a.cdi-reset-cancel',this.dbResetBoxToggle);
			$( document ).on( 'click'						, '.cdi-demo-confirm-message a.cdi-reset-confrm',this.dbResetTrigger);
			$( document ).on( 'click' 						, '.install-offline', this.installProPlugins );
			$( document ).on( 'click' 						, '.activate-now', this.activatePlugins );
			$( document ).on( 'wp-plugin-install-success'	, this.installSuccess );
			$( document ).on( 'wp-plugin-installing' 		, this.pluginInstalling );
			$( document ).on( 'wp-plugin-install-error'		, this.installError );

		},


		// Get demo data.
		getDemoData: function( demo_name ) {
			var that = this;

			// Get import data
			$.ajax( {
				url: centuryLoc.ajaxurl,
				type: 'get',

				data: {
					action: 'cdi_ajax_get_import_data',
					demo_name: demo_name,
					security: centuryLoc.cdi_import_data_nonce
				},

				complete: function( data ) {
					that.importData = $.parseJSON( data.responseText );
					that.importTexts = $.parseJSON( data.responseText );
					$('.cdi-open-popup').removeClass('updating-message');
				}
			} );

			// Run the import
			$.ajax( {
				url: centuryLoc.ajaxurl,
				type: 'get',

				data: {
					action : 'cdi_ajax_get_demo_data',
					demo_name: demo_name,
					demo_data_nonce: centuryLoc.demo_data_nonce
				},

				complete: function( data ) {
					that.runPopup( data );

					// Vars
					var $loading_icon 		= $( '.preview-' + demo_name ),
					$disable_preview 	= $( '.preview-all-' + demo_name );

					// Hide loader
					$loading_icon.hide();
					$disable_preview.hide();
				}

			} );

		},

		//load demo iframe
		demoIframeLoad: function(e){
			e.preventDefault();
			$('body').addClass('cdi-demo-overflow');
			var srcUrl = $(this).attr('href');
			var popupDiv = $('.cdi-popup-preview');
			popupDiv.find('iframe').attr('src',srcUrl);
			popupDiv.removeClass('hidden');

			
			setTimeout(function(){
				popupDiv.removeClass('import-php');
			}, 3000);
		},

		//close demo iframe
		demoIframeClose: function(e){
			e.preventDefault();


			$('body').removeClass('cdi-demo-overflow');
			$('.cdi-popup-preview').addClass('hidden');
			var srcUrl = '';
			var popupDiv = $('.cdi-popup-preview');
			popupDiv.find('iframe').attr('src',srcUrl);
			$('.cdi-popup-preview').addClass('import-php');
		},

		//db reset popop open and close 
		dbResetBoxToggle: function(e){
			$('.cdi-demo-confirm-message').toggleClass('active');
		},

		//db reset trigger
		dbResetTrigger: function(e){
			$('.cdi-demo-confirm-message .cdi-msg-btn-wrapp').hide();
			$('.cdi-demo-confirm-message .cdi-reset-progress').show();

			$.ajax( {
				url: centuryLoc.ajaxurl,
				type: 'post',
				data: {
					action: 'cdi_demo_data_reset'
				},
				complete: function( data ) {
					$('.cdi-demo-confirm-message .cdi-reset-progress .reset-info').html(centuryLoc.reset_success);
					location.reload();
				}
			} );


		},

		// Run popup.
		runPopup: function( data ) {
			var that = this

			var innerWidth = $( 'html' ).innerWidth();
			$( 'html' ).css( 'overflow', 'hidden' );
			var hiddenInnerWidth = $( 'html' ).innerWidth();
			$( 'html' ).css( 'margin-right', hiddenInnerWidth - innerWidth );

			// Show popup
			$( '#cdi-demo-popup-wrap' ).addClass('cp-popup-show');
			$( data.responseText ).appendTo( $( '#cdi-demo-popup-content' ) );

			// Close popup
			$( '.cdi-demo-popup-close, .cdi-demo-popup-overlay' ).on( 'click', function( e ) {
				e.preventDefault();
				if ( that.allowPopupClosing === true ) {
					that.closePopup();
				}
			} );

			// Display the step two
			$( '.cdi-plugins-next' ).on( 'click', function( e ) {
				e.preventDefault();
				
				var pluginCurrentClass = $('.cdi-required-plugins button');
				if( pluginCurrentClass.hasClass('activate-now') || pluginCurrentClass.hasClass('install-offline') || pluginCurrentClass.hasClass('install-now') ){
					$('.msg-wrapp').addClass('active');
					$('.msg-wrapp .pl-install-wraning').html(centuryLoc.plugin_activate_info);
					return;
				}
				
				// Hide step one
				$( '#cdi-demo-plugins' ).hide();

				// Display step two
				$( '.cp-importer-form-wrapper' ).addClass('cp-active');
				$('.cdi-loader').show();

			} );

			// if clicked on import data button
			$( '#cdi-demo-import-form' ).submit( function( e ) {
				e.preventDefault();


				// Vars
				var demo 	= $( this ).find( '[name="cdi_import_demo"]' ).val(),
				nonce 	= $( this ).find( '[name="cdi_import_demo_data_nonce"]' ).val(),
				contentToImport = [];

				// Check what need to be imported
				$( this ).find( 'input[type="checkbox"]' ).each( function() {
					if ( $( this ).is( ':checked' ) === true ) {
						contentToImport.push( $( this ).attr( 'name' ) );
					}
				} );
				

				// Hide the checkboxes and show the loader
				$( this ).hide();
				$( '.cdi-loader' ).show();
				$( '.cdi-loader' ).removeClass('cp-hidden');
				$( '.cdi-loader' ).addClass('cp-active');

				//pass values to display current demo importing progress texts
				that.importingDemoContents({
					demo: demo,
					nonce: nonce,
					contentToImport: contentToImport,
					isXML: $( '#cdi_import_xml' ).is( ':checked' )
				});

				// Start importing the content
				that.importContent( {
					demo: demo,
					nonce: nonce,
					contentToImport: contentToImport,
					isXML: $( '#cdi_import_xml' ).is( ':checked' )
				} );


			} );

		},

		// importing the content.
		importContent: function( importData ) {
			var that = this,
			currentContent,
			importingLimit,
			timerStart = Date.now(),
			ajaxData = {
				cdi_import_demo: importData.demo,
				cdi_import_demo_data_nonce: importData.nonce
			};

			this.allowPopupClosing = false;
			$( '.cdi-demo-popup-close' ).fadeOut();

			// When all the selected content has been imported
			if ( importData.contentToImport.length === 0 ) {
				
				// Show the imported screen after 1 second
				setTimeout( function() {
					$( '.cdi-loader' ).hide();
					$( '.cdi-last' ).removeClass('cp-hidden');
					$( '.cdi-last' ).addClass('cp-active');
				}, 1000 );

				// Notify the server that the importing process is complete
				$.ajax( {
					url: centuryLoc.ajaxurl,
					type: 'post',
					data: {
						action: 'cdi_after_import',
						cdi_import_demo: importData.demo,
						cdi_import_demo_data_nonce: importData.nonce,
						cdi_import_is_xml: importData.isXML
					},
					complete: function( data ) {}
				} );

				this.allowPopupClosing = true;
				$( '.cdi-demo-popup-close' ).fadeIn();

				return;
			}


			// Check the content that was selected to be imported.
			for ( var key in this.importData ) {

				// Check if the current item in the iteration is in the list of importable content
				var contentIndex = $.inArray( this.importData[ key ][ 'input_name' ], importData.contentToImport );

				// If it is:
				if ( contentIndex !== -1 ) {

					// Get a reference to the current content
					currentContent = key;

					// Remove the current content from the list of remaining importable content
					importData.contentToImport.splice( contentIndex, 1 );

					// Get the AJAX action name that corresponds to the current content
					ajaxData.action = this.importData[ key ]['action'];

					// After an item is found get out of the loop and execute the rest of the function
					break;
				}
			}

			

			// Tell the user which content is currently being imported
			//$( '.cdi-import-status' ).append( '<p class="cdi-importing">' + this.importData[ currentContent ]['loader'] + '</p>' );
			$( '.cdi-import-status .cdi-importing-text.demo-id'+currentContent ).addClass('cdi-importing');

			var loaderImporting = '<i class="dashicons dashicons-update-alt"></i>';
			var importedLoader = '<i class="dashicons dashicons-yes-alt"></i>';
			//add loader for importing demos contents
			$( '.cdi-import-status .cdi-importing').prepend( loaderImporting );
			$( '.cdi-import-status .cdi-imported i').remove();
			$( '.cdi-import-status .cdi-imported').prepend( importedLoader );


			// Tell the server to import the current content
			var ajaxRequest = $.ajax( {
				url: centuryLoc.ajaxurl,
				type: 'post',
				data: ajaxData,
				complete: function( data ) {
					clearTimeout( importingLimit );

					// Indicates if the importing of the content can continue
					var continueProcess = true;

					// Check if the importing of the content was successful or if there was any error
					if ( data.status === 500 || data.status === 502 || data.status === 503 ) {
						$( '.cdi-importing' )
						.addClass( 'cdi-importing-failed' )
						.removeClass( 'cdi-importing' )
						.text( centuryLoc.content_importing_error + ' '+ data.status );
					} else if ( data.responseText.indexOf( 'successful import' ) !== -1 ) {
						$( '.cdi-importing' ).addClass( 'cdi-imported' ).removeClass( 'cdi-importing' );
					} else {
						var errors = $.parseJSON( data.responseText ),
						errorMessage = '';

						// Iterate through the list of errors
						for ( var error in errors ) {
							errorMessage += errors[ error ];

							// If there was an error with the importing of the XML file, stop the process
							if ( error === 'xml_import_error' ) {
								continueProcess = false;
							}
						}

						// Display the error message
						$( '.cdi-importing' )
						.addClass( 'cdi-importing-failed' )
						.removeClass( 'cdi-importing' )
						.text( errorMessage );

						that.allowPopupClosing = true;
						$( '.cdi-demo-popup-close' ).fadeIn();
					}

					// Continue with the loading only if an important error was not encountered
					if ( continueProcess === true ) {

						// Load the next content in the list
						that.importContent( importData );
					}

				}
			} );

			// Set a time limit of 15 minutes for the importing process.
			importingLimit = setTimeout( function() {

				// Abort the AJAX request
				ajaxRequest.abort();

				// Allow the popup to be closed
				that.allowPopupClosing = true;
				$( '.cdi-demo-popup-close' ).fadeIn();

				$( '.cdi-importing' )
				.addClass( 'cdi-importing-failed' )
				.removeClass( 'cdi-importing' )
				.text( centuryLoc.content_importing_error );
			}, 15 * 60 * 1000 );

		},

		//display importing demo contents
		importingDemoContents: function(importData){

			var currentContent;

				// Check the content that was selected to be imported.
				for ( var key in this.importData ) {

				// Check if the current item in the iteration is in the list of importable content
				var contentIndex = $.inArray( this.importData[ key ][ 'input_name' ], importData.contentToImport );
				

				// If it is:
				if ( contentIndex !== -1 ) {

					// Get a reference to the current content
					currentContent = key;
					
					var contentDisp = this.importData;
					var demoTextsContent = contentDisp[currentContent]['loader'];
					
					$( '.cdi-import-status' ).append( '<p class="cdi-importing-text demo-id'+currentContent+'"><span class="dashicons dashicons-backup"></span>' + demoTextsContent + '</p>' );

				}
			}


		},

		// Close demo popup.
		closePopup: function() {
			$( 'html' ).css( {
				'overflow': '',
				'margin-right': '' 
			} );

			// Hide loader
			$( '.preview-icon' ).hide();
			$( '.preview-all' ).hide();

			
			// Remove content in the popup
			setTimeout( function() {
				$('#cdi-demo-popup-wrap').removeClass('cp-popup-show');
				$( '#cdi-demo-popup-content' ).html( '' );

			}, 600);
		},




		

		// Install required plugins.
		installNow: function( e ) {
			e.preventDefault();

			// Vars
			var $button 	= $( e.target ),
			$document   = $( document );

			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			if ( wp.updates.shouldRequestFilesystemCredentials && ! wp.updates.ajaxLocked ) {
				wp.updates.requestFilesystemCredentials( e );

				$document.on( 'credential-modal-cancel', function() {
					var $message = $( '.install-now.updating-message' );

					$message
					.removeClass( 'updating-message' )
					.text( wp.updates.l10n.installNow );

					wp.a11y.speak( wp.updates.l10n.updateCancel, 'polite' );
				} );
			}

			wp.updates.installPlugin( {
				slug: $button.data( 'slug' )
			} );
		},

		//install pro plugins
		installProPlugins: function(e){
			e.preventDefault();

			var $button 			= $( e.target ),
			 	el 					= $(this),
			 	pluginCurrentClass 	= $('.cdi-required-plugins button');

		 	if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			var is_loading = true;
			el.addClass('updating-message');

			//disable activating plugins if one plugin is in process
			if( $button.hasClass('updating-message') ){
				if( pluginCurrentClass.hasClass('activate-now') || pluginCurrentClass.hasClass('install-now') ){
					pluginCurrentClass.addClass('button-disabled');
					$button.removeClass('button-disabled');
				}
			}

			var file_location 	= el.attr('data-href');
			var file 			= el.attr('data-file');
			var host_type 		= (el.attr('data-host-type') === undefined) ? 'remote' : el.attr('data-host-type');
			var class_name 		= el.attr('data-class');
			var slug 			= el.attr('data-slug');
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'plugin_offline_installer',
					file_location: file_location,
					file: file,
					host_type: host_type,
					class_name: class_name,
					slug: slug,
					dataType: 'json'
				},
				success: function(response) {

					if(response == 'success'){

						el.attr('class', 'button disabled');
						el.html(centuryLoc.button_activated);
						if( pluginCurrentClass.hasClass('activate-now') || pluginCurrentClass.hasClass('install-now') ){
							pluginCurrentClass.removeClass('button-disabled');	
						}

					}

					is_loading = false;
			   		//location.reload();
			   	},
			   	error: function(xhr, status, error) {
			   		el.removeClass('updating-message');
			   		is_loading = false;
			   	}
			   });

		},



		// Activate required plugins.
		activatePlugins: function( e ) {
			e.preventDefault();

			// Vars
			var $button 		= $( e.target ),
			$init 				= $button.data( 'init' ),
			$slug 				= $button.data( 'slug' ),
			pluginCurrentClass 	= $('.cdi-required-plugins button');
			
			


			if ( $button.hasClass( 'updating-message' ) || $button.hasClass( 'button-disabled' ) ) {
				return;
			}

			$button.addClass( 'updating-message button-primary' ).html( centuryLoc.button_activating );
			
			//disable activating plugins if one plugin is in process
			if( $button.hasClass('updating-message') ){
				if( pluginCurrentClass.hasClass('activate-now') || pluginCurrentClass.hasClass('install-now') ){
					pluginCurrentClass.addClass('button-disabled');
					$button.removeClass('button-disabled');
				}
			}

			$.ajax( {
				url: centuryLoc.ajaxurl,
				type: 'POST',
				data: {
					action : 'cdi_ajax_required_plugins_activate',
					init   : $init,
				},
			} ).done( function( result ) {

				if ( result.success ) {

					$button.removeClass( 'button-primary install-now activate-now updating-message' )
					.attr( 'disabled', 'disabled' )
					.addClass( 'disabled' )
					.text( centuryLoc.button_active );


					if( pluginCurrentClass.hasClass('activate-now') || pluginCurrentClass.hasClass('install-now') ){
						pluginCurrentClass.removeClass('button-disabled');	
					}
						//window.location.reload();
					}

				} );
		},

		// Install success.
		installSuccess: function( e, response ) {
			e.preventDefault();

			var $message = $( '.cdi-plugin-' + response.slug ).find( '.button' );

			// Transform the 'Install' button into an 'Activate' button.
			var $init = $message.data('init');

			$message.removeClass( 'install-now installed button-disabled updated-message' )
			.addClass( 'updating-message' )
			.html( centuryLoc.button_activating );

			// WordPress adds "Activate" button after waiting for 1000ms. So we will run our activation after that.
			setTimeout( function() {

				$.ajax( {
					url: centuryLoc.ajaxurl,
					type: 'POST',
					data: {
						action : 'cdi_ajax_required_plugins_activate',
						init   : $init,
					},
				} ).done( function( result ) {

					if ( result.success ) {

						$message.removeClass( 'button-primary install-now activate-now updating-message' )
						.attr( 'disabled', 'disabled' )
						.addClass( 'disabled' )
						.text( centuryLoc.button_active );

							//window.location.reload();

						} else {
							$message.removeClass( 'updating-message' );
						}

					} );

			}, 1200 );
		},

		// Plugin installing.
		pluginInstalling: function( e, args ) {
			e.preventDefault();

			var $card = $( '.cdi-plugin-' + args.slug ),
			$button = $card.find( '.button' );

			$button.addClass( 'updating-message' );
		},

		// Plugin install error.
		installError: function( e, response ) {
			e.preventDefault();

			var $card = $( '.cdi-plugin-' + response.slug );

			$card.removeClass( 'button-primary' ).addClass( 'disabled' ).html( wp.updates.l10n.installFailedShort );
		}

	};

} ) ( jQuery );