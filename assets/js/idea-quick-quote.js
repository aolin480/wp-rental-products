( function( $ ){	
			
	function _c( log ){
		return console.log( log );
	}
	

	// load wp ajax url
	var ajaxurl = qq.ajaxurl;
	var qq_input_id = qq.quick_quote_hidden_id;

	var $sel_category = $( 'select[name=category]' );
	var sel_category = 'select[name=category]';
	
	var $sel_subcategory = $( 'select[name=subcategory]' );
	var sel_subcategory = 'select[name=subcategory]';

	var $sel_product = $( 'select[name=product]' );
	var sel_product = 'select[name=product]';
	
	var $sel_rental_term = $( 'select[name=rental_term]' );
	var sel_rental_term = 'select[name=rental_term]';

	var $input_qty = $( 'input[name=qty]' );
	var input_qty = 'input[name=qty]';

	// populate subcategory select element
	// $( 'body' ).on( 'change', sel_category, function(){
		
	// 	var data = {};
	// 	data[this.name]		= 	this.value;
	// 	data['action']		=	'quick_quote_select';		
	// 	data['_wpnonce']	=	$( this ).data( 'nonce' );
	// 	var posting = 
	// 	$.post(
	//       ajaxurl,
	//       data,	      
	//       function(response) {
	//       	console.log( response.subcategories );
	//          if (response.status === 'error') {
	//             //$('#notification-bar').html('<p>' + responseText.message + '<p>');
	//          }else{
	//          	var sub_cats = response.subcategories;
	//          	$.each( sub_cats, function(i, subcat){
	//          		$sel_subcategory.removeAttr('disabled');
	//          		$sel_subcategory.append( '<option value="' + subcat.slug + '">' + subcat.name + '</option>' );	         		
	//          	} );
	//          }
	//       },
	//       'JSON'
	//    );		

	// } );
	
	
$(document).ready(function(){
	// quick quote select ajax
	$( 'body [data-select-group]' ).on( 'change', '.select select', function(){		
		
		// parent row number
		var parent_row_value =  $( this ).closest( 'tr' ).data( 'select-group' );
		var $parent_row = $('tr[data-select-group='+ parent_row_value + ']')

		var non_ajax_inputs = ['rental_term'];

		var data = {};
		data[this.name]		= 	this.value;

		// wp specific data
		data['action']		=	'quick_quote_select';		
		data['_wpnonce']	=	$( this ).data( 'nonce' );

	  	if( this.name == 'rental_term' ){	      		
      		$parent_row.find(input_qty).focus();      		
      	}

      	if( $.inArray(this.name, non_ajax_inputs) >= 0 ){
			return false;
		}else{
			// start load bar
		    NProgress.configure({
		        parent      : "#nprogress-bar",
		        showSpinner : false
		        })
		    .start();
		}

		var posting = 
		$.post(
	      ajaxurl,
	      data,	      
	      function( response ) {	
	      	// end load bar
	      	NProgress.done();

	      	if( response == null ){	      		
	        }else if (response.status === 'error') {
	            //$('#notification-bar').html('<p>' + responseText.message + '<p>');
	        }else{

	         	// if we are getting the subcategories, populate the subcategory select box and enable it
	         	if( response.subcategories ){
		         	var sub_cats = response.subcategories;
		         	var options = [];
		         	options.push( '<option>Select Subcategory</option>' );		         	
		         	
		         	$.each( sub_cats, function(i, subcat){	 
		         		i = ( i == 0 ) ? i + 1 : i;
		         		$parent_row.find(sel_subcategory).removeAttr('disabled');	         				         		
		         		if( subcat.term_id && subcat.name ){		
		         			//options[i] = '<option value="' + subcat.term_id + '">' + subcat.name + '</option>';
		         			options.push( '<option value="' + subcat.term_id + '">' + subcat.name + '</option>' );
		         		}
		         		
		         	} );
		         	$parent_row.find(sel_subcategory).html( options );	         			         			
	         	}

	         	if( response.products ){
		         	var products = response.products;
		         	var options = [];
		         	options.push( '<option>Select Product</option>' );

		         	$.each( products, function(i, product){	         			
		         		$parent_row.find(sel_product).removeAttr('disabled');	         		
		         		if( product.ID && product.post_title ){
		         			//$parent_row.find(sel_product).append( '<option value="' + product.ID + '">' + product.post_title + '</option>' );	         			         			
		         			options.push( '<option value="' + product.ID + '">' + product.post_title + '</option>' );
		         		}
		         	} );

		         	$parent_row.find(sel_product).html( options );	         			         			
	         	}

	         	if( response.product_check ){
	         		$parent_row.find(sel_rental_term).removeAttr('disabled');
	         	}


	        }
	      },
	      'JSON'
	   );		

	} ); // eof: select ajax

	

		// quantity ajax
		$( 'body [data-select-group]' ).on( 'blur', '.quantity input[name=qty], .qq_rental_term', function( event ){								
			
			// start load bar			
		    NProgress.configure({
		        parent      : "#nprogress-bar",
		        showSpinner : false
		        })
		    .start();	    

			var parent_row_value =  $( this ).closest( 'tr' ).data( 'select-group' );		

			// if product needs to be updated in database grab the reference product ID to replace with new product ID
			var $old_product = $( '[data-select-group=' + parent_row_value + ']' ).attr('data-product');

			// product info		
			var $product_id = $( '[data-select-group=' + parent_row_value + '] .qq_product_id' ).val();
			var $rental_term = $( '[data-select-group=' + parent_row_value + '] .qq_rental_term' ).val();
			
			var data = {};
			
			data['old_product_id'] = ( $old_product ) ? $old_product : null;
			
			data['rental_term'] = $rental_term;
			data['product_id'] = $product_id;
			data['qty'] = this.value;

			// wp specific data
			data['action']		=	'quick_quote_quantity';		
			data['_wpnonce']	=	$( this ).data( 'nonce' );	
			
			var posting = 
			$.post(
		      ajaxurl,
		      data,	      
		      function( response ) {	
		      	// end load bar
		      	NProgress.done();

		      	if( response == null ){	      		
		        }else if (response.status === 'error') {
		            //$('#notification-bar').html('<p>' + responseText.message + '<p>');
		        }else{
					calculate_quick_quote_totals();    		
					$( '#input_1_15' ).val( 'quick_quote' );								
					$( '[data-select-group=' + parent_row_value + ']' ).attr('data-product', $product_id);				
		        }
		      },
		      'JSON'
		   ); // eof: $.post
		

		});

	calculate_quick_quote_totals(); 

}); // eof: document.ready

	function calculate_quick_quote_totals(){
		
		var $qty = 0;
		
		$container = $( '[data-select-group]' );
		$quantity_box = $( '#quick-cart-total' );

		$container.each( function( i, el ){						
			if(parseInt( $( el ).find( 'input[name=qty]' ).val() )){
				$qty = $qty + parseInt( $( el ).find( 'input[name=qty]' ).val() );				
			}			
		} );

		$quantity_box.text( $qty );
	}



} )( jQuery );