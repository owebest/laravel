<?php 
use Illuminate\Database\Eloquent\Model;


/**
 * Video Model
 */
 
class Video extends Eloquent  {
	
/**
 * The database table used by the model.
 *
 * @var string
 */
 
 protected $table = 'videos';
 
 
/* Scope Function 
 *
 * @param null 
 *
 * return query
 */ 
	public function scopeActiveConditions($query){
		return $query->where('is_active',1);
	
	}//end scopeActiveConditions()

	
/**
 * function for highlighted video
 *
 * @param $query 	as query object
 * 
 * @return array
 */		
	public function scopeHighlighted($query)
	{
		return $query->where('is_highlight',1);
		
	}//end scopeHighlighted()
 
/* Function for  bind VideoDescription model   
 *
 * @param null 
 *
 * return query
 */ 
	public function accordingLanguage()
	{
		$currentLanguageId	=	Session::get('currentLanguageId');
		return $this->hasOne('VideoDescription','parent_id')->select('parent_id','title')->where('language_id' , $currentLanguageId);
		
	} //end accordingLanguage()
	
/* Function for  bind result from Database    
 *
 * @param $fields as fields which need to select 
 *
 * return query
 */
	public static function getResult($fields = array()){
		$videoResult		=	 Video::with('accordingLanguage')->select($fields)->ActiveConditions()->orderBy('updated_at','DESC')->get();
		return $videoResult;
	} //end getResult()
	
/* for get highlighted result 
 * 
 * @param $fields 
 * */

	public static function getHighlightResult($fields = array()){
			$video			=	 new Video();
			$videoResult	=	 $video->select($fields)->ActiveConditions()->Highlighted()->orderBy('updated_at','DESC')->first();
			return $videoResult;
			
	}//end getHighlightResult()

}// end Video class
