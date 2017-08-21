jQuery( document ).ready( function( $ ) {
	$( 'body' ).on( 'click', '.o2-comment-top', function( e ) {
		e.preventDefault();
		var comment_id = $( this ).data( 'comment_id' );
		var data = {
			'action': 'top-comment',
			'comment_id': comment_id
		};

		$.post( tfw.ajaxurl, data, function( response ) {
			if( response ) {
				var comment_container = '#comment-' + response;
				$( '#comment-' + response ).parent( '.o2-post-comments' ).find( '.comment' ).removeClass( 'top-comment' );
				$( '#comment-' + response ).addClass( 'top-comment' );
			}
		});
	});

	$( 'body' ).on( 'click', '.o2-comment-top-remove', function( e ) {
		e.preventDefault();
		var comment_id = $( this ).data( 'comment_id' );
		var data = {
			'action': 'top-comment-remove',
			'comment_id': comment_id
		};

		$.post( tfw.ajaxurl, data, function( response ) {
			if( response ) {
				var comment_container = '#comment-' + response;
				$( '#comment-' + response ).parent( '.o2-post-comments' ).find( '.comment-display-top' ).remove();
				$( '#comment-' + response ).removeClass( 'top-comment' );
			}
		});
	});

	$( 'body' ).on( 'click', '.top-comment-label a', function( e ) {
		e.preventDefault();
		var target = '#' + $( this ).data( 'comment_anchor' );
		var destination = $ ( target ).offset().top - 30;
		jQuery( 'html:not(:animated),body:not(:animated)' ).animate( { scrollTop: destination}, 800, function() {
	        window.location.hash = target;
	    });
	});
} );