<?php 
namespace App\Model; 
use Eloquent,Auth;

/**
 * MarketPlace Model
 */
 
class MarketPlace extends Eloquent  {
	
	
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
 
	protected $collection = 'marketplaces';
	
	protected $dates = ['deleted_at'];
	
	
	 /* Scope Function 
	 *
	 * @param null 
	 *
	 * return query
	 */
 
	public function scopeConditions($query){
		return $query->where('is_active',ACTIVE)->where('is_approved',APPROVED)->where('is_deleted','!=',1);
	}//end scopeConditions
	
	
	/**
	 * hasMany bind function for  MarketPlaceImage model 
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	 
	public function marketPlaceImages(){
		return $this->hasMany('App\Model\MarketPlaceImage','parent_id')->orderBy('updated_at','desc');
	}//edn marketPlaceImages()
	
	/**
	 * hasMany bind function for  user detail of market place
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function userDetail(){
		return $this->belongsTo('App\Model\User','user_id');
	}//end userDetail()
	
	/**
	 * function for find user followers 
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function userFollowers(){
		return $this->hasOne('App\Model\UserFollower','following_id','user_id');
	}//end userFollowers()
	
	/**
	 * function for find cause detail of market place  
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function causesDetail(){
		return $this->belongsTo('App\Model\Causes','causes_id');
	}//end causesDetail()
		
	/**
	 * function for get product category name
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function category(){
		return $this->belongsTo('App\Model\DropDown','category_id','_id')->select('_id','name');
	}//edn category()
	

	/**
	 * function for get question answer for marketplace
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function questionAnswer(){
		return $this->hasMany('App\Model\MarketPlaceQuestionAnswer','marketplace_id')->where('is_active',ACTIVE)->where('answer', 'exists', true);
	}//edn questionAnswer()
	
	/**
	 * function for get question answer for marketplace
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function marketPlaceUpdate(){
		return $this->hasMany('App\Model\MarketPlaceUpdate','marketplace_id')->orderBy('created_at','desc');
	}//edn marketPlaceUpdate()
	
	/**
	 * function for get viewers count for marketplace
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function marketPlaceView(){
		return $this->hasMany('App\Model\MarketPlaceView','marketplace_id');
	}//edn marketPlaceView()
	
	
	/**
	 * function for marketplace rate & reviews
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function marketPlaceReviews(){
		return $this->hasMany('App\Model\MarketPlaceRateAndReviews','marketplace_id');
	}//end marketPlaceReviews()
		
	/**
	 * function for marketplace rate & reviews
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function MarketPlaceLikes(){
		return $this->hasMany('App\Model\MarketPlaceLike','product_id')->orderBy('updated_at','desc');
	}//end MarketPlaceReviews()

	/**
	 * function for marketplace collection
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function myCollection(){
		$userId	=	Auth::user()->_id;
		return $this->hasOne('App\Model\MyCollection','marketplace_id')->where('user_id',$userId);
	}//end MarketPlaceReviews()
	

	/**
	 * function for get marketplace donation
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function marketPlaceSupporters(){
		return $this->hasMany('App\Model\MarketPlaceDonation','marketplace_id')->orderBy('created_at','desc')->limit(5);
	}//edn marketPlaceUpdate()
	
	/**
	 * function for  marketplace orders
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function MarketPlaceOrders(){
		return $this->hasMany('App\Model\OrderItem','marketplace_id');
	}//end MarketPlaceReviews()
	
	/**
	 * function for  marketplace orders
	 *
	 * @param null
	 * 
	 * @return query
	 */	
	public function MarketPlaceShares(){
		return $this->hasMany('App\Model\MarketPlaceShare','marketplace_id');
	}//end MarketPlaceReviews()
	
	
	
}// end MarketPlace class
