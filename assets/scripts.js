jQuery( document ).ready( function( $ ) {

	// On click event for setting a top comment
	$( 'body' ).on( 'click', '.o2-comment-top', function( e ) {
		e.preventDefault();

		// Get the commment ID
		var comment_id = $( this ).data( 'comment_id' );

		// Get the action nonce
		var nonce = $( this ).data( 'nonce' );

		//  Set the data for the ajax request
		var data = {
			'action': 'top-comment',
			'comment_id': comment_id,
			'nonce_data': nonce
		};

		// Post ajax request for setting top comment
		$.post( tfw.ajaxurl, data, function( response ) {

			// Response is comment ID of top comment or 0 on failure
			if( response ) {

				// Get ID of comment container
				var comment_container = '#comment-' + response;

				// Remove currently displayed top comment
				$( comment_container ).closest( '.o2-post-comments' ).find( '.comment-display-top' ).remove();

				// Remove 'top-comment' class from existing comments if necessary
				$( comment_container ).closest( '.o2-post-comments' ).find( '.comment' ).removeClass( 'top-comment' );

				// Add the 'top-comment' class to chosen comment
				$( comment_container ).addClass( 'top-comment' );
			}
		});
	});

	// On click event for removing a top comment
	$( 'body' ).on( 'click', '.o2-comment-top-remove', function( e ) {
		e.preventDefault();

		// Get the commment ID
		var comment_id = $( this ).data( 'comment_id' );

		// Get the action nonce
		var nonce = $( this ).data( 'nonce' );

		//  Set the data for the ajax request
		var data = {
			'action': 'top-comment-remove',
			'comment_id': comment_id,
			'nonce_data': nonce
		};
		// Post ajax request for setting top comment
		$.post( tfw.ajaxurl, data, function( response ) {

			// Response is comment ID of top comment or 0 on failure
			if( response ) {

				// Get ID of comment container
				var comment_container = '#comment-' + response;

				// Remove currently displayed top comment
				$( comment_container ).closest( '.o2-post-comments' ).find( '.comment-display-top' ).remove();

				// Remove 'top-comment' class from top comment
				$( comment_container ).removeClass( 'top-comment' );
			}
		});
	});

	// On click event for viewing the top comment in context
	$( 'body' ).on( 'click', '.top-comment-label a', function( e ) {
		e.preventDefault();

		// Get the ID of the top comment element in context
		var target = '#' + $( this ).data( 'comment_anchor' );

		// Set the scroll destination to 30px above top comment container
		var destination = $ ( target ).offset().top - 30;

		// Animate the scroll to the top comment
		jQuery( 'html:not(:animated),body:not(:animated)' ).animate( { scrollTop: destination}, 800, function() {
			// Update the URL in the address bar to include the comment anchor
	        window.location.hash = target;
	    });
	});
} );