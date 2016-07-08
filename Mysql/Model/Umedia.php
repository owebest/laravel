<?php 
use Illuminate\Database\Eloquent\Model;

/**
 * Umedia Model
 */
 
class Umedia extends Eloquent   {
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
 
	protected $table = 'umedia';
	
	/**
	* belongsTo bind to user to get user
	*
	* @param null
	* 
	* @return query
	*/
	
	public function user()
	{
		return $this->belongsTo('User')->select('full_name','id');
	} //end user()

	/**
	* hasOne bind function with NewsDescription model
	*
	* @param null
	* 
	* @return query
	*/
	
	public function accordingLanguage()
    {
		$currentLanguageId	=	Session::get('currentLanguageId');
        return $this->hasOne('UmediaDescription','parent_id')
					->select('description','title','parent_id')
					->where('language_id' , $currentLanguageId);
		
    } //end accordingLanguage()
	
	/**
	* scope function
	*
	* @param $query 	as query object
	* 
	* @return query
	*/	
	public  function scopeOfActive($query)
    {
        return $query->where('is_active',1);
    } //end scopeOfActive()

	/**
	 * function for find result form database function
	 *
	 * @param $limit	
	 * @param $fields 	as fields which need to select
	 * 
	 * @return array
	 */	
	 
	public static function getResult($fields=array()){
		$topics	=	 DropDown::with('description')->where('dropdown_type','umedia')->select($fields)
						->with('umedia')->orderBy('created_at','desc')->get()->toArray();
	
		return $topics;
	} //end getResult()

	/**
	 * function for find result form database function
	 *
	 * @param $query
	 * 
	 * @return array
	 */	
	 
	public function scopeHighlighted($query)
    {
		return $query->where('is_highlight',1);
    } // end scopeHighlighted()
    
    
	/**
	 * function for find highlight  news or special mark
	 *
	 * @param $fields 	as fields which need to select
	 * 
	 * @return array
	 */	
 
	public static function getHighlightResult($fields = array()){
		$highlightResult	=	Umedia::with('accordingLanguage','user')
									->select('*')
									->OfActive()
									->Highlighted()
									->first();
		if(!empty($highlightResult)){
			$highlightResult	=	$highlightResult->toArray();
		}
		
		return $highlightResult;
	} //end getHighlightResult()
	
} // end Umedia class
