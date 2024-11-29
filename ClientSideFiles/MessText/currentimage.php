<?php
if($_SERVER["REQUEST_METHOD"] === "GET"){
    require_once("/ServerSideFiles/vars.php");
    clearstatcache();
    $imgs = glob("$data_dir/IMG/*.png");
    $i = 0;
    if(sizeOf($imgs) > 0){
        $mtimes = 0;
        $mostRecentIMG = $imgs[0];
        foreach($imgs as $img){
            if(filemtime($img) >= $mtimes){
                $mtimes = filemtime($img);
                $mostRecentIMG = $img;
            }
        }
        if(!isset($_GET["history"])){
            header("Content-Type: image/png");
            echo file_get_contents($mostRecentIMG);
        } else {
            function sortByModifTime($a,$b){
                return filemtime($b) - filemtime($a);
            }
            usort($imgs, "sortByModifTime");
            $imgs = array_slice($imgs, 1);
            foreach($imgs as $img){
                $path = explode("/", $img);
                $path = end($path);
                echo $path . PHP_EOL;
            }
        }
    }
}
?>
