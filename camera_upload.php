<?php
/**
 * Created by PhpStorm.
 * User: Josh
 * Date: 6/08/2015
 * Time: 3:04 PM
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if($_GET['camera'] == "1") {
    echo "Image 1 uploaded";
    move_uploaded_file($_FILES["file"]["tmp_name"],"/var/www/green.joshmcculloch.nz/images/camera_1.jpg");
}
else if ($_GET['camera'] == "2") {
    echo "Image 2 uploaded";
    move_uploaded_file($_FILES["file"]["tmp_name"],"/var/www/green.joshmcculloch.nz/images/camera_2.jpg");
}
else if ($_GET['camera'] == "3") {
    echo "Image 3 uploaded";
    move_uploaded_file($_FILES["file"]["tmp_name"],"/var/www/green.joshmcculloch.nz/images/camera_3.jpg");
} else {
    echo "Image upload failed: no matching camera id.";
}
