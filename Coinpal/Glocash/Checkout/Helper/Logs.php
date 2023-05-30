<?php

namespace Glocash\Checkout\Helper;

use \Magento\Framework\App\ResourceConnection;

class Logs //implements InstallDataInterface
{

    public static function logw($content,$name,$type="")
    {
        $dir = 'var/log';
        if (!is_dir($dir)) {
            mkdir($dir,0777,true);
        }
		$pathLogfile = "var/log/".$name;
		$myfile = fopen($pathLogfile, "a");
		fwrite($myfile, date("Y-m-d H:i:s",time()).":".$type." ".$content."\n");
		fclose($myfile);
		
    }


	public static function dbw($order_id="",$order_number,$message){



	}
	
	
}
