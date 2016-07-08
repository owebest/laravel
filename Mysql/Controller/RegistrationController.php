<?php

/**
 * Registration Controller
 *
 * Add your methods in the class below
 *
 * This file will render views from views/registration
 */
 
class RegistrationController extends \BaseController {

/**
 * Function for display registration page
 *
 * @param $type  type of user selected plan 
 *
 * @return view page. 
 */
	public function getIndex($type='MA=='){
		$userType	=	base64_decode($type);
		
		if(Auth::check()){
			return Redirect::route('user-dashboard');
		}
		$termCondition		=	Cms::getResult('terms',array('id','body'));
		$regionList			=	array();
		$old_country 		= 	Input::old('country');
		$old_region 		= 	Input::old('region');
		
		if(!empty($old_country)){
			$countryCode	=	Input::old('country');
							
			$regionList		=	DB::table('geonames')
									->where('feature_code','=','ADM1')
									->where('country_code','=',$countryCode)
									->lists('name','admin1_code');
			
		}
		
		$countryList	=	DB::table('countries')->lists('name','iso3166_1');
		$blocks			=	Block::getResult('Registration',array('id','description','block'));
		
		View::share('pageTitle', 'Sign up');
		return View::make('registration.index',compact('termCondition','countryList','regionList','userType','blocks'));
	}// end getIndex()

/**
 * Function for save user registration details
 *
 * @param null
 *
 * @return url. 
 */
	public function postIndex(){	
		if(Request::ajax()){ 
			
			$formData	=	Input::all();
			
			if(!empty($formData)){
				
				$messages = array(
					'first_name.required' 			=> trans("messages.The firstname field is required."),
					'last_name.required' 			=> trans('messages.The lastname field is required.'),
					'username.required' 			=> trans('messages.The username field is required.'),
					'username.unique' 				=> trans('messages.The username has already been taken.'),
					'email.required' 				=> trans('messages.The email field is required.'),
					'email.email' 					=> trans('messages.The email must be a valid email address.'),
					'email.unique' 					=> trans('messages.The email has already been taken.'),
					'password.required' 			=> trans('messages.The password field is required.'),
					'password.min' 					=> trans('messages.The password must be at least 6 characters.'),
					'confirm_password.required' 	=> trans('messages.The confirm password field is required.'),
					'confirm_password.min' 			=> trans('messages.The confirm password must be at least 6 characters.'),
					'confirm_password.same' 		=> trans('messages.The confirm password and password must match.'),
					'address.required'				=> trans('messages.The address field is required.'),
					'country.required' 				=> trans('messages.The country field is required.'),
					'region.required'				=> trans('messages.The region field is required.'),
					'city.required' 				=> trans('messages.The city field is required.'),
					'zip_code.required'				=> trans('messages.The zip code field is required.'),
					'security_question.required' 	=> trans('messages.The security question field is required.'),
					'security_answer.required' 	 	=> trans('messages.The answer field is required.'),
					'know_about.required' 			=> trans('messages.The where did you know about Ummercial field is required.'),
					'user-captcha.required' 		=> trans('messages.The captcha field is required.'),
					'user-captcha.captcha'  		=> trans('messages.The captcha must be correct.'),
					'term_conditions.required' 		=> trans('messages.The term conditions field is required.'),
					'others.required'				=> trans('messages.Please specify other source,from where you know about Ummercial.'),
					'others.required'				=> trans('messages.Please specify other source,from where you know about Ummercial.'),
					'zip_code.numeric' 				=> trans('messages.The zip code must be a number.'),
					'phone_number.regex'			=> trans('messages.The phone number format is invalid.'),
				);
				
				// validation rule 
				
 
				$validationRules	=	array(
						'first_name'		=> 'required',
						'last_name' 		=> 'required',
						'username' 			=> 'required|unique:users,username,NULL,id,is_deleted,0',
						'email' 			=> 'required|email|unique:users',
						'password'			=> 'required|min:6',
						'confirm_password'  => 'required|min:6|same:password', 
						'address' 			=> 'required',
						'country' 			=> 'required',
						'region'			=> 'required',
						'city'				=> 'required',
						'zip_code' 			=> 'required|numeric',
						'security_question'	=> 'required',
						'security_answer' 	=> 'required',
						'know_about' 		=> 'required',
						'user-captcha' 		=> 'required|captcha',
						'term_conditions' 	=> 'required',
						'phone_number' 		=> 'regex:/(^([0-9]+[-]*[0-9])*$)/',
					);
				
				$knowAbout	  =	 Input::get('know_about');
				
				// validation on 'Others field' when user select the others option from Known about dropdown
				
				if($knowAbout == KNOW_FROM_OTHER ){
					$validationRules	=	array_merge($validationRules,array('others'=>'required'));
				}
				
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
						'success' => false,
						'errors' => $allErrors
					);
					
					return  Response::json($response); die;

				}else{
					
					$planType			=	Input::get('user_type');
					$userRoleId			=	FRONT_USER ;
					$get_middlename 	= 	Input::get('middle_name');
					$middlename			=	(!empty($get_middlename)) ? ' '.$get_middlename.' ' : ' ';
					$fullName			=	Input::get('first_name').$middlename.Input::get('last_name');
					
					$obj 				=  new User;
					
					$validateString			=	md5(time() . Input::get('email'));
					$obj->validate_string	=  $validateString;
									
					$obj->full_name 		=  $fullName;
					$obj->email 			=  Input::get('email');
					$obj->username 			=  Input::get('username');
					$obj->slug	 			=  $this->getSlug($fullName,'User');
					$obj->password	 		=  Hash::make(Input::get('password'));
					$obj->user_role_id		=  $userRoleId;
					$obj->is_payment_complete=  0;
					$obj->is_verified		=   1;
						
					$obj->save(); 	
					
					$userId					=	$obj->id;
						
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
				
					$encId			=	md5(time() . Input::get('email'));
					
					// insert into user table
					DB::table('newsletter_subscribers')->insert(
						array(
							'user_id' 		=>  $userId,
							'email'	  		=>  Input::get('email'),
							'is_verified' 	=>  0,
							'status' 		=>  1,
							'enc_id' 		=>  $encId,
							'created_at' 	=>   DB::raw('NOW()'),
							'updated_at' 	=>   DB::raw('NOW()')
						)
					);
							
					$userProfile							=	array();
					$userProfile['User.full_name']			=	$fullName;
					$userProfile['User.prefix']				=	Input::get('prefix');
					$userProfile['User.firstname']			=	Input::get('first_name');
					$userProfile['User.middlename']			=	Input::get('middle_name');
					$userProfile['User.lastname']			=	Input::get('last_name');
					$userProfile['User.suffix']				=	Input::get('suffix');
					$userProfile['User.skype_id']			=	Input::get('skype_id');
					$userProfile['User.phone_number']		=	Input::get('phone_number');
					$userProfile['User.address']			=	Input::get('address');
					$userProfile['User.address_2']			=	Input::get('address_2');
					$userProfile['User.country']			=	$countryCode;
					$userProfile['User.country_name']		=	(isset($countryName[0])) ? $countryName[0] : '';
					$userProfile['User.region']				=	$regionCode;
					$userProfile['User.region_name']		=	(isset($regionName[0])) ? $regionName[0] : '';
					$userProfile['User.city_name']			=	Input::get('city');
					$userProfile['User.zip_code']			=	Input::get('zip_code');
					$userProfile['User.date_of_birth']		=	Input::get('date_of_birth');
					$userProfile['User.security_question']	=	Input::get('security_question');
					$userProfile['User.security_answer']	=	Input::get('security_answer');
					$userProfile['User.know_about']			=	(Input::get('others') != "") ? Input::get('others') : Input::get('know_about') ;
					$userProfile['User.job_notifications']	=	'daily';
					
					// insert into user_profile table
					foreach ($userProfile as $key => $value) {
						DB::table('user_profiles')->insert(
							array(
								'user_id'		=>	$userId,
								'field_name'	=>	$key,
								'field_value'	=>	$value
							)
						);
					}			
												
					$response	=	array(
						'userId'	=>$userId,
						'planType'	=>$planType,
						'success' 	=>'1',
					);
					
					$user = User::find($userId);
					Auth::login($user);
					$userdata	=	$this->userProfile(Auth::user()->id);
					Session::put('User.Auth', $userdata);
					
					$userInfo	=	User::where('id' ,'=', $userId)->get(array('validate_string','is_verified','id','email','full_name'))->toArray();
					$this->thanksForRegistration($userInfo);
					
					return Response::json($response); die;						
				}	
			}
		}
	}// end postIndex()

/**
 * Function for send verification link to user by email
 *
 * @param $validate_string as validate string which stored in database
 *
 * @return url. 
 */	
	public function send_verification_link($validate_string=null){ 
	
		if($validate_string==''){
			return Redirect::to('/')
				->with('error', trans('messages.Sorry, you are using wrong link.'));
		}

		$userDetail			=	DB::table('users')->where('active','1')->where('validate_string',$validate_string)->first();
		$emailActions		=	EmailAction::where('action','=','account_verification')->get()->toArray();

		$emailTemplates		=	EmailTemplate::where('action','=','account_verification')->get(array('name','subject','action','body'))->toArray();
		$cons 				=   explode(',',$emailActions[0]['options']);
		$constants 			=   array();
		foreach($cons as $key => $val){
			$constants[] = '{'.$val.'}';
		}
		
		$username			=   Input::get('firstname');
		$loginLink   	 	= 	URL::to('account-verification/'.$validate_string);  
		$verificationUrl 	=   $loginLink;
		$loginLink 			=   '<a style="font-weight:bold;text-decoration:none;color:#000;" target="_blank" href="'.$loginLink.'">click here</a>';
		
		$subject 			=  $emailTemplates[0]['subject'];
		$rep_Array 			=  array($userDetail->full_name,$loginLink,$verificationUrl); 
		
		$messageBody		=  str_replace($constants, $rep_Array, $emailTemplates[0]['body']);
		 
		$this->sendMail($userDetail->email,$userDetail->full_name,$subject,$messageBody);
		Session::flash('flash_notice', trans('messages.A verification email has been emailed to you please verify email and login.')); 
		return Redirect::to('/login');
	}// end send_verification_link()

/** 
 * Function for user account verification
 *
 * @param $validate_string as validate string
 *
 * @return void
 */
	function accountVerification($validate_string = ''){
	
		$userInfo	=	User::where('validate_string' ,'=', $validate_string)->get(array('validate_string','is_verified','id','email','full_name'))->toArray();
		if(!empty($userInfo)){
		
			if($userInfo['0']['is_verified']==1){
				
				Session::flash('flash_notice', trans('messages.Your account is already verified. Please login to access to your account.')); 
				return Redirect::to('/login');
				
			}else{
				User::where('id', $userInfo['0']['id'])->update(array(
					'validate_string'   =>  '',
					'is_verified' 		=>  1,
					'active' 			=>  1
				));
				
				$this->thanksForRegistration($userInfo);
				
				Session::flash('flash_notice', trans('messages.Your account has been verified. Please login.')); 
				return Redirect::to('/login');
			}
		}else{
			Session::flash('flash_notice', trans('messages.Sorry, you are using wrong link.')); 
			return Redirect::to('/');
		}
	}// end accountVerification()
	
/** 
 * Function for user newsletter verification
 *
 * @param $validate_string as validate string
 *
 * @return redirect page after  newsletter subscribe
 */
	
	function newsletterVerification($encId = ''){
		
		$checkUser	=	DB::table('newsletter_subscribers')->where('enc_id',$encId)->count();
	
		if($checkUser > 0 && $encId !='' ){
			$userInfo	=	DB::table('newsletter_subscribers')->where('enc_id',$encId)->first();
			if($userInfo->is_verified=='1'){
				Session::flash('flash_notice', trans('messages.Sorry,You have already verified newsletter.')); 
				return Redirect::to('/');
			}
			//update user as varified
			DB::table('newsletter_subscribers')->where('enc_id',$encId)->update(array(
				'is_verified' 	=>  1
			));
			
			$emailActions		=	EmailAction::where('action','=','newsletter_subscription')->get()->toArray();
	
			$emailTemplates		=	EmailTemplate::where('action','=','newsletter_subscription')->get(array('name','subject','action','body'))->toArray();
		
			$cons = explode(',',$emailActions[0]['options']);
			$constants = array();
			foreach($cons as $key=>$val){
				$constants[] = '{'.$val.'}';
			}
			
			$username			=   $userInfo->email;
			$username			=	User::where('email',$username)->pluck('full_name');
			$loginLink   	 	= 	WEBSITE_URL.'newsletter-unsubscribe/'.$encId.'/email';
			
			$verificationUrl 	=   $loginLink;
			$loginLink 			=   '<a style="font-weight:bold;text-decoration:none;color:#000;" target="_blank" href="'.$loginLink.' ">Click here</a>';
			
			$subject 			=  $emailTemplates[0]['subject'];
			$rep_Array 			=  array($username,$loginLink); 
			$messageBody		=  str_replace($constants, $rep_Array, $emailTemplates[0]['body']);
			
			 
			$this->sendMail($userInfo->email,$userInfo->email,$subject,$messageBody);
			Session::flash('flash_notice', trans('messages.Newsletter Subscribe successfully.')); 
			return Redirect::to('/');
		}else{
			Session::flash('flash_notice', trans('messages.Sorry, you are using wrong link.')); 
			return Redirect::to('/');
		}
	}// end newsletterVerification()	
	
/**
 * Function for thanks for registration mail
 * 
 * @param $userDetails as user detail array of user
 * 
 * @return void. 
 */
	function thanksForRegistration($userDetails){
		$emailActions		=	EmailAction::where('action','=','thanks_for_registration')->get()->toArray();
		
		$emailTemplates		=	EmailTemplate::where('action','=','thanks_for_registration')->get(array('name','subject','action','body'))->toArray();
		$cons = explode(',',$emailActions[0]['options']);
		$constants = array();
		foreach($cons as $key=>$val){
			$constants[] = '{'.$val.'}';
		}
		
		$username			=   $userDetails[0]['full_name'];
		
		$subject 			=  $emailTemplates[0]['subject'];
		$rep_Array 			=  array($username); 
		
		$messageBody		=  str_replace($constants, $rep_Array, $emailTemplates[0]['body']);
		 
		$this->sendMail($userDetails[0]['email'],$userDetails[0]['full_name'],$subject,$messageBody);
		
	}// end thanksForRegistration()
	
/**
 * Function for subscribe newsletter for user
 * 
 * @param null
 * 
 * @return status. 
 */
	function newsLetterSubscription(){
		$email	=	Input::get('email');
		
		$checkVerification	=	DB::table('newsletter_subscribers')->where('email',$email)->where('is_verified',0)->count();
		
		$messages = array(
			'email.required' 		=> trans('messages.The email field is required.'),
			'email.email' 			=> trans('messages.The email must be a valid email address.')
		);
		$validator = Validator::make(
			Input::all(),
			array(
				'email' 			=> 'required|email|Unique:newsletter_subscribers'
			),
			$messages
		);
		if ($validator->fails()){
			if($checkVerification){
			
					$encId				=	DB::table('newsletter_subscribers')->where('email',$email)->pluck('enc_id');
			
					$emailActions		=	EmailAction::where('action','=','newsletter_verification')->get()->toArray();
			
					$emailTemplates		=	EmailTemplate::where('action','=','newsletter_verification')->get(array('name','subject','action','body'))->toArray();
					$cons = explode(',',$emailActions[0]['options']);
					$constants = array();
					foreach($cons as $key=>$val){
						$constants[] = '{'.$val.'}';
					}
					
					$username			=   Input::get('email');
					$username			=	User::where('email',$username)->pluck('full_name');
					$loginLink   	 	= 	WEBSITE_URL.'newsletter-verification/'.$encId;
					
					$verificationUrl 	=   $loginLink;
					$loginLink 			=   '<a style="font-weight:bold;text-decoration:none;color:#000;" target="_blank" href="'.$loginLink.'">Click here</a>';
					
					$subject 			=  $emailTemplates[0]['subject'];
					$rep_Array 			=  array($username,$loginLink,$verificationUrl); 
					$messageBody		=  str_replace($constants, $rep_Array, $emailTemplates[0]['body']);
					$this->sendMail(Input::get('email'),Input::get('email'),$subject,$messageBody);
					echo 'notVerify'; die;
			}else{
				$errors	=	$validator->messages()->getMessages();
				echo $errors['email'][0]; die;
			}
		}else{
			$encId			=	md5(time() . Input::get('email'));
			
			DB::table('newsletter_subscribers')->insert(
				array(
					'user_id' 		=>  (Auth::check() ? Auth::user()->id : 0),
					'email'	  		=>  Input::get('email'),
					'name'	  		=>  Input::get('name'),
					'is_verified' 	=>  0,
					'status' 		=>  1,
					'enc_id' 		=>  $encId,
					'created_at' 	=>  DB::raw('NOW()'),
					'updated_at' 	=>   DB::raw('NOW()')
				)
			);
			
			
			$emailActions		=	EmailAction::where('action','=','newsletter_verification')->get()->toArray();
	
			$emailTemplates		=	EmailTemplate::where('action','=','newsletter_verification')->get(array('name','subject','action','body'))->toArray();
			$cons = explode(',',$emailActions[0]['options']);
			$constants = array();
			foreach($cons as $key=>$val){
				$constants[] = '{'.$val.'}';
			}
			
			$email				=   Input::get('email');
			$username			=	User::where('email',$email)->pluck('full_name');
			$loginLink   	 	= 	WEBSITE_URL.'newsletter-verification/'.$encId;
			
			$verificationUrl 	=   $loginLink;
			$loginLink 			=   '<a style="font-weight:bold;text-decoration:none;color:#000;" target="_blank" href="'.$loginLink.'">Click here</a>';
			
			$subject 			=  $emailTemplates[0]['subject'];
			$rep_Array 			=  array($username,$loginLink,$verificationUrl); 
			$messageBody		=  str_replace($constants, $rep_Array, $emailTemplates[0]['body']);
			$this->sendMail(Input::get('email'),Input::get('email'),$subject,$messageBody);
			echo 'success'; die;
		}
	}// end newsLetterSubscription()
	
/**
 * Function for unsubscribe newsletter
 * 
 * @param $encId as string which stored in database
 * 
 * @return url. 
 */
	function newsLetterUnSubscribe($encId){
		$subscriberStatus	=	DB::table('newsletter_subscribers')->where('enc_id', '=', $encId)->count('id'); 
		if($subscriberStatus >= 1){
			DB::table('newsletter_subscribers')->where('enc_id', '=', $encId)->delete();
			Session::flash('flash_notice',trans('messages.You are successfully unsubscribe from newletters.')); 
		}else{
			Session::flash('flash_notice', trans('messages.Invalid link.')); 
		}
		if(Request::segment(1)=='newsletter-unsubscribed'){
			return Redirect::to('/');
		}else{
			return Redirect::back();
		}

	}// end newsLetterUnSubscribe()
	
}// end RegistrationController class
