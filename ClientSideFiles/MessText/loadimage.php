<?php
if($_SERVER["REQUEST_METHOD"] === "GET"){
  if(isset($_GET["id"])){
    require_once("/ServerSideFiles/vars.php");
    $id = str_replace("/", "", strval($_GET["id"]));
    if(file_exists("$data_dir/IMG/" . $id)){
      header("Content-Type: image/png");
      echo file_get_contents("$data_dir/IMG/" . $id);
    }
  }
}
?>
