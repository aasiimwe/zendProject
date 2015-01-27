<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

return array(
     'controllers' => array(
         'invokables' => array(
             'Album\Controller\Album' => 'Album\Controller\AlbumController',
             
             
         ),
     ),
    
    // The following section is new and should be added to your file
     'router' => array(
         'routes' => array(
              'album' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/album[/][:action][/:id]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'id'     => '[0-9]+',
                     ),
                     'defaults' => array(
                         'controller' => 'Album\Controller\Album',
                         'action'     => 'index',
                         //'action'     => 'index',
                     ),
                 ),
             ),   
         ),
     ),
    
    'view_manager' => array(
         'template_path_stack' => array(
             'album' => __DIR__ . '/../view',
         ),
        'display_exceptions' => true,
     ),
    'service_manager' => array(
		// added for Authentication and Authorization. Without this each time we have to create a new instance.
		// This code should be moved to a module to allow Doctrine to overwrite it
		'aliases' => array( // !!! aliases not alias
			'Zend\Authentication\AuthenticationService' => 'my_auth_service',
		),
		'invokables' => array(
			'my_auth_service' => 'Zend\Authentication\AuthenticationService',
		),
	),
 );

