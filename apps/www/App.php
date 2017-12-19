<?php
class App implements \Qing\Lib\ApplicationInterface{
	
	/**
	 * Register routes
	 * @param unknown $di
	 */
	public  function registerRoutes($di){
		$router = new \Phalcon\Mvc\Router();
		$router->add('/:controller', array(
				//'module' => 'frontend',
				'namespace' => 'Qing\Api\Controllers\\',
				'controller' => 1,
				'action' => 'index'
		));
		$router->add('/:controller/', array(
				//'module' => 'frontend',
				'namespace' => 'Qing\Api\Controllers\\',
				'controller' => 1,
				'action' => 'index'
		));
		$router->add('/', array(
				//'module' => 'frontend',
				'namespace' => 'Qing\Api\Controllers\\',
				'controller' => 'index',
				'action' => 'index'
		));
		$router->add(
				"/:controller/:action/:params",
				array(
		
						'namespace' => 'Qing\Api\Controllers\\',
						"controller" => 1,
						"action"     => 2,
						"params"=>3
				)
		);
		//$router->setDefaultModule("frontend");
		$router->setDefaultController('index');
		$router->setDefaultAction('index');
		$di->set('router',$router);
	}
	/**
	 * Register autoloader
	 * @param unknown $loader
	 */
	public  function registerAutoloaders($loader,$di){
		$loader->registerNamespaces(array(
			'Qing\Api\Controllers' => __DIR__.'/controllers/'
		));
		$loader->registerDirs(array(
			__DIR__.'/controllers/'
		));
		$loader->register();
	}
	/**
	 * Register service
	 * @param unknown $di
	 * @return \Phalcon\Mvc\View\Engine\Volt|unknown
	 */
	public  function registerServices($di){
		/**
		 * Setting up the view component
		 */
		$di->set('view', function() {
	
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir(__DIR__.'/views/');
			$view->registerEngines(array(
				".volt" => function($view, $di) {
					$volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
					return $volt;
				}
			));
			return $view;
	
		});
	
	}
}
