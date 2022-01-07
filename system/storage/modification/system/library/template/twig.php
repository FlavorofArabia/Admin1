<?php
namespace Template;
final class Twig {
	private $twig;
	private $data = array();
	
	public function __construct() {
		// include and register Twig auto-loader
		include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');
		
		\Twig_Autoloader::register();
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render($template, $cache = false) {
		// specify where to look for templates
				
		$loader = new \Twig_Loader_Filesystem();
		
		if (defined('DIR_CATALOG') && is_dir(DIR_MODIFICATION . 'admin/view/template/')) {	
			$loader->addPath(DIR_MODIFICATION . 'admin/view/template/');
		} elseif (is_dir(DIR_MODIFICATION . 'catalog/view/theme/')) {
			$loader->addPath(DIR_MODIFICATION . 'catalog/view/theme/');
		}
		
		$loader->addPath(DIR_TEMPLATE);

        // << LIVEOPENCART: Parent-child Options: twig-include fix
        if ((VERSION === '3.0.3.5' || VERSION === '3.0.3.6' || VERSION === '3.0.3.7') && !($loader instanceof \Twig\Loader\ChainLoader)) {

            $loader_fs = new \Twig_Loader_Filesystem();
            if (defined('DIR_CATALOG') && is_dir(DIR_MODIFICATION . 'admin/view/template/')) {
                $loader_fs->addPath(DIR_MODIFICATION . 'admin/view/template/');
            } elseif (is_dir(DIR_MODIFICATION . 'catalog/view/theme/')) {
                $loader_fs->addPath(DIR_MODIFICATION . 'catalog/view/theme/');
            }
            $loader_fs->addPath(DIR_TEMPLATE);

            $loader = new \Twig\Loader\ChainLoader(array($loader, $loader_fs));
        }
        // >> LIVEOPENCART: Parent-child Options: twig-include fix


		// initialize Twig environment
		$config = array('autoescape' => false);

		if ($cache) {
			$config['cache'] = DIR_CACHE;
		}

		$this->twig = new \Twig_Environment($loader, $config);
		
		try {
			// load template
			$template = $this->twig->loadTemplate($template . '.twig');
			
			return $template->render($this->data);
		} catch (Exception $e) {
			trigger_error('Error: Could not load template ' . $template . '!');
			exit();	
		}	
	}	
}
