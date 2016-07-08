@extends('layouts.default')
@section('content')
<div class="main">
	@include('elements.left_panel')
	<div class="right-pannel makeequal">
		@include('elements.marketplace-elements.seller_counter')
		<!-- Chart Start -->
		<div class="chart">
			<div class="container-fluid">
				<!-- Manage-sales Start -->
				<div class="whitebg manage_sale">
					<div class="row brdr_btm">
						<div class="col-sm-3">
							<div class="our_sale">Manage Sales</div>
						</div>
						<div class="sale_tab col-sm-7">
							<ul class="nav nav-tabs" role="tablist">
							  <li  id="my_items_section" class="active"><a href="javascript:void(0)" onclick="loadMarketplaceElements('my_items_section')">My Items</a></li>
							  <li id="orders-section"  role="presentation"><a href="javascript:void(0)" onclick="loadMarketplaceElements('orders-section')">Orders</a></li>
							  <li id="analytics-section"  role="presentation"><a href="javascript:void(0)" onclick="loadMarketplaceElements('analytics-section')">Analytics</a></li>
							  <li id="buyers-section"  role="presentation"><a href="javascript:void(0)" onclick="loadMarketplaceElements('buyers-section')">Buyer Request</a></li>
							  <li id="report_problem-section"  role="presentation"><a href="javascript:void(0)" onclick="loadMarketplaceElements('report_problem-section')">Report Problem</a></li>
							  <li id="contest-section"  role="presentation"><a href="javascript:void(0)" onclick="loadMarketplaceElements('contest-section')">Contest</a></li>
							</ul>
						</div>
						<div class="col-sm-2 tabs_btn_sell">
							<a href="{{ route('create-market-place')}}"><button type="button" class="btn btn-default sell_on_anytwist">Sell on Anytwist</button></a>
						</div>
					</div>
					<div class="tab-content">
					</div>
				</div>
			</div>
		</div>
		<!-- Chart End -->
		<!-- Footer Start -->
			@include('elements.footer')
		<!-- Footer End -->
	</div>
</div>
<!-- buyer request modal -->
<div class="modal fade" id="viewOfferModal" tabindex="-1" role="dialog"> 
      
</div>
<!-- view Order popup-->
<div class="modal fade" id="viewOrderModal" tabindex="-1" role="dialog"> 
      
</div>
<!-- view product request popup-->
<div class="modal fade" id="viewProductRequestModal" tabindex="-1" role="dialog"> 
      
</div>
    
    
{{ HTML::script('js/admin/bootbox.js') }}
{{ HTML::script('js/jquery.easypiechart.js') }}	

{{ HTML::script('js/gstatic_charts.js') }}	
{{ HTML::script('js/google_charts_jsapi.js') }}	



@section('header')
	{{ HTML::style('css/admin/bootmodel.css') }}
	{{ HTML::style('css/jquery.easypiechart.css') }}
    
	{{ HTML::style('css/table_responsive.css') }}
@stop

<script>	
	 
	 google.charts.load('current', {'packages':['geochart','corechart','bar']});
    
	function loadMarketplaceElements(element){
		$(".nav-tabs li").removeClass('active');
		$("#"+element).addClass('active');
		$.ajax({
		  method: "GET", 
		  url: "<?php echo route('marketplace-element-load');?>",
		  data:{'element' : element,'response':'<?php echo Input::get('response'); ?>'},
		  beforeSend:function(){
			$("#overlay").show();
		  },
		  success:function(data){
			  $("#overlay").hide(); 
				$(".tab-content").html(data);
				if(element=='analytics-section'){
					drawChart();
				}else if(element=='buyers-section'){
					<?php $noti_request =	 Request::segment(3); ?>
					$('#<?php echo $noti_request; ?>').trigger('click');
				}else if(element=='orders-section'){
					$('#<?php echo Input::get('order_id'); ?>').trigger('click');
				}
			}
		});
	}

	/** if user came to this page to see buyer request
	 when click on its corresonding notifications 
	 then negotiations tab will be load */
	 
	
	@if(Request::segment(2) == 'buyer-request')
		loadMarketplaceElements('buyers-section');
	@elseif(Request::segment(2) == 'order-placed')
		loadMarketplaceElements('orders-section');
	@elseif(Request::segment(2) == 'report_problem-section')
		loadMarketplaceElements('report_problem-section');
	@elseif(Request::segment(2) == 'contest-section')
		loadMarketplaceElements('contest-section');
	@else
		loadMarketplaceElements('my_items_section');
	@endif
			
	function graphFillterByYear(element,section){
		
		var search_by	=	$("#"+section+" .chart_img .yearbox #item_select_year").val()
		
		$(".nav-tabs li").removeClass('active');
		$("#"+element).addClass('active');
		
		$.ajax({
		  method: "GET", 
		  url: "<?php echo route('marketplace-element-load');?>",
		  data:{'element' : element,'section': section ,'search':search_by},
		  beforeSend:function(){
			$("#overlay").show();
		  },
		  success:function(data){
			  $("#overlay").hide();
			   drawChart();
			    $(".chart_tab ul li").removeClass('active');
			    $("#"+section+"_li").parent().addClass('active');
				
			   $(".tab-content div").removeClass('active in');
			  $("#"+section).addClass('tab-pane fade active in');
			}
		});
	}

	 /**
     function for open buyer request view modal
     @param offerId as id of buyer request item 
     */ 
     
	function viewOfferModal(offerId){
		 $.ajax({
		   method: "GET", 
		   url: "<?php echo route('buyers-offer-popup');?>",
		   data:{'offer_id':offerId },
		   beforeSend:function(){
			 $('#overlay').show();
		   },
		  success:function(data){
			  $('#overlay').hide();
			 $("#viewOfferModal").html(data);
			 $("#viewOfferModal").modal('show');
		  },
		  complete:function(){
		 }
		 });
		 
		 
	 }
	
	 /**
     function for open buyer request view modal
     @param offerId as id of buyer request item 
     */ 
     
	function viewrequestedProductDetail(requestId){
		 $.ajax({
		   method: "GET", 
		   url: "<?php echo route('view-product-request-detail');?>",
		   data:{'request_id':requestId },
		   beforeSend:function(){
			 $('#overlay').show();
		   },
		  success:function(data){
			  $('#overlay').hide();
			 $("#viewProductRequestModal").html(data);
			 $("#viewProductRequestModal").modal('show');
		  },
		  complete:function(){
		 }
		 });
		 
		 
	 }
	
	// counter
    jQuery('.count').each(function () {
		jQuery(this).prop('Counter',0).animate({
			Counter: jQuery(this).text()
		}, {
			duration: 3000,
			easing: 'linear',
			step: function (now) {
				jQuery(this).text(Math.ceil(now));
			}
		});
	});
    
    <!-- Scrollbar script -->
	
	resizeequalheight(".makeequal");
	
	$(".navabr_section a.menu_toggle").click(function(){
	   $("body").toggleClass("small-menu");
	});
	
	$(".mobile-menu").click(function(){
	  $("body").addClass("small-menu");
	});

	if(($(window).width() <= 767)){
		  $("body").addClass("small-menu");
	
	}else{
	   $("body").removeClass("small-menu");
	}
				
	
	//basic detail graph
	function marketPlaceDetail(marketPlaceId){
		$(".mr_detail").hide();
		$("#detail_markertplace"+marketPlaceId).toggle("slow");
		$(".items-inner").removeClass('basic_detail');
		$("#marketplace_"+marketPlaceId).addClass("basic_detail");
	}
	
	/**
	 * Function to load more marketplace
	 */
	function loadMore(limit,offset,element){
		var searchValue =	$("#search_box").val();
		$.ajax({
		  method: "GET", 
		  url: "<?php echo route('marketplace-element-load');?>",
		  data:{'search' : searchValue,'limit':limit,'offset':offset,'element' : element, 'load_more' : 1},
		  beforeSend:function(){
			$(".load_more_tr").html('<td colspan="7"><div style="text-align: center; padding:10px;" id="load_more_button"><a href="javascript:void(0)" class="load_more text-center loadmore"><img src="<?php echo WEBSITE_IMG_URL; ?>admin/ajax-more-loader.gif" alt="Loading"></a></div></td>');
		  },
		  success:function(data){
			$(".load_more_tr").remove();
			
			if(element=='my_items_section'){
				$(".add-more-mp").append(data).hide().show('slow');
			}else if(element=='contest-section'){
				$(".add-more-mp").append(data).hide().show('slow');
			}else{
				$(".scroll").append(data).hide().show();
			}
				

		   },
		  complete:function(){
		  }
		});
	}
	
	/**
	 * Function for search marketplace
	 */
	function search(element){
		var searchValue 		=	$("#search_box").val();
		var category_id 	 	=	$("#sort_by_category").val();
		var sub_category_id  	=	$("#sort_by_sub_category").val();
		if(searchValue!=''){
			$.ajax({
				method: "GET", 
				url: "<?php echo route('marketplace-element-load');?>",
				data:{'search':searchValue,'category_id':category_id,'sub_category_id':sub_category_id,'element' : element, 'load_more' : 1},
				beforeSend:function(){
					$("#overlay").show();
					$("#load_more_button").hide();
				},
				success:function(data){
					$("#overlay").hide();
					if(element=='my_items_section'){
						$('.show_delete_icon').hide();
						$('.checkAllUser').prop('checked', false);
						$(".scroll").nextAll("tr").remove();
						$(".scroll").after(data).hide().show('slow');
					}else if(element=='contest-section'){
						$(".scroll").nextAll("tr").remove();
						$(".scroll").after(data).hide().show('slow');
					}else{
						$(".scroll").html(data).hide().show('slow');
					}
				 },
				complete:function(){
				}
			});
		}else{
			loadMarketplaceElements(element);
		}
	}
	/**
	 * Function for sort marketplace
	 */
	function sortMarketPlace(element){
		var orderBy 		 =	$("#sort_marketplace").val();
		var category_id 	 =	$("#sort_by_category").val();
		var sub_category_id  =	$("#sort_by_sub_category").val();
		$.ajax({
			method: "GET", 
			url: "<?php echo route('marketplace-element-load');?>",
			data:{'order_by':orderBy,'category_id':category_id,'sub_category_id':sub_category_id,'element' : element, 'load_more' : 1},
			beforeSend:function(){
				$("#overlay").show();
				//~ $(".load_more_tr").hide();
				$("#load_more_button").hide();
			},
			success:function(data){
				$("#overlay").hide();
				if(element=='my_items_section'){
					$('.show_delete_icon').hide();
					$('.checkAllUser').prop('checked', false);
					$(".scroll").nextAll("tr").remove();
					$(".scroll").after(data).hide().show('slow');
				}else if(element=='contest-section'){
					$(".scroll").nextAll("tr").remove();
					$(".scroll").after(data).hide().show('slow');
				}else{
					$(".scroll").html(data).hide().show('slow');
				}
			},
			complete:function(){
				
			}
		});
	}
	/**
	 * Function for Search Marketplace By Category And Subcategory...
	 */
	function sortByCategory(element){
		var category_id 	 =	$("#sort_by_category").val();
		var orderBy 		 =	$("#sort_marketplace").val();
		$("#sort_by_sub_category").val('');
		$.ajax({
			url  : '<?php echo route('sub-category-search'); ?>',
			data	:{'cat_id':category_id,'element':element},
			type	:'post',
			beforeSend:function(){
				$("#overlay").show();
				$("#load_more_button").hide();
			},
			success	: function(data){
				$('#select_sub_cat').html(data);
			}
		});
		if(category_id ==''){
			loadMarketplaceElements(element);
		}else{
			$.ajax({
				method: "GET", 
				url: "<?php echo route('marketplace-element-load');?>",
				data:{'order_by':orderBy,'category_id':category_id,'element' : element, 'load_more' : 1},
				beforeSend:function(){
					$("#overlay").show();
					//~ $(".load_more_tr").hide();
					$("#load_more_button").hide();
				},
				success:function(data){
					$("#overlay").hide();
					if(element=='my_items_section'){
						$('.show_delete_icon').hide();
						$('.checkAllUser').prop('checked', false);
						$(".scroll").nextAll("tr").remove();
						$(".scroll").after(data).hide().show('slow');
					}else{
						$(".scroll").html(data).hide().show('slow');
					}
				},
				complete:function(){
					
				}
			});
		}	
		//~ sortMarketPlace(element);
	}
	/**
	 * Function for Search Marketplace By Sub Category...
	 */
	function sortBySubCategory(element){
		sortMarketPlace(element);
	}
	
	/**
	 * Function for delete marketplace
	 */
	function deleteMarketplace(marketPlaceId){
		bootbox.confirm("{{ trans('messages.MarketPlace.delete_marketplace_alert_msg') }}",
		function(result){
			if(result){
				$.ajax({
					method: "GET", 
					url: "<?php echo route('marketplace-delete');?>",
					data:{'marketplace_id':marketPlaceId},
					  beforeSend:function(){
						$("#overlay").show();
						//$(".load_more_tr").empty();
					  },
					  success:function(data){
						if(data.lastRecord){
							$(".styled-selectors").hide();
							$("#powerwidgets").html('<tr class="no-records"><td colspan="7"><center><br>{{ trans("messages.global.front_no_record_found") }} <center></td></tr>');
						
						}
						$("#overlay").hide();
						$("#detail_markertplace"+marketPlaceId).hide('slow');
						$("#marketplace_"+marketPlaceId).hide('slow');
						loadMarketplaceElements('my_items_section');
						notice('{{ trans("messages.MarketPlace.add_market_place_notice_title") }}','{{ trans("messages.MarketPlace.remove_market_place_notice_error_msg") }}','success');
					 },
					 complete:function(){
					 }
				});
			}
		}).find("div.modal-content").addClass("deleteMarketPlaceConfirmBoxWidth");
	}
</script>


<!-- menu-toggle height -->
@stop
