( function( $ ) {
	
	$ipt_product_qty = $('.product_quantity input[type=text]');
	
	$ipt_product_qty.on( 'blur', function(){
		if( $(this).val().length == 0 ){
			$(this).val( '0' );
		}
	} );

	/* Update Cart functionality */
	$btn_update = $( '#update-cart' );
	$frm_update = $( '#form-update-cart' );
	$btn_update.on( 'click', function(){
		$frm_update.submit();
		return false;
	} );

	/* Cart Update */
	//qty-547
	$qty_inputs = $( 'input[name^=qty-]' );

	$qty_inputs.on( 'blur', function(){
		$data_quantity = $( this ).attr( 'name' );
		$data_quantity_val = $( this ).val();

		$('input[data-quantity=' + $data_quantity + ']').val( $data_quantity_val );
		console.log( $data_quantity );
	} );

	/* Empty Cart */
	$( '#btn-empty-cart' ).on( 'click', function(){
		var confirm = window.confirm("Are you sure you want to empty your rental cart?");
		
		if( confirm != true ) {
			return false;
		}

	} );

} )( jQuery );