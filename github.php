<?php
/**
 * Web_hook
 *
 * Automatically generate a .plg with updated version number and zip 
 * location after making a commit to GitHub 
 *
 * @package     Web_hook
 * @author      Kode
 * @link        https://fanart.tv/webservice/plex/plex.php
 * @since       Version 1.0
 */
 // --------------------------------------------------------------------

$hook = new Web_hook();
$hook->check_hook();

class Web_hook {

	private $secret = '';

	public $name           = 'Aesir';
	public $displayName    = 'Aesir WebGUI';
	public $author         = 'Kode';
	public $repo           = 'https://github.com/linuxserver/Aesir';
	public $plgurl         = 'https://linuxserver.io/aesir/aesir-cache.plg';
	public $install_dir    = '/mnt/cache/appdata';
	public $data;

    /**
     * Check if JSON actually came from GitHub by testing against secret key
     *
     * @access	public
     * @return void
     */	
    public function check_hook() 
    {

        $payload = file_get_contents( 'php://input' );
		
		if( !array_key_exists( 'HTTP_X_HUB_SIGNATURE', $_SERVER ) ) {
			die( 'Missing X-Hub-Signature header. Did you configure secret token in hook settings?' );
		}
		list ( $enc, $git_sig ) = explode( "=", $_SERVER['HTTP_X_HUB_SIGNATURE'] );
        $payload_hash = hash_hmac( 'sha1', $payload, $this->secret );

        if ($payload_hash == $git_sig) {
	        $this->data = json_decode( $payload );
	        $this->build_details();
	    } else {
	    	die("X-Hub-Signature header did not match");
	    }
    }

    /**
     * Generate .plg file and update JSON for saving current version details
     *
     * @access	public
     * @return void
     */	    
    protected function build_details() 
    {
		$details = $this->name.'.json';
		if( file_exists( $details ) ) {
			$json = file_get_contents( $details );
			$data = json_decode( $json );
			$date = $data->version_date;
			$int = $data->version_int;

			if( date("Y-m-d") == $data->version_date ) {
				$int = $int+=1;
			} else {
				$date = date("Y-m-d");
				$int = 1;
			}
		} else {
			$date = date("Y-m-d");
			$int = 1;
		}

		$zip = $this->repo.'/archive/'.$this->data->after.'.zip';

		file_put_contents( $details, json_encode( array( 'version_date' => $date, 'version_int' => $int, 'zip' => $zip ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		$plg_content = $this->build_plg( $date, $int, $zip );
		file_put_contents( $this->name.'.plg', $plg_content );

	}



    /**
     * Generate the string to put in the .plg file
     *
     * @access	public
     * @param 	string $date
     * @param 	integer $int
     * @param 	string $zip
     * @return void
     */	
	protected function build_plg( $date, $int, $zip ) {
 
$plg = '<?xml version=\'1.0\' standalone=\'yes\'?>

<!DOCTYPE PLUGIN [
	<!ENTITY name			"'.$this->name.'">
	<!ENTITY displayName	"'.$this->displayName.'">
	<!ENTITY author			"'.$this->author.'">
	<!ENTITY plgVersion		"'.$date.'.'.$int.'">
	<!ENTITY pluginURL		"'.$this->plgurl.'">
	<!ENTITY appURL			"'.$zip.'">
	<!ENTITY installDir		"'.$this->install_dir.'">
]>

<PLUGIN
	name="&name;"
	author="&author;"
	version="&plgVersion;"
	pluginURL="&pluginURL;"
	installDir="&installDir;"
>

<!--

=========================================================================
THE REST OF YOUR PLUGIN, IF THERE ARE ANY SINGLE QUOTES ESCAPE THEM WITH
A BACKSLASH LIKE \' THAT
=========================================================================

';
		return $plg;
	}

}