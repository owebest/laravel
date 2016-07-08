<?php
namespace App\Http\Controllers;
use App\Model\UserPhoto;
use App\Model\UserPhotoLike;
use App\Model\UserPitch;
use App\Model\User;
use Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Input,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,MongoId;

/**
 * UserPhoto Controller
 *
 * Add your methods in the class below
 *
 * This file will render views from views
 */ 
 
class UserPhotoController extends BaseController {
	
	/**
	 * Function for main listing page
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	
	public function main($mediaType = 'photos'){
		return View::make('usermedia.index',compact('mediaType'));
	}//end main()
	
	/**
	 * Function for user photo page
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	
	public function index(){
		
		$search						=	Input::get('search');
		$offset						=	(int)Input::get('offset',0);
		$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
		
		$userPhotoCondition			=	UserPhoto::with('userPhotoLikes')->where('user_id',Auth::user()->_id)->where('album_id','');
		$countPhotos  				=	$userPhotoCondition->count();
		// search management
		
		if($search!=''){
			Session::put('search_photo',$search);
			$userPhotoCondition	->where('caption','like',"%$search%");
			$countPhotos	  		=	$userPhotoCondition->count();
		}else{
			if(Input::get('load_more')==1){
				if(Session::has('search_photo') && $search !=''){
					$search					=	Session::get('search_photo');
					$userPhotoCondition->where('caption','like',"%$search%");
					$countPhotos  		=	$userPhotoCondition->count();
				}
			}
		}
		
		// search management
		$userPhotos					=	$userPhotoCondition->orderBy('created_at','desc')->skip($offset)->take($limit)->get();
		
		if(Input::get('load_more')==1){
			return View::make('usermedia.user_photo.more_user_photo',compact('countPhotos','userPhotos','offset','limit','userPhotoLikeCount'));
		}else{
			if(Session::has('search_photo')){
				Session::forget('search_photo');
			}
			return View::make('usermedia.user_photo.index',compact('countPhotos','userPhotos','offset','limit','userPhotoLikeCount'));
		}
	}//end index()
	
	/**
	 * Function for add photo
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function add(){
		$groupId		=	Input::get('group_id');
		$userPhotoCondition	=	UserPhoto::where('user_id',Auth::user()->_id)->where('album_id','');
		
		if($groupId){
			$userPhotoCondition	=	$userPhotoCondition->where('photo_group',$groupId);	
		}
		$userPhotos	=	$userPhotoCondition->orderBy('updated_at','desc')->get();
		
		return View::make('usermedia.user_photo.add',compact('userPhotos'));
	}//end add()
	
	/**
	 * Function for upload user photo
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function uploadUserPhoto(){
		
		$modelName		=	Input::get('model_name');
		$albumId		= 	Input::get('album_id');
		$userId			=	Auth::user()->_id;
		$userSlug		=	Auth::user()->slug;
		
		$folderName		=	 USER_IMAGES_ROOT_PATH.$userSlug.DS.USER_PHOTO_FOLDER;
		$html			=	'';
		if($albumId != ''){
			$userPhotos		=	UserPhoto::where('user_id',$userId)->where('album_id',$albumId)->orderBy('updated_at','desc')->get();
			$html		=	(string) View::make('usermedia.user_photo.user_photo',compact('userPhotos'));
		}
		
		$phohoGroupId	=  $userId.rand();
			
		foreach(Input::file('user_photo') as $key =>  $image){
			$extension 			=	 $image->getClientOriginalExtension();
			$rules 				=    explode(',',IMAGE_EXTENSION);
			
			$imageSize		=	getimagesize($image);
			$width			=	$imageSize[0];
			$height			=	$imageSize[1];
			//check image extension
			if(!in_array($extension,$rules)){
				$response	=	array(
					'success' => false,
					'errors' => "<ul><li>".IMAGE_EXTENSION_ERROR_MSG."</li></ul>",
					'html'   => $html
					);
				return  Response::json($response); die;
			}else{
					//check image size
					if($width >= 468 && $height >= 688){
					
						$fileName		=	 $userId.time().rand()."user-photo.".$extension;
						
						if(!File::exists($folderName)) {
							
							File::makeDirectory($folderName, $mode = 0777,true);
						}
						if($image->move($folderName, $fileName)){
							$NamespacedModel 						= '\\App\Model\\' . $modelName;
							$objUserPhoto							=	 new $NamespacedModel();
							$objUserPhoto->user_id	 				=	 $userId;
							if($modelName != 'UserPitch'){
								$objUserPhoto->album_id					=	($albumId !='') ? $albumId : '';
							}	
							
							$objUserPhoto->image	 				=	 $fileName;
							
							if($modelName == 'UserPitch'){
								$objUserPhoto->type					=	'image';	
							}
							
							$objUserPhoto->photo_group = $phohoGroupId;
							
							$objUserPhoto->save();
						}
					}else{
						$response	=	array(
							'success'	 	=> false,
							'file_size'		=> "<ul><li>Minimum size for image should be ".PHOTOS_SIZE."</li></ul>",
							'html'   		=> $html
						);
						return  Response::json($response); die;
					}
					
					if($albumId != ''){
						$userPhotos		=	UserPhoto::where('user_id',$userId)->where('album_id',$albumId)->orderBy('updated_at','desc')->get();
						$html		=	(string) View::make('usermedia.user_photo.user_photo',compact('userPhotos'));
					}
		
				}
			}
			$response	=	array(
				'success' 		=>	1,
				'photo_group'	=>	$phohoGroupId,
				'html'   		=> $html
				
			);
					
		return  Response::json($response); die;
		
	}//end uploadUserPhoto()
	
	/**
	 * Function for update photo caption
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function updatePhotoCaption(){
		
		if(Request::ajax()){
		
			$modelName		=	Input::get('model_name');
			
			if($modelName == ''){
				return View::make('errors.404');
			}
			
			$captionId		=	Input::get('caption_id');
			$captionName	=	Input::get('name');
			
			$NamespacedModel 	= '\\App\Model\\' . $modelName;
			
			$objCaption				=	$NamespacedModel::find($captionId);
			$objCaption->caption	=	nl2br(strip_tags(trim($captionName)));
			$objCaption->save();
			
			$response	=	array(
				'success' => true,
			);
			return  Response::json($response);
		}
	}//end updatePhotoCaption()
	
	/**
	 * Function for delete multiple user photos
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function userPhotoMultipleDelete(){
		if(Request::ajax()){
			$modelName	=	Input::get('model_name');
			$albumId	=	Input::get('album_id');
			
			if(!empty(Input::get('ids')) && $modelName != ''){
					$NamespacedModel 	= '\\App\Model\\' . $modelName;
						
					$photo		=	$NamespacedModel::whereIn('_id',Input::get('ids'))->get();	
					
					if(!empty($photo)){
						foreach($photo as $data){
							switch($modelName){
								case 'UserPitch' :
									
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_MP4_URL.'/'.$data->video)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_MP4_URL.'/'.$data->video);
									}
									
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_THUMB_URL.'/'.$data->image)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_THUMB_URL.'/'.$data->image);
									}
									
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_ORG_URL.'/'.$data->video)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_ORG_URL.'/'.$data->video);
									}
								break; 
								
								case 'UserPhoto' :
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.'/'.USER_PHOTO_FOLDER.'/'.$data->image)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.'/'.USER_PHOTO_FOLDER.'/'.$data->image);
									}
								break; 
								
								case 'UserAlbum' :
										$userphotos  =  UserPhoto::whereIn('album_id',Input::get('ids'))->get();
										if(!empty($userphotos)){
											foreach($userphotos as $photoAlbum){
												if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.'/'.USER_PHOTO_FOLDER.'/'.$photoAlbum->image)){
													
													@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.'/'.USER_PHOTO_FOLDER.'/'.$photoAlbum->image);
												}
											}
										}
									UserPhoto::whereIn('album_id',Input::get('ids'))->delete();
									
								break; 
								
								case 'UserVideo' :
								
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_MP4_URL.'/'.$data->video)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_MP4_URL.'/'.$data->video);
									}
									
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_THUMB_URL.'/'.$data->image)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_THUMB_URL.'/'.$data->image);
									}
									
									if(File::exists(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_ORG_URL.'/'.$data->video)){
										@unlink(USER_IMAGES_ROOT_PATH.Auth::user()->slug.DS.USER_VIDEO_ORG_URL.'/'.$data->video);
									}
									
								break; 
								
							
							}
							
								
						}
				}
				
				$NamespacedModel::whereIn('_id',Input::get('ids'))->delete();
			}
			
				$userPhotoCount	=	$NamespacedModel::where('user_id',Auth::user()->id)->where('album_id',$albumId)->count();
			
			if($userPhotoCount < 1){
				$lastRecord		=	true;
			}
			$response	=	array(
				'success' 		=>	'1',
				'lastRecord'	=>	isset($lastRecord) ? $lastRecord : ''
				
			);
			return  Response::json($response); die;
		}	
	}//end userPhotoMultipleDelete()

	/**
	 * Function for like and unlike  the photo/video/album and pitch section
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	public function userLikePhoto(){
		if(Request::ajax()){   	
			
			$userId		=	Auth::user()->_id;
			$photoId	=	Input::get('photo_id');
			$modelName	=	Input::get('model_name');
			echo $modelName;  
			
			if($modelName == ''){
					return View::make('errors.404');
			}
			
			$NamespacedModel 	= '\\App\Model\\' . $modelName;
			
			// $status 0 as unlike and 1 as like 
			$status		=	Input::get('status');
		
			if($userId != '' && $photoId !='' && $status !=''){
				$userId		=	Auth::user()->id;
				if($status == 1){
					
					// insert the record and update the count of photos
					$NamespacedModel::raw()->update(
						array(
							'_id'=>new MongoId($photoId)
						),
						array(
							'$push'=>array(
								'likes'=>array(
									'user_id'		=> $userId,
									'created_at'	=>time()
								)
							),
							'$inc'=>array(
								'total_likes'=> 1
							)
						)
					);
		
		
				}else{
					// delete the record and update the count of photos
					$NamespacedModel::raw()->update(
						array(
							'_id'=>new MongoId($photoId),
						),
						array(
							'$pull'=>array(
								'likes'=>array(
									'user_id'=> $userId
								)
							),
							'$inc'=>array(
								'total_likes'=> -1
							)
						)
					);
					
				}
			}
		}
	}//end likePhoto()
	
	/**
	 * Function for comment on  photo/video/album and pitch section
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function saveUserComment(){	
		if (Request::ajax()) {
			$inputTextArea	=	Input::get('user_comment');
			$modelName	=	Input::get('model_name');
			$modelId	=	Input::get('model_id');
			$userId		=	Auth::user()->_id;
			$NamespacedModel 	= '\\App\Model\\' . $modelName;
			
			 $validator = Validator::make(Input::all(), array(
				'user_comment'	 => 'required',
			 ),array('user_comment.required' => 'Please enter your comment.'));
			 
			if ($validator->fails()) {
				$error	=	$validator->errors()->first('user_comment');
				$response = array(
					'success' => false,
					'errors'  => $error
				);
				return Response::json($response);
				
				die;
		
			}else{
				
				// insert the record and update the count of photos
				$newCommentId	=	new MongoId();
				$NamespacedModel::raw()->update(
					array(
						'_id'=>new MongoId($modelId)
					),
					array(
						'$push'=>array(
							'comments'=>array(
								'comment_id'	=>	$newCommentId,
								'user_id'		=>  $userId,
								'user_comment'	=>	nl2br(strip_tags(trim($inputTextArea))),
								'created_at'	=>  time()
							)
						),
						'$inc'=>array(
							'total_comments'=> 1
						)
					)
				);
				
				if($modelName == 'UserPost'){
					$feedData			=	$NamespacedModel::where('_id',$modelId)->first()->toArray();
					$html				=	View::make('wall.comment',compact('feedData'))->render();
				}elseif($modelName == 'UserAlbum'){
					$modelData			=	$NamespacedModel::where('_id',$modelId)->first();
					$html				=	View::make('media.albums.album_comment',compact('modelData','modelName'))->render();
				}else{
					$modelData			=	$NamespacedModel::where('_id',$modelId)->first();
					
					$html				=	View::make('usermedia.comment',compact('modelData','modelName'))->render();
				}

				$response = array(
					'success' => true,
					'errors'  => '',
					'html'		=>	$html
				);
				
				return Response::json($response);
			}
		}
	}//end saveUserComment()
	
	/**
	 * Function for open popup on photo/video/album and pitch section
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	public function userPhotoViewPopup(){
		
		if(Request::ajax()){
			
			$modelName					=	Input::get('model_name'); 
			$fromAboutUsPage			=	Input::get('fromAboutUsPage'); 
	
			$NamespacedModel 	= '\\App\Model\\' . $modelName;
			
			$modelId			=	Input::get('model_id');
			$photoId			=	Input::get('photo_id');
			$postId				=	Input::get('post_id');
		
			if($photoId != ''){
				$modelData			=	$NamespacedModel::with('userDetail')->where('_id',$photoId)->where('album_id',$modelId)->first();
			}elseif($postId != ''){
				$modelData			=	$NamespacedModel::with('userDetail')->where('_id',$modelId)->where('post_id',new mongoId($postId))->first();
			}else{
				if($modelName == 'UserPhoto'){
					$modelData			=	$NamespacedModel::with('userDetail')->where('_id',$modelId)->where('album_id','')->first();
				}else{
					$modelData			=	$NamespacedModel::with('userDetail')->where('_id',$modelId)->first();
				}
			}
		
			if(Input::get('user_slug') !=''){
				$userId					=	User::where('slug',Input::get('user_slug'))->pluck('_id');
				$userSlug				=	Input::get('user_slug');
			}else{
				$userId					=	isset($modelData->userDetail->_id) ? $modelData->userDetail->_id : ''; 
				$userSlug				=	isset($modelData->userDetail->slug) ?$modelData->userDetail->slug : '';
			}
			
			if($userId == Auth::user()->_id){
				$onProfile	=	true;
			}
		
			if($photoId != ''){
				
				$previousId 		= 	$NamespacedModel::where('_id', '<', $photoId)->where('user_id',$userId)->where('album_id',$modelId)->max('_id');
				
				$nextId 			= 	$NamespacedModel::where('_id', '>', $photoId)->where('user_id',$userId)->where('album_id',$modelId)->min('_id');
				
			}elseif($postId != ''){
				
				$previousId 		= 	$NamespacedModel::where('_id', '<', $modelData->_id)->where('post_id',new mongoId($postId))->max('_id');
				$nextId 			= 	$NamespacedModel::where('_id', '>',$modelData->_id)->where('user_id',$userId)->where('post_id',new mongoId($postId))->min('_id');
				
			}else{
				
				if($modelName == 'UserPhoto'){
					$previousId 		= 	$NamespacedModel::where('_id', '<', $modelData->_id)->where('user_id',$userId)->where('album_id','')->max('_id');
					$nextId 			= 	$NamespacedModel::where('_id', '>', $modelData->_id)->where('user_id',$userId)->where('album_id','')->min('_id');
				}else{
					$previousId 		= 	$NamespacedModel::where('_id', '<', $modelData->_id)->where('user_id',$userId)->max('_id');
					$nextId 			= 	$NamespacedModel::where('_id', '>', $modelData->_id)->where('user_id',$userId)->min('_id');
				}
				
				
			}
			
			$allComments	=	'';
			return View::make('usermedia.media_popup',compact('modelData','previousId','nextId','modelName','nextPhotoId','photoId','userSlug','onProfile','fromAboutUsPage','postId','allComments'));
		}
	}//end userPhotoViewPopup()
	
	/**
	 * Function for load more comment of photo/video/album and pitch section
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function loadUserCommentElement(){
	
		if(Request::ajax()){
				
				$modelName			=	Input::get('model_name');
				$modelId			=	Input::get('model_id');
				$userId				=	Auth::user()->_id;
				$NamespacedModel 	= '\\App\Model\\' . $modelName;
				
				$modelData			=	$NamespacedModel::where('_id',$modelId)->first();
				$allcomments		=	true;
				
				if($modelName == 'UserPost'){
					$feedData			=	$NamespacedModel::where('_id',$modelId)->first();
					$html =	View::make('wall.comment',compact('feedData','allcomments','modelName','modelId'))->render();
				}elseif($modelName == 'UserAlbum'){
					$html =	View::make('media.albums.album_comment',compact('modelData','allcomments','modelName','modelId'))->render();
				}else{
					$html =	View::make('usermedia.comment',compact('modelData','allcomments','modelName','modelId'))->render();
				}
				
				$response = array(
						'success' => true,
						'errors'  => '',
						'html'		=>	$html
				);
					return Response::json($response);
					
				
		}
	}//end loadUserCommentElement()
	
	/**
	 * Function for delete comment of photo/video/album and pitch section
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function deleteUserComment(){
	
		if(Request::ajax()){
				
				$modelName			=	Input::get('model_name');
				$modelId			=	Input::get('model_id');
				$commentId			=	Input::get('comment_id');
				
				$NamespacedModel 	= '\\App\Model\\' . $modelName;
				$modelData			=	$NamespacedModel::where('_id',$modelId)->first();
				
				
				$NamespacedModel::raw()->update(
					array(
						'_id'=>new MongoId($modelId),
					),
					array(
						'$pull'=>array(
							'comments'=>array(
								'comment_id'=> new MongoId($commentId) 
							)
						),
						'$inc'=>array(
							'total_comments'=> -1
						)
					)
				);
				
				$modelData			=	$NamespacedModel::where('_id',$modelId)->first();
				$allcomments		=	Input::get('allcomments');
				
				if($modelName == 'UserPost'){
					$feedData			=	$NamespacedModel::where('_id',$modelId)->first();
					$html	=	View::make('wall.comment',compact('feedData','allcomments','modelName','modelId'))->render();
				}elseif($modelName == 'UserAlbum'){
					$modelData			=	$NamespacedModel::where('_id',$modelId)->first();
					$html				=	View::make('media.albums.album_comment',compact('modelData','modelName','allcomments','modelId'))->render();
				}else{
					$html	=	View::make('usermedia.comment',compact('modelData','modelName','modelId','allcomments'))->render();
				}
				
				$response = array(
						'success' => true,
						'errors'  => '',
						'html'	  => $html
				);
				
				return Response::json($response);
					
				
		}
	}//end deleteUserComment()
	
	/**
	 * Function for edit comment of photo/video/album and pitch section
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function editUserComment(){
		
		$modelName			=	Input::get('model_name');
		$modelId			=	Input::get('model_id');
		$commentId			=	Input::get('comment_id');
		$comment			=	Input::get('comment');
		$NamespacedModel 	= '\\App\Model\\' . $modelName;
				
		$NamespacedModel::raw()->update(
			array(
				 '_id'					=> new MongoId($modelId),
				 'comments.comment_id'	=>	new MongoId($commentId)
			),
			array(
				'$set'=>array(
					'comments.$.user_comment'=>nl2br(strip_tags(trim($comment)))
				)
			)
		);
		
		$response = array(
				'success' => true,
				'errors'  => '',
				
		);
		
		return Response::json($response);										
	}
	
	
	/**
	 * Function for add share count 
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function sharePost(){
		if(Request::ajax()){
			
			$userId		=	Auth::user()->_id;
			$postId		=	Input::get('post_id');
			$modelName	=	Input::get('model_name');
		
			$NamespacedModel 	= '\\App\Model\\' . $modelName;
			
			// insert the record and update the share of post
				$NamespacedModel::raw()->update(
					array(
						'_id'=>new MongoId($postId)
					),
					array(
						'$inc'=>array(
							'total_shares'=> 1
						)
					)
				);
			}
	}//end sharePost()
	
	/**
	 * Function for open popup for move photos to album
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function openPhotosMovePopup(){
		if(Request::ajax()){
			
			$userId			=	Auth::user()->_id;
			$photoIds		=	Input::get('ids');
			$albumId		=	Input::get('current_album_id');
			$modelName		=	'UserAlbum';
		
			$NamespacedModel 	= '\\App\Model\\' . $modelName;
			
			if($albumId == ''){
				$getAlbumList	=	$NamespacedModel::where('user_id',Auth::user()->_id)->orderBy('name','asc')->lists('name','_id');
			}else{
				$getAlbumList	=	$NamespacedModel::where('user_id',Auth::user()->_id)->where('_id','!=',$albumId)->orderBy('name','asc')->lists('name','_id');
			}
			
			return View::make('usermedia.user_photo.move_to_album_popup',compact('getAlbumList','photoIds'));
		}
	}//end openPhotosMovePopup()
	
	/**
	 * Function move photos into selected album
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	 
	public function movePhotosToAlbum(){
		
		$response = array('success' => false);
		
		$photoIds		=	json_decode(Input::get('photoIds'));
		$albumId		=	Input::get('album_id');
		
		if(!empty($photoIds) && $albumId !=''){
			UserPhoto::whereIn('_id',$photoIds)->update(array('album_id' => $albumId));
			$response = array('success' => true);
		}
		
		return Response::json($response);	
		
	}//end movePhotosToAlbum()
	
}//end MediaController class
