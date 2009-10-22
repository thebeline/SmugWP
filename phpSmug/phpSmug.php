<?php 
/* phpSmug Class 1.0.4
 * By Colin Seymour
 * Released under GNU Lesser General Public License (http://www.gnu.org/copyleft/lgpl.html)
 *
 * For more information about the class and upcoming tools and toys using it,
 * visit http://www.lildude.co.uk/projects/phpsmug/
 *
 *     For installation and usage instructions, open the README.txt file 
 *	   packaged with this class. If you don't have a copy, you can refer to the 
 * 	   documentation at:
 *          http://www.lildude.co.uk/projects/phpsmug/docs/
 *     or grab a copy of the README.txt from:
 *			http://dev.lildude.co.uk/phpSmug/browser/trunk/README.txt?format=raw
 *
 *     Please raise a ticket for any problems encountered with this class at:
 *			http://dev.lildude.co.uk/phpSmug/newticket
 *
 * phpSmug is based on phpFlickr 2.1.0 (http://www.phpflickr.com) by Dan Coulter
 *
 */

/* Decide which include path delimiter to use.  Windows should be using a semi-colon
 * and everything else should be using a colon.  If this isn't working on your system,
 * comment out this if statement and manually set the correct value into $path_delimiter.
 */
$path_delimiter = (strpos(__FILE__, ':') !== false) ? ';' : ':';

/* This will add the packaged PEAR files into the include path for PHP, allowing you
 * to use them transparently.  This will prefer officially installed PEAR files if you
 * have them.  If you want to prefer the packaged files (there shouldn't be any reason
 * to), swap the two elements around the $path_delimiter variable.  If you don't have
 * the PEAR packages installed, you can leave this like it is and move on.
 */
ini_set('include_path', ini_get('include_path') . $path_delimiter . dirname(__FILE__) . '/PEAR');

class phpSmug {
	var $version = '1.0.4';
    var $APIKey;
	var $PHP = 'http://api.smugmug.com/hack/php/1.2.0/';
    var $Upload = 'http://upload.smugmug.com';     
	var $req;
    var $response;
    var $parsed_response;
    var $cache = FALSE;
    var $cache_db = NULL;
    var $cache_table = NULL;
    var $cache_dir = NULL;
    var $cache_expire = NULL;
    var $die_on_error;
    var $error_code;
    var $error_msg;
    var $php_version;
	var $SessionID;
	var $AppName;
	
	/*
     * When your database cache table hits this many rows, a cleanup
     * will occur to get rid of all of the old rows and cleanup the
     * garbage in the table.  For most personal apps, 1000 rows should
     * be more than enough.  If your site gets hit by a lot of traffic
     * or you have a lot of disk space to spare, bump this number up.
     * You should try to set it high enough that the cleanup only
     * happens every once in a while, so this will depend on the growth
     * of your table.
     */
    var $max_cache_rows = 1000;
	
    function phpSmug($APIKey, $AppName = NULL, $die_on_error = FALSE)
    {
		/* The Application Name (AppName) is not obligatory, but it helps 
		 * SmugMug diagnose any problems users of your application may encounter.
		 * If you're going to use this, please use a string and include your
		 * version number and URL as follows.
		 * For example "My Cool App/1.0 (http://my.url.com)"
		 */
		
        /* The API Key must be set before any calls can be made.  You can
         * get your own at http://www.smugmug.com/hack/apikeys
	 	 */
        $this->APIKey = $APIKey;
        $this->die_on_error = $die_on_error;

		// Set the Application Name
		$this->AppName = (strlen($AppName)>0) ?  $AppName : 'Unknown Application';

        // Find the PHP version and store it for future reference
        $this->php_version = explode("-", phpversion());
        $this->php_version = explode(".", $this->php_version[0]);

        // All calls to the API are done via the POST method using the PEAR::HTTP_Request package.
        require_once 'HTTP/Request.php';
        $this->req =& new HTTP_Request();
        $this->req->setMethod(HTTP_REQUEST_METHOD_POST);
		$this->req->addHeader("User-Agent", "$this->AppName using phpSmug/$this->version");
    }
	
	function enableCache($type, $connection, $cache_expire = 600, $table = 'smugmug_cache')
    {
        /* Turns on caching.  $type must be either "db" (for database caching) or "fs" (for filesystem).
         * When using db, $connection must be a PEAR::DB connection string. Example:
         *      "mysql://user:password@server/database"
         * If the $table, doesn't exist, it will attempt to create it.
         * When using file system, caching, the $connection is the folder that the web server has write
         * access to. Use absolute paths for best results.  Relative paths may have unexpected behavior
         * when you include this.  They'll usually work, you'll just want to test them.
		 */
        if ($type == 'db') {
            require_once 'DB.php';
            $db =& DB::connect($connection);
            if (PEAR::isError($db)) {
                die($db->getMessage());
            }

            /*
             * If high performance is crucial, you can easily comment
             * out this query once you've created your database table.
             */

            $db->query("
                CREATE TABLE IF NOT EXISTS `$table` (
                    `request` CHAR( 35 ) NOT NULL ,
                    `response` LONGTEXT NOT NULL ,
                    `expiration` DATETIME NOT NULL ,
                    INDEX ( `request` )
                ) TYPE = MYISAM");

            if ($db->getOne("SELECT COUNT(*) FROM $table") > $this->max_cache_rows) {
                $db->query("DELETE FROM $table WHERE expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
                $db->query('OPTIMIZE TABLE ' . $this->cache_table);
            }

            $this->cache = 'db';
            $this->cache_db = $db;
            $this->cache_table = $table;
        } elseif ($type == 'fs') {
            $this->cache = 'fs';
            $connection = realpath($connection);
            $this->cache_dir = $connection;
            if ($dir = @opendir($this->cache_dir)) {
                while ($file = readdir($dir)) {
                    if (substr($file, -6) == '.cache' && ((filemtime($this->cache_dir . '/' . $file) + $cache_expire) < time()) ) {
                        unlink($this->cache_dir . '/' . $file);
                    }
                }
            } else {
				die("Cache Directory \"$this->cache_dir\" doesn't exist.  Please create this directory and set appropriate permissions.");
			}
        }
        $this->cache_expire = $cache_expire;
    }

    function getCached($request)
    {
        /* Checks the database or filesystem for a cached result to the request.
         * If there is no cache result, it returns a value of false. If it finds one,
         * it returns the unparsed serialized PHP.
		 */
		$request['SessionID'] = ''; // Unset SessionID
		$reqhash = md5(serialize($request));
        if ($this->cache == 'db') {
            $result = $this->cache_db->getOne("SELECT response FROM " . $this->cache_table . " WHERE request = ? AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration", $reqhash);
            if (!empty($result)) {
                return $result;
            }
        } elseif ($this->cache == 'fs') {
            $file = $this->cache_dir . '/' . $reqhash . '.cache';
            if (file_exists($file)) {
				if ($this->php_version[0] > 4 || ($this->php_version[0] == 4 && $this->php_version[1] >= 3)) {
					return file_get_contents($file);
				} else {
					return implode('', file($file));
				}
            }
        }
        return false;
    }

    function cache($request, $response)
    {
        // Caches the unparsed serialized PHP of a request.
		$request['SessionID'] = ''; // Unset SessionID
        $reqhash = md5(serialize($request));
        if ($this->cache == 'db') {
            if ($this->cache_db->getOne("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'")) {
                $sql = "UPDATE " . $this->cache_table . " SET response = ?, expiration = ? WHERE request = ?";
                $this->cache_db->query($sql, array($response, strftime("%Y-%m-%d %H:%M:%S"), $reqhash));
            } else {
                $sql = "INSERT INTO " . $this->cache_table . " (request, response, expiration) VALUES ('$reqhash', '" . str_replace("'", "\''", $response) . "', '" . strftime("%Y-%m-%d %H:%M:%S") . "')";
                $this->cache_db->query($sql);
            }
        } elseif ($this->cache == "fs") {
            $file = $this->cache_dir . "/" . $reqhash . ".cache";
            $fstream = fopen($file, "w");
            $result = fwrite($fstream,$response);
            fclose($fstream);
            return $result;
        }
        return false;
    }
	
	function request($command, $args = array(), $nocache = FALSE)
    {
       	// Sends a request to SmugMug's PHP endpoint via POST.
        $this->req->setURL($this->PHP);
        $this->req->clearPostData();
        if (substr($command,0,8) != "smugmug.") {
            $command = "smugmug." . $command;
        }

        // Process arguments, including method and login data.
        $args = array_merge(array("method" => $command, "APIKey" => $this->APIKey), $args);
        ksort($args);
        $auth_sig = "";
        if (!($this->response = $this->getCached($args)) || $nocache) {
            foreach ($args as $key => $data) {
                $auth_sig .= $key . $data;
                $this->req->addPostData($key, $data);
            }
            
            //Send Requests
            if ($this->req->sendRequest()) {
                $this->response = $this->req->getResponseBody();
                $this->cache($args, $this->response);
            } else {
                die("There has been a problem sending your command to the server.");
            }
        }

		$this->parsed_response = unserialize($this->response);
        if ($this->parsed_response['stat'] == 'fail') {
            if ($this->die_on_error) die("The SmugMug API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
            else {
                $this->error_code = $this->parsed_response['code'];
                $this->error_msg = $this->parsed_response['message'];
                $this->parsed_response = FALSE;
            }
        } else {
            $this->error_code = FALSE;
            $this->error_msg = FALSE;
        }
        return $this->response;
    }
	
    function setProxy($server, $port)
    {
        // Sets the proxy for all phpSmug calls.
        $this->req->setProxy($server, $port);
    }

    function getErrorCode()
    {
		// Returns the error code of the last call.  If the last call did not
		// return an error. This will return a false boolean.
		return $this->error_code;
    }

    function getErrorMsg()
    {
		// Returns the error message of the last call.  If the last call did not
		// return an error. This will return a false boolean.
		return $this->error_msg;
    }
	
	/*
     * These functions are the direct implementations of SmugMug calls.
     * For method documentation, including arguments, visit the address
     * included in a comment in the function.
	 */
	
	function login_withPassword($EmailAddress, $Password) 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.login.withPassword */
		$this->request('smugmug.login.withPassword', array("EmailAddress" => $EmailAddress, "Password" => $Password));
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	function login_withHash($UserID, $PasswordHash) 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.login.withHash */
		$this->request('smugmug.login.withHash', array("UserID" => $UserID, "PasswordHash" => $PasswordHash));
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	function login_anonymously() 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.login.anonymously */
		$this->request('smugmug.login.anonymously');
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	function logout() 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.logout */
		$this->request('smugmug.logout', array("SessionID" => $this->SessionID));
		return $this->parsed_response ? $this->parsed_response['Logout'] : FALSE;
	}
	
	function users_getTree($NickName = NULL, $Heavy = FALSE, $SitePassword = NULL) 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.users.getTree */
		$this->request('smugmug.users.getTree', array("SessionID" => $this->SessionID, "NickName" => $NickName, "Heavy" => $Heavy, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Categories'] : FALSE;
	}

	function users_getTransferStats($Month, $Year)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.users.getTransferStats */
		$this->request('smugmug.users.getTransferStats', array("SessionID" => $this->SessionID, "Month" => intval($Month), "Year" => intval($Year)));
		return $this->parsed_response ? $this->parsed_response['Albums'] : FALSE;
	}
		
	function albums_get($NickName = NULL, $Heavy = FALSE, $SitePassword = NULL)
    {
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.get */
        $this->request('smugmug.albums.get', array("SessionID" => $this->SessionID, "NickName" => $NickName, "Heavy" => $Heavy, "SitePassword" => $SitePassword));
        return $this->parsed_response ? $this->parsed_response['Albums'] : FALSE;
    }
	
	function albums_getInfo($AlbumID, $Password = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.getInfo */
	    $this->request('smugmug.albums.getInfo', array("SessionID" => $this->SessionID, "AlbumID" => intval($AlbumID), "Password" => $Password, "SitePassword" => $SitePassword));
        return $this->parsed_response ? $this->parsed_response['Album'] : FALSE;
    }
	
	function albums_getStats($AlbumID, $Month, $Year, $Heavy = FALSE)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.getStats */
		$this->request('smugmug.albums.getStats', array("SessionID" => $this->SessionID, "AlbumID" => intval($AlbumID), "Month" => intval($Month), "Year" => intval($Year), "Heavy" => $Heavy));
		return $this->parsed_response ? $this->parsed_response['Album'] : FALSE;
	}
	
	function albums_create($Title, $CategoryID, $OptArgs = NULL) {
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.create 
		
		   I've broken away from the standard format for this function as there
		   are just soooo many optional parameters. To pass the optional
		   parameters, use an associative array for all the options you wish
		   to set.
		
		   For example:
		   $NewAlbumID = $f->albums_create($SessionID, $Title, $CategoryID, array("AlbumTemplateID"=>"5", "SubCategoryID"=>"20", "Keywords"=>"cat,pets,dog")); 
		
		   See the API page for the full list of optional parameters
		*/
		$this->request('smugmug.albums.create', array_merge(array("SessionID" => $this->SessionID, "Title" => $Title, "CategoryID" => $CategoryID), $OptArgs));
		return $this->parsed_response ? $this->parsed_response['Album']['id'] : FALSE;
	}
	
	function albums_changeSettings($AlbumID, $OptArgs = NULL) {
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.changeSettings 
		
		   I've broken away from the standard format for this function as there
		   are just soooo many optional parameters. 
		
		   See albums_create() for more details.
		
		   See the API page for the full list of optional parameters
		
		NOTE: The API doesn't return the AlbumID as the API docs say.  Returning the status instead.
		*/
		$this->request('smugmug.albums.changeSettings', array_merge(array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID), $OptArgs));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	function albums_reSort($AlbumID, $By, $Direction)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.reSort */
		$this->request('smugmug.albums.reSort', array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID, "By" => $By, "Direction" => $Direction));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	function albums_delete($AlbumID)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albums.delete */
		$this->request('smugmug.albums.delete', array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	function albumtemplates_get()
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.albumtemplates.get */
		$this->request('smugmug.albumtemplates.get', array("SessionID" => $this->SessionID));
		return $this->parsed_response ? $this->parsed_response['AlbumTemplates'] : FALSE;
	}
	
	function images_get($AlbumID, $Heavy = FALSE, $Password = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.get */
		$this->request('smugmug.images.get', array("SessionID" => $this->SessionID, "AlbumID" => intval($AlbumID), "Heavy" => $Heavy, "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Images'] : FALSE;
	}
	
	function images_getURLs($ImageID, $TemplateID = NULL, $Password = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.getURLs
		Whilst the API page details various options for the TemplateID, they don't seem to have
		any effect.  The AlbumURL always remains the same. It's probably of no use other than
		to the actual SmugMug site at the moment. It's been implemented anyway. */
		$this->request('smugmug.images.getURLs', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "TemplateID" => intval($TemplateID), "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	function images_getInfo($ImageID, $Password = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.getInfo */
		$this->request('smugmug.images.getInfo', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	function images_getEXIF($ImageID, $Password = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.getEXIF */
		$this->request('smugmug.images.getEXIF', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "Password" => $Password, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	function images_changeSettings($ImageID, $AlbumID = NULL, $Caption = NULL, $Keywords = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.changeSettings*/
		$this->request('smugmug.images.changeSettings', array("SessionID" => $this->SessionID, "ImageID" => $ImageID, "AlbumID" => $AlbumID, "Caption" => $Caption, "Keywords" => $Keywords));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	function images_changePosition($ImageID, $Position)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.changePosition*/
		$this->request('smugmug.images.changePosition', array("SessionID" => $this->SessionID, "ImageID" => $ImageID, "Position" => $Position));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	function images_upload($AlbumID, $File, $Caption = NULL, $Keywords = NULL, $Latitude = NULL, $Longitude = NULL, $Altitude = NULL, $ImageID = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/API/Uploading 
		 * 
		 * I break away from the standard API here as recommended by SmugMug at
		 * http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.upload.
		 *
		 * I've chosen to go with the HTTP PUT method as it is quicker, simpler
		 * and more reliable than using the API or POST methods. 
 		 */

		$fp = fopen ($File, "r");
		$data = '';
		while (!feof($fp)) {
		  $data .= fread($fp, 8192);
		}
		fclose($fp);

		$upload_req =& new HTTP_Request();
        $upload_req->setMethod(HTTP_REQUEST_METHOD_PUT);
		$upload_req->setHttpVer(HTTP_REQUEST_HTTP_VER_1_1);
		$upload_req->clearPostData();
		
		$FileName = basename($File);

		/* For some reason things go a bit TU when I set this - I think it's a problem with the HTTP::Request
		$upload_req->addHeader("Content-Length", $ContentLength); */
		$upload_req->addHeader("User-Agent", "$this->AppName using phpSmug/$this->version");
		$upload_req->addHeader("Content-MD5", md5_file($File));
		$upload_req->addHeader("X-Smug-SessionID", $this->SessionID);
		$upload_req->addHeader("X-Smug-Version", $this->version);
		$upload_req->addHeader("X-Smug-ResponseType", "PHP");
		$upload_req->addHeader("X-Smug-AlbumID", $AlbumID);
		$upload_req->addHeader("Connection", "keep-alive");
		$upload_req->addHeader("X-Smug-Filename", $FileName); // This is actually optional, but we may as well use what we're given
		
		/* Optional Headers */
		(isset($ImageID)) ? $upload_req->addHeader("X-Smug-ImageID", $ImageID) : false;
		(isset($Caption)) ? $upload_req->addHeader("X-Smug-Caption", $Caption) : false;
		(isset($Keywords)) ? $upload_req->addHeader("X-Smug-Keywords", $Keywords) : false;
		(isset($Latitude)) ? $upload_req->addHeader("X-Smug-Latitude", $Latitude) : false;
		(isset($Longitude)) ? $upload_req->addHeader("X-Smug-Longitude", $Longitude) : false;
		(isset($Altitude)) ? $upload_req->addHeader("X-Smug-Altitude", $Altitude) : false;

		$upload_req->setURL($this->Upload . "/".$FileName);

		$result = $upload_req->setBody($data);

	    if (PEAR::isError($result)) {
	        die($result->getMessage());
	    }

		// Send Requests
	    if ($upload_req->sendRequest()) {
	        $this->response = $upload_req->getResponseBody();
	    } else {
	        die("There has been a problem sending your command to the server.");
	    }

	
		$this->parsed_response = unserialize($this->response);
        if ($this->parsed_response['stat'] == 'fail') {
            if ($this->die_on_error) die("The SmugMug API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
            else {
                $this->error_code = $this->parsed_response['code'];
                $this->error_msg = $this->parsed_response['message'];
                $this->parsed_response = FALSE;
            }
        } else {
            $this->error_code = FALSE;
            $this->error_msg = FALSE;
        }
		return $this->parsed_response ? $this->parsed_response['Image']['id'] : FALSE;
	}
	
	function images_uploadFromURL($AlbumID, $URL, $Caption = NULL, $Keywords = NULL, $Latitude = NULL, $Longitude = NULL, $Altitude = NULL, $ByteCount = NULL, $MD5Sum = NULL) 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.uploadFromURL */
		$this->request('smugmug.images.uploadFromURL', array("SessionID" => $this->SessionID, "AlbumID" => $AlbumID, "URL" => $URL, "Caption" => $Caption, "Keywords" => $Keywords, "Latitude" => $Latitude, "Longitude" => $Longitude, "Altitude" => $Altitude, "ByteCount" => $ByteCount, "MD5Sum" => $MD5Sum));
		return $this->parsed_response ? $this->parsed_response['Image']['id'] : FALSE;
	}
	
	function images_delete($ImageID)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.delete */
		$this->request('smugmug.images.delete', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID)));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;	
	}
	
	function images_getStats($ImageID, $Month)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.images.getStats */
		$this->request('smugmug.images.getStats', array("SessionID" => $this->SessionID, "ImageID" => intval($ImageID), "Month" => intval($Month)));
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	function categories_get($NickName = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.categories.get */
		$this->request('smugmug.categories.get', array("SessionID" => $this->SessionID, "NickName" => $NickName, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['Categories'] : FALSE;
	}
	
	function categories_create($Name)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.categories.create */
		$this->request('smugmug.categories.create', array("SessionID" => $this->SessionID, "Name" => $Name));
		return $this->parsed_response ? $this->parsed_response['Category']['id'] : FALSE;
	}
	
	function categories_delete($CategoryID)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.categories.delete */	
		$this->request('smugmug.categories.delete', array("SessionID" => $this->SessionID, "CategoryID" => $CategoryID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	function categories_rename($CategoryID, $Name) 
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.categories.rename 
		
		BUG #5: This doesn't work for some reason. Returns "Invalid user". */
		$this->request('smugmug.categories.rename', array("SessionID" => $this->SessionID, "CategoryID" => $CategoryID, "Name" => $Name));
		return $this->parsed_response ? $this->parsed_response : FALSE;
	}
	
	function subcategories_get($CategoryID, $NickName = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.subcategories.get */
		$this->request('smugmug.subcategories.get', array("SessionID" => $this->SessionID, "CategoryID" => intval($CategoryID), "NickName" => $NickName, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['SubCategories'] : FALSE;
	}

	function subcategories_getAll($NickName = NULL, $SitePassword = NULL)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.subcategories.getAll */
		$this->request('smugmug.subcategories.getAll', array("SessionID" => $this->SessionID, "NickName" => $NickName, "SitePassword" => $SitePassword));
		return $this->parsed_response ? $this->parsed_response['SubCategories'] : FALSE;
	}
	
	function subcategories_create($Name, $CategoryID)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.subcategories.create */
		$this->request('smugmug.subcategories.create', array("SessionID" => $this->SessionID, "Name" => $Name, "CategoryID" => $CategoryID));
		return $this->parsed_response ? $this->parsed_response['Subcategory']['id'] : FALSE;
	}
	
	function subcategories_delete($SubCategoryID)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.subcategories.delete */
		$this->request('smugmug.subcategories.delete', array("SessionID" => $this->SessionID, "SubCategoryID" => $SubCategoryID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
	
	function subcategories_rename($SubCategoryID, $Name)
	{
		/* http://smugmug.jot.com/WikiHome/1.2.0/smugmug.subcategories.rename */
		$this->request('smugmug.subcategories.rename', array("SessionID" => $this->SessionID, "Name" => $Name, "SubCategoryID" => $SubCategoryID));
		return $this->parsed_response ? $this->parsed_response['stat'] : FALSE;
	}
}

?>
