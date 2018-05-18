<?php
/**
 * To download card attachment
 *
 * PHP version 5
 *
 * @category   PHP
 * @package    Tixmall
 * @subpackage Core
 * @author     Agriya <info@agriya.com>
 * @copyright  2018 Agriya Infoway Private Ltd
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 * @link       http://www.agriya.com
 */
require_once 'config.inc.php';
define(DIRECTORY_SEPARATOR,'/');
$db_lnk = pg_connect('host=' . R_DB_HOST . ' port=' . R_DB_PORT . ' dbname=' . R_DB_NAME . ' user=' . R_DB_USER . ' password=' . R_DB_PASSWORD . ' options=--client_encoding=UTF8') or die('Database could not connect');
if (!empty($_GET['id']) && !empty($_GET['hash'])) {
    $md5_hash = md5('download' . $_GET['id']);    
    if ($md5_hash == $_GET['hash']) {                  
            $val_array = array(
                $_GET['id']
            );
            $result = pg_query_params($db_lnk, 'SELECT * FROM orders WHERE id = $1', $val_array);
            $order = pg_fetch_assoc($result);            

            $mediadir = APP_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'Booking' . DIRECTORY_SEPARATOR . $order['id'];
            $file = $mediadir . DIRECTORY_SEPARATOR . "booking.pdf";              
            if (file_exists($file)) {                
                $basename = basename($file);
                $add_slash = addcslashes($basename, '"\\');
                $quoted = sprintf('"%s"', $add_slash);
                $size = filesize($file);
                $path_info = pathinfo($file);
                $image_extensions = array(
                    'pdf'                    
                );
               
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename=' . $quoted);
                    header('Content-Transfer-Encoding: binary');
                    header('Connection: Keep-Alive');
                    header('Content-length: ' . $size);
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');                
                    readfile($file);
                    exit;
            }        
    } else {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
    }
} else {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
}
