@extends('layouts.default')

@section('content')

@include('elements.header_large')

<!-- falsh message element-->
	@include('elements.flash_message')
<!--flash message elemnet end here-->

<!-- scroll bar js end css start here-->
{{ HTML::script('js/enscroll-0.6.1.min.js') }}
{{ HTML::style('css/scroll.css') }}
<!-- scroll bar js and css end  here-->

<script>
$(document).ready(function() {
	/*  For Read More Link */
	$('[id^="more_"]').on('click',function(e) {
		 e.stopPropagation();				
		var id	=	$(this).attr('id').replace('more_','');
		$('#first_'+id).hide();
		$('#second_'+id).show();
		$(this).hide();
	});
	$('[id^="less_"]').on('click',function() {
		var id	=	$(this).attr('id').replace('less_','');
		$('#more_'+id).show();
		$('#first_'+id).show();
		$('#second_'+id).hide();
    });		
	
	/* for scroll  */
	$('.scrollbox').enscroll({
		verticalTrackClass: 'track4',
		verticalHandleClass: 'handle4',
		minScrollbarLength: 28,
	});
});

 // this function  is use for open popup for delete 
function deletes(title,id,model,confirm_text,success_messages){
	$("#heading").html(title);
	$("#confirm_text").html(confirm_text);
	var url = "<?php echo URL::to('delete-user-info')?>"+'/'+model+'/'+id;
	$("#confirm_yes").attr({ "href": url, "title": title, "messages": success_messages,"element" : model+'_'+id});
}

// function for hide confirm box and delete
function confirmDelete(){
	$("#del-brochure").modal("hide");
	var elementId	= $("#confirm_yes").attr("element");
	var url	= $("#confirm_yes").attr("href");
	var title =	$("#confirm_yes").attr("title");
	var messages = $("#confirm_yes").attr("messages");
	$.ajax({
		url:url,
		beforeSend:function(){
			$("#overlay").show();
		},
		success:function(data){
			$('#'+elementId).remove();
			$("#overlay").hide();
			$('li.active').find('a:first').trigger('click');
			$("#detail_"+data.id).remove();
			var brochure_count	=	$("#brochure_list").children().length;
			var savedjob_count	=	$("#savedjob_list").children().length;
			var resource_count	=	$("#resource_list").children().length;
			
			if(title=='Brochure' && brochure_count==0){
				$("#brochure_list").html("<?php echo trans('messages.<li>You have not saved any brochure yet.</li>') ?>");
			}
			if(title=='Saved Jobs' && savedjob_count==0){
				$("#savedjob_list").html("<?php echo trans('messages.<li>You have not saved any job yet.</li>') ?>");
			
			}
			if(title=='Eliminate Resource' && resource_count==0){
				$("#resource_list").html("<?php echo trans('messages.<li>You have not saved any resource yet.</li>'); ?>")
			}
			notice(title,messages,'success');
		}
	});
}
	
/* for job apply popup */
function applyJobPopup(jobId){	
	$.ajax({
		url: "<?php echo URL::to('user-apply-job-popup')?>",
		type: "GET",
		data: {'jobId' : jobId },
		beforeSend: function() {
			$("#overlay").show();
		},
		success: function(data) {
			if(data.success == 1){		
				$('#applyForjob').html(data.html);
				$('#applyForjob').modal("show");
				$("#overlay").hide();

			}else if(data.success == 'needLogin'){
				notice('<?php echo trans("messages.Jobs"); ?>','<?php echo trans("messages.You need to login first to apply for this job."); ?>' , 'error');
				$("#overlay").hide();
			}
		}
	});
	return false;
}
	

/* for brochure popup detail*/
function brochurePopup(brochureId){	
	$.ajax({
		url: "<?php echo URL::to('user-brochure-popup')?>",
		type: "GET",
		data: {'brochureId' : brochureId},
		beforeSend: function() {
			$("#overlay").show();
		},
		success: function(data) {
			if(data.success == 1){		
				$('#applyForjob').html(data.html);
				$('#applyForjob').modal("show");
				$("#overlay").hide();
			}
		}
	});
	return false;
}

/* for Saved job  popup detail*/
function saveJobPopup(jobId){	
	$.ajax({
		url: "<?php echo URL::to('user-save-job-popup')?>",
		type: "GET",
		data: {'jobId' : jobId},
		beforeSend: function() {
			$("#overlay").show();
		},
		success: function(data) {
			if(data.success == 1){		
				$('#applyForjob').html(data.html);
				$('#applyForjob').modal("show");
				$("#overlay").hide();
			}
		}
	});
	return false;
}

</script>	
	
<style>
.scrollbox{
   height: 150px;  
}
</style>

<div class="iner-page-heading wlcm heading-top">
	<div class="container">
		<h1>{{ trans("messages.Welcome Back") }}, {{ Session::get('User.Auth')->firstname }}</h1>
	</div>
</div>
<div class="welcsect">
	<div class="container" id="scroll_error">
		<div class="row">
			<div class="col-lg-3 col-sm-6 section">
				<img alt="{{ trans('messages.Your Profile') }}" title="{{ trans('messages.Your Profile') }}" src="<?php echo WEBSITE_IMG_URL; ?>your_profile.png" />
				<h2><a href="{{ route('edit-profile-start') }}">{{ trans("messages.Your Profile") }}</a></h2>
				
				<a  href="{{ (Auth::user()->new_to_edit_profile)? URL::to('dashboard/edit-profile'):route('edit-profile-start') }}"  class="btn btn-green new-brouchure-link">
				{{ trans("messages.Edit Profile") }}
				</a>
				<ul>
					<li><a href="{{ route('user-change-password') }}">{{ trans("messages.Change Password") }}</a></li>
					<li><a href="{{ route('my-settings')}}">{{ trans("messages.Settings") }}</a></li>
					<li><a href="{{ route('user-membership') }}">{{ trans("messages.Membership") }}</a></li>
				</ul>
			</div>
			<div class="col-lg-3 col-sm-6 section last1">
				<img alt="{{ trans('messages.Brochures') }}" title="{{ trans('messages.Brochures') }}" src="<?php echo WEBSITE_IMG_URL; ?>broucher.png"  />
				<h2><a href="{{ route('create-brochure') }}">{{ trans("messages.Brochures") }}</a></h2>
				
				<a href="{{  (Auth::user()->new_to_brochure)? URL::to('dashboard/create-brochure'):route('create-brochure-start')  }}"  class="btn btn-green new-brouchure-link">
				{{ trans("messages.New Brochure") }}
				</a>
				
				
				
				<div class="scrollbox">
					<ul id="brochure_list">
						@if((!$brochures->isEmpty()))
							@foreach($brochures as $brochure)
								<li id="UserBrochure_<?php echo $brochure->id; ?>">

								
								<div class="wl-icon">
								
								<a href="{{ route('edit-brochure',array('id'=>$brochure->id))}}"  ><img src="<?php echo WEBSITE_IMG_URL; ?>icon1.png" /> </a>

								
								</div>
								<div class="wl-txt">
															
								<a  href="javascript:void(0)" onclick="brochurePopup('<?php echo $brochure->id; ?>')">{{ $brochure->brochure_name }}</a>
								</div>
								<span class="del-img-icon del-img-icon action mlr20">
									<a target="_blank" href="{{ URL::to('preview-resume/'.$brochure->validate_string)}}"><img alt="{{ trans('messages.Preview Brochure')}}" title="{{ trans('messages.Preview Brochure')}}" src="<?php echo WEBSITE_IMG_URL; ?>view.png" /></a>
								</span>

								
								<span class="del-img-icon del-img-icon action" onclick='deletes("<?php echo trans("messages.Brochure"); ?>",<?php echo $brochure->id; ?>,"UserBrochure","<?php echo trans("messages.Are you sure you want to delete this brochure?"); ?>","<?php echo trans("messages.Brochure deleted successfully."); ?>")'  >
								
									<a href="#" data-toggle="modal" data-target="#del-brochure">
										<img alt="{{ trans('messages.Delete') }}" src="<?php echo WEBSITE_IMG_URL; ?>close.png" alt="{{ trans('messages.delete')}}" title="{{ trans('messages.Delete')}}" align="right" class="closebut"/>
									</a>
								</span>
								
								</li>
							@endforeach
						@else
							<li>{{ trans("messages.You have not saved any brochure yet.") }}</li>
						@endif
					</ul>
				</div>
			</div>
			<div class="col-lg-3 col-sm-6 section">
				<img alt="{{ trans('messages.Saved Jobs') }}" title="{{ trans('messages.Saved Jobs') }}" src="<?php echo WEBSITE_IMG_URL; ?>search_job.png"  />
				<h2>{{ trans("messages.Saved Jobs") }}</h2>
				<div class="scrollbox">
				<ul id="savedjob_list">
					<?php $isSavedJob =  false ;?>
					@if(!$userSavedJobs->isEmpty()) 
							@foreach($userSavedJobs as $savedJobs)
								@if(!Empty($savedJobs->Jobs))
									<?php $jobId	=	$savedJobs->job_id ;?>
									<?php $isSavedJob =  true ;?>
									<li id="UserSavedJobs_<?php echo $savedJobs->job_id; ?>">
										<div class="wl-icon">
											<a href="javascript:void(0)" class="<?php echo ($savedJobs->applied_jobs) ? 'applied-jobs' : ''; ?>" onclick="saveJobPopup('<?php echo $jobId ?>')" >
												<img src="<?php echo WEBSITE_IMG_URL; ?>search_icon.png"  /></a>
										</div>	
										<div class="wl-txt">	
										<a href="javascript:void(0)" onclick="saveJobPopup('<?php echo $jobId ?>')" class="<?php echo ($savedJobs->applied_jobs) ? 'applied-jobs' : ''; ?>" >{{ isset($savedJobs->jobs->title) ? $savedJobs->jobs->title : '' }}</a>
										</a> 
										</div>
										<span  class="del-img-icon action" onclick='deletes("<?php echo trans("messages.Saved Jobs"); ?>",<?php echo $savedJobs->job_id; ?>,"UserSavedJobs","<?php echo trans("messages.Are you sure you want to delete this job?"); ?>","<?php echo trans("messages.Job deleted successfully."); ?>")' >
											<a href="javascript:void(0)" data-toggle="modal" data-target="#del-brochure">
												<img alt="{{ trans('messages.delete')}}" title="{{ trans('messages.Delete')}}" src="<?php echo WEBSITE_IMG_URL; ?>close.png"  alt="" align="right" class="closebut"/>
											</a>
										</span>
									</li>
								@endif	
							@endforeach
					@endif

					@if($isSavedJob==false)
						<li>
							{{ trans("messages.You have not saved any job yet.")}}
						</li>
					@endif
						
				</ul>
				</div>
			</div>
			<div class="col-lg-3 col-sm-6 section last">
				<img alt="{{ trans('messages.Resources') }}" title="{{ trans('messages.Resources') }}" src="<?php echo WEBSITE_IMG_URL; ?>resource.png"  />
				<h2>{{ trans("messages.Resources") }}</h2>
				<div class="scrollbox">
				<ul id="resource_list">
					@if(!empty($userSavedResources))
						@foreach($userSavedResources as $savedResources)
						<li id="UserSavedResources_<?php echo $savedResources['resource_id']; ?>">
							<a href="{{ URL::to('resources/downloadfile/'.$savedResources['file'].'/'.$savedResources['resource_id'])}}">
								{{ isset($savedResources['according_language']['title']) ? $savedResources['according_language']['title'] : '' }}
							</a> 
							<span class="del-img-icon action" onclick='deletes("<?php echo trans("messages.Eliminate Resource"); ?>",<?php echo $savedResources["resource_id"]; ?>,"UserSavedResources","<?php echo trans("messages.Are you sure you want to eliminate this link from your dashboard?"); ?>","<?php echo trans("messages.Resource deleted successfully."); ?>")' >
								<a href="javascript:void(0)" data-toggle="modal" data-target="#del-brochure">
									<img alt="{{ trans('messages.delete')}}" title="{{ trans('messages.Delete')}}" src="<?php echo WEBSITE_IMG_URL; ?>close.png"  alt="" align="right" class="closebut"/>
								</a>
							</span>
						</li>
						@endforeach
					@else
						<li>{{ trans("messages.You have not saved any resources yet.") }}</li>
					@endif
				</ul>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="clearfix"></div>

<div class="newjob">
	<div class="container ">
		<div>
			<h2>{{ trans("messages.New Jobs") }}</h2>
		</div>
		<div class="clearfix"></div>
		
			@if(!$jobResult->isEmpty())
				<?php 	$z = 0; 
				$firstColumm	=	'';
				$secondColumm	=	''; ?>
				@foreach( $jobResult as $record )
				<?php 
					ob_start();
					if($z%2 == 0){ ?>
						@include('elements.new_jobs')
					<?php	$firstColumm	.=	ob_get_contents();
					}else{ ?>
						@include('elements.new_jobs')
					<?php	$secondColumm	.=	ob_get_contents();
					}
					$z++;
					ob_end_clean();						
				?>
				@endforeach
			
				<div class="col-sm-6">
					{{ $firstColumm }} 
				</div> 
				<div class="col-sm-6">
					{{ $secondColumm }} 
				</div>	
			@else
				<div class="row jobs" style="min-height:100px">
					{{ trans("messages.No jobs found under to your search criteria ") }}
				</div>	
			@endif	
		
			@if($count >= 5)
				<div class="clearfix showmore">
					<a href="{{URL::to('show-more-matches')}}"><strong>{{ trans("messages.Show More Matches")}}</strong> </a>
				</div>
			@endif
	
	</div>
</div>
<div class="clearfix"></div>

<div class="clearfix"></div>

<!-- modal is used to Delete the links from dashboard -->
<div  class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="del-brochure">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<button aria-label="Close" data-dismiss="modal" class="close" type="button" id="playButton">
				<img src="<?php echo WEBSITE_IMG_URL; ?>close-popup.png" alt="" />
			</button>
            <div class="modal-body">
				<div class="txt-about popup-content">
					<h3 id="heading">{{ trans("messages.Eliminate Resource")}}</h3>
					<p id="confirm_text"> {{ trans("messages.Are you sure you want to eliminate this link from your dashboard?")}}</p>  
				</div>
			</div>
            <div class="modal-footer  text-right">
				<button data-dismiss="modal" type="button" class="btn btn-grey whit-txt">{{ trans("messages.Cancel") }}</button>
				<button onclick="confirmDelete()" id="confirm_yes" messages=""  href="" title=""  type="button" class="btn btn-default whit-txt">{{ trans("messages.Yes") }}</button>
			</div>
		</div>
    </div>
</div>
<!-- end modal -->

<!-- this modal is used to apply for job and for display a popup,displaying the details of brochure-->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="applyForjob">
	
</div>
<!-- end here -->

<!-- Loader div  -->
<div id="overlay" style="display:none">
	<img src="<?php echo WEBSITE_IMG_URL; ?>ajax-loader2.GIF" class="loading_circle" alt="Loading..." /></span>
</div>
<!-- end here -->
@include('elements.footer_large')

@stop
