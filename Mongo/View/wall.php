@extends('layouts.default')

@section('content_page')
<!-- CSS for multiple images upload plugin --->
{{ HTML::style('css/jquery.filer.css') }}
<!-- CSS for slider --->
{{ HTML::style('css/owl.theme.css') }}
{{ HTML::style('css/owl.carousel.css') }}
<!-- CSS for images preview popup --->
{{ HTML::style('css/lightbox.css') }}	
<!-- Node js functions script  --->
{{ HTML::script('js/node_functions.js') }}	

<script>
/** File style on video button */
$( document ).ready(function() {
	 $("#wall_video").filestyle({iconName: "glyphicon glyphicon-facetime-video", input:false ,buttonText: 'Add Videos'});
});
</script>

<div class="col-md-7 col-sm-8 profile_wall whitebgequal">
	<div id="scroll_to_image"></div>
	<div class="share-twist">
		@if(route('wall',$userData->slug) != request::url() )
			<a href="{{ route('wall',$userData->slug) }}" >My Feed</a>
			<div class="clearfix"></div>
		@endif
		@if($userData->slug == Auth::user()->slug)
			{{ Form::open(['url' => route('add-wall-post'),'id'=>'wall_post','files'=>'true','snovalidate'=>'snovalidate' ]) }}
				{{ Form::textarea('post','',['class'=>'form-control','placeholder'=>'Write Something...','id'=>'post_text','required'=>'required'])}}
				<!-- div for selected images preview  --->
				<div id="appendDiv" class="hideMe "></div>
				<!-- div for selected images preview  --->
				<div class="row wall-row-mrgin">
					<div class="col-sm-5">
						<div class="wall-post"> 
							<input type="file" name="post_image[]" id="wall_image" multiple="multiple">
							
							<a class="postimage"   href="javascript:void(0);" title="Browse Video">
								<input type="file" accept="video/*"  class="mt5 filestyle file_image filestyle" id="wall_video" name="post_video">
							</a>
						</div>
					</div>
					<div class="col-sm-2">
						<div class="progress hideMe" style="margin-top: 15px; margin-bottom: 0;" id="progress" >
							<div id="progress_bar" class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow=""  aria-valuemin="0" aria-valuemax="100" ></div>
						</div>
					</div>
					<div class="col-sm-5 text-right">
						<span class="select">
							<select name="privacy_status" class="form-control" id="post_add_permission">
								@foreach(Config::get('privacy_status') as $privacyKey => $privacyValue)
									<option value="{{ $privacyKey }}">{{ $privacyValue }}</option>
								@endforeach
								
							</select>
						</span>
						<button type="button" class="btn btn-default rectangle add_post">Post</button>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-2 col-sm-3 col-md-1"></div>
					<div class="col-xs-10 col-sm-9 col-md-11">
						<div id="post_create_error" class="pt10" style="display:none;"></div>
					</div>
				</div>
			{{ Form::close() }}
			<div class="row">
				<div class="col-md-12 post_on">
					<span>Post On</span>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}fb.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}twtr.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}goolge.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}tumbler.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}pintrest.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}socialicon.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}reddit.png" alt="img"></a>
					<a href=""><img src="{{ WEBSITE_IMG_URL }}linkdin.png" alt="img"></a>
				</div>
			</div> 
		@endif
	</div>
	<div id="wall_id">  
		<!-- include wall element  --->
        @include('wall.load_more_posts')
        <!-- include wall element  --->
	</div>
	<p class="copyright hidden-sm hidden-xs">{{ Config::get('Site.copyright_text') }}</p>
</div>
<div class="col-md-3  col-sm-12">
	<div class="right_part whitebgequal">
		<div>
			<h3>Recent Campaigns</h3>
			<div class="recentbox">
				<figure><img src="{{ WEBSITE_IMG_URL }}campaign.jpg" alt="img"></figure>
				<div>
					<h5>James Spann</h5>
					<p>Suspendisse sit amet mi eu urna scelerisque blandit. Praesent nec</p>  
					<ul class="camp-detail">
						<li><strong>$56,678</strong> Contributed</li>
						<li><strong>250%</strong> Funded</li>
						<li><strong>5</strong> Days left</li>
					</ul>
				</div>
			</div>
			<a href=""><img src="{{ WEBSITE_IMG_URL }}show-all1.png" alt="img"> See All</a>
			<div class="clearfix"></div>
		</div>
		<div>
			<h3>Recent Marketplace </h3>
			<div class="recentbox">
				<div class="offer"><img src="{{ WEBSITE_IMG_URL }}offer.png" alt="img"> <span><b>20%</b> OFF</span></div>
				<figure><img src="{{ WEBSITE_IMG_URL }}market-place.jpg" alt="img"></figure>
				<div>
					<h5>James Spann</h5>
					<p>Suspendisse sit amet mi eu urna  scelerisqu blandit. Praesent nec</p>
					<div class="price"><del>US $65.78</del> <span>US $56.87</span></div>
					<span class="percntg"> <img src="{{ WEBSITE_IMG_URL }}percentage.png" alt="img">90%</span>
					<button type="button" class="btn btn-default rectangle">BUY NOW</button>
				</div>
			</div>
			<a href=""><img src="{{ WEBSITE_IMG_URL }}show-all1.png" alt="img"> See All</a>
			<div class="clearfix"></div>
		</div>
		<p class="copyright visible-sm visible-xs">{{ Config::get('Site.copyright_text') }}</p>
	</div>
</div>

<!-- view user photo popup -->
<div class="modal fade media-popup-height" id="viewUserPhotoModal" tabindex="-1" role="dialog">
</div>
<!-- view user photo popup -->
<script>
var imageExtensions	=	"{{ IMAGE_EXTENSION }}";
</script>
<!-- Js for multiple images upload plugin --->
{{ HTML::script('js/jquery.filer.js') }}
{{ HTML::script('js/jquery-filer-custom.js') }}

<!-- Js for filestyle --->
{{ HTML::script('js/bootstrap-filestyle.js') }}
<!-- Js for carousel slider --->
{{ HTML::script('js/owl.carousel.min.js') }}
<!-- Js for preview images on popup  --->
{{ HTML::script('js/lightbox.js') }}
	
<script>

	/** on file select scroll to wall textarea */
	$("#wall_image").change(function(){
        var pos = $("#scroll_to_image").offset().top;
		$('body, html').animate({scrollTop: pos});
		$("#appendDiv").show();
		//reset video field
		$("#wall_video").val('');
		$("#wall_video").filestyle('clear');
	});
	
	/** reset filer div when select video */
	$("#wall_video").change(function(){
        var filerKit = $("#wall_image").prop("jFiler");
		filerKit.reset();
		$("#appendDiv").hide();
	});

	/** remove red border from teaxtarea when click on window  */
	$(".add_post").focusout(function(){
		$('#post_text').removeClass('border-red');
		$('#post_text').val('');
	});
	
	
	/** For display progress bar */
	function OnProgress(event, position, total, percentComplete){ 	
		var progress_bar     = $('#progress_bar');
		$('#progress').show();
		progress_bar.width(percentComplete + '%') //update progressbar percent complete
		progress_bar.html(percentComplete + '%'); //update status text
		progress_bar.css('width',percentComplete + '%'); //change status text to white after 50%
		progress_bar.attr('aria-valuenow',percentComplete); //change status text to white after 50%
	}
	
	/** Define variables for load more posts */
	var loadMorelimit		=   "{{ $limit }}";
	var loadMoreoffset		=   0 + Number(loadMorelimit);
	var feedCount			=	"{{ $feedCount}}";
	/** Define variables for load more posts */
	
	/** function For add post  */
	$(".add_post").click(function(){
		 
		  $("#post_create_error").hide();
		  
		  var progress_bar     = $('#progress_bar');
		  progress_bar.width(0 + '%') //update progressbar percent complete
		  progress_bar.html(0 + '%'); 
		  $btn = $(this);
		 
		  var post    = $('#post_text').val();
		  var post_image   = $('#wall_image').val();
		  var post_video   = $('#wall_video').val();
		  
		  $('#post_text').removeClass('border-red');
		  var post_placeholder = $('#post_text').attr('placeholder');
		
		//validate teaxtarea 
		 if(post.trim() == '' && post_video.trim() == '' && post_image.trim() == ''){
			 $('#post_text').addClass('border-red');
			return false;
		 }
		 if (post == post_placeholder && post_image.trim() != ''){
			   $('#post_text').attr('placeholder','');
			   $('#post_text').val('');
		 }
		if (post == post_placeholder && post_video.trim() != ''){
			  $('#post_text').attr('placeholder','');
			  $('#post_text').val('');
		}
		$btn.button('loading');
		
		  //ajax calling for post the data 
		  var options = {
			uploadProgress: OnProgress, //upload progress callback
			success:function(r){
				
				//$('#progress').hide('slide',{direction: 'left'}, 2000);
				$('#progress').hide();
				$('#wall_no_text').hide();
				$btn.button('reset');
				$(".filestyle").filestyle('clear');
				
				if(r.success == true){
					$("#wall_id").prepend(r.html);
					$('html, body').animate({scrollTop:($("#wall_id :first").offset().top-100)}, 'slow');
					var offset	=	$("#wall_id").find('.active_user').size();
					var limit	=	<?php echo $limit; ?>;
					$('.loadmore').attr('onclick','loadMore('+limit+','+(offset)+')');
					$('.owl-theme').owlCarousel({
						items :4,
						navigation : false,
						navigationText: false,
						itemsCustom : false,
						itemsDesktop : [1370,4],
						itemsDesktopSmall : [1151,4],
						itemsTablet: [992,4],
						itemsTabletSmall: false,
						itemsMobile : [695,5],
						singleItem : false,
						autoPlay:true,
						pagination : false,
						itemsScaleUp : false, 
						stopOnHover :true
					});
					notice('Post Added','Your post has been added successfully.' , 'success');
					/** reset filer div for images */
					var filerKit = $("#wall_image").prop("jFiler");
					filerKit.reset();
				    $("#appendDiv").hide();
				    $('#filer-add-more').html('Add Photos');
					/** reset filer div for images */
					
					if(r.activityId){
						client.emit('wall_post', {activityId : r.activityId , userId : r.userId });
					}
					
					/** increase offset and total count of post bye one for load more posts */
					loadMoreoffset = Number(loadMoreoffset)+Number(1);
					feedCount = Number(feedCount)+Number(1);
				}else{
					
					$('#progress').hide();
					$(".filestyle").filestyle('clear');
					$btn.button('reset');
					error_msg = r.errors;
					notice('Error',error_msg , 'error');
					
				}
			
				$('#post_add_permission option').removeAttr('selected');
				$('#post_add_permission option[value="0"]').attr('selected',true);
				//$('.selectpicker').selectpicker('refresh');
			
		   },
		   resetForm:true
		  };
		  $("form#wall_post").ajaxSubmit(options); 
		  return false;
	 });

	/**
	 * Function to add New Post (NodeJS)
	 * 
	 * @params newPost As New post Data
	 * @params extension As Extension of a file
	 * 
	 * return baseName
	 */
	var activityId = '';
	function addNewPost(activityId){
		if(activityId){
			$.ajax({
				method: "POST", 
				url: '<?php echo route('get-activity'); ?>',
				data:{'activityId':activityId},
				beforeSend:function(){
				},
				success:function(data){
					if(data.success == true){
						$("#wall_id").prepend(data.html);
						$('#wall_no_text').hide();
						var offset	=	$("#wall_id").find('.active_user').size();
						var limit	=	<?php echo $limit; ?>;
						$('.loadmore').attr('onclick','loadMore('+limit+','+(offset)+')');
						
						$('.owl-theme').owlCarousel({
						items :4,
						navigation : false,
						navigationText: false,
						itemsCustom : false,
						itemsDesktop : [1370,4],
						itemsDesktopSmall : [1151,4],
						itemsTablet: [992,4],
						itemsTabletSmall: false,
						itemsMobile : [695,5],
						singleItem : false,
						autoPlay:true,
						pagination : false,
						itemsScaleUp : false, 
						stopOnHover :true
					});
					}
				},
			});
		}
	}

	/**
	 * Function for load more posts when scroll to bottom 
	 */
	$(window).scroll(function() {
	   if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
		 
		   @if(route('wall',$userData->slug) == Request::url())
				var url = "{{ route('wall',$userData->slug) }}"		
			@else
				var url = "{{ route('my-feed',$userData->slug) }}"
			@endif
			
			 if(Number(feedCount) > Number(loadMoreoffset)){
				$.ajax({
				  method: "GET", 
				  url: url,
				  data:{'limit':loadMorelimit,'offset':loadMoreoffset,'load_more':1},
				  beforeSend:function(){
					$(".load_more").html('<div class="clearfix"></div><div style="text-align: center; padding:10px;" id="load_more_button"><a href="javascript:void(0)" class="load_more text-center loadmore"><img src="<?php echo WEBSITE_IMG_URL; ?>admin/ajax-more-loader.gif" alt="Loading"></a></div>');
				  },
				  success:function(data){
					$(".load_more").remove();
					$("#wall_id").append(data).show('slow');
					
				},
				  complete:function(){
					  resizeequalheight();
				  }
				});
				
				console.log(loadMoreoffset);
				loadMoreoffset =	Number(loadMoreoffset) + Number(loadMorelimit);
			}
	   }
	   
	});
	
	/**
	 * Function to open photo popup
	 * @param photoId for id of photo
	 */
	function viewMediaPopup(photoId,postId){
		$.ajax({
			url:"<?php echo route('user-photo-view-popup'); ?>",
			type:'POST',
			data:{'model_id':photoId,'model_name' : 'UserPhoto','post_id' : postId},
			   beforeSend:function(){
				 $('#overlay').show();
			   },
			   
			success:function(data){
				$("#viewUserPhotoModal").html(data);
				$("#viewUserPhotoModal").modal('show');
			  
				$('#popup_container').imagesLoaded()
				.done( function( instance ) {
					$('#whatIDoSectionLoader').hide();
				})
				.progress( function( instance, image ) {
					$('#whatIDoSectionLoader').show();
				}); 
			},
			complete:function(){
				$('#overlay').hide();
			}
		});
	}
	
	/**
	 * Function to delete feed
	 */
	function delete_feeds(data){
		var activity_id	=	$(data).attr('id');
		var feed_type	=	$(data).parents().find('div.active_user').attr('data-feed-type');			
		if(activity_id && feed_type){
			bootbox.confirm("Are you sure to delete post ?",
				function(result){
					if(result){
						$.ajax({
							url: '<?php echo route('delete-feed') ?>',
							data: {'activity_id':activity_id, 'feed_type' : feed_type},
							type: "POST",
							beforeSend:function(){
								
							},
							success: function(response){
								if(response.success){
									$("#feed_"+activity_id).slideUp('slow');
									
									var offset	=	$("#wall_id").find('.active_user:visible').size();
									var limit	=	<?php echo $limit; ?>;
									$('.loadmore').attr('onclick','loadMore('+limit+','+(offset-1)+')');
									
									if(offset < 2){
										$( ".loadmore" ).trigger( "click" );
									}
					
								}
							}
						});	
					}
			}).find("div.modal-content").addClass("wallConfirmBoxWidth"); 
		}
	}
	
	/**
	 * Function to change privacy settings
	*/
	function change_privacy(data, privacy_type){
		
		var activity_id	=	$(data).parent().parent().parent().parent().parent().attr('data-activity-id');
	
		var feed_type	=	$(data).parent().parent().parent().parent().parent().attr('data-feed-type');
		if(activity_id && feed_type){
			bootbox.confirm("Are you sure to change privacy of this post ?",
				function(result){
					if(result){
						$.ajax({
							url: '<?php echo route('change-post-privacy') ?>',
							data: {'activity_id':activity_id, 'feed_type' : feed_type, 'privacy_type' : privacy_type},
							type: "POST",
							success: function(response){
								if(response.success){
									$(data).parent().parent().find('.active').removeClass('active');
									$(data).parent().addClass('active');
									notice('Privacy', 'Privacy changed successfully.','success');
								}
							}
						});	
					}
			}).find("div.modal-content").addClass("wallConfirmBoxWidth"); 
		}
	}

	/**
	 * Function to like photo
	 * status as 1 for like and 0 for unlike
	 */
	function likePhoto(photoId,status){
	var disable = $('#like_button_'+photoId).attr('disabled');
	if(typeof(disable) == 'undefined'){
		$('#like_button_'+photoId).attr('disabled',true);
		$.ajax({
			url	: "<?php echo route('user-photo-like'); ?>",
			type : 'POST',
			data : {'status' : status,'photo_id' : photoId ,'model_name' : 'UserPhoto' },
			beforeSend : function(){
				$('#like_button_'+photoId).attr('disabled',true);
				
			},
			success	: function(data){
				
				var currentLikes 		= parseInt($('#total-likes-'+photoId).text());	
				var currentLikesonPopup 		= parseInt($('.total_likes_span').text());	
				
				if(status == 1){
					//user media popup section start here
					$('.like_txt_span').text('Unlike');
					$('.like_image_tag').attr('src','<?php echo WEBSITE_IMG_URL; ?>unlike.png');
					$('.like_a_tag').attr('onclick','likePhoto("'+photoId+'",0)');
					$('.like_image_tag').attr('title','Unlike');
					$('.total_likes_span').text(currentLikesonPopup+1);
					//user media popup section end here
					
				}else{
					//user media popup section start here
					$('.like_txt_span').text('Like');
					$('.like_image_tag').attr('src','<?php echo WEBSITE_IMG_URL; ?>like.png');
					$('.like_a_tag').attr('onclick','likePhoto("'+photoId+'",1)');
					$('.like_image_tag').attr('title','Like');
					$('.total_likes_span').text(currentLikesonPopup-1);
					//user media popup section end here
					
				}
				$('#like_button_'+photoId).attr('disabled',false);
			}	
		});
	}
} 
	
	/** function for add comment
	@param null 
	 */
	function addPostComment(postId){
		var comment	=	$('#add_comment_textarea_'+postId).val();
		$("#add_comment_textarea_"+postId).removeClass('border-red');
		comment_placeholder	=	$("#add_comment_textarea_"+postId).attr('placeholder');
		
		if (comment == comment_placeholder || $.trim(comment).length == 0 ){
			$("#add_comment_textarea_"+postId).addClass('border-red');
			return false;
		}
		
		$.ajax({
		  method: "POST", 
		  url: "<?php echo route('save-media-comment');?>",
		  data:{'user_comment': comment, 'model_name'  : 'UserPost','model_id' : postId },
		  beforeSend:function(){
			 $("#postSectionLoader_"+postId).show();
		  },
			success:function(data){
			$('#add_comment_textarea_'+postId).val('');
			$("#postSectionLoader_"+postId).hide();
			
			if(data.success == 1){
				$("#comment_error_div").html('');
				$("#comment_box_"+postId).html(data.html);
				var totalComments	=	parseInt($("#total_comment_span_"+postId).text());
				$("#total_comment_span_"+postId).text(totalComments+1);
				
			}else{
				$("#comment_error_div").html(data.errors);
			}
		   },
		  complete:function(){
		  }
		});
	
		
	}
		
	/** function for delete comment
	 * @param commentId 
	 */
	function deletePostComment(commentId,postId,allComments){
		 
		bootbox.confirm("Do you want to delete this comment ?",
		function(result){
			if(result){
				$.ajax({
				  method: "POST", 
				  url: "<?php echo route('delete-media-comment-element');?>",
				  data:{'comment_id': commentId, 'model_name'  : 'UserPost','model_id' : postId,'allcomments' : allComments},
				  beforeSend:function(){
						  $("#postSectionLoader_"+postId).show();
				  },
				  success:function(data){
					   $("#postSectionLoader_"+postId).hide();
					if(data.success == 1){
						$("#cmnt_"+commentId).hide('slow');
						var totalComments	=	parseInt($("#total_comment_span_"+postId).text());
						$("#total_comment_span_"+postId).text(totalComments-1);
						var totalComments	=	parseInt($("#less_cmnt_"+postId).text());
						$("#less_cmnt_"+postId).text(totalComments-1);
						
						$("#comment_box_"+postId).hide().html(data.html).fadeIn(1000);
				
					}
				   },
				  complete:function(){
				  }
				});
			}
		});
	}

	/** For slider when post having more than 4 images */
	$('.owl-theme').owlCarousel({
		items :4,
		navigation : true,
		navigationText: false,
		itemsCustom : false,
		itemsDesktop : [1370,4],
		itemsDesktopSmall : [1151,4],
		itemsTablet: [992,4],
		itemsTabletSmall: false,
		itemsMobile : [695,5],
		singleItem : false,
		autoPlay:true,
		pagination : false,
		itemsScaleUp : false, 
		stopOnHover :true
	});
	/** For slider when post having more than 4 images */	
	
	/** For horizontal scroll bar on wall images div when user select imagres for post */		
	(function($){
		$(window).load(function(){
			$("#appendDiv").mCustomScrollbar({
				horizontalScroll:true,
				axis:"x",
				theme:"light-3",
				advanced:{autoExpandHorizontalScroll:true}
			});	
	});
	})(jQuery);
	/** For horizontal scroll bar on wall images div */

</script>


@stop

@section('content')
		
@include('elements.header_top') 
			
@stop
