<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Album\Controller;

 // module/Album/src/Album/Controller/AlbumController.php:

 //...
 use Zend\Mvc\Controller\AbstractActionController;
 use Zend\View\Model\ViewModel;
 use Zend\Authentication\Result;
 use Zend\Authentication\AuthenticationService;
 use Zend\Authentication\Storage\Session as SessionStorage;
 use Zend\Db\Adapter\Adapter as DbAdapter;
 use Zend\Authentication\Adapter\DbTable as AuthAdapter;
 use Album\Model\Album;          // <-- Add this import
 use Album\Form\AlbumForm;       // <-- Add this import
 use Album\Model\Auth;
 use Album\Form\AuthForm;





 
 

 class AlbumController extends AbstractActionController
 {
     protected $albumTable;
     
     // module/Album/src/Album/Controller/AlbumController.php:
     public function getAlbumTable()
     {
         if (!$this->albumTable) {
             $sm = $this->getServiceLocator();
             $this->albumTable = $sm->get('Album\Model\AlbumTable');
         }
         return $this->albumTable;
     }
     
     // module/Album/src/Album/Controller/AlbumController.php:
 // ...
     public function indexAction()
             
     {
         
         return new ViewModel(array(
             'albums' => $this->getAlbumTable()->fetchAll(),
         ));
     }
 // ...

     // Add content to this method:
     public function addAction()
     {
         $form = new AlbumForm();
         $form->get('submit')->setValue('Add');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $album = new Album();
             $form->setInputFilter($album->getInputFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $album->exchangeArray($form->getData());
                 $this->getAlbumTable()->saveAlbum($album);

                 // Redirect to list of albums
                 return $this->redirect()->toRoute('album');
             }
         }
         return array('form' => $form);
     }
 //...

      // module/Album/src/Album/Controller/AlbumController.php:
 //...

     // Add content to this method:
     public function editAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('album', array(
                 'action' => 'add'
             ));
         }

         // Get the Album with the specified id.  An exception is thrown
         // if it cannot be found, in which case go to the index page.
         try {
             $album = $this->getAlbumTable()->getAlbum($id);
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('album', array(
                 'action' => 'index'
             ));
         }

         $form  = new AlbumForm();
         $form->bind($album);
         $form->get('submit')->setAttribute('value', 'Edit');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setInputFilter($album->getInputFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $this->getAlbumTable()->saveAlbum($album);

                 // Redirect to list of albums
                 return $this->redirect()->toRoute('album');
             }
         }

         return array(
             'id' => $id,
             'form' => $form,
         );
     }
 //...


     // module/Album/src/Album/Controller/AlbumController.php:
 //...
     // Add content to the following method:
     public function deleteAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('album');
         }

         $request = $this->getRequest();
         if ($request->isPost()) {
             $del = $request->getPost('del', 'No');

             if ($del == 'Yes') {
                 $id = (int) $request->getPost('id');
                 $this->getAlbumTable()->deleteAlbum($id);
             }

             // Redirect to list of albums
             return $this->redirect()->toRoute('album');
         }

         return array(
             'id'    => $id,
             'album' => $this->getAlbumTable()->getAlbum($id)
         );
     }
 //...
     
     //Authentication methods added by Apollo
     public function loginAction()
	{
		$user = $this->identity();
		$form = new AuthForm();
		$form->get('submit')->setValue('Login');
		$messages = null;

		$request = $this->getRequest();
        if ($request->isPost()) {
			$authFormFilters = new Auth();
            $form->setInputFilter($authFormFilters->getInputFilter());	
			$form->setData($request->getPost());
			 if ($form->isValid()) {
				$data = $form->getData();
				$sm = $this->getServiceLocator();
				$dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
				
				$config = $this->getServiceLocator()->get('Config');
				//$staticSalt = $config['static_salt'];

				$authAdapter = new AuthAdapter($dbAdapter,
										   'users', // there is a method setTableName to do the same
										   'username', // there is a method setIdentityColumn to do the same
										   'password', // there is a method setCredentialColumn to do the same
										   "MD5(password)" // setCredentialTreatment(parametrized string) 'MD5(?)'
										  );
				$authAdapter
					->setIdentity($data['username'])
					->setCredential($data['password'])
				;
				
				$auth = new AuthenticationService();
				// or prepare in the globa.config.php and get it from there. Better to be in a module, so we can replace in another module.
				// $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
				// $sm->setService('Zend\Authentication\AuthenticationService', $auth); // You can set the service here but will be loaded only if this action called.
				$result = $auth->authenticate($authAdapter);			
				
				switch ($result->getCode()) {
					case Result::FAILURE_IDENTITY_NOT_FOUND:
						// do stuff for nonexistent identity
						break;

					case Result::FAILURE_CREDENTIAL_INVALID:
						// do stuff for invalid credential
						break;

					case Result::SUCCESS:
						$storage = $auth->getStorage();
						$storage->write($authAdapter->getResultRowObject(
							null,
							'password'
						));
						$time = 1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days
//						if ($data['rememberme']) $storage->getSession()->getManager()->rememberMe($time); // no way to get the session
						if ($data['rememberme']) {
							$sessionManager = new \Zend\Session\SessionManager();
							$sessionManager->rememberMe($time);
						}
						break;

					default:
						// do stuff for other failure
						break;
				}				
				foreach ($result->getMessages() as $message) {
					$messages .= "$message\n"; 
				}			
			 }
		}
		return new ViewModel(array('form' => $form, 'messages' => $messages));
	}
	
	public function logoutAction()
	{
		$auth = new AuthenticationService();
		// or prepare in the globa.config.php and get it from there
		// $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
		
		if ($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
		}			
		
		$auth->clearIdentity();
//		$auth->getStorage()->session->getManager()->forgetMe(); // no way to get the sessionmanager from storage
		$sessionManager = new \Zend\Session\SessionManager();
		$sessionManager->forgetMe();
		
		return $this->redirect()->toRoute('album', array( 'action' => 'login'));		
	}	

     
     
 }
