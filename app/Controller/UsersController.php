<?php
/**
* UsersController is responsible for login pages
* 
* UsersController has multiple functionalities which includes:
* -Register a new user(employee|manager)
* -Local Login, Facebook Login(unfinished), Logout
* -passwordreset(unfinished)
*/

class UsersController extends AppController{
	public function beforeFilter(){
		parent::beforeFilter();
		$this->Auth->allow('register', 'logout');
	}

	public function index(){
		$this->set('users', $this->paginate());
	}

	/**
	* Register a new account
	*
	* By using register(), user can register account with either manager role
	* or employee role. Manager accounts need to provide reference code 
	* (current code is 111).
	*
	*@return redirect to login page if account registration is succeed
	*/
	public function register(){
		if($this->request->is('post')){
			$this->User->create();
			$data['username'] = $this->request->data['User']['username'];
			$data['password'] = $this->request->data['User']['password'];
			if($this->request->data['User']['manager']){
				if($this->request->data['User']['reference']){
					$reference = $this->request->data['User']['reference'];
					if ($reference == '111'){
						$data['role'] = 'manager';

						if ($this->User->save($data)){
							$this->Session->setFlash(__('Welcome Manager'));
							return $this->redirect(array('action'=>'login'));
						}
					}else{
						$this->Session->setFlash(__('Wrong Reference Code'));
						return $this->redirect(array('action'=>'register'));
					}
				}
			}

			$data['role'] = 'employee';
			
			if ($this->User->save($data)){
				$this->Session->setFlash(__('Your account successfully registered'));
				return $this->redirect(array('action'=>'login'));
			}

			$this->Session->setFlash(
				__('Account creation failed, please try again'));
		}
	}
	/**
	* Login as a user
	*
	* By using login(), user can access the client information, but only
	* manager can perform actions on client information. 
	*
	*@return redirect to index page if account registration is succeed
	*@Added facebook login(not fully functional)
	*/
	public function login(){
		if($this->request->is('post')){
			if($this->Auth->login()){
				$role = $this->Auth->user('role');
				if($role=='manager'){
					$this->Auth->loginRedirect = array('controller'=>'clients', 'action'=>'index');
				}
				if($role=='employee'){
					$this->Auth->loginRedirect = array('controller'=>'clients', 'action'=>'indexe');
				}
				return $this->redirect($this->Auth->loginRedirect);
			}
			$this->Session->setFlash(__('Wrong username and password combination, please try again'));
		}
/**		elseif($this->request->query('code')){
			$fb_user = $this->Facebook->api('/me');

			$local_user = $this->User->find('first', array('conditions'=>array('username'=>$fb_user['email'])));
			if($local_user){
				$this->Auth->login($local_user['User']);
				$this->redirect($this->Auth->redirectUrl());
			}

			else{
				$data['User'] = array(
					'username' => $fb_user['email'],
					'password' => AuthComponent::password(uniqid(md5(mt_rand()))),
					'role' => 'employee'
					);
				$this->User->save($data, array('validate'=>false));

				$this->redirect(Router::url('/users/login?code=true', true));
			}
		}
		*/
	}
	/**
	* Logout current session
	*/
	public function logout(){
		return $this->redirect($this->Auth->logoutRedirect);
	}

	/**
	* Reset Password(not fully functional)
	*
	* By using resetpassword(), user can reset the account password, system
	* will auto generate a hashed password and send to user through email
	*
	*@return redirect to index page if account registration is succeed
	*@Added facebook login(not fully functional)
	*/
	public function resetpassword(){
		if($this->request->is('post')){
			if(!(($this->request->data['username'])&&($this->request->data['email']))){
				$this->Session->setFlash(__('please enter a valid combination'));
				return $this->redirect(array('action'=>'resetpassword'));
			}
			$this->loadmodel('User');
			$data['username'] = $this->request->data['username'];
			$data['email'] = $this->request->data['email'];
			$newpassword = AuthComponent::password(uniqid(md5(mt_rand())));
			$user = $this->User->find('first', array('conditions' => array('username' => $data['username'])));

			if(!$user){
				$this->Session->setFlash(__('please enter a valid combination'));
				return $this->redirect(array('action'=>'resetpassword'));
			}

			$Email = new CakeEmail();
			$Email->from(array('clark@faceapp.com' => 'My Site'));
			$Email->to($data['email']);
			$Email->subject('PasswordReset');
			$Email->send(__('your new password is %s',h($newpassword)));

			$user['password']=$newpassword;
			if ($this->User->save($user)){
				$this->Session->setFlash(__('Please Check Your email'));
				return $this->redirect(array('action'=>'login'));
			}else{
				$this->Session->setFlash(__('Reset Password failed'));
				return $this->redirect(array('action'=>'login'));
			}
		}
		$this->Session->setFlash(__('SOMETHIGN WRONG HERE'));
	}

}