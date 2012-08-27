<?php
add_plugin_hook('install', 'DocsViewerPlugin::install');
add_plugin_hook('uninstall', 'DocsViewerPlugin::uninstall');
add_plugin_hook('config_form', 'DocsViewerPlugin::configForm');
add_plugin_hook('config', 'DocsViewerPlugin::config');
add_plugin_hook('admin_append_to_items_show_primary', 'DocsViewerPlugin::append');
add_plugin_hook('public_append_to_items_show', 'DocsViewerPlugin::append');



if (!defined('WEB_DOC_VIEWER_PLUGIN_DIR')) {
  define('WEB_DOC_VIEWER_PLUGIN_DIR', WEB_PLUGIN . '/DocsViewer');
}


class DocsViewerPlugin
{
    const API_URL = 'http://docs.google.com/viewer';
    const DEFAULT_VIEWER_EMBED = 1;
    const DEFAULT_VIEWER_WIDTH = 500;
    const DEFAULT_VIEWER_HEIGHT = 600;
    
    
    public static function install()
    {
        set_option('docsviewer_embed_admin', DocsViewerPlugin::DEFAULT_VIEWER_EMBED);
        set_option('docsviewer_width_admin', DocsViewerPlugin::DEFAULT_VIEWER_WIDTH);
        set_option('docsviewer_height_admin', DocsViewerPlugin::DEFAULT_VIEWER_HEIGHT);
        set_option('docsviewer_embed_public', DocsViewerPlugin::DEFAULT_VIEWER_EMBED);
        set_option('docsviewer_width_public', DocsViewerPlugin::DEFAULT_VIEWER_WIDTH);
        set_option('docsviewer_height_public', DocsViewerPlugin::DEFAULT_VIEWER_HEIGHT);
    }
    
  
    
    public static function uninstall()
    {
        delete_option('docsviewer_width');
        delete_option('docsviewer_height');
    } 
    
    public static function configForm()
    {
        include 'config_form.php';
    }
    
    public static function config($post)
    {
        if (!is_numeric($post['docsviewer_width_admin']) || 
            !is_numeric($post['docsviewer_height_admin']) || 
            !is_numeric($post['docsviewer_width_public']) || 
            !is_numeric($post['docsviewer_height_public'])) {
            throw new Exception('The width and height must be numeric.');
        }
        set_option('docsviewer_embed_admin', (int) (boolean) $post['docsviewer_embed_admin']);
        set_option('docsviewer_width_admin', $post['docsviewer_width_admin']);
        set_option('docsviewer_height_admin', $post['docsviewer_height_admin']);
        set_option('docsviewer_embed_public', (int) (boolean) $post['docsviewer_embed_public']);
        set_option('docsviewer_width_public', $post['docsviewer_width_public']);
        set_option('docsviewer_height_public', $post['docsviewer_height_public']);
    }


    public function supportedFormats($format = false) 
    {
		// When $format is set it will be checked against the accepted file extensions for compatibility
		// When $format is false (not set) this function will return the array of valid extensions    
		$supported = array(
	      	'doc',		// Microsoft Word
    	  	'docx',		// Microsoft Word
      		'xls',		// Microsoft Excel
	      	'xlsx',		// Microsoft Excel
    	  	'ppt',		// Microsoft PowerPoint
      		'pptx',		// Microsoft PowerPoint
	      	'pdf',		// Adobe Portable Document Format
    	  	'pages',	// Apple Pages 
      		'ai',		// Adobe Illustrator
			'psd',		// Adobe Photoshop
			'tiff',		// Tagged Image File Format
			'tif',		// Tagged Image File Format
			'dxf',		// Autodesk AutoCad
			'svg',		// Scalable Vector Graphics
			'eps',		// PostScript
			'ps',		// PostScript
			'ttf',		// TrueType
			'xps',		// XML Paper Specification
			'zip',		// Zip Archive file
			'rar',		// Rar Archive file
		);
		if ($format !== false) {
			if (in_array(strtolower($format),$supported)) {
				return true;
			} else {
				return false;
			}
		}
    
    	return $supported;
    
    }
    
    public static function append()
    {
        // Embed viewer only if configured to do so.
        if ((is_admin_theme() && !get_option('docsviewer_embed_admin')) || 
            (!is_admin_theme() && !get_option('docsviewer_embed_public'))) {
            return;
        }
        $docsViewer = new DocsViewerPlugin;
        $docsViewer->embed();
    }
    


	public function embedOne (File $file)
	{
	
		$extension = pathinfo($file->archive_filename, PATHINFO_EXTENSION);
		if (!$this->supportedFormats($extension)) {
            return;
		}

?>
<div>
    <iframe src="<?php echo $this->_getUrl($file); ?>" 
            width="<?php echo is_admin_theme() ? get_option('docsviewer_width_admin') : get_option('docsviewer_width_public'); ?>" 
            height="<?php echo is_admin_theme() ? get_option('docsviewer_height_admin') : get_option('docsviewer_height_public'); ?>" 
            style="border: none;"></iframe>
</div>
<?php

		echo "<div class=\"download\"><a href=\"". WEB_FILES . '/' . $file->archive_filename ."\">";
		switch (strtolower($extension)) {
			case 'pdf':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/pdf.png">&nbsp;';
				break;
			case 'doc':
			case 'docx':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/word.png">&nbsp;';
				break;
			case 'xls':
			case 'xlsx':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/excel.png">&nbsp;';
				break;
			case 'ppt':
			case 'pptx':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/ppt.png">&nbsp;';
				break;
			case 'pages':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/pages.png">&nbsp;';
				break;
			case 'ai':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/ai.png">&nbsp;';
				break;
			case 'psd':
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/psd.png">&nbsp;';
				break;
			default:
				echo '<img src="'. WEB_DOC_VIEWER_PLUGIN_DIR .'/views/public/images/generic.png">&nbsp;';
				break;
			
		}
		echo $file->original_filename . '</a></div><br />';
	}

 
	public function embed()
    {
        foreach (__v()->item->Files as $file) {
			$this->embedOne($file);
        }
    }

   
    private function _getUrl(File $file)
    {
        require_once 'Zend/Uri.php';
        $uri = Zend_Uri::factory(self::API_URL);
        $uri->setQuery(array('url' => WEB_FILES . '/' . $file->archive_filename, 'embedded' => 'true'));
        return $uri->getUri();
    }
}