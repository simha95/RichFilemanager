<?php
require_once('application/facade/Log.php');

/**
 *	BaseFilemanager PHP class
 *
 *	Base abstract class created to define base methods
 *
 *	@license	MIT License
 *	@author		Riaan Los <mail (at) riaanlos (dot) nl>
 *	@author		Simon Georget <simon (at) linea21 (dot) com>
 *	@author		Pavel Solomienko <https://github.com/servocoder/>
 *	@copyright	Authors
 */

abstract class BaseFilemanager
{
    const FILE_TYPE_DIR = 'dir';

    public $config = array();
    protected $language = array();
    protected $get = array();
    protected $post = array();
    protected $fm_path = '';

    /**
     * Default file information template
     * @var array
     */
    protected $defaultInfo = array(
        'Path'          => '',
        'Filename'      => '',
        'File Type'     => '',
        'Protected'     => 0,
        'PreviewPath'   => '',
        'Error'         => '',
        'Code'          => 0,
        'Properties'    => array(
            'Date Created'  => '',
            'Date Modified' => '',
            'filemtime'     => '',
            'Height'        => 0,
            'Width'         => 0,
            'Size'          => 0
        ),
    );

    /**
     * BaseFilemanager constructor.
     * @param array $config
     */
    public function __construct($config = array())
    {
        // fix display non-latin chars correctly
        // https://github.com/servocoder/RichFilemanager/issues/7
        setlocale(LC_CTYPE, 'en_US.UTF-8');

        // fix for undefined timezone in php.ini
        // https://github.com/servocoder/RichFilemanager/issues/43
        if(!ini_get('date.timezone')) {
            date_default_timezone_set('GMT');
        }

        $this->config = $config;
        $this->fm_path = $this->config['fmPath'] ? $this->config['fmPath'] : dirname(dirname(dirname(__FILE__)));

        // extend server config options with the client ones which are common for both
        if($this->config['extendConfigClient'] === true) {
            $client_config = $this->retrieve_json_file("/scripts/filemanager.config.json");
            if(isset($client_config['options']['culture'])) $this->config['options']['culture'] = $client_config['options']['culture'];
            if(isset($client_config['options']['charsLatinOnly'])) $this->config['options']['charsLatinOnly'] = $client_config['options']['charsLatinOnly'];
            if(isset($client_config['options']['capabilities'])) $this->config['options']['capabilities'] = $client_config['options']['capabilities'];
            if(isset($client_config['options']['logger'])) $this->config['logger']['enabled'] = $client_config['options']['logger'];
            if(isset($client_config['images']['imagesExt'])) $this->config['images']['imagesExt'] = $client_config['images']['imagesExt'];

            if(isset($client_config['security'])) {
                $this->config['security'] = FmHelper::mergeConfigs($this->config['security'], $client_config['security']);
            }
            if(isset($client_config['upload'])) {
                $this->config['upload'] = FmHelper::mergeConfigs($this->config['upload'], $client_config['upload']);
            }
            if(isset($client_config['edit'])) {
                $this->config['edit'] = FmHelper::mergeConfigs($this->config['edit'], $client_config['edit']);
            }
        }
    }

    /**
     * Return file data - filemanager action
     * @return array
     */
    abstract function getfile();

    /**
     * Open specified folder - filemanager action
     * @return array
     */
    abstract function getfolder();

    /**
     * Open and edit file - filemanager action
     * @return array
     */
    abstract function editfile();

    /**
     * Save data to file after editing - filemanager action
     */
    abstract function savefile();

    /**
     * Rename file or folder - filemanager action
     */
    abstract function rename();

    /**
     * Move file or folder - filemanager action
     */
    abstract function move();

    /**
     * Delete existed file or folder - filemanager action
     */
    abstract function delete();

    /**
     * Replace existed file - filemanager action
     */
    abstract function replace();

    /**
     * Upload new file - filemanager action
     */
    abstract function upload();

    /**
     * Create new folder - filemanager action
     * @return array
     */
    abstract function addfolder();

    /**
     * Download file - filemanager action
     * @param bool $force Whether to start download after validation
     */
    abstract function download($force);

    /**
     * Returns image file - filemanager action
     * @param bool $thumbnail Whether to generate image thumbnail
     */
    abstract function getimage($thumbnail);

    /**
     * Read and output file contents data - filemanager action
     * Initially implemented for viewing audio/video/docs/pdf and other files hosted on AWS S3 remote server.
     * @see S3Filemanager::readfile()
     */
    abstract function readfile();

    /**
     * Retrieves storage summarize info - filemanager action
     * @return array
     */
    abstract function summarize();


    /**
     * Invokes filemanager action based on request params and returns response
     */
    public function handleRequest()
    {
        $response = '';

        if(!isset($_GET)) {
            $this->error($this->lang('INVALID_ACTION'));
        } else {

            if(isset($_GET['mode']) && $_GET['mode']!='') {

                switch($_GET['mode']) {

                    default:
                        $this->error($this->lang('MODE_ERROR'));
                        break;

                    case 'getfile':
                        if($this->getvar('path')) {
                            $response = $this->getfile();
                        }
                        break;

                    case 'getfolder':
                        if($this->getvar('path')) {
                            $response = $this->getfolder();
                        }
                        break;

                    case 'rename':
                        if($this->getvar('old') && $this->getvar('new')) {
                            $response = $this->rename();
                        }
                        break;

                    case 'move':
                        if($this->getvar('old') && $this->getvar('new')) {
                            $response = $this->move();
                        }
                        break;

                    case 'editfile':
                        if($this->getvar('path')) {
                            $response = $this->editfile();
                        }
                        break;

                    case 'delete':
                        if($this->getvar('path')) {
                            $response = $this->delete();
                        }
                        break;

                    case 'addfolder':
                        if($this->getvar('path') && $this->getvar('name')) {
                            $response = $this->addfolder();
                        }
                        break;

                    case 'download':
                        if($this->getvar('path')) {
                            $force = isset($_GET['force']);
                            $response = $this->download($force);
                        }
                        break;

                    case 'getimage':
                        if($this->getvar('path')) {
                            $thumbnail = isset($_GET['thumbnail']);
                            $this->getimage($thumbnail);
                        }
                        break;

                    case 'readfile':
                        if($this->getvar('path')) {
                            $this->readfile();
                        }
                        break;

                    case 'summarize':
                        $response = $this->summarize();
                        break;
                }

            } else if(isset($_POST['mode']) && $_POST['mode']!='') {

                switch($_POST['mode']) {

                    default:
                        $this->error($this->lang('MODE_ERROR'));
                        break;

                    case 'upload':
                        if($this->postvar('currentpath')) {
                            $this->upload();
                        }
                        break;

                    case 'replace':
                        if($this->postvar('newfilepath')) {
                            $this->replace();
                        }
                        break;

                    case 'savefile':
                        if($this->postvar('content', false) && $this->postvar('path')) {
                            $response = $this->savefile();
                        }
                        break;
                }
            }
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Retrieve client-side file via CURL or read directly
     * @param $relativePath
     * @return array|null
     */
    public function retrieve_json_file($relativePath)
    {
        // in case remote URL is specified
        if ($this->config['fmUrl']) {
            $url = $this->config['fmUrl'] . $relativePath;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response, true);
        // otherwise try to read file directly rely on base folder structure
        } elseif (file_exists($this->fm_path . $relativePath)) {
            $stream = file_get_contents($this->fm_path . $relativePath);
            return json_decode($stream, true);
        }
        return null;
    }

    /**
     * Echo error message and terminate the application
     * @param $string
     */
    public function error($string)
    {
        $array = array(
            'Error' => $string,
            'Code' => '-1',
            'Properties' => $this->defaultInfo['Properties'],
        );

        Log::info('error message: "' . $string . '"');

        echo json_encode($array);
        exit;
    }

    /**
     * Setup language by code
     * @param $string
     * @return string
     */
    public function lang($string)
    {
        if(isset($this->language[$string]) && $this->language[$string] != '') {
            return $this->language[$string];
        } else {
            return 'Language string error on ' . $string;
        }
    }

    /**
     * Retrieve data from $_GET global var
     * @param string $var
     * @param bool $sanitize
     * @return bool
     */
    public function getvar($var, $sanitize = true)
    {
        if(!isset($_GET[$var]) || $_GET[$var]=='') {
            $this->error(sprintf($this->lang('INVALID_VAR'),$var));
        } else {
            if($sanitize) {
                $this->get[$var] = $this->sanitize($_GET[$var]);
            } else {
                $this->get[$var] = $_GET[$var];
            }
            return true;
        }
    }

    /**
     * Retrieve data from $_POST global var
     * @param string $var
     * @param bool $sanitize
     * @return bool
     */
    public function postvar($var, $sanitize = true)
    {
        if(!isset($_POST[$var]) || ($var != 'content' && $_POST[$var]=='')) {
            $this->error(sprintf($this->lang('INVALID_VAR'),$var));
        } else {
            if($sanitize) {
                $this->post[$var] = $this->sanitize($_POST[$var]);
            } else {
                $this->post[$var] = $_POST[$var];
            }
            return true;
        }
    }

    /**
     * Retrieve data from $_SERVER global var
     * @param string $var
     * @param string|null $default
     * @return bool
     */
    public function get_server_var($var, $default = null)
    {
        return !isset($_SERVER[$var]) ? $default : $_SERVER[$var];
    }

    /**
     * Sanitize global vars: $_GET, $_POST
     * @param string $var
     * @return mixed|string
     */
    protected function sanitize($var)
    {
        $sanitized = strip_tags($var);
        $sanitized = str_replace('http://', '', $sanitized);
        $sanitized = str_replace('https://', '', $sanitized);
        $sanitized = str_replace('../', '', $sanitized);

        return $sanitized;
    }

    /**
     * Defines real size of file
     * Based on https://github.com/jkuchar/BigFileTools project by Jan Kuchar
     * @param string $path
     * @return int|string
     * @throws Exception
     */
    public static function get_real_filesize($path)
    {
        // This should work for large files on 64bit platforms and for small files everywhere
        $fp = fopen($path, "rb");
        if (!$fp) {
            throw new Exception("Cannot open specified file for reading.");
        }
        $flockResult = flock($fp, LOCK_SH);
        $seekResult = fseek($fp, 0, SEEK_END);
        $position = ftell($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if(!($flockResult === false || $seekResult !== 0 || $position === false)) {
            return sprintf("%u", $position);
        }

        // Try to define file size via CURL if installed
        if (function_exists("curl_init")) {
            $ch = curl_init("file://" . rawurlencode($path));
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $data = curl_exec($ch);
            curl_close($ch);
            if ($data !== false && preg_match('/Content-Length: (\d+)/', $data, $matches)) {
                return $matches[1];
            }
        }

        return filesize($path);
    }

    /**
     * Check if extension is allowed regarding the security Policy / Restrictions settings
     * @param string $file
     * @return bool
     */
    public function is_allowed_file_type($file)
    {
        $path_parts = pathinfo($file);

        // if there is no extension
        if (!isset($path_parts['extension'])) {
            // we check if no extension file are allowed
            return (bool)$this->config['security']['allowNoExtension'];
        }

        $exts = array_map('strtolower', $this->config['security']['uploadRestrictions']);

        if($this->config['security']['uploadPolicy'] == 'DISALLOW_ALL') {

            if(!in_array(strtolower($path_parts['extension']), $exts))
                return false;
        }
        if($this->config['security']['uploadPolicy'] == 'ALLOW_ALL') {

            if(in_array(strtolower($path_parts['extension']), $exts))
                return false;
        }

        return true;
    }

    /**
     * Check whether file is image by its mime type
     * For S3 plugin it may cost extra request for each file
     * @param $file
     * @return bool
     */
    public function is_image_file($file)
    {
        $mime = mime_content_type($file);
        $imagesMime = array(
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/bmp",
            "image/svg+xml",
        );
        return in_array($mime, $imagesMime);
    }

    /**
     * Check whether file type(extension) looks like image
     * @param $fileType
     * @return bool
     */
    public function is_image_type($fileType)
    {
        return in_array(strtolower($fileType), $this->config['images']['imagesExt']);
    }
}