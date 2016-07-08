<?php

/**
 * MySettings Controller
 *
 * Add your methods in the class below
 *
 * This file will render views from views/myaccount
 */ 
class MySettingsController extends BaseController {
	
 /**
 * Function for display Settings  option given in dashboard  for change security question, subscribe unsubscribe
 * newsletter and job notification setting
 *
 * @param null
 *
 * @return json response
 */
 	public function index(){
		
		if(Request::ajax()){  
				
				$formData	=	Input::all();
				
				$userId				= Auth::user()->id;
				$notification_type  = Input::get('radio');
			
				//  profile update section start here

				$messages = array(
					'first_name.required' 			=> trans("messages.The firstname field is required."),
					'last_name.required' 			=> trans('messages.The lastname field is required.'),					
					'email.required' 				=> trans('messages.The email field is required.'),
					'email.email' 					=> trans('messages.The email must be a valid email address.'),
					'address.required'				=> trans('messages.The address field is required.'),
					'country.required' 				=> trans('messages.The country field is required.'),
					'region.required'				=> trans('messages.The region field is required.'),
					'city.required' 				=> trans('messages.The city field is required.'),
					'zip_code.required'				=> trans('messages.The zip code field is required.'),
					'phone_number.regex'			=> trans('messages.The phone number format is invalid.'),
					'zip_code.numeric'				=> trans('messages.The zip code must be a number.'),
				);
				
				// validation rule 
				$validationRules	=	array(
					'first_name'		=> 'required',
					'last_name' 		=> 'required',
					'email' 			=> 'required|email',
					'address' 			=> 'required',			
					'country' 			=> 'required',
					'region'			=> 'required',
					'city'				=> 'required',
					'zip_code' 			=> 'required|numeric',	
					'phone_number' 		=> 'regex:/(^([0-9]+[-]*[0-9])*$)/',	
				);
			
				$validator = Validator::make(
					Input::all(),
					$validationRules,
					$messages
				);
				
				if ($validator->fails()){
					
					$allErrors='<ul>';
					foreach ($validator->errors()->all('<li>:message</li>') as $message)
					{
							$allErrors .=  $message; 
					}
					
					$allErrors .= '</ul>'; 
					$response	=	array(
						'success' => 0,
						'errors' => $allErrors
					);
					
					return  Response::json($response); die;

				}
				
				
					$countryCode			=	Input::get('country');
					$regionCode				=	Input::get('region'); 
				
					#### Getting country name ###
					$countryName	=	DB::table('countries')
										->where('iso3166_1','=',$countryCode)
										->lists('name');
					
					#### Getting region name ###
					$regionName		=	DB::table('geonames')
										->where('feature_code','=','ADM1')
										->where('country_code','=',$countryCode)
										->where('admin1_code','=',$regionCode)
										->lists('name');
											
										
					$get_middlename 	= 	Input::get('middle_name');
					$middlename			=	(!empty($get_middlename)) ? ' '.$get_middlename.' ' : ' ';
					$fullName			=	Input::get('first_name').$middlename.Input::get('last_name');

					$obj 					=  User::find($userId);				
					$obj->full_name 		=  $fullName;
					$obj->save();
																
					$userProfile							=	array();
					$userProfile['User.full_name']			=	$fullName;
					$userProfile['User.firstname']			=	Input::get('first_name');
					$userProfile['User.middlename']			=	Input::get('middle_name');
					$userProfile['User.lastname']			=	Input::get('last_name');
					$userProfile['User.email']				=	Input::get('email');
					$userProfile['User.phone_number']		=	Input::get('phone_number');
					$userProfile['User.address']			=	Input::get('address');
					$userProfile['User.address_2']			=	Input::get('address_2');
					$userProfile['User.country']			=	$countryCode;
					$userProfile['User.country_name']		=	(isset($countryName[0])) ? $countryName[0] : '';
					$userProfile['User.region']				=	$regionCode;
					$userProfile['User.region_name']		=	(isset($regionName[0])) ? $regionName[0] : '';
					$userProfile['User.city_name']			=	Input::get('city');
					$userProfile['User.zip_code']			=	Input::get('zip_code');
										
					// insert into user_profile table
					foreach ($userProfile as $key => $value) {
						UserProfile::updateOrcreate(
							array('user_id' 	=> $userId,'field_name'	=> $key),
							array(
								'field_value'	=>	$value
							)
						);
					}
					// profile update section end here
					
					/* 	condition for Unsubscribe the current user */
					if(Input::get('Unsubscribe') == 'yes'){
						
						$email	=	DB::table('users')->where('id', $userId)->pluck('email');
						DB::table('newsletter_subscribers')->where('email', '=',$email)->delete();	
					
					} 
					
					/* 	condition for job notification type */
					if($notification_type == 'immediate' || $notification_type == 'weekly' || $notification_type == 'daily'){	
						UserProfile::updateOrcreate(
							array('user_id' => $userId,'field_name'	=> 'User.job_notifications'),
							array(
								'field_value'	=>	Input::get('radio')
							)
						);
					} 
				
					$answer	 = Input::get('answer'); 
					// if user fill the password field,update it.
					 if(!empty($answer)){
						DB::table('user_profiles')
									->where('user_id', $userId)
									->where('field_name', 'User.security_answer')
									->update(array('field_value'=>$answer));
					 } 
					
						$response	=	array(
							'success' 	=>'1',
						);
					return  Response::json($response); die;
				}


			$userProfileData	=	$this->userProfile(Auth::user()->id);
			
			
			$regionList		=	array();
			$old_country	=	Input::get('country');
			
			if(!empty($old_country)){
				$countryCode	=  Input::get('country');
			}else{
				$countryCode	=  $userProfileData->country;
			}
			
			$regionList			=	DB::table('geonames')
									->where('feature_code','=','ADM1')
									->where('country_code','=',$countryCode)
									->lists('name','admin1_code');
			
			$countryList		=	DB::table('countries')->lists('name','iso3166_1');
			
			$userId			 	=	Auth::user()->id;
		
			
		
		// update or create notification type 
		if(!isset($notification_type->field_value)){
			UserProfile::updateOrcreate(
				array('user_id' => $userId,'field_name' => 'User.job_notifications'),
				array(
					'field_value'	=>	'daily'
				)
			);
		}
		
		
		$email	=	DB::table('users')->where('id', $userId)->pluck('email');
				
		$count	=	DB::table('newsletter_subscribers')->where('email',$email)->where('is_verified','1')->count();
		
		View::share('pageTitle', 'Setting');
		return View::make('myaccount/mysettings',compact('count','userProfileData','countryList','regionList'));
	}//end index()
	
}// end class MySettingsController
