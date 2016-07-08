@extends('layouts.default')

@section('content')
<div class="home_page_flash">
	@include('elements.flash_message')		
</div>

@include('elements.header_medium')

<div class="content">
	<div id="carousel-example-generic" class="carousel">
		<!-- Indicators -->
		<!-- Wrapper for slides -->
		<div class="carousel-inner" role="listbox">
			@if(!empty($sliders))
				<?php $i	=	1; ?>
				@foreach($sliders as $result)
					<div class="item <?php if($i==1){ echo 'active';} ?>" >
						<figure>
							<img src="<?php echo WEBSITE_URL .'image.php?width=1920px&height=926px&image=' . SLIDER_URL.$result['image'];?>" alt="">
						</figure>
						<div class="carousel-caption">	
							<?php $description	=	$result['description']; ?>
							<h1>{{ trans("messages.$description") }}</h1>
						</div>
					</div>
				<?php $i++; ?>
				@endforeach
			@endif	
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="container slide-bottom">
		
		<ol class="bottom-part">
			<p class="last-bot">
				{{ (!empty($blocks) && isset($blocks['slider-text'])) ? $blocks['slider-text']['description'] : '' }}
			</p>
			 <a href="{{ URL::to('signup')}}">
				<button type="button" class="btn btn-default ">{{ trans("messages.Get Started Now") }}</button>
			</a>
			
			<a href="{{ URL::to('how-it-works')}}" class="padleft15">
				<button type="button" class="btn btn-green ">{{ trans("messages.How It Works") }}</button>
			</a>
		</ol>
		<div class="social social-like">
			<span>
				@include('elements.social.pinterest')	
			</span>
			
			<span>
				@include('elements.social.googleplus')
			</span>
			
			<span>
				@include('elements.social.facebook_like')
			</span>
		</div>
	</div>	
</div>
<script type="text/javascript">
	/* For home page slider */
	$(function() {
	<?php	
		/* Set cookie for homepage video */
	  if (!isset($_COOKIE['user_visited_on_homepage'])){
		$cookie = 1;
		setcookie("user_visited_on_homepage", $cookie , (time() + 1800));    ?>
		
		$("#homePageVideo").modal("show");
		$('.carousel').carousel('pause');
   <?php  }else{  ?>
		$('.carousel').carousel('cycle');
   <?php  }  ?>
		
		/* For play home page slider */
		$('#homePageVideo').on('hidden.bs.modal', function (e) {
			$('.carousel').carousel('cycle');
		});
	}); 
</script>

<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="homePageVideo">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<button aria-label="Close" data-dismiss="modal" class="close" type="button" id="playButton">
            <img src="<?php echo WEBSITE_IMG_URL; ?>close-popup.png" alt="" /></button>
            
			<div class="modal-body">
				<?php $videoDetails	=	CustomHelper::getPageVideo('home_page'); ?>
				@if(!empty($videoDetails) && isset($videoDetails->home_video) && isset($videoDetails->home_video_thumb))
				<video width="100%" height="450" class="video-responsive" poster="{{ FIXED_VIDEO_THUMB_URL . $videoDetails->home_video_thumb }}" controls >
					<source src="{{ FIXED_VIDEO_MP4_URL . $videoDetails->home_video }}.mp4" type="video/mp4">
					<source src="{{ FIXED_VIDEO_WEBM_URL . $videoDetails->home_video }}.webm" type="video/webm">
					Your browser does not support the video tag.
				</video>
				@endif
				
			</div>
		</div>
    </div>
</div>
<!-- social script start here-->
@include('elements.social.facebook_like_script')
@include('elements.social.googleplus_script')
@include('elements.social.twitter_script')
<!-- social script end here-->

@stop
