jQuery( function ( $ ) {
	function cl( message ) {
		console.log( message );
	}

	function add_action_to_serialized_data( data, action ) {
		if ( data.length > 0 ) {
			return data + '&action=' + action;
		} else {
			return 'action= ' + action;
		}
	}

	function printError( message ) {
		$( '.notice.notice-error.is-dismissible.shake-shake-baby' ).remove();
		var $div = $( document.createElement( 'div' ) );
		$div.attr( 'class', 'notice notice-error is-dismissible shake-shake-baby' );
		var $p = $( document.createElement( 'p' ) );
		$p.html( message );
		$div.append( $p );
		$( 'div.wrap h1' ).first().after( $div );
	}

	/*
	 * Модальное окно и работа с ним
	 */

	var sourceLangError = [];
	var addedToModal = 0;

	function prepareInfo( postNumber ) {
		var $inlineElement = $( '#inline_' + postNumber );
		var title = $inlineElement.find( ".post_title" ).first().html();

        var $translation_connectors_column = $('#translation-connectors-' + postNumber);

		var author = $translation_connectors_column.attr('data-author');
		var status = $inlineElement.find( '._status' ).first().text();
		var sourceLangName = $translation_connectors_column.attr( 'data-source-language-name' );
		var sourceLangSlug = $translation_connectors_column.attr( 'data-source-language-slug' );

		var translation_slugs = $translation_connectors_column.attr( 'data-translation-slugs' );
		var pll_slugs = $translation_connectors_column.attr( 'data-post-pll-slugs' );
		var isPostHaveAllTranslates = (translation_slugs === pll_slugs);

        var $tr;
		if (!isPostHaveAllTranslates) {
            // noinspection JSUnresolvedVariable
            var isFound = $.inArray( sourceLangSlug + "", SmartcatFrontend.pll_languages_supported_by_sc );

            if ( isFound <= - 1 ) {
                cl( sourceLangSlug );
                cl( SmartcatFrontend.pll_languages_supported_by_sc );

                sourceLangError.push( '<b>' + title + '</b>' );
                //sourceLang не находится в списке допустимых, выделяем title красным
                title = '<span style="color: red">' + title + '</span>';
            }

            $tr = $( document.createElement( 'tr' ) );
            $tr.html( '<td>' + title + '</td><td>' + author + '</td><td>' + status + '</td><td>' + sourceLangName + '</td>' );
            addedToModal++;
		} else {
			$tr = '';
		}

        return $tr;
	}

	function add_post_to_hidden( postNumber ) {
		var $mwPosts = $( '#smartcat-modal-window-posts' );
		var val = $mwPosts.val().toString();
		var posts = (
			val === ''
		) ? [] : val.split( ',' );

		posts.push( postNumber );
		$mwPosts.val( posts.join( ',' ) );
	}

	function checkSourceLanguageError() {
		cl( sourceLangError );
		if ( sourceLangError.length > 0 ) {
			var $modal = $( "#smartcat-modal-window" );
			var $errorDiv = $modal.find( '.smartcat-source-language-error' ).first();

			$errorDiv.html( '' );
			$errorDiv.html( sourceLangError.join( ', ' ) + ' ' + SmartcatFrontend.sourceLanguageNotSupported );
			$errorDiv.css( 'display', 'block' );
			sourceLangError = [];
		}
	}

	function modalWindowHandler( event ) {
		addedToModal = 0;
		var $info = $( "#smartcat-modal-window" );
		var $mwPosts = $( '#smartcat-modal-window-posts' );
		$mwPosts.val( '' );

		$info.dialog( {
			dialogClass: 'wp-dialog',
			height: "auto",
			width: 700,
			modal: true,
			autoOpen: false,
			closeOnEscape: true
		} );

		var $tbody = $info.find( 'table tbody' ).first();
		$tbody.html( '' );
		//var $theList = $('#the-list');

		var isChecked = false;

		if ( event.target.tagName === 'A' ) {
			var $a = $( event.target );
			console.log( $a.closest( 'tr' ) );
			var id = $a.closest( 'tr' ).get( 0 ).id;
			var $regOutput = id.match( /post-(\d+)/i );
			var postNumber = $regOutput[1];

			cl( postNumber );

			add_post_to_hidden( postNumber );

			var $tr = prepareInfo( postNumber );
			$tbody.append( $tr );

			checkSourceLanguageError();
			$info.dialog( 'open' );
		} else {
			$( 'tbody th.check-column input[type="checkbox"]' ).each( function () {
				var $this = $( this );

				if ( $this.prop( "checked" ) ) {
					isChecked = true;
					var postNumber = $( this ).val();
					add_post_to_hidden( postNumber );

					var $tr = prepareInfo( postNumber );
					$tbody.append( $tr );
				}
			} );

			if ( isChecked ) {
				if (addedToModal) {
                    checkSourceLanguageError();
                    $info.dialog( 'open' );
                } else {
                    printError( SmartcatFrontend.postsAreAlreadyTranslated );
				}


			} else {
				printError( SmartcatFrontend.postsAreNotChoosen );
			}
		}

		return false;
	}

	$( 'a.send-to-smartcat-anchor' ).click( function ( event ) {
		modalWindowHandler( event );
		return false;
	} );

	//появление модала
	$( "#doaction, #doaction2" ).click( function ( event ) {
		var $this = $( this );
		var butId = $this.attr( "id" );
		var selName = butId.substr( 2 );

		if ( "send_to_smartcat" === $( 'select[name="' + selName + '"]' ).val() ) {
			modalWindowHandler( event );
			return false;
		}
	} );

	/*
	 * Обработчик "Возможность выбора нескольких языков"
	 */

	var addLanguagesCount = 1;

	// noinspection JSUnusedLocalSymbols
	function add_language_handler( event ) {
		var $this = $( this );
		var $div = $this.parent();

		var $clone = $div.clone();

		$this.removeClass( 'add-language' );
		$this.addClass( 'remove-language' );
		$this.unbind( 'click' );
		$this.click( remove_language_handler );

		if ( SmartcatFrontend.totalLanguages - 1 > addLanguagesCount ) {
			$clone.find( '.add-language' ).first().click( add_language_handler );
			addLanguagesCount ++;
		} else {
			$clone.find( '.add-language' ).first().remove();
		}

		$div.after( $clone );
	}

	// noinspection JSUnusedLocalSymbols
	function remove_language_handler( event ) {
		var $this = $( this );
		var $div = $this.parent();
		var $selectLanguagesBlock = $this.parent().parent();

		addLanguagesCount --;
		var $lastDiv = $selectLanguagesBlock.last();

		var isAddLanguage = $lastDiv.find( '.add-language' ).length;

		if ( ! isAddLanguage ) {
			//alert(111);
			var $addLanguageElement = $( document.createElement( 'div' ) );
			$addLanguageElement.addClass( 'add-language' );
			$addLanguageElement.click( add_language_handler );
			$lastDiv.find( 'select' ).last().after( $addLanguageElement );
			addLanguagesCount ++;
		}

		$div.remove();
	}

	$( '.add-language' ).click( add_language_handler );
	$( '.remove-language' ).click( remove_language_handler );

	var $modalWindow = $( '#smartcat-modal-window' );

	/*
	 * Часть по валидации страницы настроек на фронте
	 */
	$( '.smartcat-connector form[action="options.php"]' ).submit( function ( event ) {
		var $this = $( this );
		var formData = $this.serialize();
		formData = add_action_to_serialized_data(
			formData, SmartcatFrontend.smartcat_table_prefix + 'validate_settings' );

		var workflowStages = ['Translation', 'Editing', 'Proofreading', 'Postediting'];
		var selector = workflowStages.map( function ( name ) {
			return 'input[value=' + name + ']';
		} ).join( ', ' );
		var checkboxList = document.querySelectorAll( selector );

		var isWorkflowsChecked = false;
		for ( var i = 0; i < checkboxList.length; i ++ ) {
			if ( checkboxList[i].checked ) {
				isWorkflowsChecked = true;
				break;
			}
		}

		if ( ! isWorkflowsChecked ) {
			printError( SmartcatFrontend.workflowStagesAreNotSelected );
			event.preventDefault();
			return false;
		}

		// noinspection JSUnusedLocalSymbols
		$.ajax( {
			type: "POST",
			url: ajaxurl,
			data: formData,
			success: function ( responseText ) {
				//var responseJSON = JSON.parse(responseText);
				cl( 'SUCCESS' );

				//в противном случае уходило в бесконечную рекурсию
				$this.unbind( 'submit' );
				$this.submit();
			},
			error: function ( responseObject ) {
				var responseJSON = JSON.parse( responseObject.responseText );
				printError( responseJSON.message );
				cl( 'ERROR' );
				cl( responseObject );
			}
		} );

		event.preventDefault();
		return false;
	} );

	/*
	 * Обработчик самого модала
	 */

	$modalWindow.find( 'form' ).first().submit( function ( event ) {
		var $this = $( this ); //форма
		var formData = $this.serialize();
		formData = add_action_to_serialized_data(
			formData, SmartcatFrontend.smartcat_table_prefix + 'send_to_smartcat' );
		cl( formData );

		$.ajax( {
			type: "POST",
			url: ajaxurl,
			data: formData,
			success: function ( responseText ) {
				cl( 'success' );
				cl( responseText );
				$this.parent().dialog( 'close' );
				//window.location.href = SmartcatFrontend.adminUrl + '/admin.php?page=sc-translation-progress';
			},
			error: function ( responseObject ) {
				cl( 'error' );
				var responseJSON = JSON.parse( responseObject.responseText );
				printError( responseJSON.message );
				alert( responseJSON.message );
			}
		} );

		event.preventDefault();
		return false;
	} );

	/*
	 * Фронт страницы статистики
	 */

	var refreshStatButton = $( '#smartcat-connector-refresh-statistics' );
	var intervalTimer;
	var isStatWasStarted = false;

	function checkStatistics() {
		$.ajax( {
			type: "POST",
			url: ajaxurl,
			data: {
				action: SmartcatFrontend.smartcat_table_prefix + 'check_statistic'
			},
			success: function ( responseText ) {
				cl( 'SUCCESS' );
				var responseJSON = JSON.parse( responseText );
				var isActive = responseJSON.data.statistic_queue_active;

				if ( ! isActive ) {
					clearInterval( intervalTimer );
					isStatWasStarted = false;
					//refreshStatButton.prop('disabled', false);
					window.location.reload();
				}
			},
			error: function ( responseObject ) {
				cl( 'ERROR' );
				var responseJSON = JSON.parse( responseObject.responseText );
				printError( responseJSON.message );

				if ( intervalTimer ) {
					clearInterval( intervalTimer );
				}

				refreshStatButton.prop( 'disabled', false );
			}
		} );
	}

	function updateStatistics() {
		$.ajax( {
			type: "POST",
			url: ajaxurl,
			data: {
				action: SmartcatFrontend.smartcat_table_prefix + 'start_statistic'
			},
			success: function ( responseText ) {
				cl( 'SUCCESS' );
				var responseJSON = JSON.parse( responseText );
				cl( responseJSON );

				if ( responseJSON.message === 'ok' ) {
					if ( ! intervalTimer ) {
						intervalTimer = setInterval( checkStatistics, 5000 );
					}
				}
			},
			error: function ( responseObject ) {
				cl( 'ERROR' );
				var responseJSON = JSON.parse( responseObject.responseText );
				printError( responseJSON.message );
			}
		} );
	}

	function refreshTranslation( id ) {
		$.ajax( {
			type: "POST",
			url: ajaxurl,
			data: {
				stat_id: id,
				action: SmartcatFrontend.smartcat_table_prefix + 'refresh_translation'
			},
			success: function ( responseText ) {
				cl( 'SUCCESS' );
				var responseJSON = JSON.parse( responseText );
				cl( responseJSON );
			},
			error: function ( responseObject ) {
				cl( 'ERROR' );
			}
		} );
	}
	
	$('.refresh_stat_button').each(function () {
		$(this).on('click', function () {
			refreshTranslation($(this).data('bind'));
			location.reload();
		});
	});

	//проверяем на существование, что мы точно на странице статистики
	if ( refreshStatButton.length ) {
		isStatWasStarted = refreshStatButton.is( ':disabled' );

		refreshStatButton.click( function ( event ) {
			//если уже получаем статистику - ничего не делать
			if ( isStatWasStarted ) {
				event.preventDefault();
				return false;
			}

			isStatWasStarted = true;
			var $this = $( this );
			$this.prop( 'disabled', true );

			updateStatistics();

			event.preventDefault();
			return false;
		} );

		//если статистика была запущена уже в первый запуск
		if ( isStatWasStarted ) {
			intervalTimer = setInterval( checkStatistics, 5000 );
		}

		let timerStarted = false;

		if ( !isStatWasStarted ) {
			pageIntervalReload = setInterval( function () {
				if ( isStatWasStarted ) {
					event.preventDefault();
					return false;
				}

				if (timerStarted) {
					timerStarted = true;
					return false;
				}

				isStatWasStarted = true;
				var $this = $( this );
				$this.prop( 'disabled', true );

				updateStatistics();

				event.preventDefault();
				location.reload()
			}, 1000 * 300 );
		}
	}

} );