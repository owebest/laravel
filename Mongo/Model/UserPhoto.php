<?php 
namespace App\Model; 
use Eloquent;

/**
 * UserPhoto Model
 */
class UserPhoto extends Eloquent  {
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
 
	protected $collection = 'user_photos';
	
	/**
	 * function for find photos likes 
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function userPhotoLikes(){
		return $this->hasMany('App\Model\UserPhotoLike','photo_id');
	}//end userPhotoLikes()
	
	public function userDetail(){
		return $this->belongsTo('App\Model\User','user_id');
	}//end userDetail()
	
	
}// end UserPhoto class
