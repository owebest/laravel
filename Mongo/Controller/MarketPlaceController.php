<?php
namespace App\Http\Controllers;
use Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Input,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,DateTime,MongoId;
use App\Model\MarketPlace;
use App\Model\MarketPlaceLike;
use App\Model\UserFollower;
use App\Model\Causes;
use App\Model\DropDown;
use App\Model\MarketPlaceQuestionAnswer;
use App\Model\MarketPlaceView;
use App\Model\MarketPlaceImage;
use App\Model\MarketPlaceDescription;
use App\Model\MarketPlaceRateAndReviews;
use App\Model\MarketPlaceUpdate;
use App\Model\MarketPlaceReminders;
use App\Model\Block;
use CustomHelper;
use App\Model\MarketPlaceDonation;
use App\Model\MyCollection;
use App\Model\UserAddress;
use App\Model\UserCart;
use App\Model\User;
use App\Model\BuyerOffer;
use App\Model\Order;
use App\Model\OrderItem;
use App\Model\MarketPlaceShare;
use App\Model\BuyerRequest;
use App\Model\BuyerRequestResponse;
use App\Model\EmailAction;
use App\Model\EmailTemplate,App\Model\ReportMessage;
use App\Model\ContestParticipant;

/**
 * MarketPlaceController
 *
 * Add your methods in the class below
 *
 * This file will render views from views
 */ 
 
class MarketPlaceController extends BaseController {
	
	/**
	 * Function for user market place listing page
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function index($slug	 = '',$id = ''){		
		return View::make('marketplace.index');  
	}//end index()
	
	/**
	 * Function for load my_items_section, analytics-section,orders-section,negotiation-section,buyers-section and report_problem-section element of marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function loadMarketplaceElements(){
		if(Request::ajax()){
			$offset					=	(int)Input::get('offset',0);
			$limit					=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
			$elementName			=	Input::get('element');
			$search					=	Input::get('search');
			switch ($elementName) {
				case 'my_items_section':
					// global condition
					$countMarketPlace  		=	MarketPlace::where('user_id',Auth::user()->id)->where('is_deleted','!=',1)->count();
					$marketPlaceCondition 	=   MarketPlace::with('marketPlaceImages','marketPlaceView','MarketPlaceLikes','MarketPlaceOrders','MarketPlaceShares')->where('user_id',Auth::user()->id)->where('is_deleted','!=',1); 
					$dropDownSelect			=	DropDown::query()->where('dropdown_type','market-category')->orderBy('name','asc')->lists('name','_id')->toArray();
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_marketplace',Input::get('order_by'));
						$val 	=	explode(',',Input::get('order_by'));
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('order_by_marketplace')){
								$val 	=	explode(',',Session::get('order_by_marketplace'));
							}
						}
					}
					/*For Serching By Category And Sub Category*/
					if(Input::get('category_id') != '' && Input::get('sub_category_id') != ''){
						Session::put('marketplace_by_category_id',Input::get('category_id'));
						Session::put('marketplace_by_sub_category_id',Input::get('sub_category_id'));
						$val1	=	Input::get('category_id');
						$val2	=	Input::get('sub_category_id');
						$marketPlaceCondition->where('category_id',$val1)->where('sub_category_id',$val2);
						$countMarketPlace  		=	$marketPlaceCondition->count();
					}elseif(Input::get('category_id') != ''){
						Session::put('marketplace_by_category_id',Input::get('category_id'));
						if(Session::has('marketplace_by_sub_category_id')){
							Session::forget('marketplace_by_sub_category_id');
						}
						$val1	=	Input::get('category_id');
						$marketPlaceCondition->where('category_id',$val1);
						$countMarketPlace  		=	$marketPlaceCondition->count();
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('marketplace_by_category_id')){
								$val1 	=	Session::get('marketplace_by_category_id');
								$val2 	=	Session::get('marketplace_by_sub_category_id');
								if($val2 == ''){
									$marketPlaceCondition->where('category_id',$val1);
								}else{
									$marketPlaceCondition->where('category_id',$val1)->where('sub_category_id',$val2);
								}	
								$countMarketPlace  		=	$marketPlaceCondition->count();
							}
						}
					}
					$orderBy				=	(isset($val[0])) ? $val[0]:'created_at';
					$order					=	(isset($val[1])) ? $val[1]:'desc';
					// search management
					if($search!=''){
						Session::put('search_marketplace',$search);
						$marketPlaceCondition->where('title','like',"$search%");
						$countMarketPlace  		=	$marketPlaceCondition->count();
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('search_marketplace')  ){
								$search 				=	Session::get('search_marketplace');
								$marketPlaceCondition->where('title','like',"$search%");
								$countMarketPlace  		=	$marketPlaceCondition->count();
							}
						}
					}
					// search management
					$marketPlaceContent			=	$marketPlaceCondition->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
				
					if(Input::get('load_more') == 1){
						return View::make('elements.more_market_place',compact('marketPlaceContent','limit','offset','countMarketPlace'));
					}else{
						// clear search session and order
						if(Session::has('order_by_marketplace')){
							Session::forget('order_by_marketplace');
						}
						if(Session::has('search_marketplace')){
							Session::forget('search_marketplace');
						}
						if(Session::has('marketplace_by_category_id')){
							Session::forget('marketplace_by_category_id');
						}
						if(Session::has('marketplace_by_sub_category_id')){
							Session::forget('marketplace_by_sub_category_id');
						}
						return View::make('elements.marketplace-elements.my_items',compact('marketPlaceContent','limit','offset','countMarketPlace','dropDownSelect'));
					}
					
		
				break;
				case 'analytics-section':
				
				 $sectionName	=	Input::get('section');
				
				 $geoUsers	=	array();
				 $revenue	=	array();
				 $allBuyers	=	array();
				 $allOrders	=	array();
				 $ageArray	=	array();
				 
				 $age13	=	date('Y')-13;
				 $age20	=	date('Y')-20;
				 $age30	=	date('Y')-30;
				 $age40	=	date('Y')-40;
				 $age50	=	date('Y')-50;
				 $age60	=	date('Y')-60;
				
				 $userIds					=	OrderItem::where('seller_id',Auth::user()->_id)->lists('buyer_id');
				
				 $userbetween13To20			=	USER::whereIn('_id',$userIds)->whereBetween('birthday_year', [$age20, $age13])->count();
				 $userbetween20To30			=	USER::whereIn('_id',$userIds)->whereBetween('birthday_year', [$age30, $age20])->count();
				 $userbetween30To40			=	USER::whereIn('_id',$userIds)->whereBetween('birthday_year', [$age40, $age30])->count();
				 $userbetween41To50			=	USER::whereIn('_id',$userIds)->whereBetween('birthday_year', [$age50, $age40])->count();
				 $userbetween50To60			=	USER::whereIn('_id',$userIds)->whereBetween('birthday_year', [$age60, $age50])->count();
				 $above60					=	USER::whereIn('_id',$userIds)->where('birthday_year','<=',(int)$age60)->count();
				
				if($userbetween13To20 !='' ||  $userbetween20To30	!='' || $userbetween30To40 !='' ||  $userbetween41To50!='' ||$userbetween50To60 !='' || $above60 !='' ){
				 $ageArray	=	array('13-20' => $userbetween13To20,
									  '21-30' => $userbetween20To30,
									  '31-40' => $userbetween30To40,
									   '41-50' => $userbetween41To50,
									   '51-60' => $userbetween50To60,
									   '60+'   => $above60 );
				}
				
				$allFollowers				=	UserFollower::where('following_id', Auth::user()->_id)->lists('follower_id');
				
				$totalUser					=	count($allFollowers);
				
				$totalMaleUser				=	User::whereIn('_id',$allFollowers)->where('gender','male')->count();
				$totalFemaleUser			=	$totalUser - $totalMaleUser;
			
				$totalMaleUserCount			=	0;
				$totalFemaleUserCount		=	0;
				
				if($totalUser != '' && $totalMaleUser !=''){
					$percentageOfMaleUser		=	$totalMaleUser / $totalUser;
					$totalMaleUserCount			=	round($percentageOfMaleUser*100);
				}	
				
				if($totalUser != '' && $totalFemaleUser !=''){
					$percentageOfFeMaleUser		=	$totalFemaleUser / $totalUser;
					$totalFemaleUserCount		=	round($percentageOfFeMaleUser*100);
					
				}
				
				
				$month	=	date('m');
				$year	=	date('Y');
				for ($i = 0; $i < 12; $i++) {
					$months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
				}
				
				$months		=	array_reverse($months);
				$num		=	0;
				$num1		=	0;
				$num2		=	0;
				$num3		=	0;
				
				/* For items */
				foreach($months as $month){

					$monthStartDate				=	 new MongoDate(strtotime(date('Y-m-01 00:00:00', strtotime($month))));
					$monthEndDate				=	 new MongoDate(strtotime(date('Y-m-t 23:59:59', strtotime($month))));
					
					$allItems[$num]['month']	=	 $month;
					
					$itemCondition				=	OrderItem::where('seller_id',Auth::user()->_id)
														->where('created_at', '>' , $monthStartDate)
														->where('created_at','<' , $monthEndDate);
					
					if($sectionName == 'Items-chart'){
						if($search!=''){
							$itemCondition->where('year',$search);
						}
					}				
														
					$allItems[$num]['items']    =  $itemCondition->sum('quantity');
					
					$num ++;
				}
													
				/* For orders */
				foreach($months as $month){
					$monthStartDate				=	 new MongoDate(strtotime(date('Y-m-01 00:00:00', strtotime($month))));
					$monthEndDate				=	 new MongoDate(strtotime(date('Y-m-t 23:59:59', strtotime($month))));
					
					$allOrders[$num1]['month']	=	 $month;

			
					$orderCondition				=	OrderItem::where('seller_id',Auth::user()->_id)
														->where('created_at', '>' , $monthStartDate)
														->where('created_at','<' , $monthEndDate);
															
					
				
					$allOrders[$num1]['items']    =    $orderCondition->count();
					$num1 ++;
				}
				
				/* For buyers */
				foreach($months as $month){
					$monthStartDate				=	 new MongoDate(strtotime(date('Y-m-01 00:00:00', strtotime($month))));
					$monthEndDate				=	 new MongoDate(strtotime(date('Y-m-t 23:59:59', strtotime($month))));
					
					$allBuyers[$num2]['month']	=	 $month;
					$allBuyers[$num2]['items']  =    OrderItem::where('seller_id',Auth::user()->_id)
														->where('created_at', '>' , $monthStartDate)
														->where('created_at','<' , $monthEndDate)
														->distinct('buyer_id')->count();

					$num2 ++;
				}
				
				/* For revenues */
				foreach($months as $month){
					$monthStartDate				=	 new MongoDate(strtotime(date('Y-m-01 00:00:00', strtotime($month))));
					$monthEndDate				=	 new MongoDate(strtotime(date('Y-m-t 23:59:59', strtotime($month))));
					
					$revenue[$num3]['month']	=	 $month;
					$revenue[$num3]['items']    =    OrderItem::where('seller_id',Auth::user()->_id)
														->where('created_at', '>' , $monthStartDate)
														->where('created_at','<' , $monthEndDate)
														->sum('total_price');
					$num3 ++;
				}
				
				/* For geo graph  */
			
				$addressIds		=	OrderItem::where('seller_id',Auth::user()->_id)->lists('address_id')->toArray();
				$addressList	=	UserAddress::whereIn('_id',$addressIds)->select('_id','country_code')->get();	
						
				$count			=   0;
				
				foreach($addressList as $country){
					$geoUsers[$count]['country']		=	 $country->country_code;
					$geoUsers[$count]['users']          =    OrderItem::where('seller_id',Auth::user()->_id)->where('address_id',$country->_id)
																->count();
					$count ++;
				}
			
			
			$countryUserData	=	OrderItem::raw(function ($collection){
					return $collection->aggregate(
					
					array(
					
						array(
							'$match' => array(
										'seller_id'   => Auth::user()->_id,
									 )
						),
							array(
							'$group' => array(
									'_id'   => array('country_code' => '$country_code', 'country_name' => '$country','buyer_id' => '$buyer_id'),
									'totalCount' => array(
										'$sum' => 1
									),
									'maleCount' =>  array(
										'$sum' => array('$cond' => array(array('$eq' => ['$user_gender','male']),1,0 )) 
									 ),
									'femaleCount' =>  array(
										'$sum' => array('$cond' => array(array('$eq' => ['$user_gender','female']),1,0 )) 
									 ),
									 'uniqueCount' => array(
										'$sum' => 0
									)
								)
							)
						)
						
					);
				});
				
								
				return View::make('elements.marketplace-elements.analytics',compact('totalMaleUserCount','totalFemaleUserCount','ageArray','allItems','allOrders','allBuyers','revenue','geoUsers','countryUserData'));
				break;
				
				case 'orders-section':
					// global condition
					$countOrder 		=	OrderItem::where('seller_id',Auth::user()->id)->count();
					$orderCondition 	=   OrderItem::with('orderDetail','sellerDetail','marketPlaceDetail','buyerDetail')
												->where('seller_id',Auth::user()->_id);
				
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_order',Input::get('order_by'));
						$val 	=	explode(',',Input::get('order_by'));
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('order_by_order')){
								$val 	=	explode(',',Session::get('order_by_order'));
							}
						}
						
					}
					/*For Serching By Category And Sub Category*/
					
					$orderBy				=	(isset($val[0])) ? $val[0]:'created_at';
					$order					=	(isset($val[1])) ? $val[1]:'desc';
					
					if($search!=''){
						Session::put('search_order',$search);
					    $orderCondition->where('order_status',(int) $search);
						$countOrder  			=	$orderCondition->count();
							
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_order') && $search!=''){
								$search				= Session::get('search_order');	
								$orderCondition->where('order_status',(int) $search);
								$countOrder  		=	$orderCondition->count();
							}
						}
					}
					
					// search management
					$orders			=	$orderCondition->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
					if(Input::get('load_more') == 1){
						return View::make('elements.marketplace-elements.more_order',compact('orders','limit','offset','countOrder'));
					}else{
						// clear search session and order
						if(Session::has('order_by_order')){
							Session::forget('order_by_order');
						}
						if(Session::has('search_order')){
							Session::forget('search_order');
						}
						return View::make('elements.marketplace-elements.orders',compact('orders','limit','offset','countOrder'));
					}										
		
				break;
			
				// case for negotiation requests
				case 'negotiation-section':
				
					$marketPlaceId				=	Input::get('marketplace_id');
					$DB 						=   BuyerOffer::with('userDetail','marketplace','buyerDetail')->where('owner_id',Auth::user()->_id)->where('marketplace_id',$marketPlaceId);
					$countMarketPlaceoffers 	=  $DB->count();
					
					if($search!=''){
						Session::put('search_negotiation_request',$search);
					    $DB->where('status',(int) $search);
						$countMarketPlaceoffers  			=	$DB->count();
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('search_negotiation_request')  && $search!=''){
								$DB->where('status',(int) Session::get('search_negotiation_request'));
								$countMarketPlaceoffers  		=	$DB->count();
							}
						}
					}
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_buyers_negotiation',Input::get('order_by'));
						$val 	=	explode(',',Input::get('order_by'));
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('order_by_buyers_negotiation')){
								$val 	=	explode(',',Session::get('order_by_buyers_negotiation'));
							}
						}
					}
					
					$orderBy				=	(isset($val[0])) ? $val[0]:'created_at';
					$order					=	(isset($val[1])) ? $val[1]:'desc';
					
					$marketPlaceoffers 		=  $DB->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
					
					if(Input::get('load_more') == 1){
						return View::make('elements.marketplace-elements.more_negotiation_request',compact('marketPlaceoffers','limit','offset','countMarketPlaceoffers'));
					}else{
						// clear search session and order
						if(Session::has('search_negotiation_request')){
							Session::forget('search_negotiation_request');
						}
						if(Session::has('order_by_buyers_negotiation')){
							Session::forget('order_by_buyers_negotiation');
						}
						return View::make('elements.marketplace-elements.negotiation_request',compact('marketPlaceoffers','countMarketPlaceoffers','limit','offset'));
					}
				break;
				
				// case for negotiation requests
				case 'buyers-section':
					
					$currentDate	=	date(Config::get('Reading.date'));
					$DB 			=   BuyerRequest::with('userDetail','buyerRequestResponse','category')->where('buyer_id','!=',Auth::user()->_id)->where('last_date','>=',$currentDate);
					$countRequest 	=  $DB->count();
					
					if($search!=''){
						Session::put('search_buyer_request',$search);
					    $DB->where('product_name', 'like',"$search%");
						$countRequest  			=	$DB->count();
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('search_buyer_request')){
								$searchValue	=	Session::get('search_buyer_request');
								$DB->where('product_name','like',"$searchValue%" );
								$countRequest  		=	$DB->count();
							}
						}
					}
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_buyers',Input::get('order_by'));
						$val 	=	explode(',',Input::get('order_by'));
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('order_by_buyers')){
								$val 	=	explode(',',Session::get('order_by_buyers'));
							}
						}
					}
					$orderBy				=	(isset($val[0])) ? $val[0]:'created_at';
					$order					=	(isset($val[1])) ? $val[1]:'desc';
					$buyerRequests 			=  	$DB->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
					
					if(Input::get('load_more') == 1){
						return View::make('elements.marketplace-elements.more_buyer_request',compact('buyerRequests','limit','offset','countRequest'));
					}else{
						// clear search session and order
						if(Session::has('search_buyer_request')){
							Session::forget('search_buyer_request');
						}
						if(Session::has('order_by_buyers')){
							Session::forget('order_by_buyers');
						}
						return View::make('elements.marketplace-elements.buyers_request',compact('buyerRequests','countRequest','limit','offset'));
					}
				break;
				
				case 'report_problem-section':
					$search						=	Input::get('search');
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					
					$countReport  				=	OrderItem::where('seller_id',Auth::user()->id)
														->where('report_title', 'exists', true)->count();
					
					$reportCondition		 	=   OrderItem::with('marketPlaceImages')
														->where('report_title', 'exists', true)
														->where('seller_id',Auth::user()->id);													
					
					$orderBy					=	(isset($val[0])) ? $val[0]:'updated_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('report_problem',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('report_problem')){
							$val 				=	explode(',',Session::get('report_problem'));
						}
					}
					// search management
					if($search!=''){
						Session::put('search_report_problem',$search);
						$reportCondition->where('title','like',"$search%");
						$countReport 		=	$reportCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_report_problem')){
								$search			=	Session::get('search_report_problem');	
								$reportCondition->where('title','like',"$search%");
								$countReport	=	$reportCondition->count();
							}
						}
					}
					$reportCondition 	=  $reportCondition->skip($offset)->take($limit)
												->orderBy($orderBy,$order)->get();

					if(Input::get('load_more')==1){
						return View::make('elements.marketplace-elements.more_seller_report_problem',compact('countReport','offset','limit','reportCondition'));
					}else{
						// clear search session and order
						if(Session::has('report_problem')){
							Session::forget('report_problem');
						}
						if(Session::has('search_report_problem')){
							Session::forget('search_report_problem');
						}							
						return View::make("elements.marketplace-elements.seller_report_problem",compact('countReport','offset','limit','reportCondition'));
					}
				break;
					
				//contest list section
				case 'contest-section':
					
					$contestCondition 	=   MarketPlace::with('marketPlaceImages','causesDetail')
												->where('user_id',Auth::user()->_id)
												->where('contest.contest_type',1)
												->where('is_deleted','!=',1);
												
					$contestCount		=	$contestCondition->count();
					
					if($search!=''){
						Session::put('search_buying',$search);
						$contestCondition->where('contest.name','like',"$search%");
						$contestCount 		=	$contestCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_buying')){
								$search			=	Session::get('search_buying');	
								$contestCondition->where('contest.name','like',"$search%");
								$contestCount	=	$contestCondition->count();
							}
						}
					}
					
					
					
					if(Input::get('order_by')!=''){
						Session::put('order_by_order',Input::get('order_by'));
						$val 	=	explode(',',Input::get('order_by'));
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('order_by_order')){
								$val 	=	explode(',',Session::get('order_by_order'));
							}
						}
						
					}
					
					$orderBy				=	(isset($val[0])) ? $val[0]:'created_at';
					$order					=	(isset($val[1])) ? $val[1]:'desc';
					
					// search management
					$contest			=	$contestCondition->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
					
					if(Input::get('load_more') == 1){
						return View::make('elements.marketplace-elements.more_contest_list',compact('contest','limit','offset','contestCount'));
					}else{
						// clear search session and order
						if(Session::has('order_by_order')){
							Session::forget('order_by_order');
						}
						
						if(Session::has('search_buying')){
							Session::forget('search_buying');
						}
						
						return View::make('elements.marketplace-elements.contest_list',compact('contest','limit','offset','contestCount'));
					}
				break;
				
			}
		}
	}// end loadMarketplaceElements()
	
	/**
	 * Function for my collection listing
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function myCollection($limit = 0){
		// limit offset
		$search					=	Input::get('search');
		$searchElement			=	Input::get('element');
		$offset					=	(int)Input::get('offset',0);
		$limit					=	(int)Input::get('limit',Config::get("Reading.record_front_per_page")); 
		$type					=	(isset($searchElement)) ? $searchElement :'marketplace';
		$myCollection			=	 MyCollection::where('user_id',Auth::user()->_id)->where('type',$type)->get();
		$dropDownSelect			=	DropDown::query()->where('dropdown_type','market-category')->orderBy('name','asc')->lists('name','_id')->toArray();
		
		if(!$myCollection->isEmpty()){
			$myCollection =	 $myCollection->toArray();
			foreach($myCollection as $value){
				$collectionIds[]	=	$value['marketplace_id'];	
			}
		}else{
			$collectionIds =	 Array();
		}
		
		$countMarketPlace  		=	MarketPlace::where('is_active',ACTIVE)->whereIn('_id',$collectionIds)->where('is_deleted','!=',1)->count();
		$marketPlaceCondition 	=   MarketPlace::where('is_active',ACTIVE)->with('marketPlaceImages','marketPlaceView','MarketPlaceLikes','userDetail')
										->whereIn('_id',$collectionIds)
										->where('is_deleted','!=',1);
		
		// order management
		if(Input::get('order_by')!=''){
			Session::put('order_by_marketplace',Input::get('order_by'));
			$val 	=	explode(',',Input::get('order_by'));
		}else{
			if(Session::has('order_by_marketplace')){
				$val 	=	explode(',',Session::get('order_by_marketplace')); 
			}
		}
		/* For Market Place Category Or SubCategory Searching Start*/		
		if(Input::get('category_id') != '' && Input::get('sub_category_id') != ''){
			Session::put('marketplace_by_collection_category_id',Input::get('category_id'));
			Session::put('marketplace_by_collection_sub_category_id',Input::get('sub_category_id'));
			$val1	=	Input::get('category_id');
			$val2	=	Input::get('sub_category_id');
			$marketPlaceCondition->where('category_id',$val1)->where('sub_category_id',$val2);
			$countMarketPlace  		=	$marketPlaceCondition->count();
		}elseif(Input::get('category_id') != ''){
			Session::put('marketplace_by_collection_category_id',Input::get('category_id'));
			if(Session::has('marketplace_by_collection_sub_category_id')){
				Session::forget('marketplace_collection_by_sub_category_id');
			}
			$val1	=	Input::get('category_id');
			$marketPlaceCondition->where('category_id',$val1);
			$countMarketPlace  		=	$marketPlaceCondition->count();
		}else{
			if(Session::has('marketplace_by_collection_category_id')){
				$val1 	=	Session::get('marketplace_by_collection_category_id');
				$val2 	=	Session::get('marketplace_by_collection_sub_category_id');
				if($val2 == ''){
					$marketPlaceCondition->where('category_id',$val1);
				}else{
					$marketPlaceCondition->where('category_id',$val1)->where('sub_category_id',$val2);
				}	
				$countMarketPlace  		=	$marketPlaceCondition->count();
			}
		}
		/* For Market Place Category Or SubCategory Searching End*/	
		$orderBy				=	(isset($val[0])) ? $val[0]:'updated_at';
		$order					=	(isset($val[1])) ? $val[1]:'desc';
		// search management
		if($search!=''){
			Session::put('search_marketplace',$search);
			$marketPlaceCondition->where('title','like',"$search%");
			$countMarketPlace  		=	$marketPlaceCondition->count();
		}else{
			if(Session::has('search_marketplace') && $search !=''){
				$search					=	Session::get('search_marketplace');
				$marketPlaceCondition->where('title','like',"$search%");
				$countMarketPlace  		=	$marketPlaceCondition->count();
			}
		}
		// search management
		
		// searching based on type
		
		
		$marketPlaceContent			=	$marketPlaceCondition->select('user_id','title','short_description','discounted_price','price','discount')->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
		$countUserFollowers			=	UserFollower::where('following_id',Auth::user()->_id)->count();
		$countUserFollowings		=	UserFollower::where('follwer_id',Auth::user()->_id)->count();
		if(Request::ajax()){
			return View::make('elements.more_collection',compact('marketPlaceContent','limit','offset','countMarketPlace'));
		}else{
			// clear search session and order
			if(Session::has('order_by_marketplace')){
				Session::forget('order_by_marketplace');
			}
			if(Session::has('search_marketplace')){
				Session::forget('search_marketplace');
			}
			if(Session::has('marketplace_by_collection_category_id')){
				Session::forget('marketplace_by_collection_category_id');
			}
			if(Session::has('marketplace_by_collection_sub_category_id')){
				Session::forget('marketplace_by_collection_sub_category_id');
			}
			//~ pr($marketPlaceContent);die;
			return View::make('marketplace.collection',compact('marketPlaceContent','limit','offset','countMarketPlace','countUserFollowers','countUserFollowings','dropDownSelect'));
		}
	}//end myCollection()
	
	/**
	 * Function for delete collection
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function myCollectionDelete(){
		
		if(Request::ajax()){
				$myCollectionId	=	Input::get('myCollectionId');
				$collection		=	MyCollection::where('marketplace_id',$myCollectionId)->where('user_id',Auth::user()->_id)->delete();
				
				$collectionCount	=	MyCollection::where('user_id',Auth::user()->_id)->count();
			
				if($collectionCount < 1){
					$lastRecord		=	true;
				}
				
				$response	=	array(
					'success' 		=>	'1',
					'lastRecord'	=>	isset($lastRecord) ? $lastRecord : ''
				);
			return  Response::json($response); die;
		}	
		
	}//end myCollectionDelete()
	
	/**
	 * Function for delete multiple collections
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function myCollectiMultipleDelete(){
		if(Request::ajax()){
			
			if(!empty(Input::get('ids'))){
				MyCollection::whereIn('marketplace_id',Input::get('ids'))->where('user_id',Auth::user()->_id)->delete();
				
			}
			
			$collectionCount	=	MyCollection::where('user_id',Auth::user()->_id)->count();
			
			if($collectionCount < 1){
				$lastRecord		=	true;
			}
			$response	=	array(
					'success' 		=>	'1',
					'lastRecord'	=>	isset($lastRecord) ? $lastRecord : ''
				);
			return  Response::json($response); die;
		}	
	}//end myCollectiMultipleDelete()
	
	
	
	/**
	 * Function for product detail page 
	 * 
	 * @param $productId as product id
	 * 
	 * return view page 
	 * 
	 */
	public function productDetail($productId){
		
		$userId						=	MarketPlace::where('_id',$productId)->pluck('user_id');
		$productDetailCondition 	=	 MarketPlace::with('userDetail','causesDetail','questionAnswer','marketPlaceUpdate','userFollowers'												,'myCollection','marketPlaceSupporters','category')
													->where('is_deleted','!=',1)
													->where('_id',$productId);
		
		if($userId != Auth::user()->_id){
			$productDetail  = $productDetailCondition
								->where('is_approved',APPROVED)
								->where('is_active','=',ACTIVE)
								->first(); 
		}else{
			$productDetail  = $productDetailCondition->first(); 
		}
	
		/*
		* check if product is OUT OF STOCK
		*/
		$quantity			=	isset($productDetail->quantity) ? $productDetail->quantity : 0;
		$remaingingItems	=	CustomHelper::getRemainingItemsCount($productId,$quantity);
		
		if(empty($productDetail)){
			return View::make('errors.404');
		}
		
		/*
		* check if marketplace has expired
		*/
	
		
		$currentTime			=	time();
		$marketPlaceExpiryTime	=	$productDetail->expire_date;		
		if(!empty($marketPlaceExpiryTime) && $currentTime > $marketPlaceExpiryTime){
			$productExpired = true;
		}
		
		/** cookie save for user view**/
		 if(!isset($_COOKIE["marketplace_$productId"])){
			setcookie("marketplace_$productId",1,(time() + MARKETPLACE_VIEW_TIME));
			$objectMarketPlaceView 						=	new MarketPlaceView();
			$objectMarketPlaceView->marketplace_id 		= $productId;	
			$objectMarketPlaceView->user_id 			= Auth::user()->id;
			$objectMarketPlaceView->save();
		 }
		
		//seller avg rating
		$sellerMarketplaceId			=	 MarketPlace::Conditions()->where('user_id',$productDetail->user_id)->lists('_id');
			
		$sellerProductAvgRating			=	 MarketPlaceRateAndReviews::whereIn('marketplace_id',$sellerMarketplaceId)->avg('rating');
			
		  /** other item by user**/
		 $userProducts					=	 MarketPlace::with('marketPlaceImages','userDetail','marketPlaceReviews')->Conditions()->where('user_id',$productDetail->userDetail->id)->where('_id','!=',$productId)->where('is_active',1)->where('is_deleted','!=',1)->get();
		 
		 /**  total like on marketplace`**/
		$totalLikes						=	 MarketPlaceLike::where('product_id',$productId)->count();
		  /** check like  already**/
		 $currentUserLikeCount			=	 MarketPlaceLike::where('product_id',$productId)->where('user_id',Auth::user()->_id)->count();
		  /** check remind me already**/
		 $currentUserReminderCount 		=	MarketPlaceReminders::where('marketplace_id',$productId)->where('user_id',Auth::user()->_id)->count();	
		  /** all review on marketplace**/
		 $marketPlaceReviews			=	 MarketPlaceRateAndReviews::where('marketplace_id',$productId)->where('is_active',ACTIVE)->count();
		  /** for show star or not show**/
		 $reviewForCurrentUser			=	 MarketPlaceRateAndReviews::where('marketplace_id',$productId)->where('user_id',Auth::user()->_id)->count();
		  /**  total Supporters on marketplace`**/
		 $totalSupporters				=	 MarketPlaceDonation::where('marketplace_id',$productId)->count();
		
		 /**  total contest participant on marketplace`**/
		$contestCount					=	MarketPlace::where('_id',$productId)->where('contest.contest_type',1)->count();	
		 
		 $currentUserFollowCount		=	 UserFollower::where('follower_id', Auth::user()->_id)->where('following_id',$productDetail->user_id)->count(); 
		 
		
		 $previousProductID 			= 	MarketPlace::where('_id', '<', $productId)->where('user_id',$productDetail->userDetail->id)->pluck('_id');
		 $nextProductID 				= 	MarketPlace::where('_id', '>', $productId)->where('user_id',$productDetail->userDetail->id)->pluck('_id');
		
		$currentDateAndTime				=	date(Config::get('Reading.date'));
		 
		$getOfferForUser				=	BuyerOffer::where('user_id',Auth::user()->_id)->where('marketplace_id',$productId)->where('status','!=',MARKET_PLACE_OFFER_PROCESSING)->where('price','!=','')->where('time_limit','>',$currentDateAndTime)->first();
		
		$getOfferForUserExists			=	BuyerOffer::where('user_id',Auth::user()->_id)->where('marketplace_id',$productId)->count();
	
		$newProductRequestCounterOffer	=	BuyerRequestResponse::where('offer.marketplace_id', $productId)->where('buyer_id',Auth::user()->_id)->project(array( 'offer.$' => 1 ) )->orderBy('created_at','desc')->first();
		
		$soldItems 						=	OrderItem::where('marketplace_id',$productId)->sum('quantity');	
		$productSharesCount 			=	MarketPlaceShare::where('marketplace_id',$productId)->count();	
		$donatedAmount					=	MarketPlaceDonation::where('marketplace_id',$productId)->sum('amount');
		$blocks							=	Block::getResult('marketing-page-2',array('_id','description','block'));
		
		//~ return View::make('index',compact('blocks'));
		return View::make('marketplace.detail',compact('productDetail','userProducts','currentUserLikeCount','totalLikes','marketPlaceReviews','reviewForCurrentUser','currentUserReminderCount','totalSupporters','currentUserFollowCount','previousProductID','nextProductID','getOfferForUser','getOfferForUserExists','soldItems','productSharesCount','donatedAmount','sellerProductAvgRating','blocks','productExpired','newProductRequestCounterOffer','currentDateAndTime','contestCount'));
	}//end productDetail()
	
	/**
	 * Function for like and unlike the marketplace
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	public function likeProduct(){
		if(Request::ajax()){   
			$userId		=	Auth::user()->_id;
			$productId	=	Input::get('product_id');
			$status		=	Input::get('status');
			if($userId != '' && $productId !='' && $status !=''){
				$userId		=	Auth::user()->id;
				if($status == 1){
					MarketPlaceLike::insert(array(
						'user_id'		=> $userId,
						'product_id'	=> $productId,
						'created_at'	=>new MongoDate()
					));
				}else{
					MarketPlaceLike::where('user_id',$userId)->where('product_id',$productId)->delete();
				}
			}
		}
	}//end likeProduct()
	
		/**
	 * Function for share marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function shareMarketPlace(){
	
		if(Request::ajax()){ 
			
			$marketplaceId	=	 Input::get('marketplace_id');
			$userId			=	 Auth::user()->_id;
		
			$obj			=	new MarketPlaceShare;
					
			$obj->marketplace_id	=	$marketplaceId;
			$obj->user_id			=	$userId;
			$obj->save();
			
			$response	=	array('success' => 1);
			return  Response::json($response);	 
	
		}
		
	}//end shareMarketPlace()
	
	/**
	 * Function for follow/unfollow the marketplace
	 *
	 * @param null
	 *
	 * @return redirect page. 
	 */
	public function followUser(){
		if(Request::ajax()){   
			$userId			=	Auth::user()->_id;
			$otherUserId	=	Input::get('other_user_id');
			$status			=	Input::get('status'); 
			if($otherUserId != '' && $userId !='' && $status !=''){
				if($status == FOLLOW){
					UserFollower::insert(array(
						'follower_id'	=>$userId ,
						'following_id'	=> $otherUserId,
						'created_at'	=>new MongoDate()
					));
				}elseif($status == UNFOLLOW){
					UserFollower::where('following_id',$otherUserId)->where('follower_id',$userId)->delete();
				}
			}
			
		}
	}//end followUser()
	
	/**
	 * Function for create marketplace
	 * 
	 * @param marketPlaceId as id of marketplace
	 * 
	 * return view page 
	 * 
	 */
	public function createMarketPlace($marketPlaceId=''){
		if(Request::ajax()){ 
			$formData		=	Input::all();
			//pr($formData);
			$formName		=	$formData['form_name'];
			
			if(!empty($formData)){
				unset($formData['_token']);
				unset($formData['form_name']);
				// validation rule 
				switch ($formName) {
					case "overview":
						$validationRules	=	array(
							'title' 				=> 'required',
							'category_name'			=> 'required',
							'quantity' 				=> 'required|numeric|min:0',	
							'expire_date'  			=> 'required',
							'short_description'  	=> 'required|max:290', 
							'description'  			=> 'required',
						);
					break;
					case "price":
						$anyTwistPercentage					=	Config::get('Site.anytwist_percentage');
						$maxCausePercentage					=  100-$anyTwistPercentage;
						
						$validationRules	=	array(
							'price' 								=> 'required|numeric',
							'discount'								=>  'numeric|between:0,99.99',
							'tax_perc'								=>  'required_if:tax_option,'.PAIDSHIPPING.'|numeric|between:0,99.99',
							'cause_share'							=>  'numeric|between:'.Config::get('Site.cause_percentage').',99.99|max:'.$maxCausePercentage,
							'national_shipping_charge'				=>  'required_if:shipping_type,'.PAIDSHIPPING.'|numeric',
							'international_shipping_charge'			=>  'required_if:shipping_type,'.PAIDSHIPPING.'|numeric',
						);
					break;
					case "gallery":
						$validationRules	=	array(
						
						);
					break;
					case "cause":

						$validationRules	=	array(
							'causes_name' 			=> 'required',
						);
					break;
					
					case "contest":

						$validationRules	=	array(
							'name' 			=> 'required_if:contest_type,'.CONTEST_VALUE_YES,
							'quantity_of_prize'=> 'required_if:contest_type,'.CONTEST_VALUE_YES.'|numeric',
							'price_per_quantity'=> 'required_if:contest_type,'.CONTEST_VALUE_YES.'|numeric',
							'contest_expire_date'=> 'required_if:contest_type,'.CONTEST_VALUE_YES,
							'contest_description'=> 'required_if:contest_type,'.CONTEST_VALUE_YES,
						);
					break;
					
				}

				$validator = Validator::make(
					$formData,
					$validationRules,
					array(
						'causes_name.required' 							=> 'Please select cause name',
						'national_shipping_charge.required_if'			=> 'National shipping charge is required.',
						'international_shipping_charge.required_if'		=> 'International shipping charge is required.',
						'tax_perc.between'								=> 'The tax percentage must be between 0 and 99.99.',
						'name.required_if'								=> 'Name field is required.',
						'quantity_of_prize.required_if'					=> 'Quantity Prize is required.',
						'price_per_quantity.required_if'				=> 'Price Per Quantity is required.',
						'contest_expire_date.required_if'				=> 'Expire Date is required.',
						'contest_description.required_if'				=> 'Description is required.',
					)
				);
				
				if ($validator->fails()){		
					$allErrors='<ul>';
					foreach ($validator->errors()->all('<li>:message</li>') as $message)
					{
							$allErrors .=  $message; 
					}
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => false,
						'errors' => $allErrors
					);
					return  Response::json($response); die;

				}else{
					$redirectUrl	=	'';
					switch ($formName) {	
						case "overview":
							$createMarketOverview	=	array(
								'user_id' 				=>  Auth::user()->id,
								'quantity'				=> (int) Input::get('quantity'),
								'category_id' 			=>  Input::get('category_name'),						
								'sub_category_id' 		=>  Input::get('sub_category_name'),						
								'title'					=>  ucfirst(Input::get('title')),
								'short_description'		=>  Input::get('short_description'),
								'description'			=>  Input::get('description'),
								'expire_date'			=>  Input::get('expire_date')
							);
							Session::put('create_marketplace.overview',$createMarketOverview);
						break;
						case "price":
							
							### MarketPlace Tax Section Start ###
							$taxOption						=  (Input::get('tax_option'));
							if(!empty($taxOption)){
								$taxPerc 					=  (double) Input::get('tax_perc');
							}else{
								$taxPerc 					=	0;
							}
							### MarketPlace Tax Section End ###
							$createMarketPrice	=	array(
								'discount'									=>  (double) Input::get('discount'),
								'cause_share'								=>  (double) Input::get('cause_share'),
								'price' 									=>  (double)Input::get('price'),
								'discounted_price' 							=>  (double)Input::get('discounted_price'),
								'shipping_type' 							=>  (int)Input::get('shipping_type'),
								'tax_type' 									=>  (int)$taxOption,
								'earning' 									=>  (double) (Input::get('earning')),
								'national_shipping_charge' 					=>  (double) (Input::get('national_shipping_charge')),
								'international_shipping_charge' 			=>  (double) (Input::get('international_shipping_charge')),
								'tax_perc' 									=>  $taxPerc
							);
							
							Session::put('create_marketplace.price',$createMarketPrice);
						break;
						case "gallery":
							$marketPlaceImages							=	Session::get('marketplace_image_array');
							$marketPlaceImageObject						=	new MarketPlaceImage();
							if(empty($marketPlaceImages)){
								$response	=	array(
									'success' => false,
									'errors' => "<ul><li>The Image field is required.</li></ul>"
								);
								return  Response::json($response); die;
							}
							break;
						
						case "contest":
							$contestType	=	Input::get('contest_type');
							
							$createMarketContest	=	array(
									'contest_type' 		  =>   (int)$contestType,
									'name'				  =>	Input::get('name'),
									'quantity_of_prize'      =>	(double)Input::get('quantity_of_prize'),
									'price_per_quantity'  =>	(double)Input::get('price_per_quantity'),
									'contest_expire_date' =>	Input::get('contest_expire_date'),
									'contest_description' =>	Input::get('contest_description'),
										
							);
							Session::put('create_marketplace.contest',$createMarketContest);
							
						break;
						
						case "cause":
							$marketplaceData							=  new MarketPlace();
							$marketplaceData->user_id 					=  Auth::user()->id;
							$marketplaceData->quantity 					=  (int) Session::get('create_marketplace.overview.quantity');
							$marketplaceData->category_id 				=   Session::get('create_marketplace.overview.category_id');						
							$marketplaceData->sub_category_id 			=   Session::get('create_marketplace.overview.sub_category_id');						
							$marketplaceData->title						=   Session::get('create_marketplace.overview.title');
							$marketplaceData->expire_date				=   (int) strtotime(Session::get('create_marketplace.overview.expire_date'));
							$marketplaceData->discount					=  (double) Session::get('create_marketplace.price.discount');
							$marketplaceData->tax_perc					=  (double) Session::get('create_marketplace.price.tax_perc');
							$marketplaceData->price 					=  (double) Session::get('create_marketplace.price.price');
							$marketplaceData->short_description			=  Session::get('create_marketplace.overview.short_description');
							$marketplaceData->description				=  Session::get('create_marketplace.overview.description');
							$marketplaceData->discounted_price 			=  (double)Session::get('create_marketplace.price.discounted_price');	
							
							$anyTwistfees								=  (double) (Session::get('create_marketplace.price.discounted_price')!='') ? Session::get('create_marketplace.price.discounted_price'): Session::get('create_marketplace.price.price')*Config::get('Site.anytwist_percentage')/100;
							$causeDonation								=  (double)(Session::get('create_marketplace.price.discounted_price')!='') ? Session::get('create_marketplace.price.discounted_price'): Session::get('create_marketplace.price.price')* Session::get('create_marketplace.price.cause_share')/100;
							
							$marketplaceData->shipping_type				=  Session::get('create_marketplace.price.shipping_type');
							$marketplaceData->tax_type					=  Session::get('create_marketplace.price.tax_type');
							$marketplaceData->earning					=  (double)Session::get('create_marketplace.price.earning');
							$marketplaceData->anytwist_fees				=  $anyTwistfees;
							$marketplaceData->cause_donation			=  $causeDonation;
							$marketplaceData->cause_percentage			=   Session::get('create_marketplace.price.cause_share');
							if(Session::get('create_marketplace.price.shipping_type')==PAIDSHIPPING){
								$marketplaceData->national_shipping_charge 		=  Session::get('create_marketplace.price.national_shipping_charge');
								$marketplaceData->international_shipping_charge =  Session::get('create_marketplace.price.international_shipping_charge');	
							}
							 $marketplaceData->causes_id 			=   Input::get('causes_name');	
							 $causeId								=	Input::get('causes_name');
							 $getUserId								=   Causes::where('_id',$causeId)->pluck('user_id');
						     $isAdmin								=	User::where('_id',$getUserId)->pluck('user_role_id');					
							 $marketplaceData->is_approved 			=  	( Config::get("Site.causes_auto_approval") == 1 || $isAdmin == ADMIN_USER ) ? APPROVED : UNAPPROVED;
							 $marketplaceData->is_active			= 	ACTIVE; 
							//save market place contest value
							if(Session::get('create_marketplace.contest.contest_type')==CONTEST_VALUE_NO){
								$marketplaceData->contest		=	array(
									'contest_type' 		  =>    Session::get('create_marketplace.contest.contest_type')
									);
								}else{
							$marketplaceData->contest	=	array(
									'contest_type' 		  =>    Session::get('create_marketplace.contest.contest_type'),
									'name'				  =>	 Session::get('create_marketplace.contest.name'),
									'quantity_of_prize'   =>	 (double)Session::get('create_marketplace.contest.quantity_of_prize'),
									'price_per_quantity'  =>	 (double)Session::get('create_marketplace.contest.price_per_quantity'),
									'contest_expire_date' =>	 (int) strtotime(Session::get('create_marketplace.contest.contest_expire_date')),
									'contest_description' =>	 Session::get('create_marketplace.contest.contest_description'),
									'completed_contest'		=>	COMPLETED_CONTEST,	
							);
						}
							
							 $marketplaceData->save();
								
							$adminId	=	User::where('user_role_id',ADMIN_USER)->pluck('_id');
							
							/* send notification to admin for cause approval*/
							if(Config::get("Site.causes_auto_approval") != 1 || $isAdmin != ADMIN_USER ){
								$notificationData	=	array(
									'user_id'			=>	$adminId,
									'created_by'		=>	Auth::user()->_id,
									'notification_type'	=>	CAUSE_APPROVAL_REQUEST_TO_ADMIN,
									'url'				=>  route('Marketplace.request'),
								);
								$this->save_notifications($notificationData);
							}
							/* send notification to buyer if any product is create same as he/she requested  */
							$redirectUrl		=	route("market-place");
							
							if(Input::get('product-requested-by-buyer') != ''){
								
								$buyerRequestId		=	Input::get('product-requested-by-buyer');
								
								$redirectUrl	=	route("choose-existing",$buyerRequestId);
								if( $marketplaceData->is_approved == UNAPPROVED){
									Session::flash('success','Marketplace has been added successfully but you can send it to buyer when it is approved by site admin.');
								}
							}
							
							if(Session::has('marketplace_image_array')){
								$marketPlaceImages							=	Session::get('marketplace_image_array');
								if(!empty($marketPlaceImages)){
									foreach($marketPlaceImages as $value){
										$marketPlaceImageObject						=	new MarketPlaceImage();
										$marketPlaceImageObject->user_id 			=   Auth::user()->id;
										$marketPlaceImageObject->parent_id 			=   $marketplaceData->id;
										$marketPlaceImageObject->image 				=   $value;	
										$marketPlaceImageObject->save();
									}
								}
								Session::forget('marketplace_image_array');
							}
						break;
						
					}
					$response	=	array(
						'success' 		=>	'1',
						'element' 		=>	$formName,
						'redirectUrl'	=>	$redirectUrl
					);
					return  Response::json($response); die;						
				}	
				
			}
	
		}
		if(Input::get('type')!='cause'){
			Session::forget('create_marketplace');
			Session::forget('marketplace_image_array');
		}
		return View::make('marketplace.create');
	}//end createMarketPlace()
	
	/**
	 * Function for upload marketplace images
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function imageUpload(){
		if(Input::hasFile('file') ){	
			$extension 	=	 Input::file('file')->getClientOriginalExtension();
			$fileName	=	 Auth::user()->_id.rand().time().'markeplace_image.'.$extension;
			if(Input::file('file')->move(MARKET_PLACE_IMAGE_ROOT_PATH, $fileName)){
				$saveData					=	array();
				$saveData[]					=	$fileName;
				if(Session::has('marketplace_image_array')){
					$sessionData			=	Session::get('marketplace_image_array');
					$saveData 				=	array_merge_recursive($saveData,$sessionData);
				}
				Session::put('marketplace_image_array',$saveData);
				if(Session::has('markeplace_count')){
					$count = Session::get('marketplace_count');
					$count++;
				}else{
					$count = 1;
				}
				Session::put('marketplace_count',$count);
				$responseData = array();
				$responseData['name'] 	= $fileName;
				$responseData['id'] 	= time()+rand();
				return Response::json($responseData);
			}
		}
	}//end imageUpload()
	
	/**
	 * Function for remove marketplace image
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function removeMarketPlaceImage(){
		$file_name			=	Input::get('img_name');
		$job_image_array	=	Session::get('marketplace_image_array');
		foreach($job_image_array as $key => $val){
			if($val == $file_name){
				Session::forget('marketplace_image_array.'.$key);
					if(File::exists(MARKET_PLACE_IMAGE_ROOT_PATH.$file_name)){
						 @unlink(MARKET_PLACE_IMAGE_ROOT_PATH.$file_name);
					}
				
				if(empty(Session::get('marketplace_image_array'))){
					Session::forget('marketplace_image_array');
				}
				break;
			}
		}
		if(Session::has('marketplace_image_array')){
			$count	= count(Session::get('marketplace_image_array'));
		}else{
			$count	=	0;
		}
		$response	=	array('count'=>$count);
		return Response::json($response);
	}// end removeMarketPlaceImage()
	
	
	/**
	 * Function for remove marketplace image
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function removeCreateMarketPlaceImage(){
		
		$file_name			=	Input::get('img_name');
		$key			=	Input::get('key');
		$job_image_array	=	Session::get('marketplace_image_array');
			
			Session::forget('marketplace_image_array.'.$key);
			if(File::exists(MARKET_PLACE_IMAGE_ROOT_PATH.$file_name)){
				@unlink(MARKET_PLACE_IMAGE_ROOT_PATH.$file_name);
			}	
	}// end removeCreateMarketPlaceImage()
	
	/**
	 * Function use ask question for marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function askQuestionForMarketplace(){
		$marketplaceId	=	 Input::get('marketplace_id');
		$userId			=	 Auth::user()->_id;
		if(Request::ajax()){ 
			$formData	=	Input::all();
			//~ pr($formData); die;
			if(!empty($formData)){
				$validator = Validator::make(
					Input::all(),
					array(
						'question' 				=> 'required',
					)
				);
				if ($validator->fails()){
					 $allErrors	= '<ul>';
					foreach ($validator->errors()->all('<li>:message</li>') as $message)
					{
							$allErrors .=  $message; 
					}
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => false,
						'errors' => $allErrors
					);
					return  Response::json($response);
				}else{
					$marketplaceObj	=	new MarketPlaceQuestionAnswer;
					$marketplaceObj->marketplace_id	=	$marketplaceId;
					$marketplaceObj->user_id		=	Auth::user()->_id;
					$marketplaceObj->question		=	nl2br(strip_tags(trim(Input::get('question'))));
					$marketplaceObj->is_active		=	ACTIVE;
					$marketplaceObj->save();
					$newQuestiontId		=	$marketplaceObj->_id;	
					$productDetail 		=	 MarketPlace::with('userDetail')->where('_id',$marketplaceId)->first();
					$notificationData	=	array(
						'user_id'			=>	$productDetail->user_id,
						'created_by'		=>	Auth::user()->_id,
						'notification_type'	=>	QUESTION_FOR_MARKETPLACE,
						'url'				=>  route('marketplace-product-question-answer',array($marketplaceId,'question_id'=>$newQuestiontId)),
						'item_name'			=>  $productDetail->title,
						'buyer_name'		=>  Auth::user()->full_name
					);
					$this->save_notifications($notificationData);
					$response	=	array('success' => 1);
					return  Response::json($response);	 
				}
			}
		}
	}//end askQuestionForMarketplace()
	
	/**
	 * Function for reply on user question and show list 
	 *
	 * @param $productId as id marketplace 
	 *
	 * @return response array. 
	 */
	public function productQuestionAnswer($productId=''){
		
		$limit 							=	Config::get("Reading.record_front_per_page");
		$marketPlaceQuestionAnswers 	=	MarketPlaceQuestionAnswer::where('marketplace_id',$productId)->where('is_active',1)->orderBy('updated_at','desc')->limit($limit)->get();
		$resultCount					=	MarketPlaceQuestionAnswer::where('marketplace_id',$productId)->where('is_active',1)->count();
		$offset 						=	0;
		$limit  						=	Config::get("Reading.record_front_per_page");
		return View::make('marketplace.question_answer',compact('marketPlaceQuestionAnswers','resultCount','offset','limit','productId'));
	}//end productQuestionAnswer()
	
	
	/**
	 * Function for save user answer 
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function productQuestionAnswerSave(){
		if(Request::ajax()){ 
			$Id 		=	Input::get('id');
			$answer		=	Input::get('answer');
			if($answer!=''){
				$marketPlaceUpdateObject 					=	MarketPlaceQuestionAnswer::find($Id);
				$marketPlaceUpdateObject->answer 			=	nl2br(strip_tags(trim(Input::get('answer'))));
				$marketPlaceId 								=	$marketPlaceUpdateObject->marketplace_id;
				$userAuth 									=	MarketPlace::where('_id',$marketPlaceId )->where('user_id',Auth::user()->id)->count();
				if($userAuth){
					$marketPlaceUpdateObject->save();
					$productDetail 		=	 MarketPlace::with('userDetail')->where('_id',$marketPlaceId)->first();
					$notificationData	=	array(
						'user_id'			=>	$marketPlaceUpdateObject->user_id,
						'created_by'		=>	Auth::user()->_id,
						'notification_type'	=>	ANSWER_FOR_MARKETPLACE,
						'url'				=>  route('product-detail',array($marketPlaceId)),
						'item_name'			=>  $productDetail->title,
						'buyer_name'		=>  Auth::user()->full_name
					);
					$this->save_notifications($notificationData);
				}
				$response	=	array(
					'success' => true,
				);
				return Response::json($response); die;	
			}else{
				$response	=	array(
					'success' => false,
					'errors' => "<ul><li>Please enter answer.</li></ul>"
				);
				return  Response::json($response); die;
			}
		}
	}//end productQuestionAnswerSave()
	
	/**
	 * Function for list of update and insert update
	 *
	 * @param $productId as id marketplace 
	 *
	 * @return response array. 
	 */
	public function productUpdates($productId){
		$countMarketplace 	=	 MarketPlaceUpdate::where('marketplace_id',$productId)->where('user_id',Auth::user()->id)->count(); 
		$marketPlaceUpdates 	=  MarketPlaceUpdate::where('marketplace_id',$productId)->where('user_id',Auth::user()->id)->orderBy('created_at','desc')->get();
		return View::make('marketplace.updates',compact('marketPlaceUpdates','productId'));
	}//end productUpdates()
	
	/**
	 * Function for save update 
	 *
	 * @param null 
	 *
	 * @return response array. 
	 */
	
	public function productUpdatesSave(){
		if(Request::ajax()){ 
			$formData	=	Input::all();
			if(!empty($formData)){
				######### validation rule #########
				$validationRules	=	array(
					'update' 		=> 'required'
				);
				$validator = Validator::make(
					Input::all(),
					$validationRules
				);
				if (!$validator->fails()){
					$marketPlaceUpdateObject 					=	new MarketPlaceUpdate();
					$marketPlaceUpdateObject->update 			=	nl2br(strip_tags(trim(Input::get('update'))));
					$marketPlaceUpdateObject->user_id 			=	Auth::user()->id;
					$marketPlaceUpdateObject->marketplace_id 	=	Input::get('product_id');
					$marketPlaceUpdateObject->save();
					$response	=	array(
						'success' => true,
					);
					return Response::json($response); die;	
				}else{
					$allErrors='<ul>';
					foreach ($validator->errors()->all('<li>:message</li>') as $message){
							$allErrors .=  $message; 
					}
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => false,
						'errors' => $allErrors
					);
					return  Response::json($response); die;
				}	
			}
		}
	}//end productUpdatesSave()
	
	/**
	 * Function for delete marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function marketPlaceDelete(){
		if(Request::ajax()){
			$markerPlaceId	=	Input::get('marketplace_id');
		
			MarketPlace::where('_id',$markerPlaceId)->update(array('is_deleted' => 1));
		
			$marketPlacecount	=	MarketPlace::where('user_id',Auth::user()->id)->count();
			if($marketPlacecount < 1){
				$lastRecord		=	true;
			}
			$response	=	array(
				'success' 		=>	'1',
				'lastRecord'	=>	isset($lastRecord) ? $lastRecord : ''
			);
			return  Response::json($response); die;
		}	
	}//end marketPlaceDelete()
	
	
	/**
	 * Function for delete multiple marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function marketPlaceMultipleDelete(){
		if(Request::ajax()){
			
			if(!empty(Input::get('ids'))){
				MarketPlace::with('marketPlaceImages','questionAnswer','marketPlaceUpdate','marketPlaceView','MarketPlaceReviews','MarketPlaceLikes')->whereIn('_id',Input::get('ids'))->update(array('is_deleted' => 1));
			}
			
			$marketPlacecount	=	MarketPlace::where('user_id',Auth::user()->id)->count();
			if($marketPlacecount < 1){
				$lastRecord		=	true;
			}
			$response	=	array(
				'success' 		=>	'1',
				
			);
			return  Response::json($response); die;
		}	
	}//end marketPlaceMultipleDelete()
	
	/**
	 * Function for marketplace update delete
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function marketPlaceUpdateDelete(){
		if(Request::ajax()){
			$markerPlaceId	=	Input::get('marketplace_id');
			MarketPlaceUpdate::where('_id',$markerPlaceId)->delete();
			$response	=	array(
				'success' 		=> true
			);
			return  Response::json($response); die;
		}	
	}//end marketPlaceUpdateDelete()
	
	/**
	 * Function for add marketplace to collection
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function addToMyCollection(){
		
		if(Request::ajax()){ 
			
			$marketplaceId	=	 Input::get('marketplace_id');
			$userId			=	 Auth::user()->_id;
		
			$collectionObj	=	new MyCollection;
					
			$collectionObj->marketplace_id	=	$marketplaceId;
			$collectionObj->user_id			=	$userId;
			$collectionObj->type			=	COLLECTION_TYPE_MARKETPLACE;
			$collectionObj->save();
			
			$response	=	array('success' => 1);
			return  Response::json($response);	 
	
		}
	}//end addToMyCollection()
	
	/**
	 * Function for rate and review marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function rateAndReview(){
			
		if(Request::ajax()){ 
			
			$marketplaceId	=	 Input::get('marketplace_id');
			$userId		=	Auth::user()->_id;
			$formData	=	Input::all();
			if(!empty($formData)){
				
				$isPurchsedItem				=	OrderItem::where('buyer_id',Auth::user()->_id)->where('marketplace_id',$marketplaceId)->count();
				if($isPurchsedItem == 0){
					
					$response	=	array(
						'success' => false,
						'errors' => '<li >You have to purchase this item first.</li>'
					);
					return  Response::json($response);
					
				}
				
				$reviewForCurrentUser	=	MarketPlaceRateAndReviews::where('marketplace_id',$marketplaceId)->where('user_id',$userId)->count();
				 
				$message	=	array('score.required'=>'The rating field is required.');
				$validator = Validator::make(
					Input::all(),
					array(
						'score' 		=> ($reviewForCurrentUser < 1) ? 'required' : '',
						'review' 		=> 'required',
					),
					$message
				);
				if ($validator->fails()){
					 $allErrors	= '<ul>';
					foreach ($validator->errors()->all('<li class="review_error_li">:message</li>') as $message)
					{
							$allErrors .=  $message; 
					}
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => false,
						'errors' => $allErrors
					);
					return  Response::json($response);
				}else{
					
					$marketplaceObj	=	new MarketPlaceRateAndReviews;
					
					$marketplaceObj->marketplace_id	=	$marketplaceId;
					$marketplaceObj->user_id		=	$userId;
					$marketplaceObj->review			=	nl2br(strip_tags(trim(Input::get('review'))));
					$marketplaceObj->rating			=	(int)Input::get('score');
					$marketplaceObj->is_active		=	ACTIVE;
					
					$marketplaceObj->save();
					
					$response	=	array('success' => 1);
					return  Response::json($response);	 
				}
			}
		}
	}//end rateAndReview()
	
	
	/**
	 * Function for load rate & review, Q&A and supporters element of marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function reviewElementLoad(){
		$elementName			=	 Input::get('element'); 
		$offset					=	(int)Input::get('offset',0);
		$limit					=	(int)Input::get('limit',Config::get("Reading.record_front_per_page")); 
		$marketplaceId			=	 Input::get('marketplace_id');
		
		switch ($elementName) {
				case 'QuestionAnswer':
					$marketPlaceResult			=	MarketPlaceQuestionAnswer::where('marketplace_id',$marketplaceId)->where('is_active',ACTIVE)->where('answer', 'exists', true)->skip($offset)->take($limit)->orderBy('created_at','desc')->get();
					$resultCount				=	MarketPlaceQuestionAnswer::where('marketplace_id',$marketplaceId)->where('is_active',ACTIVE)->where('answer', 'exists', true)->count();
					$elementViewFile			=	'marketplace_question_answer';
				break;
				case 'QuestionAnswerReply':
					$marketPlaceResult 			=	MarketPlaceQuestionAnswer::where('marketplace_id',$marketplaceId)->where('is_active',ACTIVE)->skip($offset)->take($limit)->orderBy('updated_at','desc')->get();
					$resultCount				=	MarketPlaceQuestionAnswer::where('marketplace_id',$marketplaceId)->where('is_active',ACTIVE)->count();
					$elementViewFile			=	'marketplace_question_answer_reply';
				break;
				
				case 'RateAndReviews':
					$marketPlaceResult			=	MarketPlaceRateAndReviews::with('userDetail')->where('marketplace_id',$marketplaceId)->where('is_active',ACTIVE)->skip($offset)->take($limit)->orderBy('created_at','desc')->get();
					
					$resultCount				=	MarketPlaceRateAndReviews::where('marketplace_id',$marketplaceId)->where('is_active',ACTIVE)->count();
					$elementViewFile			=	'marketplace_rate_reviews';
				break;
				case 'Supporters':
					$marketPlaceResult			=	MarketPlaceDonation::where('marketplace_id',$marketplaceId)->skip($offset)->take($limit)->orderBy('created_at','desc')->get();
					$resultCount				=	MarketPlaceDonation::where('marketplace_id',$marketplaceId)->count();
					$elementViewFile			=	'marketplace_supporters';
				break;
				case 'Contests':
					$marketPlaceResult			=	Marketplace::where('_id',$marketplaceId)->first();	
					$elementViewFile			=	'marketplace_contests';
				break;
				
		}
			
		$reviewForCurrentUser	=	MarketPlaceRateAndReviews::where('marketplace_id',$marketplaceId)->where('user_id',Auth::user()->_id)->count();
		return View::make('elements.'.$elementViewFile,compact('limit','offset','reviewForCurrentUser','marketPlaceResult','resultCount'));
	}//end reviewElementLoad()
	
	/**
	 * Function for remind marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function remindMarketPlace(){
		
		if(Request::ajax()){   
			$marketplaceId	=	 Input::get('marketplace_id');
			$userId			=	 Auth::user()->_id;
			$status		=	Input::get('status');
			if($userId != '' && $marketplaceId !='' && $status !=''){
				if($status == 1){
					$collectionObj	=	new MarketPlaceReminders;
					$collectionObj->marketplace_id	=	$marketplaceId;
					$collectionObj->user_id			=	$userId;
					$collectionObj->save();
				}else{
					MarketPlaceReminders::where('user_id',$userId)->where('marketplace_id',$marketplaceId)->delete();
				}
			}
		}
		
			$response	=	array('success' => 1);
			return  Response::json($response);	 
	
	}//end remindMarketPlace()
	
	/**
	 * Function for add donation for marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function addMarketPlaceDonation(){
		if(Request::ajax()){ 
			
			$marketplaceId	=	Input::get('marketplace_id');
			$userId			=	Auth::user()->_id;
			$formData		=	Input::all();
			$causeId		= 	MarketPlace::where('_id',$marketplaceId)->pluck('causes_id');	
			
			if(!empty($formData)){
				
				$validator = Validator::make(
					Input::all(),
					array(
						'title' 		=> 'required',
						'name' 			=> 'required',
						'description' 	=> 'required',
						'amount' 		=> 'required|numeric',
					)
				);
				if ($validator->fails()){
					 $allErrors	= '<ul>';
					foreach ($validator->errors()->all('<li class="review_error_li">:message</li>') as $message)
					{
							$allErrors .=  $message; 
					}
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => false,
						'errors' => $allErrors
					);
					return  Response::json($response);
				}else{
				
					
					$donationArray	=	array('marketplace_id' => $marketplaceId,
												'user_id'		=> $userId,
												'name'			=>	Input::get('name'),
												'title'			=>  Input::get('title'),
												'amount'		=>	(double) Input::get('amount') ,
												'description'	=>	nl2br(strip_tags(trim(Input::get('description')))),
												'anonymous_user'=>	Input::get('anonymous_user'),
												'cause_id'		=>	$causeId
										);
										
					Session::put('marketplace_donation_detail',$donationArray);
					$response	=	array('success' => 1);
					return  Response::json($response);	 
				}
			}
		}
	}//end addMarketPlaceDonation()
	
	/**
	 * Function for edit marketplace
	 * 
	 * @param marketPlaceId as id of marketplace
	 * 
	 * return view page 
	 * 
	 */
	public function editMarketPlace($marketPlaceId=''){
		
		$marketPlaceDetail		=	array();
		if($marketPlaceId!=''){
			$marketPlaceDetail	=	MarketPlace::with('marketPlaceImages')->where('_id',$marketPlaceId)->where('user_id',Auth::user()->_id)->first();
		}
		
		if(!Request::ajax()){
			if(empty($marketPlaceDetail)) {
				return View::make('errors.404');
			}
		}

		if(Request::ajax()){ 
			$marketPlaceId			=	Input::get('marketplace_id'); 
			$formData				=	Input::all();
			$formName				=	$formData['form_name'];
			if(!empty($formData)){
				unset($formData['_token']);
				unset($formData['form_name']);
				// validation rule 
				switch ($formName) {
					case "overview":
						$validationRules	=	array(
							'title' 				=> 'required',
							'category_name'			=> 'required',
							'quantity' 				=> 'required|numeric|min:0',	
							'short_description'  	=> 'required|max:290', 
							'description'  			=> 'required',
							'expire_date'  			=> 'required',
						);
					break;
					case "price":
						$anyTwistPercentage					=	Config::get('Site.anytwist_percentage');
						$maxCausePercentage					=  100-$anyTwistPercentage;
						
						$validationRules	=	array(
							'price' 								=> 'required|numeric',
							'discount'								=>  'numeric|between:0,99.99',
							'tax_perc'								=>  'required_if:tax_option,'.PAIDSHIPPING.'|numeric|between:0,99.99',
							'cause_share'							=>  'numeric|between:'.Config::get('Site.cause_percentage').',99.99|max:'.$maxCausePercentage,
							'national_shipping_charge'				=>  'required_if:shipping_type,'.PAIDSHIPPING.'|numeric',
							'international_shipping_charge'			=>  'required_if:shipping_type,'.PAIDSHIPPING.'|numeric',
						);
					break;
					case "gallery":
						$validationRules	=	array(
						
						);
					break;
					case "cause":

						$validationRules	=	array(
							'causes_name' 			=> 'required',
						);
					break;
					
					case "contest":

						$validationRules	=	array(
							'name' 			=> 'required_if:contest_type,'.CONTEST_VALUE_YES,
							'quantity_of_prize'=> 'required_if:contest_type,'.CONTEST_VALUE_YES.'|numeric',
							'price_per_quantity'=> 'required_if:contest_type,'.CONTEST_VALUE_YES.'|numeric',
							'contest_expire_date'=> 'required_if:contest_type,'.CONTEST_VALUE_YES,
							'contest_description'=> 'required_if:contest_type,'.CONTEST_VALUE_YES,
						);
					break;
					
				}

				$validator = Validator::make(
					$formData,
					$validationRules,
					array(
						'causes_name.required' 							=> 'Please select cause name',
						'national_shipping_charge.required_if'			=> 'National shipping charge is required.',
						'international_shipping_charge.required_if'		=> 'International shipping charge is required.',
						'tax_perc.between'								=> 'The tax percentage must be between 0 and 99.99.',
						'name.required_if'								=> 'Name field is required.',
						'quantity_of_prize.required_if'					=> 'Quantity Prize is required.',
						'price_per_quantity.required_if'				=> 'Price Per Quantity is required.',
						'contest_expire_date.required_if'				=> 'Expire Date is required.',
						'contest_description.required_if'				=> 'Description is required.',	
					)
				);
				
				if ($validator->fails()){		
					$allErrors='<ul>';
					foreach ($validator->errors()->all('<li>:message</li>') as $message)
					{
							$allErrors .=  $message; 
					}
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => false,
						'errors' => $allErrors
					);
					return  Response::json($response); die;

				}else{
					switch ($formName) {	
						case "overview":
							$marketplaceData							=  MarketPlace::find($marketPlaceId);
							$marketplaceData->quantity 					=  (int) Input::get('quantity');
							$marketplaceData->category_id 				=   Input::get('category_name');						
							$marketplaceData->sub_category_id 			=   Input::get('sub_category_name');						
							$marketplaceData->title						=   ucfirst(Input::get('title'));
							$marketplaceData->short_description			=   Input::get('short_description');
							$marketplaceData->description				=   Input::get('description');
							$marketplaceData->expire_date				=  (int) strtotime(Input::get('expire_date'));
							$marketplaceData->save();
						break;
						case "price":
							
							$taxOption											=  (Input::get('tax_option'));
							$marketplaceData									=   MarketPlace::find($marketPlaceId);
							$marketplaceData->discount 							=  (double) Input::get('discount');
							$marketplaceData->price 							=  (double) Input::get('price');						
							$marketplaceData->discounted_price					=  (double) (Input::get('discounted_price'));
							$marketplaceData->shipping_type						=  (int) (Input::get('shipping_type'));
							$marketplaceData->tax_type							=  (int)$taxOption;
							$marketplaceData->earning							=  (int) (Input::get('earning'));
							$marketplaceData->cause_percentage					=	(Input::get('cause_share'));
							
							$marketplaceData->cause_percentage					=	(Input::get('cause_share'));
							### MarketPlace Tax Section Start ###
							
							if(!empty($taxOption)){
								$marketplaceData->tax_perc 					=  (double) Input::get('tax_perc');
							}else{
								$marketplaceData->tax_perc 					=	0;
							}
							### MarketPlace Tax Section End ###
							if(Input::get('shipping_type')==PAIDSHIPPING){
								$marketplaceData->national_shipping_charge 		=  (double) Input::get('national_shipping_charge');
								$marketplaceData->international_shipping_charge =  (double) Input::get('international_shipping_charge');	
							}
							
							$marketplaceData->save();
						break;
						case "gallery":
							$marketPlaceImagesCount 				=	MarketPlaceImage::where('parent_id',$marketPlaceId)->count();
							$marketPlaceImages						=	Session::get('marketplace_image_array');
							if(empty($marketPlaceImages)){
								if(count(Session::get('deleted_image_array')) == $marketPlaceImagesCount){
									$response	=	array(
										'success' => false,
										'errors' => "<ul><li>The Image field is required.</li></ul>"
									);
									return  Response::json($response); die;
								}
							}
							
							if(!empty(Session::get('deleted_image_array'))){
								foreach(Session::get('deleted_image_array') as $key=> $imageId){
									$name =	 MarketPlaceImage::where('_id',$imageId)->pluck('name');
									MarketPlaceImage::where('_id',$imageId)->where('user_id',Auth::user()->_id)->delete();
									@unlink(MARKET_PLACE_IMAGE_ROOT_PATH.$name);
								}
								Session::forget('deleted_image_array');
							}
							
							if(Session::has('marketplace_image_array')){
								$marketPlaceImages							=	Session::get('marketplace_image_array');
								if(!empty($marketPlaceImages)){
									foreach($marketPlaceImages as $value){
										$marketPlaceImageObject						=	new MarketPlaceImage();
										$marketPlaceImageObject->user_id 			=   Auth::user()->id;
										$marketPlaceImageObject->parent_id 			=   $marketPlaceId;
										$marketPlaceImageObject->image 				=   $value;	
										$marketPlaceImageObject->save();
									}
								}
								Session::forget('marketplace_image_array');
							}

							break;
						case "contest":
							$contestType								=  Input::get('contest_type');
							$marketplaceData							=   MarketPlace::find($marketPlaceId);
								if($contestType==CONTEST_VALUE_NO){
								$marketplaceData->contest		=	array(
									'contest_type'				=>  (int)$contestType,
								);
							}else{
								$marketplaceData->contest		=	array(
									'contest_type'				=>  (int)$contestType,
									'name'						=>   Input::get('name'),
									'quantity_of_prize' 		=>   (double)Input::get('quantity_of_prize'),
									'price_per_quantity'		=>   (double)Input::get('price_per_quantity'),
									'contest_expire_date'		=>   (int)strtotime(Input::get('contest_expire_date')),
									'contest_description'		=>   Input::get('contest_description'),
								);
								}
							$marketplaceData->save();
						break;
						
						case "cause":
							$marketplaceData							=   MarketPlace::find($marketPlaceId);
							$marketplaceData->causes_id 				=   Input::get('causes_name');
							$marketplaceData->save();
						break;
						
					}
					$response	=	array(
						'success' 		=>	'1',
						'element' 		=>	$formName
					);
					return  Response::json($response); die;						
				}
			}
		}
		Session::forget('search_cause');
		return View::make('marketplace.edit',compact('marketPlaceDetail','marketPlaceId'));
	}//end editMarketPlace()
	
	/**
	 * Function for delete gallery image
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function galleryImageDelete(){
		if(Request::ajax()){
			$imageId		=	 Input::get('id');
			$userId			=	 Auth::user()->_id;
			$name 			=	MarketPlaceImage::where('_id',$imageId)->where('user_id',$userId)->pluck('image');
			
			$deletedImageArray	=	 Session::get('deleted_image_array', array());
			array_push($deletedImageArray,$imageId);
			Session::put('deleted_image_array',$deletedImageArray);
			$response	=	array('success' => 1);
			return  Response::json($response);	 
		}
	}//edn galleryImageDelete()
	
	/**
	 * Function for delete description 
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function marketPlaceDescriptionDelete(){
		if(Request::ajax()){ 
			$descriptionId		=	 Input::get('des_id');
			$userId				=	 Auth::user()->_id;
			$name			=	 MarketPlaceDescription::where('_id',$descriptionId)->where('user_id',$userId)->pluck('value');
			MarketPlaceDescription::where('_id',$descriptionId)->where('user_id',$userId)->delete();
			@unlink(MARKET_PLACE_DESCRIPTION_ROOT_PATH.$name);
			$response			=	array('success' => 1);
			return  Response::json($response);	
		}
	}//end marketPlaceDescriptionDelete()
	
	/**
	 * Function for add causes
	 *
	 * @param null
	 *
	 * @return view page. 
	 */
	public function addCause(){
		return View::make('marketplace.add_cause');
	}//end addCause()
	
	/**
	 * Function for save causes
	 *
	 * @param null
	 *
	 * @return view page. 
	 */
	public function saveCause(){
		if(Request::ajax()){
			$formData 	=	Input::all();
			
			$message	=	array('image.mimes' => IMAGE_EXTENSION_ERROR_MSG,'image.img_min_size'=>'Minimum size for image should be ' . CAUSES_SUPPORT_IMAGE_SIZE);
			
			$validator	=	validator::make(
							$formData,
							array(
								'title'					=>		'required',
								'image'					=>		'required|mimes:'.IMAGE_EXTENSION.'|img_min_size:241,195',
								'email'					=>		'required|email|unique:users',
								'website'				=>		'required',
								'city'					=>		'required',
								'short_description'		=>		'required|max:100',
								'description'			=>		'required',
								
							),$message);
			
			if($validator->fails()){
				$allErrors='<ul>';
				foreach ($validator->errors()->all('<li>:message</li>') as $message){
						$allErrors .=  $message; 
				}
				$allErrors .= '</ul>'; 
				$response	=	array(
					'success' => false,
					'errors' => $allErrors
				);
				return  Response::json($response); die;
			}else{
				$model = new Causes;
				if(Input::hasFile('image')){
					$extension 	=	 Input::file('image')->getClientOriginalExtension();
					$fileName	=	 Auth::user()->id.time().rand()."-cause.".$extension;
					//image move folder
					if(Input::file('image')->move(CAUSES_IMAGE_ROOT_PATH, $fileName)){
						$model->image		= $fileName;
					}
				}
				$model->title					=		Input::get('title');
				$model->email					=		Input::get('email');
				$model->website					=		Input::get('website');
				$model->country_code			=		Input::get('country_code');
				$model->country_name			=		Input::get('country_name') ? Input::get('country_name') : '';
				$model->city					=		Input::get('city') ? Input::get('city') : '';
				$model->short_description		=		Input::get('short_description');
				$model->description				=		Input::get('description');
				$model->is_active				=		ACTIVE;
				$model->user_id					=		Auth::user()->_id;
				$model->save();
				$url										=	route('create-market-place'); 
				$response	=	array(
					'success' 			=>1,
					'redirect_url'		=> $url
				);
				return Response::json($response); die;	
			}
		}
	}//end saveCause()
	
	/**
	 * Function for cart products checkout
	 *
	 * @param null
	 *
	 * @return view page. 
	 */
	public function marketPlaceCheckout(){
		$userAddresses		=	 UserAddress::where('user_id',Auth::user()->id)->orderBy('created_at','desc')->get();
		$userAddressesCount	=	 UserAddress::where('user_id',Auth::user()->id)->count();
		return View::make('marketplace.checkout',compact('userAddresses','userAddressesCount'));
	}//end marketPlaceCheckout()
	
	/**
	 * Function for cart products checkout
	 *
	 * @param null
	 *
	 * @return view page. 
	 */
	public function contestCheckout(){
		$userAddresses		=	 UserAddress::where('user_id',Auth::user()->id)->orderBy('created_at','desc')->get();
		$userAddressesCount	=	 UserAddress::where('user_id',Auth::user()->id)->count();
		return View::make('marketplace.contest_checkout',compact('userAddresses','userAddressesCount'));
	}//end marketPlaceCheckout()
	
	/** 
     * Function for add items into cart
     *
     * @param null
     * 
     * @return json array
     */
    public function addCart(){
		Session::forget('cart_invalid');
        $addType                   = Input::get('type');
        $productId                 = Input::get('product_id');
        $sessionCart               = Session::get('cart_item', array());
        $userCart                  = array();
        $userCart['items']         = array();
        $sessionCart               = (Session::has('cart_item')) ? Session::get('cart_item') : $userCart;
        $addressId	=	Input::get('address_id');
        switch ($addType) {
			case 'remove':
					unset($sessionCart['items'][$productId]);
					UserCart::where('product_id',$productId)->delete();
                break;
			case 'increment':
					if(Input::get('quantity')>0){
						$sessionCart['items'][$productId]['quantity']	= (int)Input::get('quantity');
					}
					UserCart::where('product_id',$productId)->update(array('quantity'=> (int) Input::get('quantity')));
					break;
            default:
                ### product add ###
                $productDetail   			=   MarketPlace::with('marketPlaceImages')->where('_id', $productId)->first();
                $productPrice				=	$productDetail->price;
				$currentDateAndTime			=	date(Config::get('Reading.date_format'));
				$currentDate				=	date(Config::get('Reading.date'));
				 
                $getOfferForUser				=	BuyerOffer::where('user_id',Auth::user()->_id)
														->where('marketplace_id',$productId)
														->where('status','!=',MARKET_PLACE_OFFER_PROCESSING)
														->where('time_limit','>',$currentDate)
														->first();
		
				$getOfferForUserExists			=	BuyerOffer::where('user_id',Auth::user()->_id)
														->where('marketplace_id',$productId)
														->where('status','!=',MARKET_PLACE_OFFER_PROCESSING)
														->where('time_limit','>',$currentDate)
														->count();
						
				$newProductRequestCounterOffer	=	BuyerRequestResponse::where('offer.marketplace_id', $productId)->where('offer.counter_offer','>=',$currentDate)->where('buyer_id',Auth::user()->_id)->project(array( 'offer.$' => 1 ) )->orderBy('created_at','desc')->first();
				
				$productPrice		=	($productDetail->discounted_price!='')? $productDetail->discounted_price: $productDetail->price;
														

				if((isset($newProductRequestCounterOffer->offer[0]['seller_price']))){
					$productPrice		=	$newProductRequestCounterOffer->offer[0]['seller_price'];
				}
				
				if($getOfferForUserExists && isset($getOfferForUser->final_price) && $getOfferForUser->final_price !=''){
					$productPrice		=	$getOfferForUser->final_price;
				}
				
				$itemArray = array(
                    'title' 				=> $productDetail->title,
                    'quantity' 				=> 1,
                    'price' 				=> $productPrice,
					'image' 				=> isset($productDetail->marketPlaceImages[0]['image'])? $productDetail->marketPlaceImages[0]['image']:'',
					'short_description' 	=> $productDetail->short_description,
                );
				
				UserCart::insert(array(
				  'user_id'				=>	Auth::user()->_id,
				  'product_id'			=>	$productId,
				  'quantity'			=>	1,	
				  'title'				=>	$productDetail->title,
				  'short_description'	=>	$productDetail->short_description,
				  'price'				=>	$productDetail->price,
				  'image'				=>	isset($productDetail->marketPlaceImages[0]['image'])? $productDetail->marketPlaceImages[0]['image']:'',
				  'created_at' 			=>  new mongoDate(),
				  'updated_at'	 		=>  new mongoDate()
				));
				
                if(isset($sessionCart['items'][$productId]) && $sessionCart['items'][$productId]!='' ){
					$sessionCart['items'][$productId]['quantity']	= $sessionCart['items'][$productId]['quantity']+1;	
				}else{
					$sessionCart['items'][$productId] = $itemArray;
				}
        }   
        // cart total price save     
        $totalPrice	=	0;
        if(!empty($sessionCart['items'])){
			foreach($sessionCart['items'] as $key=> $item){
				$totalPrice 	+= $item['quantity']*$item['price'];
			}
		}
		$sessionCart['total_price']	= $totalPrice;	
        Session::put('cart_item', $sessionCart);
        $cart = $sessionCart;
        $countCart = count($cart['items']);
        $response = array(
            'countCart' 		=> $countCart,
            'htmlcart' 			=>  View::make('elements.user_aboutus.marketplace_cart')->render(),
			'htmlcartDetail' 	=> View::make('elements.user_aboutus.cart_detail',compact('addressId'))->render()
        );
        return Response::json($response);
    } //end addCart
	
	/** 
     * Function for add user address
     *
     * @param null
     * 
     * @return json array
     */
	public function addUserAddress(){
		$allData = Input::all();
		$addressId 	=	Input::get('address_id');
        if (Request::ajax()) {
            if (!empty($allData)) {
				$message=	array('mobile_number.required' => 'The phone number field is required.',
								'mobile_number.regex' => 'The phone number format is invalid.');
                $validator = Validator::make(Input::all(), array(
                    'first_name'	 => 'required',
					'last_name' 	 => 'required',
					'email' 	     => 'required|email',
                    'mobile_number'  => 'required|regex:/(^([0-9]+[-]*[0-9])*$)/',
					'location' 		 => 'required',
					'address_line_1' => 'required'					
                ),$message);
             
                if ($validator->fails()) {
                    $allErrors = '<ul>';
                    foreach ($validator->errors()->all('<li>:message</li>') as $message) {
                        $allErrors .= $message;
                    }
                    $allErrors .= '</ul>';
                    $response = array(
                        'success' => false,
                        'errors' => $allErrors
                    );
                    return Response::json($response);
                    die;
                } else {
					if($addressId==''){
						UserAddress::insert(array(
							'user_id' 			=> Auth::user()->id,
							'first_name' 		=> Input::get('first_name'),
							'last_name' 		=> Input::get('last_name'),
							'email' 			=> Input::get('email'),
							'mobile_number' 	=> Input::get('mobile_number'),
							'address_line_1'	=> Input::get('address_line_1'),
							'address_line_2'	=> Input::get('address_line_2'),
							'location' 			=> Input::get('location'),
							'city' 				=> Input::get('city'),
							'state'				=> Input::get('state'),
							'country' 			=> Input::get('country'),
							'country_code' 		=> Input::get('country_code'),
							'created_at' 		=> new mongoDate(),
							'updated_at'	 	=> new mongoDate(),
						));
					}else{
						UserAddress::where('_id',$addressId)->update((array(
							'first_name' 		=> Input::get('first_name'),
							'last_name' 		=> Input::get('last_name'),
							'email' 			=> Input::get('email'),
							'mobile_number' 	=> Input::get('mobile_number'),
							'address_line_1'	=> Input::get('address_line_1'),
							'address_line_2'	=> Input::get('address_line_2'),
							'location' 			=> Input::get('location'),
							'city' 				=> Input::get('city'),
							'state'				=> Input::get('state'),
							'country' 			=> Input::get('country'),
							'country_code' 		=> Input::get('country_code'),
							'updated_at'	 	=> new mongoDate(),
							'address_line_1'	=> Input::get('address_line_1'),
							'address_line_2'	=> Input::get('address_line_2')
						)));
						
					}
                    $response = array(
                        'success' => true
                    );
                }
                return Response::json($response);
            }
        }
	}//end addUserAddress()
	
	/** 
     * Function  for list user address
     *
     * @param null
     * 
     * @return view page
     */
    public function userAddresses(){
        $userAddresses = UserAddress::where('user_id', Auth::user()->id)->orderBy('updated_at', 'desc')->get();
        return View::make('elements.user_addresses', compact('userAddresses'));
    } //end userAddresses()
	
	/** 
     * Function  for delete user address
     *
     * @param null
     * 
     * @return josn  response
     */
	public function userAddressDelete(){
		if(Request::ajax()){
			$addressId			=	Input::get('address_id');
			UserAddress::where('_id',$addressId)->where('user_id',Auth::user()->_id)->delete();
			$userAddressCount 	=	 UserAddress::where('user_id',Auth::user()->_id)->count();
			$response	=	array(
				'success' 		=> true,
				'count'			=> $userAddressCount
			);
			return  Response::json($response); die;
		}
	}//end userAddressDelete()
	
	/** 
     * Function  for user address edit
     *
     * @param null
     * 
     * @return json response
     */
	public function userAddressEdit(){
		if(Request::ajax()){
			$addressId			=	Input::get('address_id');
			$userAddressDetail	=	UserAddress::where('_id',$addressId)->where('user_id',Auth::user()->_id)->first();
			$response	=	array(
				'success' 		=> true,
				'html'			=> (String) View::make('elements.user_aboutus.add_deliver_address_form',compact('userAddressDetail')),
			);
			return  Response::json($response); die;
		}
	}//end userAddressEdit()
	
	/**
	 * Function for show payment page
	 *
	 * @param null
	 *
	 * @return view page. 
	 */
	public function marketPlacePayment(){
		$paymentFor		=	 Input::get('type',MARKETPLACE_PAYMENT);	
		
		$marketPlaceId	=	 Input::get('m_id');
		if($marketPlaceId!='' && $paymentFor==CONTEST_PAYMENT ){
			$contestDetail 	=	MarketPlace::where('_id',$marketPlaceId)->select('_id','contest')->first()->toArray();
			Session::put('contest_detail_for_payment',$contestDetail);
		}
		if(Session::get('cart_invalid')==1){
			Session::forget('cart_invalid');
			return Redirect::route('marketplace-checkout');
		}
		
		$requestToken = bin2hex(openssl_random_pseudo_bytes(16));
		Session::put('request_token',$requestToken);

		
		return View::make('marketplace.payment',compact('requestToken','paymentFor'));
	}//end marketPlacePayment()
	
	/**
	 * Function for make an offer on marketplace
	 *
	 * @param null
	 *
	 * @return view page. 
	 */
	 
	public function makeAnOfferByBuyer(){
	
        if (Request::ajax()) {
			$allData = Input::all();
            if (!empty($allData)) {
                $validator = Validator::make(Input::all(), array(
                    'price'			 => 'required|numeric'
                ));
             
                if ($validator->fails()) {
                    $allErrors = '<ul>';
                    foreach ($validator->errors()->all('<li>:message</li>') as $message) {
                        $allErrors .= $message;
                    }
                    $allErrors .= '</ul>';
                    $response = array(
                        'success' => false,
                        'errors' => $allErrors
                    );
                    return Response::json($response);
                    die;
                } else {
					
					$marketPlaceId	=	Input::get('marketplace_id');
					$marketPlace	=	Marketplace::where('_id',$marketPlaceId)->select('discounted_price','price','user_id')->first();
					
					if($marketPlace->discounted_price !=''){
						$mainPrice = $marketPlace->discounted_price;
					}else{
						$mainPrice = $marketPlace->price;
					}
					
					if(Input::get('price') > $mainPrice){
						$response = array(
							'success' => false,
							'errors' => "<ul><li>Negotiation price should be less than actual price.</li></ul>"
						);
						return Response::json($response);
					}else{
						$buyerOfferId=	 BuyerOffer::insertGetId(array(
							 'user_id' 			 => Auth::user()->id,
							 'owner_id'			 => Input::get('owner_id'),
							 'marketplace_id' 	 => $marketPlaceId,
							 'negotiation_price' => (Double) Input::get('price'),
							 'buyer_comment' 	 =>  nl2br(strip_tags(trim(Input::get('description')))),
							 'status' 			 =>  MARKET_PLACE_OFFER_PROCESSING,
							 'created_at' 		 => new mongoDate(),
							 'updated_at'	 	 => new mongoDate()
						));
						
						//~ BuyerOffer
						/* send notification to seller which shows that an offer has been requested by the buyer  */
						$marketPlaceName	=	MarketPlace::where('_id',$marketPlaceId)->pluck('title');
						$notificationData	=	array(
							'user_id'			=>	$marketPlace->user_id,
							'created_by'		=>	Auth::user()->_id,
							'notification_type'	=>	NEGOTIATION_REQUEST_RECEIVED,
							'url'				=>  route('negotiation-requests',array('marketplace_id'=>$marketPlaceId,'buyerOfferId'=>$buyerOfferId)),
							'item_name'			=>  $marketPlaceName,
							'buyer_name'		=> Auth::user()->full_name
						);
						$this->save_notifications($notificationData);
						$response = array(
							'success' => true
						);
					}
                }
                return Response::json($response);
            }
        }
        
	}//end makeAnOffer()
	

	/**
	 * Function for open offer popup
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function marketplaceBuyerOffersPopup(){
		if (Request::ajax()) {
			$offerId			=	Input::get('offer_id');
			$allNegotiation		=	Input::get('allNegotiation');
			$marketPlaceoffers 	=  BuyerOffer::with('marketplace','userDetail','buyerDetail')->where('_id',$offerId)->first();
			return View::make('elements.view_offer_popup',compact('marketPlaceoffers','allNegotiation'));
		}
	}//end marketplaceBuyerOffersPopup()
	
	/**
	 * Function for open offer popup
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function marketplaceSellerOffersPopup(){
		if (Request::ajax()) {
			$offerId			=	Input::get('offer_id');
			$marketPlaceoffers 	=  BuyerOffer::with('marketplace','userDetail','buyerDetail')->where('_id',$offerId)->first();
			return View::make('elements.seller_offer_popup',compact('marketPlaceoffers'));
		}
	}//end marketplaceSellerOffersPopup()
	
	/**
	 * Function for update offer popup either except the offer or reject 
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function updateBuyerOffer(){
		
		if (Request::ajax()) {
			$allData	=	Input::all();
			$offerId	=	Input::get('offer_id');
			$status		=	Input::get('status');
           
            if (!empty($allData)) {
              
              if($status == MARKET_PLACE_OFFER_ACCEPTED){
			   $validator = Validator::make(Input::all(), array(
                    'time_limit'	 => 'required',
					//~ 'comment' 		 => 'required',
                ));
			  
			  }elseif($status == MARKET_PLACE_COUNTER_OFFER){
				$messages	=	array('price.required_with' =>'The price field is required.',
									  'time_limit.required_with' =>'The time limit field is required.');
				
				$validator = Validator::make(Input::all(), array(
					'price'			 =>	'required|numeric',		
					'time_limit'	 =>	'required',		
					//'comment' 	 	 => 'required',
                ),$messages);
				  
			  }else{
					$validator = Validator::make(Input::all(), array());
			  }
              
                if($validator->fails()){
                    $allErrors = '<ul>';
                    foreach ($validator->errors()->all('<li>:message</li>') as $message) {
                        $allErrors .= $message;
                    }
                    $allErrors .= '</ul>';
                    $response = array(
                        'success' => false,
                        'errors' => $allErrors
                    );
                    return Response::json($response);
                    die;
                }else{
					
					$offerObj	=	BuyerOffer::find($offerId);
					
					if($status == MARKET_PLACE_COUNTER_OFFER){
						$offerObj->final_price		=	(Double) Input::get('price');
						$offerObj->status			=	MARKET_PLACE_COUNTER_OFFER;
					}elseif($status == MARKET_PLACE_OFFER_ACCEPTED){
						$offerObj->final_price		=	$offerObj->negotiation_price;
						$offerObj->status			=	MARKET_PLACE_OFFER_ACCEPTED;
					}elseif($status == MARKET_PLACE_OFFER_REJECTED){
						$offerObj->status			=	MARKET_PLACE_OFFER_REJECTED;
					}
					
					$offerObj->seller_comment		=	Input::get('comment');
					$offerObj->time_limit			=	Input::get('time_limit');
					
					$offerObj->save();
					
					$productName	=	MarketPlace::where('_id',$offerObj->marketplace_id)->pluck('title');
					/* send notification to buyer regarding buyer's offer request,respond by the seller */
					$sellerName		=	User::where('_id',$offerObj->owner_id)->pluck('full_name');
					
					$notificationData	=	array(
						'user_id'			=>	$offerObj->user_id,
						'created_by'		=>	$offerObj->owner_id,
						'notification_type'	=>	NEGOTIATION_REQUEST_REPLY,
						'url'				=>  route('market-place-buying-slug',array('negotiation','offer_id' => $offerId)),
						'item_name'			=>  $productName,
						'status_of_offer'	=>  $offerObj->status,
						'seller_name'		=>  $sellerName
					);
					
					$this->save_notifications($notificationData);
						
					$response = array(
						'success' => true
					);

                }
                return Response::json($response);
            }
        }
	}//end updateBuyerOffer()

	
	/**
	 * Function for order item listing page
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function marketPlaceBuying(){

		$countUserFollowers						=	UserFollower::where('following_id',Auth::user()->_id)->count();
		$countUserFollowings					=	UserFollower::where('follwer_id',Auth::user()->_id)->count();
		return View::make("marketplace.order",compact('countUserFollowers','countUserFollowings'));
	}//end marketPlaceBuying()
	
	/**
	 * Function for load purchased_item, negotiation,my_requests,my_request_reponse and report_problem element of marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function loadBuyerElements(){
		if(Request::ajax()){
			$offset								=	(int)Input::get('offset',0);
			$limit								=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
			$elementName						=	Input::get('element');
			
			switch ($elementName) {
				case 'purchased_item':
				
					$search						=	Input::get('search');
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					
					$countOrder  				=	OrderItem::where('buyer_id',Auth::user()->id)->count();
					
					$orderCondition		 		=   OrderItem::with('buyerDetail','marketPlaceDetail','marketPlaceImages')
														->where('buyer_id',Auth::user()->id);													
					
					$orderBy					=	(isset($val[0])) ? $val[0]:'updated_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_buying',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('order_by_buying')){
							$val 				=	explode(',',Session::get('order_by_buying'));
						}
					}
					$orderBy					=	(isset($val[0])) ? $val[0]:'updated_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					// search management
					
					if($search!=''){
						Session::put('search_buying',$search);
						$orderCondition->where('title','like',"$search%");
						$countOrder 		=	$orderCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_buying')){
								$search			=	Session::get('search_buying');	
								$orderCondition->where('title','like',"$search%");
								$countOrder	=	$orderCondition->count();
							}
						}
					}
					$orderCondition 			=  $orderCondition->skip($offset)->take($limit)
															->orderBy($orderBy,$order)->get();
					if(Input::get('load_more')==1){
						return View::make('elements.marketplace-elements.more_purchase_item',compact('countOrder','offset','limit','orderCondition'));
					}else{
						// clear search session and order
						if(Session::has('order_by_buying')){
							Session::forget('order_by_buying');
						}
						if(Session::has('search_buying')){
							Session::forget('search_buying');
						}							
						return View::make("elements.marketplace-elements.purchase_item",compact('countOrder','offset','limit','orderCondition'));
					}
				break;
				
				case 'negotiation':
				
					$search						=	Input::get('search');
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					
					// global condition
					$countBuyinOffer  			=	BuyerOffer::with('marketplace')->where('user_id',Auth::user()->id)
														->count();
					
					$buyingOfferCondition 		=  	BuyerOffer::with('marketplace')->where('user_id',Auth::user()->id);
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_buying',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('order_by_buying')){
							$val 				=	explode(',',Session::get('order_by_buying'));
						}
					}
					$orderBy					=	(isset($val[0])) ? $val[0]:'created_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
						
					// search management
					if($search!=''){
						Session::put('search_buying',$search);
						$buyingOfferCondition->where('seller_comment','like',"$search%");
						$countBuyinOffer  		=	$buyingOfferCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_buying')){
								$search				=	Session::get('search_buying');
								$buyingOfferCondition->where('seller_comment','like',"$search%");
								$countBuyinOffer  	=	$buyingOfferCondition->count();
							}
						}
					}
					
					$buyerOffer 				=  $buyingOfferCondition->skip($offset)->take($limit)
															->orderBy($orderBy,$order)->get();
					if(Input::get('load_more')==1){
						return View::make('elements.marketplace-elements.more_negotiation',compact('buyerOffer','limit','offset','countBuyinOffer'));
					}else{
						// clear search session and order
						if(Session::has('order_by_buying')){
							Session::forget('order_by_buying');
						}
						if(Session::has('search_buying')){
							Session::forget('search_buying');
						}							
						return View::make("elements.marketplace-elements.negotiation",compact('offset','limit','buyerOffer','countBuyinOffer'));
					}
				break;
				
				case 'my_requests':
					$search						=	Input::get('search');
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					$countBuyerRequest  		=	BuyerRequest::with('buyerRequestResponse')->where('buyer_id',Auth::user()->id)->count();
					$buyerRequestCondition		=   BuyerRequest::with('buyerRequestResponse','category')->where('buyer_id',Auth::user()->id);
					
					if(Input::get('order_by')!=''){
						Session::put('order_by_buying',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('order_by_buying')){
							$val 				=	explode(',',Session::get('order_by_buying'));
						}
					}
																		
					$orderBy					=	(isset($val[0])) ? $val[0]:'created_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					
					// search management
					
					if($search!=''){
						Session::put('search_buying',$search);
						$buyerRequestCondition->where('product_name','like',"$search%");
						$countBuyerRequest 		=	$buyerRequestCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_buying')){
								$search			=	Session::get('search_buying');	
								$buyerRequestCondition->where('product_name','like',"$search%");
								$countBuyerRequest	=	$buyerRequestCondition->count();
							}
						}
					}
					$buyerRequestCondition 		=  $buyerRequestCondition->skip($offset)->take($limit)
															->orderBy($orderBy,$order)->get();
					
					
					if(Input::get('load_more')==1){
						return View::make('marketplace.buyer.more_view_my_new_product_requests',compact('countBuyerRequest','offset','limit','buyerRequestCondition'));
					}else{
						// clear search session and order
						if(Session::has('order_by_buying')){
							Session::forget('order_by_buying');
						}
						if(Session::has('search_buying')){
							Session::forget('search_buying');
						}							
						return View::make("marketplace.buyer.view_my_new_product_requests",compact('countBuyerRequest','offset','limit','buyerRequestCondition'));
					}
				break;
				
				case 'my_request_reponse':
					
					$search						=	Input::get('search');
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					$buyerRequestId				=	Input::get('request_id');
					
					$countBuyerResponse  		=	BuyerRequestResponse::where('buyer_request_id',$buyerRequestId)->count();
					$buyerResponseCondition		=   BuyerRequestResponse::with('sellerDetail')->where('buyer_request_id',$buyerRequestId);											
					$orderBy					=	(isset($val[0])) ? $val[0]:'updated_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('order_by_buying',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('order_by_buying')){
							$val 				=	explode(',',Session::get('order_by_buying'));
						}
					}
					$orderBy					=	(isset($val[0])) ? $val[0]:'created_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					// search management
					
					if($search!=''){
						Session::put('search_buying',$search);
						$buyerResponseCondition->where('title','like',"$search%");
						$countBuyerResponse 		=	$buyerResponseCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_buying')){
								$search			=	Session::get('search_buying');	
								$buyerResponseCondition->where('title','like',"$search%");
								$countBuyerResponse	=	$buyerResponseCondition->count();
							}
						}
					}
					
					$buyerResponse 				=  $buyerResponseCondition->skip($offset)->take($limit)
															->orderBy($orderBy,$order)->get();
								
																
					if(Input::get('load_more')==1){
						return View::make('marketplace.buyer.more_view_new_product_response',compact('countBuyerResponse','offset','limit','buyerResponse','buyerRequestId'));
					}else{
						// clear search session and order
						if(Session::has('order_by_buying')){
							Session::forget('order_by_buying');
						}
						if(Session::has('search_buying')){
							Session::forget('search_buying');
						}							
						return View::make("marketplace.buyer.view_new_product_response",compact('countBuyerResponse','offset','limit','buyerResponse','buyerRequestId'));
					}
				break;
				
				case 'report_problem':
				
					$search						=	Input::get('search');
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					
					$countReport  				=	OrderItem::where('buyer_id',Auth::user()->id)
														->where('report_title', 'exists', true)->count();
					
					$reportCondition		 	=   OrderItem::with('marketPlaceImages')
														->where('buyer_id',Auth::user()->id)
														->where('report_title', 'exists', true);													
					
					// order management
					if(Input::get('order_by')!=''){
						Session::put('report_problem',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('report_problem')){
							$val 				=	explode(',',Session::get('report_problem'));
						}
					}
					$orderBy					=	(isset($val[0])) ? $val[0]:'title';
					$order						=	(isset($val[1])) ? $val[1]:'asc';
					// search management
					
					if($search!=''){
						Session::put('search_report_problem',$search);
						$reportCondition->where('title','like',"$search%");
						$countReport 		=	$reportCondition->count();
					}else{
						if(Input::get('load_more')==1){
							if(Session::has('search_report_problem')){
								$search			=	Session::get('search_report_problem');	
								$reportCondition->where('title','like',"$search%");
								$countReport	=	$reportCondition->count();
							}
						}
					}
					$reportCondition 			=  $reportCondition->skip($offset)->take($limit)
															->orderBy($orderBy,$order)->get();
					
					if(Input::get('load_more')==1){
						return View::make('elements.marketplace-elements.more_buyer_report_problem',compact('countReport','offset','limit','reportCondition'));
					}else{
						// clear search session and order
						if(Session::has('report_problem')){
							Session::forget('report_problem');
						}
						if(Session::has('search_report_problem')){
							Session::forget('search_report_problem');
						}							
						return View::make("elements.marketplace-elements.buyer_report_problem",compact('countReport','offset','limit','reportCondition'));
					}
				break;
			}
		}
	}//end loadBuyerElements()
	
	/**
	 * Function for open order popup
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function viewMarketplaceOrderPopup(){
		if (Request::ajax()) {
			$orderId						=	Input::get('order_id');
			$isDisplayShippedAndDeliver		=	Input::get('is_display_shipped_delivered');
			$orders 		=  OrderItem::with('orderDetail','sellerDetail','marketPlaceDetail','buyerDetail')
												->where('_id',$orderId)->first();
			return View::make('elements.marketplace-elements.view_order_popup',compact('orders','isDisplayShippedAndDeliver'));
		}
	}//end viewMarketplaceOrderPopup()
	
	
	/**
	 * Function for open order item popup
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	public function viewMarketplaceOrderItemPopup(){
		if (Request::ajax()) {
			$orderId		=	Input::get('order_id');
			$orders 		= 	 OrderItem::with('userAddressDetail','sellerDetail')
											->where('_id',$orderId)->first();
			return View::make('elements.marketplace-elements.view_order_item_popup',compact('orders'));
		}
	}//end viewMarketplaceOrderItemPopup()	
	
	/**
	 * Function for update order status
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function updateOrderStatus(){
		if (Request::ajax()) {			
			$orderId					=	Input::get('order_id');
			$status						=	Input::get('order_status');
			$orderObj					=	OrderItem::find($orderId);
			$orderObj->order_status		=	(int) $status;
			if($status == ORDER_SHIPPED){
				$messages	=	array('tracking_url.required_with' =>'The tracking url field is required.',
									  'tracking_number.required_with' =>'The tracking number field is required.');
				
				$validator = Validator::make(Input::all(), array(
                    'tracking_url'		 => 'required_with:tracking_number|url',
					'tracking_number' 	 => 'required_with:tracking_url|numeric',
                ),$messages);
                
                if($validator->fails()){
					$allErrors = '<ul>';
                    foreach ($validator->errors()->all('<li>:message</li>') as $message) {
                        $allErrors .= $message;
                    }
                    $allErrors .= '</ul>';
                    $response = array(
                        'success' => false,
                        'errors' => $allErrors
                    );
                    return Response::json($response);
                    die;
                }else{
					$orderObj->shipped_at		=	date(Config::get('Reading.date'));
					$orderObj->tracking_url		=	Input::get('tracking_url');
					$orderObj->tracking_number	=	Input::get('tracking_number');
				}
			}else{
				$orderObj->delivered_at			=	date(Config::get('Reading.date'));	
			}
				$orderObj->save();
			//$orderObj->save();
			
			$orderStatus		=	($status == ORDER_SHIPPED) ? 'shipped' : 'delivered';
			/* send notification to buyer regarding order status */
			
			$notificationData	=	array(
				'user_id'			=>	$orderObj->buyer_id,
				'created_by'		=>	$orderObj->seller_id,
				'notification_type'	=>	ORDER_STATUS,
				'status_of_order'	=>	$orderStatus,
				'url'				=>  route('market-place-buying',array('order_id'=>$orderId)),
				'item_name'			=>	$orderObj->title
			);
		
			$this->save_notifications($notificationData);
					
			$response = array(
				'success' => true
			);
			
			return Response::json($response);
			
        }
	}//end updateOrderStatus()
	
	/**
	 * Function for save delivery address of product
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function saveDeliveredAddress(){
		$addressId	=	Input::get('address_id');
		Session::put('address_id',$addressId);
	}//end saveDeliveredAddress()
	
	/**
	 * Function for save delivery address of product when claim for any contest
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function saveWinnerAddress(){
		$contestId 						=	Input::get('c_id');
		
		if($contestId!=''){
			$addressId					=	Input::get('address_id');
			$userAddressDetail			=	UserAddress::where('_id',$addressId)->first();
			$shippingDetail				=	array(
												'first_name'	=>	$userAddressDetail->first_name,
												'last_name'		=>	$userAddressDetail->last_name,
												'email'			=>	$userAddressDetail->email,
												'mobile_number'	=>	$userAddressDetail->mobile_number,
												'location'		=>  $userAddressDetail->location,
												'city'			=>  $userAddressDetail->city,
												'country'		=>  $userAddressDetail->country,
												'country_code'	=>  $userAddressDetail->country_code,
											);
			// update is claimed status 
			ContestParticipant::where('participant_id',Auth::user()->_id)
				->where('_id',$contestId)
				->update(Array('shipping_detail'=>$shippingDetail,'is_claim'=>1));
			
			$contestDetail		=	ContestParticipant::where('_id',$contestId)->select('_id','name','marketplace_id')->first();	
			$sellerId			=	Marketplace::where('_id',$contestDetail->marketplace_id)->pluck('user_id');	
			
			$notificationData	=	array(
										'user_id'			=>	$sellerId,
										'created_by'		=>	Auth::user()->_id,
										'notification_type'	=>	CONTEST_CLAIM_BY_BUYER,
										'url'				=>  route('view-contest-detail-page',array('mp_id' => $contestDetail->marketplace_id,'c_id' => $contestDetail->_id)),
										'contest_name'		=> 	$contestDetail->name,
										'buyer_name'		=> 	Auth::user()->full_name
									);
				
			$this->save_notifications($notificationData);
			
			
		}
	}
	/**
	 * Function for create marketplace element load
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function loadCreateMarketplaceElements(){ 
		if(Request::ajax()){
			$elementName						=	Input::get('element');
			switch ($elementName) {
				case 'overview':
					$causesListByUser		=	Causes::where('is_active',ACTIVE)->where('user_id',Auth::user()->_id)->lists('title','_id')->toArray();
					$adminID				=	User::where('user_role_id',ADMIN_USER)->pluck('_id');
					$causesListByAdmin		=	Causes::where('is_active',ACTIVE)->where('user_id', $adminID)->lists('title','_id')->toArray();
					$categoryList			=	DropDown::where('dropdown_type','market-category')->lists('name','_id')->toArray();
					return View::make('elements.create_marketplace.overview',compact('causesListByUser','causesListByAdmin','categoryList'));
				break;
				case 'price':
					return View::make('elements.create_marketplace.price');
				break;
				case 'gallery':
					return View::make('elements.create_marketplace.gallery');
				break;
				case 'cause':
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					$adminID					=	User::where('user_role_id',ADMIN_USER)->pluck('_id');

					$search						=	Input::get('search');
					$causeCondition				=   Causes::where('is_active',ACTIVE)
													->where('user_id',Auth::user()->_id);
					// search management
					if($search!=''){
						Session::put('search_cause',$search);
						$causeCondition	->where('title','like',"$search%");
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('search_cause')  ){
								$search 		=	Session::get('search_cause');
								$causeCondition	->where('title','like',"$search%");
							}
						}else{
							Session::forget('search_cause');
						}
					}
					$countCause					=	$causeCondition->count();
					$causesListByUser			=	$causeCondition->skip($offset)->take($limit)
												->get();
					if(Input::get('load_more')==1){
						$file 					=	 'load_more_cause';		
					}else{
						$file 					=	 'cause';	
					}	
										
				return View::make("elements.create_marketplace.$file",compact('causesListByUser','limit','offset','countCause'));
				break;
				case 'contest':
					return View::make('elements.create_marketplace.contest');
				break;
			}
		}
	}//end loadCreateMarketplaceElements()
	
	/**
	 * Function for edit marketplace element load
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	 
	public function loadEditMarketplaceElements(){
		if(Request::ajax()){
			$marketPlaceId 						=	Input::get('marketplace_id');
			$marketPlaceDetail					=	array();
			if($marketPlaceId!=''){
				$marketPlaceDetail				=	MarketPlace::with('marketPlaceImages')->where('_id',$marketPlaceId)->where('user_id',Auth::user()->_id)->first();
			}
			$elementName						=	Input::get('element');
			switch ($elementName) {
				case 'overview':
					$causesListByUser		=	Causes::where('is_active',ACTIVE)->where('user_id',Auth::user()->_id)->lists('title','_id')->toArray();
					$adminID				=	User::where('user_role_id',ADMIN_USER)->pluck('_id');
					$causesListByAdmin		=	Causes::where('is_active',ACTIVE)->where('user_id', $adminID)->lists('title','_id')->toArray();
					$categoryList			=	DropDown::where('dropdown_type','market-category')->lists('name','_id')->toArray();
					
					return View::make('elements.edit_marketplace.overview',compact('causesListByUser','causesListByAdmin','categoryList','marketPlaceDetail'));
				break;
				case 'price':
					return View::make('elements.edit_marketplace.price',compact('marketPlaceDetail'));
				break;
				case 'gallery':
					return View::make('elements.edit_marketplace.gallery',compact('marketPlaceDetail'));
				break;
				case 'cause':
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					$adminID					=	User::where('user_role_id',ADMIN_USER)->pluck('_id');
					$search						=	Input::get('search');
					$causeCondition				=   Causes::where('is_active',ACTIVE)
														->where('user_id',Auth::user()->_id);
					// search management
					if($search!=''){
						Session::put('search_cause',$search);
						$causeCondition	->where('title','like',"$search%");
					}else{
						if(Input::get('load_more') == 1){
							if(Session::has('search_cause')){
								$search 		=	Session::get('search_cause');
								$causeCondition	->where('title','like',"$search%");
							}
						}else{
							Session::forget('search_cause');
						}
					}
					$countCause					=	$causeCondition->count();
					$causesListByUser			=	$causeCondition->skip($offset)->take($limit)
													->get();
					if(Input::get('load_more')==1){
						$file 					=	 'load_more_cause';		
					}else{
						$file 					=	 'cause';	
					}	
					return View::make("elements.edit_marketplace.$file",compact('causesListByUser','marketPlaceDetail','countCause','limit','offset'));
				break;
				case 'contest':
					return View::make('elements.edit_marketplace.contest',compact('marketPlaceDetail'));
				break;
			}
		}
	}//end loadEditMarketplaceElements()
	
	/**
	 * Function for buyer requests
	 *
	 * @param null
	 *
	 * @return viewpage. 
	 */
	 
	public function negotiationRequest(){
		return View::make('marketplace.negotiation_requests');
	}//end negotiationRequest()
	
	/**
	 * Function for all Negotiation Request
	 *
	 * @param null
	 *
	 * @return viewpage. 
	 */
	 
	public function allNegotiationRequest(){
		
		$offset					=	(int)Input::get('offset',0);
		$limit					=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
		$search					=	Input::get('search');
		
		$marketPlaceId			=   Marketplace::where('user_id',Auth::user()->_id)->where('is_deleted','!=',1)->lists('_id');
		$DB				 		=   BuyerOffer::with('marketplace')->whereIn('marketplace_id',$marketPlaceId)->where('owner_id',Auth::user()->_id);
		$countMarketPlaceoffers 	=  $DB->count();
		
		// order management
		if(Input::get('order_by')!=''){
			Session::put('order_by_buyers_negotiation',Input::get('order_by'));
			$val 	=	explode(',',Input::get('order_by'));
		}else{
			if(Input::get('load_more') == 1){
				if(Session::has('order_by_buyers_negotiation')){
					$val 	=	explode(',',Session::get('order_by_buyers_negotiation'));
				}
			}
		}
		
		 $orderBy				=	(isset($val[0])) ? $val[0]:'created_at';
		 $order					=	(isset($val[1])) ? $val[1]:'desc';
		 
		 if($search != ''){
			Session::put('search_negotiation_request',$search);
			$DB->where('status',(int) $search);
			$countMarketPlaceoffers  			=	$DB->count();
		}else{
			if(Input::get('load_more') == 1){
				if(Session::has('search_negotiation_request')  && $search!=''){
					$DB->where('status',(int) Session::get('search_negotiation_request'));
					$countMarketPlaceoffers  		=	$DB->count();
					
				}
			}
		}
		
		$marketPlaceoffers 		=  $DB->take($limit)->skip($offset)->orderBy($orderBy,$order)->get();
		
		
		if(Input::get('load_more') == 1){
			return View::make('elements.marketplace-elements.more_negotiation_request',compact('marketPlaceoffers','limit','offset','countMarketPlaceoffers'));
		}else{
			// clear search session and order
			if(Session::has('search_negotiation_request')){
				Session::forget('search_negotiation_request');
			}
			if(Session::has('order_by_buyers_negotiation')){
				Session::forget('order_by_buyers_negotiation');
			}
			return View::make('marketplace.see_all_negotiation_requests',compact('marketPlaceoffers','countMarketPlaceoffers','limit','offset'));
		}
		
	}//end allNegotiationRequest()
	
	/**
	 * Function for add  request for product by buyer
	 *
	 * @param null
	 *
	 * @return viewpage. 
	 */
	 
	public function buyersProductRequestPopup(){
		if (Request::ajax()) {
			$dropDownSelect			=	DropDown::query()->where('dropdown_type','market-category')->orderBy('name','asc')->lists('name','_id')->toArray();
			return View::make('elements.marketplace-elements.buyer_product_request_popup',compact('marketPlaceoffers','dropDownSelect'));
		}
	}//end buyersProductRequestPopup()
	
	/**
	 * Function for send request for product by buyer
	 *
	 * @param null
	 *
	 * @return viewpage. 
	 */
	 
	public function sendbuyersProductRequest(){
		if (Request::ajax()) {
			$allData	=	Input::all();
			
			$userId		=	Auth::user();
			
            if (!empty($allData)) {
              
			   $validator = Validator::make(Input::all(), array(
                    'name'			 => 'required',
					'category'	 	 => 'required',
					'sub_category_name' => 'required',
					'description' 	 => 'required',
					'expected_price' => 'required|numeric',
					'last_date'		 => 'required',
					
                ),array('sub_category_name.required' => 'The sub category field is required.'));
			 
               if ($validator->fails()){
            
                    $allErrors = '<ul>';
                    foreach ($validator->errors()->all('<li>:message</li>') as $message) {
                        $allErrors .= $message;
                    }
                    $allErrors .= '</ul>';
                    $response = array(
                        'success' => false,
                        'errors' => $allErrors
                    );
                    return Response::json($response);
                    die;
               
                }else{
					$request					=	new BuyerRequest;
					$categoryId					=	Input::get('category');
					$subCategoryId				=	Input::get('sub_category_name'); 
					$request->buyer_id			=	Auth::user()->_id;
					$request->product_name		=	ucfirst(Input::get('name'));
					$request->category_id		=	$categoryId;
					$request->sub_category_id	=	$categoryId;
					$request->description		=	Input::get('description');
					$request->expected_price	=	(double) Input::get('expected_price');
					$request->last_date			=	Input::get('last_date');
					$request->status			=	BUYER_REQUEST_PROCESSING;
					$request->save();
					$newProductId				=	$request->_id;
					
					// notifiction will be send only those user who are belongs to selected category and sub category
					// $sellerIdArray contain array of those  user
					
					$sellerIdArray				=	User::where('_id','!=',Auth::user()->_id)->where('service_category','exists',true)->where('service_category','573d58d02f3541200d000029')->lists('_id')->toArray();		
								
					 /* send notification to every seller */
					if(!empty($sellerIdArray)) {
						foreach($sellerIdArray as $id){
							$notificationData	=	array(
								'user_id'			=>	$id,
								'item_name'			=>	Input::get('name'),
								'buyer_name'		=>	Auth::user()->full_name,
								'created_by'		=>	Auth::user()->_id,
								'notification_type'	=>	BUYER_REQUEST_RECEIVED,
								'url'				=>  route('market-place-slug',array('buyer-request',$newProductId)),
							);
							$this->save_notifications($notificationData);
						}
					}
					
					$response = array(
						'success' => true
					);

                }
                return Response::json($response);
            }
			
		}
	}//end sendbuyersProductRequest()
	
	/**
	 * Function for show request list
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function viewrequestedProductDetail(){
		Session::forget('marketplace_donation_detail'); 
		if (Request::ajax()) {
			
			$requestId		=	 Input::get('request_id');
			$requestData	=	BuyerRequest::with('BuyerRequestResponse')->where('_id',$requestId)->first();
			return View::make('elements.marketplace-elements.view_requested_product_detail_popup',compact('requestData'));
		}
	}//end viewrequestedProductDetail()
	
	/**
	 * Function for open message Popup
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function messagePopup(){
		if(Request::ajax()){
			$userSlug				=	 Input::get('slug');
			$userData				=	User::where('slug',$userSlug)->first();
			return	View::make("elements.message_popup",compact('userData'));
		}
	}//end messagePopup()
	
	/**
	 * Function for choose existing marketplace listing
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function chooseExistingMarketplace($buyerRequestId = 0){
		
		$offset					=	(int)Input::get('offset',0);
		$limit					=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
		$search					=	Input::get('search');
		// global condition
		
		$dropDownSelect			=	DropDown::query()->where('dropdown_type','market-category')->orderBy('name','asc')->lists('name','_id')->toArray();
		
		$countMarketPlace  		=	MarketPlace::where('user_id',Auth::user()->id)->Conditions()->where('expire_date','>',time())->where('quantity','!=',0)->count();
	
		$marketPlaceCondition 	=   MarketPlace::with('marketPlaceImages','marketPlaceView','MarketPlaceLikes','MarketPlaceOrders','MarketPlaceShares')->where('user_id',Auth::user()->id)->Conditions()->where('expire_date','>',time())->where('quantity','!=',0); 
		
		
		// order management
		if(Input::get('order_by')!=''){
			Session::put('order_by_marketplace',Input::get('order_by'));
			$val 	=	explode(',',Input::get('order_by'));
		}else{
			if(Input::get('load_more') == 1){
				if(Session::has('order_by_marketplace')){
					$val 	=	explode(',',Session::get('order_by_marketplace'));
				}
			}
		}
		
		/*For Serching By Category And Sub Category*/
		if(Input::get('category_id') != '' && Input::get('sub_category_id') != ''){
			Session::put('marketplace_by_category_id',Input::get('category_id'));
			Session::put('marketplace_by_sub_category_id',Input::get('sub_category_id'));
			$val1	=	Input::get('category_id');
			$val2	=	Input::get('sub_category_id');
			$marketPlaceCondition->where('category_id',$val1)->where('sub_category_id',$val2);
			$countMarketPlace  		=	$marketPlaceCondition->count();
		}elseif(Input::get('category_id') != ''){
			Session::put('marketplace_by_category_id',Input::get('category_id'));
			if(Session::has('marketplace_by_sub_category_id')){
				Session::forget('marketplace_by_sub_category_id');
			}
			$val1	=	Input::get('category_id');
			$marketPlaceCondition->where('category_id',$val1);
			$countMarketPlace  		=	$marketPlaceCondition->count();
		}else{
			if(Input::get('load_more') == 1){
				if(Session::has('marketplace_by_category_id')){
					$val1 	=	Session::get('marketplace_by_category_id');
					$val2 	=	Session::get('marketplace_by_sub_category_id');
					if($val2 == ''){
						$marketPlaceCondition->where('category_id',$val1);
					}else{
						$marketPlaceCondition->where('category_id',$val1)->where('sub_category_id',$val2);
					}	
					$countMarketPlace  		=	$marketPlaceCondition->count();
				}
			}
		}
		
		$orderBy				=	(isset($val[0])) ? $val[0]:'title';
		$order					=	(isset($val[1])) ? $val[1]:'asc';
		// search management
		
		if($search!=''){
			Session::put('search_marketplace',$search);
			$marketPlaceCondition->where('title','like',"$search%");
			$countMarketPlace  		=	$marketPlaceCondition->count();
		}else{
			if(Input::get('load_more') == 1){
				if(Session::has('search_marketplace')  ){
					$search 				=	Session::get('search_marketplace');
					$marketPlaceCondition->where('title','like',"$search%");
					$countMarketPlace  		=	$marketPlaceCondition->count();
				}
			}
		}
		// search management
		$marketPlaceContent			=	$marketPlaceCondition->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
		
		//pr($marketPlaceContent); die;
	
		if(Request::ajax()){
			return View::make('marketplace.seller.more_choose_existing',compact('marketPlaceContent','limit','offset','countMarketPlace','buyerRequestId','dropDownSelect'));
		}else{
			// clear search session and order
			if(Session::has('order_by_marketplace')){
				Session::forget('order_by_marketplace');
			}
			if(Session::has('search_marketplace')){
				Session::forget('search_marketplace');
			}
			if(Session::has('marketplace_by_category_id')){
				Session::forget('marketplace_by_category_id');
			}
			if(Session::has('marketplace_by_sub_category_id')){
				Session::forget('marketplace_by_sub_category_id');
			}
			return View::make('marketplace.seller.choose_existing',compact('marketPlaceContent','limit','offset','countMarketPlace','buyerRequestId','dropDownSelect'));
		}
	}//end chooseExisting
	
	/**
	 * Function for select sub category of parent category
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function subCategoryAppend(){
		if(Request::ajax()){ 
			$formData	=	Input::all();
			if($formData !=''){
				$mainCategory	= $formData['cat_id'];
				if ($mainCategory != '') {
					$primaryCategory = DropDown::where('_id', new MongoId($mainCategory))->select('sub_category.name','sub_category.sub_category_id')->first();
					if(!empty($primaryCategory->sub_category)) {
						$primaryCategoryArr = array();
						foreach ($primaryCategory->sub_category as $key => $subcategory) {
							$k                        = $subcategory['sub_category_id'];
							$primaryCategoryArr["$k"] = $subcategory['name'];
						}
					}
					$list = '<select  id="set_sub_cat" class="form-control"  name="sub_category_name"><option value="">Select Sub Category</option>';
					if (!empty($primaryCategoryArr)) {
						foreach ($primaryCategoryArr as $key => $value) { //main category loop
							$list .= '<option value=' . $key . '>' . $value . '</option>'; //subcategory name listing
						}
					}
					$list .= '</select>';
					echo $list;
					die;
				}else{
					echo '<select id="set_sub_cat"  class="form-control"  name="sub_category_name" ><option value="">Select Sub Category Name</option></select>';
					die;
				}
			}
		}
	}//end subCategoryAppend()
	
	/**
	 * Function for select sub category of parent category
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function subCategorySearch(){
		if(Request::ajax()){ 
			$formData	=	Input::all();
			if($formData !=''){
				$mainCategory	= $formData['cat_id'];
				$formData['element']	= (isset($formData['element'])) ? $formData['element'] :'';
				if($formData['element'] != ''){
					$data = "sortBySubCategory('".$formData['element']."')";	
				}else{
					$data = "sortBySubCategory()";
				}
				if ($mainCategory != '') {
					$primaryCategory = DropDown::where('_id', new MongoId($mainCategory))->select('sub_category.name','sub_category.sub_category_id')->first();
					if(!empty($primaryCategory->sub_category)) {
						$primaryCategoryArr = array();
						foreach ($primaryCategory->sub_category as $key => $subcategory) {
							$k                        = $subcategory['sub_category_id'];
							$primaryCategoryArr["$k"] = $subcategory['name'];
						}
					}
					$list = '<select class="form-control" onchange ="'.$data.'"  id="sort_by_sub_category" name="parent_sub_category" ><option value="">Select Sub Category</option>';
					if (!empty($primaryCategoryArr)) {
						foreach ($primaryCategoryArr as $key => $value) { //main category loop
							$list .= '<option value=' . $key . '>' . $value . '</option>'; //subcategory name listing
						}
					}
					$list .= '</select>';
					echo $list;
					die;
				}else{
					echo '<select class="form-control" onchange ="'.$data.'"  id="sort_by_sub_category" name="parent_sub_category" ><option value="">Select Sub Category </option></select>';
					die;
				}
			}
		}
	}//end subCategorySearch()
	/**
	 * Function for report a problem for purchased item
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function updateReport(){
		if (Request::ajax()) {			
			$orderId	=	Input::get('order_id');
			$orderObj	=	OrderItem::find($orderId);
			
			$validator = Validator::make(
						Input::all(), 
						array(
							'title'			 => 'required',
							'description'	 => 'required',
						));
		
			if($validator->fails()){
				$allErrors = '<ul>';
				foreach ($validator->errors()->all('<li>:message</li>') as $message) {
					$allErrors .= $message;
				}
				
				$allErrors .= '</ul>';
				$response = array(
					'success' => false,
					'errors' => $allErrors
				);
				return Response::json($response);
				die;
			}else{
				
				$report_title				=	Input::get('title');
				$description				=	Input::get('description');
				
				$orderObj->report_title		=	$report_title;
				$orderObj->description		=	$description;
				$orderObj->report_status	=	MARKETPLACE_REPORT_ISSUE_OPEN;
				$orderObj->save();
				$lastId	= $orderObj->_id;
				
				if(Input::hasFile('image')){
					foreach(Input::file('image') as $key =>  $image){
						$extension 	=	 $image->getClientOriginalExtension();
						$fileName	=	 time().rand()."order_item-image.".$extension;
						if($image->move(ORDER_ITEM_IMAGE_ROOT_PATH, $fileName)){
							OrderItem::raw()->update(
								array(
									'_id'=>new MongoId($lastId)
								),
								array(
									'$push'=>array(
									'report_image'=>	$fileName
									)
								)
							);
						} 
					}
				}
				
				$buyerName					=	Auth::user()->full_name; 
				$userId						=	$orderObj->seller_id;
				$marketPlaceId				=	$orderObj->marketplace_id;
				$marketPlaceTitle			=	$orderObj->title;
				$price						=	$orderObj->price;
				$qty						=	$orderObj->quantity;
				$totalPrice					=	$orderObj->total_price;
				
				$sellerDetail				=	User::where('_id',$userId)->select('email','full_name','_id')->first();
				$marketPlaceDetail			=	MarketPlace::where('_id',$marketPlaceId)->pluck('short_description');
				$marketPlaceimage			=	MarketPlaceImage::where('parent_id',$marketPlaceId)->pluck('image');
				
				$sellerName					=	$sellerDetail->full_name;
				$sellerEmail				=	$sellerDetail->email;
				$adminDetail				=	User::where('user_role_id',ADMIN_USER)->select('email','_id')->first();
				
				$link						=   route('product-detail',$orderObj->marketplace_id);
				
				
				/* Send notification to seller and admin */
				
				$notificationIdArray		=	array();
				$notificationIdArray		=	array($adminDetail->_id,$sellerDetail->_id);
				
				if(!empty($notificationIdArray)){
					foreach($notificationIdArray as $Id){	
						
						$notificationData	=	array(
							'user_id'			=>	$Id,
							'created_by'		=>	Auth::user()->_id,
							'notification_type'	=>	REPORT_A_PROBLEM,
							'buyer_name'		=>  Auth::user()->full_name,
							'item_name'			=>  $orderObj->title,
							'url'				=>  route('seller-message-request-send',$lastId),
						);
						
						$this->save_notifications($notificationData);
					}
				}
				/* Send notification to seller and admin end here  */
				
				/* Send email to seller and admin */
				$reportDetail				=	'';
				$reportDetail				.= '<table class="table" cellpadding="10">
													<tr class="items-inner">
														<td class="prdcts2">
															<figure width="110px" class="cart_img">
																<a href='.route("product-detail","$marketPlaceId").' target = "_blank">
																<img width="100px" height="100px" src="'.MARKET_PLACE_IMAGE_URL.$marketPlaceimage.'" alt="" />
																</a>
																</figure>
														</td>
														<td class="prdcts1">
															<h4><a href='.route("product-detail","$marketPlaceId").' target = "_blank">'.$marketPlaceTitle.'</a></h4>
															<p><a href='.route("product-detail","$marketPlaceId").' target = "_blank">'.$marketPlaceDetail.'</a></p>
															<label>Price:</label><span>'.$price.'</span><br/>
															<label>Qty:</label><span>'.$qty.'</span><br/>
															<label>Total Price:</label><span>'.$totalPrice	.'</span><br/>	
														</td>
													</tr>
												</table>';
				
				$emailActions				=	EmailAction::where('action','=','report_a_problem_for_product')->get()->toArray();
				$emailTemplates				=	EmailTemplate::where('action','=','report_a_problem_for_product')->get(array('name','subject','action','body'))->toArray();				
				$cons 						=	explode(',',$emailActions[0]['options']);
				$constants 					=	array();					
				
				foreach($cons as $key => $val){
					$constants[] = '{'.$val.'}';
				} 					
				$subject			 		=	$emailTemplates[0]['subject'];
				
				$rep_Array 					=	array($buyerName,$sellerName,$report_title,$description,$marketPlaceTitle,$link,$reportDetail); 
				
				$messageBody				=	str_replace($constants, $rep_Array, $emailTemplates[0]['body']);
				
				$this->sendMail(array($sellerEmail,$adminDetail->email),$buyerName,$subject,$messageBody);	
			}
			
			$response = array('success' => true);
			return Response::json($response);
		}
	}//end updateReport()
	
	
	/**
	 * Function for Post Buyer Message
	 * 
	 * @param $orderId as id of OrderItem
	 * 
	 * return view page 
	 * 
	 */
	
	public function buyerMessageSend($orderId){
		$orders 			= 	OrderItem::where('_id',$orderId)->first();
		$userMessageData 	= 	ReportMessage::with('senderDetail')
								->where('report_id',$orderId)
								->whereRaw(array('is_deleted' => array('$ne' => array('user_id'=>Auth::user()->_id ))))
								->get();
		
		return View::make('marketplace.buyer.buyer_message_request',compact('orders','userMessageData'));
	}// end buyerMessageSend()
	
	
	/**
	 * Function for Post seller Message
	 * 
	 * @param $orderId as id of OrderItem
	 * 
	 * return view page 
	 * 
	 */
	public function sellerMessageSend($orderId){
		$orders 		= 	OrderItem::where('_id',$orderId)->first();
		$userMessageData 	= 	ReportMessage::with('senderDetail')
								->where('report_id',$orderId)
								->whereRaw(array('is_deleted' => array('$ne' => array('user_id'=>Auth::user()->_id ))))
								->get();
		return View::make('marketplace.seller.seller_message_request',compact('orders','userMessageData'));
	}// end sellerMessageSend()
	
	
	/**
	 * Function for download image
	 *
	 * @param null
	 *
	 * @return response array. 
	 */	
	
	public function downloadImage($fileName =''){
		$filePath				=	ORDER_ITEM_IMAGE_ROOT_PATH.$fileName;
		if(File::exists($filePath)){
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header("Content-Disposition: attachment; filename=\"$fileName\"");
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.filesize($filePath));
			flush();
			readfile($filePath);
			exit;
		}
	}//end downloadImage
	
	
	/**
	 * Function for open my contest popup
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function myContestPopup(){
		if(Request::ajax()){
			$contestId			=	Input::get('contest_id');
			$myContestDetail 	=   MarketPlace::with('userDetail','causesDetail')->where('_id',$contestId)->first();
			return View::make('elements.marketplace-elements.view_my_contest_detail_popup',compact('myContestDetail'));
		}
	}//end myContestPopup()
	
	
	/**
	 * Function for contest and contest participant listing page
	 * 
	 * @param $id as id of marketplace
	 * 
	 * return view page 
	 * 
	 */
	
	public function viewContestDetail($marketPlaceId){
		
		$contestDetails				=	MarketPlace::with('causesDetail','marketPlaceImages')->where('_id', '=', $marketPlaceId)->first();
		
		$offset						=	(int)Input::get('offset',0);
		$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
		$elementName				=	Input::get('element');
		
		//contest winner list
		$isWinnerCondtion			=	ContestParticipant::with('userDetail')->where('is_win',1)
											->where('marketplace_id',$marketPlaceId);
											
		$countIsWinner				=	$isWinnerCondtion->count();	
		
		//contest participant list
		$contestCondition			=	ContestParticipant::with('userDetail')
											->where('marketplace_id',$marketPlaceId);
											
		$contestCount				=	$contestCondition->count();	
		
		// order management
		if(Input::get('order_by')!=''){
			Session::put('order_by_buying',Input::get('order_by'));
			$val 					=	explode(',',Input::get('order_by'));
		}else{
			if(Session::has('order_by_buying')){
				$val 				=	explode(',',Session::get('order_by_buying'));
			}
		}
		$orderBy					=	(isset($val[0])) ? $val[0]:'updated_at';
		$order						=	(isset($val[1])) ? $val[1]:'desc';
		
		$contestParticipant 		=  $contestCondition->skip($offset)->take($limit)
												->orderBy($orderBy,$order)->get();
												
		$isWinner 					=  $isWinnerCondtion->skip($offset)->take($limit)
												->orderBy($orderBy,$order)->get();
		
		if(Input::get('load_more')==1){
			switch ($elementName) {
				case 'participant':
					return View::make('elements.marketplace-elements.more_view_contest_detail_page',compact('contestDetails','contestParticipant','contestCount','offset','limit'));
				break;
		
				case 'winner':
					return View::make('elements.marketplace-elements.more_contest_winner',compact('contestDetails','offset','limit','isWinner','countIsWinner'));
				break;
			}
		}else{	
			// clear search session and order
			if(Session::has('order_by_buying')){
				Session::forget('order_by_buying');
			}
			return View::make("elements.marketplace-elements.view_contest_detail_page",compact('contestDetails','contestParticipant','contestCount','offset','limit','isWinner','countIsWinner'));
		}
	}//end viewContestDetail()
	
	
	/**
	 * Function for contest listing page
	 * 
	 * @param null
	 * 
	 * return view page 
	 * 
	 */
	public function contestParticipant(){
		return View::make("marketplace.contest");
	}//end contestParticipant()
	
	
	/**
	 * Function for load participant, winner, element of marketplace
	 *
	 * @param null
	 *
	 * @return response array. 
	 */
	
	public function loadContestElements(){
		if(Request::ajax()){
			$offset								=	(int)Input::get('offset',0);
			$limit								=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
			$elementName						=	Input::get('element');
			
			switch ($elementName) {
				case 'contest_participant':
				
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					
					$contestCondition 			=   ContestParticipant::with('marketPlaceDetail')->where('participant_id',Auth::user()->_id);
					$contestCount				=	$contestCondition->count();
					
					
					
					if(Input::get('order_by')!=''){
						Session::put('order_by_buying',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('order_by_buying')){
							$val 				=	explode(',',Session::get('order_by_buying'));
						}
					}
					
					$orderBy					=	(isset($val[0])) ? $val[0]:'created_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					
					$contest					=	$contestCondition->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
					
					if(Input::get('load_more')==1){
						return View::make('elements.marketplace-elements.more_contest_participant',compact('contest','limit','offset','contestCount'));
					}else{
						// clear search session and order
						if(Session::has('order_by_buying')){
							Session::forget('order_by_buying');
						}
						
						return View::make('elements.marketplace-elements.contest_participant',compact('contest','limit','offset','contestCount'));
					}
				
				break;
				
				case 'winner':
				
					$offset						=	(int)Input::get('offset',0);
					$limit						=	(int)Input::get('limit',Config::get('Reading.record_front_per_page')); 
					
					$isWinnerCondition 			=   ContestParticipant::with('marketPlaceDetail')
														->where('is_win',1)
														->where('participant_id',Auth::user()->_id);
					$isWinnerCount				=	$isWinnerCondition->count();
					
					
					if(Input::get('order_by')!=''){
						Session::put('order_by_buying',Input::get('order_by'));
						$val 					=	explode(',',Input::get('order_by'));
					}else{
						if(Session::has('order_by_buying')){
							$val 				=	explode(',',Session::get('order_by_buying'));
						}
					}
					
					$orderBy					=	(isset($val[0])) ? $val[0]:'created_at';
					$order						=	(isset($val[1])) ? $val[1]:'desc';
					
					$isWinner					=	$isWinnerCondition->skip($offset)->take($limit)->orderBy($orderBy,$order)->get();
					
					if(Input::get('load_more')==1){
						return View::make('elements.marketplace-elements.more_contest_participant_winner',compact('isWinner','limit','offset','isWinnerCount'));
					}else{
						// clear search session and order
						if(Session::has('order_by_buying')){
							Session::forget('order_by_buying');
						}
						
						return View::make('elements.marketplace-elements.contest_participant_winner',compact('isWinner','limit','offset','isWinnerCount'));
					}
				
				break;
				
			}
		}
	}//end loadContestElements()
	
}